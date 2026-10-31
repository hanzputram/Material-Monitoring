<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Material;
use App\Models\MaterialRealization;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\RabItem;
use App\Models\RabItemMaterial;
use App\Models\RabNode;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * RAB tidak selalu lengkap level 1-5: ada yang hanya level 4, hanya level 5, atau hanya
 * level 1/2/3. Import dan dashboard harus menyesuaikan struktur yang diinput.
 */
class RabFlexibleStructureTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([UnitSeeder::class, RolePermissionSeeder::class]);
        $this->actingAs(User::where('email', 'superadmin@sirisolab.com')->firstOrFail());
        $this->project = Project::create(['name' => 'Proyek Struktur Fleksibel', 'status' => 'active']);
    }

    public function test_level_4_only_rab_uses_item_monitoring_and_item_chart(): void
    {
        $this->importRows([
            [4, '1.0', 'Mobilisasi Alat', 1, 'Ls', 5000000],
            [4, '2.0', 'Safety Helmet', 10, 'Bh', 125000],
        ]);

        $root = RabNode::where('project_id', $this->project->id)->whereNull('parent_id')->sole();
        $this->assertSame('PEKERJAAN UMUM', $root->name);
        $this->assertEquals(6250000.0, (float) $root->subtotal_cache);

        $this->get(route('dashboard', ['project_id' => $this->project->id]))
            ->assertOk()
            ->assertSee('per Item Pekerjaan')
            ->assertSee('Monitoring Realisasi Item Pekerjaan vs RAB')
            ->assertSee('Safety Helmet')
            ->assertDontSee('Monitoring Realisasi Material vs RAB (Baseline)');
    }

    public function test_level_5_only_rab_creates_item_per_material_with_bom(): void
    {
        $this->importRows([
            [5, 'M-1', 'Semen Uji Level 5', 40, 'Zak', 78000],
            [5, 'M-2', 'Pasir Uji Level 5', 3, 'M3', 290000],
        ]);

        $this->assertSame(2, RabItem::count());
        $semenItem = RabItem::where('name', 'Semen Uji Level 5')->sole();
        $this->assertTrue($semenItem->is_composite);
        $this->assertEquals(3120000.0, (float) $semenItem->total_price);
        $this->assertSame(1, RabItemMaterial::where('rab_item_id', $semenItem->id)->count());

        $semen = Material::where('name', 'Semen Uji Level 5')->sole();
        $this->assertEquals(40.0, (float) MaterialRealization::where('project_id', $this->project->id)->where('material_id', $semen->id)->value('planned_qty'));

        $this->get(route('dashboard', ['project_id' => $this->project->id]))
            ->assertOk()
            ->assertSee('Monitoring Realisasi Material vs RAB (Baseline)')
            ->assertDontSee('Monitoring Realisasi Item Pekerjaan vs RAB');
    }

    public function test_category_only_rab_records_category_values_as_items(): void
    {
        $this->importRows([
            [1, 'I', 'PEKERJAAN PERSIAPAN', 1, 'Ls', 15000000],
            [1, 'II', 'PEKERJAAN STRUKTUR', null, null, null],
            [2, 'II.A', 'Struktur Bawah', 1, 'Ls', 40000000],
            [2, 'II.B', 'Struktur Atas', 1, 'Ls', 60000000],
        ]);

        $persiapan = RabNode::where('project_id', $this->project->id)->where('code', 'I')->sole();
        $struktur = RabNode::where('project_id', $this->project->id)->where('code', 'II')->sole();

        $this->assertEquals(15000000.0, (float) $persiapan->subtotal_cache);
        $this->assertEquals(100000000.0, (float) $struktur->subtotal_cache);
        // Kategori induk yang punya sub kategori tidak dijadikan item
        $this->assertSame(0, $struktur->rabItems()->count());
        $this->assertSame(3, RabItem::count());
        $this->assertEquals(115000000.0, $this->project->fresh()->total_rab);

        $this->get(route('dashboard', ['project_id' => $this->project->id]))
            ->assertOk()
            ->assertSee('per Kategori');
    }

    public function test_skipped_levels_attach_to_nearest_parent(): void
    {
        $this->importRows([
            [2, 'S', 'Sub Tanpa Kategori', null, null, null],
            [4, '1.0', 'Item Di Sub', 1, 'Ls', 50000],
            [1, 'I', 'KATEGORI A', null, null, null],
            [3, 'A.1', 'Sub-Sub Langsung', null, null, null],
            [4, '2.0', 'Item Di Sub-Sub', 2, 'M2', 100000],
        ]);

        // Level 2 tanpa level 1 menjadi kategori utama
        $this->assertNull(RabNode::where('code', 'S')->sole()->parent_id);

        // Level 3 tanpa level 2 dipasang langsung di bawah level 1
        $kategori = RabNode::where('code', 'I')->sole();
        $this->assertSame($kategori->id, RabNode::where('code', 'A.1')->sole()->parent_id);
        $this->assertEquals(200000.0, (float) $kategori->subtotal_cache);
        $this->assertEquals(250000.0, $this->project->fresh()->total_rab);

        // Builder tetap bisa dibuka untuk struktur ini
        $this->get(route('rab.builder', ['project_id' => $this->project->id]))->assertOk()->assertSee('Sub-Sub Langsung');
    }

    public function test_item_monitoring_tracks_allocated_po_and_do_without_false_material_alert(): void
    {
        $this->importRows([[4, '1.0', 'Safety Helmet', 10, 'Bh', 125000]]);

        $item = RabItem::where('name', 'Safety Helmet')->sole();
        $unit = Unit::where('code', 'Bh')->sole();
        $supplier = Supplier::create(['name' => 'Toko APD Uji']);
        $material = Material::create(['code' => 'APD-HLM', 'name' => 'Helm Proyek Uji', 'category' => 'alat_bantu', 'default_unit_id' => $unit->id]);

        $po = PurchaseOrder::create([
            'project_id' => $this->project->id, 'supplier_id' => $supplier->id,
            'po_number' => 'PO-APD-1', 'po_date' => now()->toDateString(), 'status' => 'sent',
        ]);
        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id, 'material_id' => $material->id, 'rab_item_id' => $item->id,
            'qty_ordered' => 10, 'unit_id' => $unit->id, 'unit_price' => 120000,
        ]);

        $do = DeliveryOrder::create([
            'project_id' => $this->project->id, 'purchase_order_id' => $po->id, 'supplier_id' => $supplier->id,
            'do_number' => 'SJ-APD-1', 'do_date' => now()->toDateString(), 'attachment_path' => 'x.pdf',
            'received_by' => auth()->id(), 'status' => 'validated',
        ]);
        DeliveryOrderItem::create([
            'delivery_order_id' => $do->id, 'purchase_order_id' => $po->id,
            'material_id' => $material->id, 'qty_received' => 4, 'unit_id' => $unit->id,
        ]);
        MaterialRealization::recalculateAllForProject($this->project->id);

        // Material dari PO teralokasi ke item RAB tidak menjadi "material melebihi RAB" (rencana 0)
        $this->assertNull(MaterialRealization::where('project_id', $this->project->id)->where('material_id', $material->id)->first());
        $this->assertSame(0, Alert::where('project_id', $this->project->id)->count());

        $this->get(route('dashboard', ['project_id' => $this->project->id]))
            ->assertOk()
            ->assertSee('Monitoring Realisasi Item Pekerjaan vs RAB')
            ->assertSee('40%')
            ->assertSee('1.200.000');
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function importRows(array $rows): void
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->setTitle('Info Proyek');
        $detail = $spreadsheet->createSheet();
        $detail->setTitle('Detail RAB');
        $detail->fromArray(['Level (1-5)', 'Kode', 'Uraian', 'Volume', 'Satuan', 'Harga Satuan (Rp)', 'Jumlah Harga (Rp)'], null, 'A1');

        $r = 2;
        foreach ($rows as $row) {
            $detail->fromArray($row, null, "A{$r}");
            $detail->setCellValue("G{$r}", "=IF(AND(ISNUMBER(D{$r}),ISNUMBER(F{$r})),ROUND(D{$r}*F{$r},2),\"\")");
            $r++;
        }

        $path = tempnam(sys_get_temp_dir(), 'rab').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        $this->post(route('rab.import.preview'), [
            'file' => new UploadedFile($path, 'rab.xlsx', null, null, true),
            'target_project_id' => $this->project->id,
        ])->assertRedirect(route('rab.import.preview.show'));

        $preview = session('rab_import_preview');
        $this->assertTrue($preview['valid'], 'Preview harus valid: '.json_encode($preview['errors']));

        $this->post(route('rab.import.commit'))
            ->assertRedirect(route('rab.builder', ['project_id' => $this->project->id]));
    }
}
