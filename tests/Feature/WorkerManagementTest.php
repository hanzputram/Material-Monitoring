<?php

namespace Tests\Feature;

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
        $response->assertSee('Master Pekerja &amp; Tukang', false);
        $response->assertSee('Pekerja di Proyek Ini');
        $response->assertSee('Katalog Master Tenaga Kerja');
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
}
