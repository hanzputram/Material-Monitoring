<?php

namespace Tests\Feature;

use App\Models\MandorBorongan;
use App\Models\MandorBoronganPanjar;
use App\Models\Project;
use App\Models\ProjectWorker;
use App\Models\User;
use App\Models\Worker;
use Database\Seeders\EquipmentMasterSeeder;
use Database\Seeders\MaterialMasterSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SampleProjectSeeder;
use Database\Seeders\UnitSeeder;
use Database\Seeders\WorkerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkerManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            UnitSeeder::class,
            RolePermissionSeeder::class,
            EquipmentMasterSeeder::class,
            MaterialMasterSeeder::class,
            SampleProjectSeeder::class,
            WorkerSeeder::class,
        ]);

        $superadmin = User::where('email', 'superadmin@sirisolab.com')->first();
        if ($superadmin) {
            $this->actingAs($superadmin);
        }
    }

    public function test_workers_index_page_loads_with_active_project(): void
    {
        $response = $this->get(route('workers.index'));
        $response->assertStatus(200);
        $response->assertSee('Mandor & Pekerjaan Borongan (LS)', false);
        $response->assertSee('Pekerja Harian (Opsional)');
        $response->assertSee('Katalog Master Tenaga Kerja');
        $response->assertSee('Potong Panjar Mandor');
        $response->assertSee('Pemotongan Panjar Hasil Kontrak Mandor');
        $response->assertSee('Pemotongan Panjar');
        $response->assertSee('Nilai Kontrak Borongan');
        $response->assertSee('x-data="workerManagementData()"', false);
        $response->assertSee('function workerManagementData()', false);
        $response->assertDontSee('0)?this.boronganList[0]');
    }

    public function test_master_worker_can_be_created(): void
    {
        $response = $this->post(route('workers.master.store'), [
            'name' => 'Kuswanto Aris',
            'trade' => 'Tukang Besi / Pembesian',
            'code' => 'WKR-TKG-999',
            'phone' => '0812-9988-7766',
            'nik' => '3302011902880099',
            'daily_rate' => 180000,
            'address' => 'Banyumas',
            'notes' => 'Tukang besi senior',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('workers.index', ['tab' => 'master']));
        $this->assertDatabaseHas('workers', [
            'name' => 'Kuswanto Aris',
            'code' => 'WKR-TKG-999',
            'trade' => 'Tukang Besi / Pembesian',
            'daily_rate' => 180000,
        ]);
    }

    public function test_master_worker_can_be_updated(): void
    {
        $worker = Worker::where('code', 'WKR-MDR-001')->firstOrFail();

        $response = $this->put(route('workers.master.update', $worker->id), [
            'name' => 'Bambang Sutrisno Update',
            'trade' => 'Mandor',
            'code' => $worker->code,
            'phone' => '0812-0000-1111',
            'nik' => $worker->nik,
            'daily_rate' => 250000,
            'address' => 'Semarang',
            'notes' => 'Update catatan',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('workers.index', ['tab' => 'master']));
        $this->assertDatabaseHas('workers', [
            'id' => $worker->id,
            'name' => 'Bambang Sutrisno Update',
            'daily_rate' => 250000,
        ]);
    }

    public function test_worker_can_be_assigned_to_project(): void
    {
        $project = Project::firstOrFail();
        $worker = Worker::where('code', 'WKR-TKG-008')->firstOrFail(); // Tukang cat

        $response = $this->post(route('workers.assign'), [
            'project_id' => $project->id,
            'worker_id' => $worker->id,
            'assigned_trade' => 'Tukang Cat Dinding Eksterior',
            'daily_wage' => 175000,
            'status' => 'active',
            'start_date' => '2026-09-28',
            'notes' => 'Pengecatan fasad depan',
        ]);

        $response->assertRedirect(route('workers.index', ['project_id' => $project->id, 'tab' => 'project']));
        $this->assertDatabaseHas('project_workers', [
            'project_id' => $project->id,
            'worker_id' => $worker->id,
            'assigned_trade' => 'Tukang Cat Dinding Eksterior',
            'daily_wage' => 175000,
            'status' => 'active',
        ]);
    }

    public function test_worker_can_be_assigned_by_typing_name_directly(): void
    {
        $project = Project::firstOrFail();

        $response = $this->post(route('workers.assign'), [
            'project_id' => $project->id,
            'name' => 'Sutarno Hardi',
            'assigned_trade' => 'Tukang Batu',
            'phone' => '0812-7777-8888',
            'daily_wage' => 165000,
            'status' => 'active',
            'start_date' => '2026-09-28',
            'notes' => 'Pasangan hebel lantai 1',
        ]);

        $response->assertRedirect(route('workers.index', ['project_id' => $project->id, 'tab' => 'project']));

        // Verify worker automatically registered in master
        $this->assertDatabaseHas('workers', [
            'name' => 'Sutarno Hardi',
            'trade' => 'Tukang Batu',
            'phone' => '0812-7777-8888',
        ]);

        $worker = Worker::where('name', 'Sutarno Hardi')->firstOrFail();

        // Verify allocated to project
        $this->assertDatabaseHas('project_workers', [
            'project_id' => $project->id,
            'worker_id' => $worker->id,
            'assigned_trade' => 'Tukang Batu',
            'daily_wage' => 165000,
            'status' => 'active',
        ]);
    }

    public function test_fast_bulk_assign_workers_to_project(): void
    {
        $project = Project::firstOrFail();
        $workerA = Worker::where('code', 'WKR-TKG-009')->firstOrFail();

        $response = $this->post(route('workers.fast_bulk'), [
            'project_id' => $project->id,
            'items' => [
                [
                    'selected' => '1',
                    'worker_id' => $workerA->id,
                    'assigned_trade' => 'Tukang Las Konstruksi',
                    'daily_wage' => 195000,
                    'status' => 'active',
                    'notes' => 'Pekerjaan kanopi baja',
                ],
            ],
        ]);

        $response->assertRedirect(route('workers.index', ['project_id' => $project->id, 'tab' => 'project']));
        $this->assertDatabaseHas('project_workers', [
            'project_id' => $project->id,
            'worker_id' => $workerA->id,
            'assigned_trade' => 'Tukang Las Konstruksi',
            'daily_wage' => 195000,
        ]);
    }

    public function test_project_worker_assignment_can_be_updated(): void
    {
        $projectWorker = ProjectWorker::firstOrFail();

        $response = $this->put(route('workers.project.update', $projectWorker->id), [
            'assigned_trade' => 'Mandor Utama Zona A',
            'daily_wage' => 240000,
            'status' => 'standby',
            'start_date' => '2026-09-20',
            'notes' => 'Rotasi ke zona standby',
        ]);

        $response->assertRedirect(route('workers.index', ['project_id' => $projectWorker->project_id, 'tab' => 'project']));
        $this->assertDatabaseHas('project_workers', [
            'id' => $projectWorker->id,
            'assigned_trade' => 'Mandor Utama Zona A',
            'daily_wage' => 240000,
            'status' => 'standby',
        ]);
    }

    public function test_project_worker_assignment_can_be_deleted(): void
    {
        $projectWorker = ProjectWorker::firstOrFail();
        $id = $projectWorker->id;
        $projectId = $projectWorker->project_id;

        $response = $this->delete(route('workers.project.destroy', $id));

        $response->assertRedirect(route('workers.index', ['project_id' => $projectId, 'tab' => 'project']));
        $this->assertDatabaseMissing('project_workers', [
            'id' => $id,
        ]);
    }

    public function test_mandor_borongan_can_be_created_and_updated_with_panjar(): void
    {
        $project = Project::firstOrFail();

        // 1. Create Borongan
        $response = $this->post(route('workers.borongan.store'), [
            'project_id' => $project->id,
            'mandor_name' => 'Mandor Budi Santoso',
            'mandor_phone' => '0812-3344-5566',
            'work_scope' => 'Pekerjaan Borongan Pasang Bata & Plesteran',
            'spk_number' => 'SPK/2026/BOR-01',
            'contract_amount' => 100000000,
            'panjar_amount' => 10000000,
            'paid_amount' => 20000000,
            'progress_percent' => 30,
            'status' => 'active',
            'start_date' => '2026-10-01',
            'notes' => 'Termasuk bahan bantu',
        ]);

        $response->assertRedirect(route('workers.index', ['project_id' => $project->id, 'tab' => 'borongan']));

        $borongan = MandorBorongan::where('spk_number', 'SPK/2026/BOR-01')->firstOrFail();
        $this->assertEquals(100000000, (float) $borongan->contract_amount);
        $this->assertEquals(10000000, (float) $borongan->panjar_amount);
        $this->assertEquals(90000000, (float) $borongan->net_contract_amount);
        $this->assertEquals(70000000, (float) $borongan->remaining_amount);

        // 2. Update Borongan
        $updateResponse = $this->put(route('workers.borongan.update', $borongan->id), [
            'mandor_name' => 'Mandor Budi Santoso (Revisi)',
            'mandor_phone' => '0812-3344-5566',
            'work_scope' => 'Pekerjaan Borongan Pasang Bata & Plesteran Full',
            'spk_number' => 'SPK/2026/BOR-01',
            'contract_amount' => 120000000,
            'panjar_amount' => 15000000,
            'paid_amount' => 30000000,
            'progress_percent' => 40,
            'status' => 'active',
        ]);

        $updateResponse->assertRedirect(route('workers.index', ['project_id' => $project->id, 'tab' => 'borongan']));
        $borongan->refresh();
        $this->assertEquals(120000000, (float) $borongan->contract_amount);
        $this->assertEquals(15000000, (float) $borongan->panjar_amount);
        $this->assertEquals(105000000, (float) $borongan->net_contract_amount);
        $this->assertEquals(75000000, (float) $borongan->remaining_amount);
    }

    public function test_mandor_borongan_panjar_can_be_stored_and_accumulated(): void
    {
        $project = Project::firstOrFail();
        $borongan = MandorBorongan::create([
            'project_id' => $project->id,
            'mandor_name' => 'Mandor Ali',
            'work_scope' => 'Pekerjaan Keramik Lantai',
            'contract_amount' => 50000000,
            'panjar_amount' => 0,
            'paid_amount' => 0,
            'progress_percent' => 0,
            'status' => 'active',
        ]);

        // Simpan Panjar ke-1
        $response1 = $this->post(route('workers.borongan.panjar.store'), [
            'mandor_borongan_id' => $borongan->id,
            'amount' => 5000000,
            'panjar_date' => '2026-10-09',
            'reference_no' => 'KSB-001',
            'notes' => 'Panjar kasbon tukang termin 1',
        ]);

        $response1->assertRedirect(route('workers.index', ['project_id' => $project->id, 'tab' => 'borongan']));
        $borongan->refresh();
        $this->assertEquals(5000000, (float) $borongan->panjar_amount);
        $this->assertEquals(45000000, (float) $borongan->net_contract_amount);
        $this->assertEquals(45000000, (float) $borongan->remaining_amount);

        // Simpan Panjar ke-2
        $response2 = $this->post(route('workers.borongan.panjar.store'), [
            'mandor_borongan_id' => $borongan->id,
            'amount' => 3000000,
            'panjar_date' => '2026-10-10',
            'reference_no' => 'KSB-002',
            'notes' => 'Panjar kasbon tukang termin 2',
        ]);

        $response2->assertRedirect(route('workers.index', ['project_id' => $project->id, 'tab' => 'borongan']));
        $borongan->refresh();
        $this->assertEquals(8000000, (float) $borongan->panjar_amount);
        $this->assertEquals(42000000, (float) $borongan->net_contract_amount);
        $this->assertEquals(42000000, (float) $borongan->remaining_amount);

        // Hapus Panjar ke-1
        $panjar1 = MandorBoronganPanjar::where('reference_no', 'KSB-001')->firstOrFail();
        $destroyResponse = $this->delete(route('workers.borongan.panjar.destroy', $panjar1->id));
        $destroyResponse->assertRedirect(route('workers.index', ['project_id' => $project->id, 'tab' => 'borongan']));

        $borongan->refresh();
        $this->assertEquals(3000000, (float) $borongan->panjar_amount);
        $this->assertEquals(47000000, (float) $borongan->net_contract_amount);
    }
}
