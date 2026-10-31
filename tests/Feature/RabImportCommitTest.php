<?php

namespace Tests\Feature;

use App\Models\CostRealization;
use App\Models\Material;
use App\Models\MaterialRealization;
use App\Models\Project;
use App\Models\RabItem;
use App\Models\RabNode;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class RabImportCommitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([UnitSeeder::class, RolePermissionSeeder::class]);
        $this->actingAs(User::where('email', 'superadmin@sirisolab.com')->firstOrFail());
    }

    /**
     * Template v2 berisi formula di kolom "Jumlah Harga"; import harus menghitung total,
     * memakai satuan master yang sudah ada (case-insensitive), dan menyinkronkan realisasi.
     */
    public function test_import_commit_with_formula_totals_and_existing_units(): void
    {
        $project = Project::create(['name' => 'Proyek Import Uji', 'status' => 'active']);
        $unitCountBefore = Unit::count();

        $path = $this->makeWorkbook([
            [1, 'I', 'PEKERJAAN UMUM', null, null, null],
            [4, '2.0', 'Mobilisasi', 1, 'LS', 271855000],
            [4, '8.0', 'Spanduk (Banner)', 2, 'Buah', 350000],
            [4, '9.0', 'Pasangan Bata', 10, 'M2', 150000],
            [5, '9.0-1', 'Semen Portland Uji Import', 4.5, 'zak', 78000],
        ]);

        $file = new UploadedFile($path, 'rab.xlsx', null, null, true);
        $this->post(route('rab.import.preview'), ['file' => $file, 'target_project_id' => $project->id])
            ->assertRedirect(route('rab.import.preview.show'));

        // Pratinjau dibuka via GET (refresh / Back / ganti proyek tidak lagi 405)
        $this->get(route('rab.import.preview.show'))
            ->assertOk()
            ->assertSee('Mobilisasi')
            ->assertSee('Proyek Import Uji');

        $this->post(route('rab.import.commit'))
            ->assertRedirect(route('rab.builder', ['project_id' => $project->id]));

        // Setelah commit, membuka ulang URL pratinjau diarahkan ke form, bukan error
        $this->get(route('rab.import.preview.show'))
            ->assertRedirect(route('rab.import.form'));

        // Tidak ada satuan baru: LS -> Ls, Buah -> Bh, zak -> Zak
        $this->assertSame($unitCountBefore, Unit::count());

        $mobilisasi = RabItem::where('name', 'Mobilisasi')->firstOrFail();
        $this->assertEquals(271855000.0, (float) $mobilisasi->total_price);
        $this->assertSame('Ls', $mobilisasi->unit->code);

        $spanduk = RabItem::where('name', 'Spanduk (Banner)')->firstOrFail();
        $this->assertEquals(700000.0, (float) $spanduk->total_price);
        $this->assertSame('Bh', $spanduk->unit->code);

        $bata = RabItem::where('name', 'Pasangan Bata')->firstOrFail();
        $this->assertTrue($bata->is_composite);

        $root = RabNode::where('project_id', $project->id)->whereNull('parent_id')->firstOrFail();
        $this->assertEquals(271855000.0 + 700000.0 + 1500000.0, (float) $root->subtotal_cache);
        $this->assertEquals((float) $root->subtotal_cache, $project->fresh()->total_rab);

        $this->assertEquals(
            271855000.0,
            (float) CostRealization::where('rab_item_id', $mobilisasi->id)->value('budget_amount')
        );
        $this->assertSame(3, CostRealization::where('project_id', $project->id)->count());

        $semen = Material::where('name', 'Semen Portland Uji Import')->firstOrFail();
        $this->assertSame('Zak', $semen->defaultUnit->code);
        $realization = MaterialRealization::where('project_id', $project->id)
            ->where('material_id', $semen->id)
            ->firstOrFail();
        $this->assertEquals(4.5, (float) $realization->planned_qty);
    }

    public function test_switching_project_from_preview_page_does_not_hit_post_only_route(): void
    {
        $project = Project::create(['name' => 'Proyek Switch Pratinjau', 'status' => 'active']);
        $path = $this->makeWorkbook([[4, '1.0', 'Item Uji Switch', 1, 'Ls', 1000]]);

        $this->post(route('rab.import.preview'), ['file' => new UploadedFile($path, 'rab.xlsx', null, null, true)]);

        $this->from(route('rab.import.preview.show'))
            ->post(route('projects.switch'), ['project_id' => $project->id])
            ->assertRedirect(route('rab.import.preview.show'));

        $this->get(route('rab.import.preview.show'))->assertOk()->assertSee('Item Uji Switch');
    }

    public function test_unreadable_file_redirects_back_to_form_with_error(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'bad').'.xlsx';
        file_put_contents($path, 'bukan file excel');

        $this->post(route('rab.import.preview'), ['file' => new UploadedFile($path, 'rab.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true)])
            ->assertRedirect();

        $this->assertNull(session('rab_import_preview'));
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function makeWorkbook(array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $info = $spreadsheet->getActiveSheet();
        $info->setTitle('Info Proyek');
        $info->setCellValue('A5', 'Nama Proyek');
        $info->setCellValue('B5', 'Proyek Import Uji');

        $detail = $spreadsheet->createSheet();
        $detail->setTitle('Detail RAB');
        $detail->fromArray(['Level (1-5)', 'Kode', 'Uraian', 'Volume', 'Satuan', 'Harga Satuan (Rp)', 'Jumlah Harga (Rp)'], null, 'A1');

        $r = 2;
        foreach ($rows as $row) {
            $detail->fromArray($row, null, "A{$r}");
            $detail->setCellValue("G{$r}", "=IF(AND(ISNUMBER(D{$r}),ISNUMBER(F{$r})),ROUND(D{$r}*F{$r},2),\"\")");
            $r++;
        }

        // Baris template kosong yang hanya berisi formula harus dilewati
        for ($i = $r; $i < $r + 20; $i++) {
            $detail->setCellValue("G{$i}", "=IF(AND(ISNUMBER(D{$i}),ISNUMBER(F{$i})),ROUND(D{$i}*F{$i},2),\"\")");
        }

        $path = tempnam(sys_get_temp_dir(), 'rab').'.xlsx';
        // Simpan tanpa pre-calculate agar meniru file yang nilai formulanya belum di-cache
        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->save($path);

        return $path;
    }
}
