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

    public function test_project_switching_updates_session_and_handles_previous_url_query_params()
    {
        $project1 = Project::firstOrFail();
        $project2 = Project::create([
            'name' => 'Proyek Kedua Untuk Switch Test',
            'prototype_type' => 'Standar',
            'floor_count' => 2,
            'budget_year' => '2026',
            'status' => 'active',
        ]);

        // Scenario 1: User switches from a URL containing ?project_id=2
        $response = $this->from(route('rab.builder', ['project_id' => $project2->id]))
            ->post(route('projects.switch'), ['project_id' => $project1->id]);

        $this->assertEquals($project1->id, session('active_project_id'));
        $response->assertRedirect(route('rab.builder', ['project_id' => $project1->id]));

        // Scenario 2: User switches from a clean URL without query parameters
        $response2 = $this->from(route('dashboard'))
            ->post(route('projects.switch'), ['project_id' => $project2->id]);

        $this->assertEquals($project2->id, session('active_project_id'));
        $response2->assertRedirect(route('dashboard'));
    }

    public function test_user_can_update_rab_node_category_and_subtotals_recalculate()
    {
        $project = Project::first();
        $node = $project->rootRabNodes()->first();
        $this->assertNotNull($node);

        $response = $this->put(route('rab.nodes.update', $node->id), [
            'code' => 'I-REV',
            'name' => 'PEKERJAAN PERSIAPAN & TANAH DIREVISI',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $node->refresh();
        $this->assertEquals('I-REV', $node->code);
        $this->assertEquals('PEKERJAAN PERSIAPAN & TANAH DIREVISI', $node->name);
    }

    public function test_user_can_update_rab_item_and_subtotals_recalculate()
    {
        $project = Project::first();
        $item = RabItem::first();
        $this->assertNotNull($item);

        $unit = Unit::first();

        $response = $this->put(route('rab.items.update', $item->id), [
            'item_no' => '1.99',
            'name' => 'Pembersihan Lapangan Updated',
            'volume' => 50,
            'unit_id' => $unit->id,
            'unit_price' => 200000,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $item->refresh();
        $this->assertEquals('1.99', $item->item_no);
        $this->assertEquals('Pembersihan Lapangan Updated', $item->name);
        $this->assertEquals(50, (float) $item->volume);
        $this->assertEquals(200000, (float) $item->unit_price);
        $this->assertEquals(10000000, (float) $item->total_price);
    }

    public function test_user_can_update_rab_item_material()
    {
        $item = RabItem::first();
        $material = Material::first();
        $unit = Unit::first();

        // Create a material breakdown
        $itemMaterial = $item->materials()->create([
            'material_id' => $material->id,
            'volume' => 10,
            'unit_id' => $unit->id,
            'unit_price' => 50000,
            'total_price' => 500000,
            'input_by' => 1,
        ]);

        $response = $this->put(route('rab.materials.update', $itemMaterial->id), [
            'material_id' => $material->id,
            'volume' => 15,
            'unit_id' => $unit->id,
            'unit_price' => 60000,
            'notes' => 'Catatan revisi',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $itemMaterial->refresh();
        $this->assertEquals(15, (float) $itemMaterial->volume);
        $this->assertEquals(60000, (float) $itemMaterial->unit_price);
        $this->assertEquals(900000, (float) $itemMaterial->total_price);
        $this->assertEquals('Catatan revisi', $itemMaterial->notes);
    }

    public function test_newly_created_rab_items_and_materials_do_not_appear_in_non_rab_monitoring()
    {
        // 1. Create a brand new project
        $project = Project::create([
            'name' => 'Proyek RAB Baru Monitoring Test',
            'prototype_type' => 'Gedung Uji',
            'floor_count' => 1,
            'budget_year' => '2026',
            'location_kds' => 'Zona A',
            'foundation_type' => 'Batu Kali',
            'alert_over_threshold_pct' => 10,
            'alert_under_threshold_pct' => 5,
        ]);

        // 2. Create Level 1 Root Category
        $rootNode = $project->rabNodes()->create([
            'code' => 'I',
            'name' => 'Pekerjaan ABCD',
            'level' => 1,
            'parent_id' => null,
            'sort_order' => 1,
        ]);

        $unit = Unit::first();
        $matParent = Material::first();
        $matChild = Material::skip(1)->first();

        // 3. Create RAB Item directly under Root Node (Level 1)
        $rabItem = $rootNode->rabItems()->create([
            'item_no' => '1.0',
            'name' => 'ABC 1',
            'volume' => 10,
            'unit_id' => $unit->id,
            'unit_price' => 10000000,
            'total_price' => 100000000,
            'sort_order' => 1,
        ]);

        // 4. Add BOM material (parent composite)
        $parentItemMaterial = $rabItem->materials()->create([
            'material_id' => $matParent->id,
            'volume' => 50,
            'unit_id' => $unit->id,
            'unit_price' => 195000,
            'total_price' => 9750000,
            'input_by' => 1,
        ]);

        // 5. Add breakdown material (child leaf)
        $childItemMaterial = $rabItem->materials()->create([
            'material_id' => $matChild->id,
            'parent_id' => $parentItemMaterial->id,
            'volume' => 100,
            'unit_id' => $unit->id,
            'unit_price' => 16500,
            'total_price' => 1650000,
            'input_by' => 1,
        ]);

        // 6. View dashboard for this project
        $response = $this->get('/?project_id='.$project->id);
        $response->assertStatus(200);

        // Check view variables
        $nonRabPurchases = $response->viewData('nonRabPurchases');
        $stats = $response->viewData('stats');

        // Neither the parent nor the child breakdown material should be in nonRabPurchases!
        $nonRabMaterialIds = collect($nonRabPurchases)->pluck('material_id')->toArray();
        $this->assertNotContains($matParent->id, $nonRabMaterialIds, 'Parent material in RAB must not be in Non-RAB purchases');
        $this->assertNotContains($matChild->id, $nonRabMaterialIds, 'Breakdown material in RAB must not be in Non-RAB purchases');
        $this->assertEmpty($nonRabPurchases, 'No purchases have been made, so non-RAB purchases recap should be empty');
        $this->assertEquals(0, $stats['non_rab_items_count']);
        $this->assertEquals(0, $stats['non_rab_total_cost']);
    }

    public function test_material_status_can_be_toggled(): void
    {
        $material = Material::first();
        $this->assertTrue((bool) $material->is_active);

        $response = $this->post(route('materials.toggle_status', $material));
        $response->assertSessionHas('success');
        $this->assertFalse((bool) $material->refresh()->is_active);

        $response2 = $this->post(route('materials.toggle_status', $material));
        $response2->assertSessionHas('success');
        $this->assertTrue((bool) $material->refresh()->is_active);
    }

    public function test_material_can_be_updated_with_inactive_status(): void
    {
        $material = Material::first();
        $unit = Unit::first();

        $response = $this->put(route('materials.update', $material), [
            'name' => $material->name,
            'code' => $material->code,
            'category' => $material->category,
            'default_unit_id' => $unit->id,
            'standard_price' => 50000,
            'is_active' => '0',
        ]);

        $response->assertSessionHas('success');
        $this->assertFalse((bool) $material->refresh()->is_active);
    }

    public function test_material_deletion_blocked_by_history_sets_deactivation_flash(): void
    {
        // Find a material referenced by PO
        $material = Material::whereHas('purchaseOrderItems')->first();
        $this->assertNotNull($material);

        $response = $this->delete(route('materials.destroy', $material));
        $response->assertSessionHas('error');
        $response->assertSessionHas('deactivate_material_id', $material->id);
    }
}
