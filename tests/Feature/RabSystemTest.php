<?php

namespace Tests\Feature;

use App\Models\DeliveryOrder;
use App\Models\EquipmentCategory;
use App\Models\EquipmentMaster;
use App\Models\Material;
use App\Models\MaterialRealization;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\RabItem;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\EquipmentMasterSeeder;
use Database\Seeders\MaterialMasterSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SampleProjectSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

        $superadmin = User::where('email', 'superadmin@sirisolab.com')->first();
        if ($superadmin) {
            $this->actingAs($superadmin);
        }
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
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('project_equipment', [
            'project_id' => $project->id,
            'equipment_master_id' => $eq1->id,
            'qty' => 5,
            'source' => 'sewa',
        ]);
    }

    public function test_equipment_master_crud_and_pricing()
    {
        $cat = EquipmentCategory::where('name', 'Mesin')->first();
        $unit = Unit::where('code', 'Unit')->first();

        // 1. Create equipment with price
        $response = $this->post(route('equipment.master.store'), [
            'equipment_category_id' => $cat->id,
            'name' => 'Mesin Molen Mini 250L',
            'code' => 'EQP-MSN-999',
            'default_unit_id' => $unit->id,
            'price' => 250000,
            'spec' => 'Kapasitas 0.5 sak mesin bensin',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('equipment.index', ['tab' => 'master']));
        $this->assertDatabaseHas('equipment_master', [
            'code' => 'EQP-MSN-999',
            'name' => 'Mesin Molen Mini 250L',
            'price' => 250000,
        ]);

        $created = EquipmentMaster::where('code', 'EQP-MSN-999')->first();

        // 2. Update equipment price and spec
        $updateResponse = $this->put(route('equipment.master.update', $created->id), [
            'equipment_category_id' => $cat->id,
            'name' => 'Mesin Molen Mini 250L Upgraded',
            'code' => 'EQP-MSN-999',
            'default_unit_id' => $unit->id,
            'price' => 275000,
            'spec' => 'Kapasitas 0.5 sak mesin bensin Honda',
            'is_active' => 1,
        ]);

        $updateResponse->assertRedirect(route('equipment.index', ['tab' => 'master']));
        $this->assertDatabaseHas('equipment_master', [
            'id' => $created->id,
            'name' => 'Mesin Molen Mini 250L Upgraded',
            'price' => 275000,
        ]);

        // 3. Delete equipment
        $deleteResponse = $this->delete(route('equipment.master.destroy', $created->id));
        $deleteResponse->assertRedirect(route('equipment.index', ['tab' => 'master']));
        $this->assertDatabaseMissing('equipment_master', [
            'id' => $created->id,
        ]);
    }

    public function test_po_can_be_created_with_rab_item_uraian_pekerjaan()
    {
        $project = Project::first();
        $supplier = Supplier::firstOrCreate(
            ['name' => 'PT Mitra Semen Perkasa'],
            ['category' => 'Supplier Utama', 'phone' => '08123456789']
        );
        $material = Material::first();
        $unit = Unit::first();
        $rabItem = RabItem::whereHas('rabNode', fn ($q) => $q->where('project_id', $project->id))->first();

        $this->assertNotNull($rabItem, 'RabItem must exist in sample project');

        $poNumber = 'PO/2026/TEST/001';

        $response = $this->post(route('procurement.po.store'), [
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
            'po_number' => $poNumber,
            'po_date' => '2026-09-28',
            'notes' => 'Pengadaan material untuk pekerjaan persiapan',
            'items' => [
                [
                    'material_id' => $material->id,
                    'rab_item_id' => $rabItem->id,
                    'qty_ordered' => 10,
                    'unit_id' => $unit->id,
                    'unit_price' => 75000,
                ],
            ],
        ]);

        $response->assertRedirect(route('procurement.po.index', ['project_id' => $project->id]));

        $this->assertDatabaseHas('purchase_orders', [
            'po_number' => $poNumber,
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
        ]);

        $po = PurchaseOrder::where('po_number', $poNumber)->first();
        $this->assertNotNull($po);

        $this->assertDatabaseHas('purchase_order_items', [
            'purchase_order_id' => $po->id,
            'material_id' => $material->id,
            'rab_item_id' => $rabItem->id,
            'qty_ordered' => 10,
            'unit_price' => 75000,
        ]);

        // PO Detail & Index view check
        $indexResponse = $this->get(route('procurement.po.index', ['project_id' => $project->id]));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee($poNumber);

        // PDF Generation check
        $pdfResponse = $this->get(route('procurement.po.pdf', $po->id));
        $pdfResponse->assertStatus(200);
    }

    public function test_material_master_can_be_created_and_updated_without_price()
    {
        $unit = Unit::first();

        // 1. Create without standard_price
        $response = $this->post(route('materials.store'), [
            'name' => 'Batu Belah Kali Karawang',
            'code' => 'MAT-BSR-999',
            'category' => 'bahan_dasar',
            'default_unit_id' => $unit->id,
            'specification' => 'Diameter 15-20cm batu keras',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('materials.index', ['tab' => 'materials']));
        $this->assertDatabaseHas('materials', [
            'code' => 'MAT-BSR-999',
            'name' => 'Batu Belah Kali Karawang',
            'standard_price' => 0,
        ]);

        $material = Material::where('code', 'MAT-BSR-999')->first();

        // 2. Update without standard_price
        $updateResponse = $this->put(route('materials.update', $material->id), [
            'name' => 'Batu Belah Kali Karawang Grade A',
            'code' => 'MAT-BSR-999',
            'category' => 'bahan_dasar',
            'default_unit_id' => $unit->id,
            'specification' => 'Diameter 15-20cm batu keras terpilih',
            'is_active' => 1,
        ]);

        $updateResponse->assertRedirect(route('materials.index', ['tab' => 'materials']));
        $this->assertDatabaseHas('materials', [
            'id' => $material->id,
            'name' => 'Batu Belah Kali Karawang Grade A',
            'standard_price' => 0,
        ]);
    }

    public function test_one_delivery_order_can_link_multiple_purchase_orders()
    {
        Storage::fake('public');

        $project = Project::first();
        $supplier = Supplier::first();
        $materials = Material::take(2)->get();
        $unit = Unit::first();

        // Create 2 Purchase Orders
        $po1 = PurchaseOrder::create([
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
            'po_number' => 'PO/TEST/001',
            'po_date' => now()->subDays(2),
            'status' => 'approved',
            'created_by' => auth()->id(),
        ]);
        $po1->items()->create([
            'material_id' => $materials[0]->id,
            'qty_ordered' => 20,
            'unit_id' => $unit->id,
            'unit_price' => 50000,
        ]);

        $po2 = PurchaseOrder::create([
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
            'po_number' => 'PO/TEST/002',
            'po_date' => now()->subDay(),
            'status' => 'approved',
            'created_by' => auth()->id(),
        ]);
        $po2->items()->create([
            'material_id' => $materials[1]->id,
            'qty_ordered' => 15,
            'unit_id' => $unit->id,
            'unit_price' => 75000,
        ]);

        // Submit single DO linking both PO1 and PO2
        $file = UploadedFile::fake()->create('surat_jalan.pdf', 100);

        $response = $this->post(route('procurement.do.store'), [
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
            'do_number' => 'DO/MULTI-PO/001',
            'do_date' => now()->format('Y-m-d'),
            'purchase_order_ids' => [$po1->id, $po2->id],
            'attachment' => $file,
            'notes' => 'Pengiriman gabungan dua PO',
            'items' => [
                [
                    'material_id' => $materials[0]->id,
                    'purchase_order_id' => $po1->id,
                    'qty_received' => 20,
                    'unit_id' => $unit->id,
                ],
                [
                    'material_id' => $materials[1]->id,
                    'purchase_order_id' => $po2->id,
                    'qty_received' => 15,
                    'unit_id' => $unit->id,
                ],
            ],
        ]);

        $response->assertRedirect(route('procurement.do.index', ['project_id' => $project->id]));

        $this->assertDatabaseHas('delivery_orders', [
            'do_number' => 'DO/MULTI-PO/001',
            'project_id' => $project->id,
        ]);

        $do = DeliveryOrder::where('do_number', 'DO/MULTI-PO/001')->first();

        // Check pivot table
        $this->assertDatabaseHas('delivery_order_purchase_order', [
            'delivery_order_id' => $do->id,
            'purchase_order_id' => $po1->id,
        ]);
        $this->assertDatabaseHas('delivery_order_purchase_order', [
            'delivery_order_id' => $do->id,
            'purchase_order_id' => $po2->id,
        ]);

        // Check items PO references
        $this->assertDatabaseHas('delivery_order_items', [
            'delivery_order_id' => $do->id,
            'material_id' => $materials[0]->id,
            'purchase_order_id' => $po1->id,
        ]);
        $this->assertDatabaseHas('delivery_order_items', [
            'delivery_order_id' => $do->id,
            'material_id' => $materials[1]->id,
            'purchase_order_id' => $po2->id,
        ]);

        // Check DO list view displays both POs
        $doListResponse = $this->get(route('procurement.do.index', ['project_id' => $project->id]));
        $doListResponse->assertStatus(200);
        $doListResponse->assertSee('PO/TEST/001');
        $doListResponse->assertSee('PO/TEST/002');
    }

    public function test_material_detail_page_shows_purchase_order_references_and_history()
    {
        $realization = MaterialRealization::first();

        $response = $this->get(route('monitoring.material.show', $realization->id));
        $response->assertStatus(200);
        $response->assertSee('Riwayat Pesanan Pembelian (PO)');
        $response->assertSee('No. Pesanan Pembelian (PO)');
        $response->assertSee('Pesanan Pembelian (PO)');
    }

    public function test_delivery_order_can_have_multiple_pos_and_cash_non_po_items()
    {
        Storage::fake('public');

        $project = Project::first();
        $supplier = Supplier::first();
        $materials = Material::take(3)->get();
        $unit = Unit::first();

        // Create 2 Purchase Orders
        $po1 = PurchaseOrder::create([
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
            'po_number' => 'PO/TEST/MULTI-1',
            'po_date' => now()->subDays(2),
            'status' => 'approved',
            'created_by' => auth()->id(),
        ]);
        $po1->items()->create([
            'material_id' => $materials[0]->id,
            'qty_ordered' => 10,
            'unit_id' => $unit->id,
            'unit_price' => 50000,
        ]);

        $po2 = PurchaseOrder::create([
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
            'po_number' => 'PO/TEST/MULTI-2',
            'po_date' => now()->subDay(),
            'status' => 'approved',
            'created_by' => auth()->id(),
        ]);
        $po2->items()->create([
            'material_id' => $materials[1]->id,
            'qty_ordered' => 15,
            'unit_id' => $unit->id,
            'unit_price' => 75000,
        ]);

        // Submit single DO linking PO1, PO2, plus 1 additional cash item without PO
        $file = UploadedFile::fake()->create('surat_jalan_campuran.pdf', 100);

        $response = $this->post(route('procurement.do.store'), [
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
            'do_number' => 'DO/CAMPURAN/001',
            'do_date' => now()->format('Y-m-d'),
            'purchase_order_ids' => [$po1->id, $po2->id],
            'attachment' => $file,
            'notes' => 'DO gabungan 2 PO dan ada tambahan pembelian tunai langsung',
            'items' => [
                [
                    'material_id' => $materials[0]->id,
                    'purchase_order_id' => $po1->id,
                    'qty_received' => 10,
                    'unit_id' => $unit->id,
                ],
                [
                    'material_id' => $materials[1]->id,
                    'purchase_order_id' => $po2->id,
                    'qty_received' => 15,
                    'unit_id' => $unit->id,
                ],
                [
                    'material_id' => $materials[2]->id,
                    'purchase_order_id' => null, // Pembelian tunai non-PO
                    'qty_received' => 5,
                    'unit_id' => $unit->id,
                ],
            ],
        ]);

        $response->assertRedirect(route('procurement.do.index', ['project_id' => $project->id]));

        $do = DeliveryOrder::where('do_number', 'DO/CAMPURAN/001')->firstOrFail();
        $this->assertTrue($do->hasNonPoItems());

        // Check pivot table has both POs
        $this->assertEqualsCanonicalizing([$po1->id, $po2->id], $do->purchaseOrders->pluck('id')->all());

        // Check cash non-po item exists with null purchase_order_id
        $this->assertDatabaseHas('delivery_order_items', [
            'delivery_order_id' => $do->id,
            'material_id' => $materials[2]->id,
            'purchase_order_id' => null,
            'qty_received' => 5,
        ]);

        // Verify DO index view
        $doListResponse = $this->get(route('procurement.do.index', ['project_id' => $project->id]));
        $doListResponse->assertStatus(200);
        $doListResponse->assertSee('PO/TEST/MULTI-1');
        $doListResponse->assertSee('PO/TEST/MULTI-2');
        $doListResponse->assertSee('+ Tunai');
        $doListResponse->assertSee('Tunai (Non-PO)');

        // Verify Material Detail page for Material 3
        $realization3 = MaterialRealization::where('project_id', $project->id)
            ->where('material_id', $materials[2]->id)
            ->firstOrFail();

        $detailResponse = $this->get(route('monitoring.material.show', $realization3->id));
        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('Pembelian Tunai (Non-PO)');
    }
}
