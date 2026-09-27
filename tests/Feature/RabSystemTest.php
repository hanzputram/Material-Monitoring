<?php

namespace Tests\Feature;

use App\Models\EquipmentCategory;
use App\Models\EquipmentMaster;
use App\Models\Material;
use App\Models\Project;
use App\Models\ProjectEquipment;
use App\Models\RabItem;
use App\Models\RabItemMaterial;
use App\Models\RabNode;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\EquipmentMasterSeeder;
use Database\Seeders\MaterialMasterSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SampleProjectSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RabSystemTest extends TestCase
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
        ]);
    }

    public function test_dashboard_loads_with_active_project()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Review Rumah Susun (Prototipe)');
        $response->assertSee('Barak Rembunai');
    }

    public function test_project_can_be_created()
    {
        $response = $this->post(route('projects.store'), [
            'name' => 'Proyek Uji Coba Barak 2',
            'prototype_type' => 'Barak Rembunai Tipe B',
            'floor_count' => 2,
            'budget_year' => '2026',
            'location_kds' => 'Rembunai Sektor Selatan',
            'foundation_type' => 'Tiang Pancang',
            'alert_over_threshold_pct' => 12.0,
            'alert_under_threshold_pct' => 6.0,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('projects', ['name' => 'Proyek Uji Coba Barak 2']);
    }

    public function test_rab_builder_displays_hierarchy_and_subtotals()
    {
        $project = Project::first();
        $response = $this->get(route('rab.builder', ['project_id' => $project->id]));
        $response->assertStatus(200);
        $response->assertSee('PEKERJAAN PERSIAPAN STANDAR');
        $response->assertSee('PEKERJAAN STRUKTUR');
    }

    public function test_fast_bulk_equipment_input()
    {
        $project = Project::first();
        $eq1 = EquipmentMaster::first();

        $response = $this->post(route('equipment.fast_bulk'), [
            'project_id' => $project->id,
            'items' => [
                [
                    'selected' => 1,
                    'equipment_master_id' => $eq1->id,
                    'qty' => 5,
                    'source' => 'sewa',
                    'condition' => 'layak_pakai',
                    'notes' => 'Uji fast bulk store',
                ]
            ]
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('project_equipment', [
            'project_id' => $project->id,
            'equipment_master_id' => $eq1->id,
            'qty' => 5,
            'source' => 'sewa',
        ]);
    }
}
