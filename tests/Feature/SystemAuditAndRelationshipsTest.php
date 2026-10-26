<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Material;
use App\Models\MaterialRealization;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchasePayment;
use App\Models\PurchasePaymentItem;
use App\Models\RabItem;
use App\Models\RabItemMaterial;
use App\Models\RabNode;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\EquipmentMasterSeeder;
use Database\Seeders\MaterialMasterSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SampleProjectSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class SystemAuditAndRelationshipsTest extends TestCase
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

    /**
     * Test 1: Relasi Eloquent pada Project, PurchaseOrder, dan PurchaseOrderItem
     */
    public function test_project_and_purchase_order_relationships_exist(): void
    {
        $project = Project::firstOrFail();

        // Relasi Project
        $this->assertInstanceOf(HasMany::class, $project->mandorBorongans());
        $this->assertInstanceOf(HasMany::class, $project->purchasePayments());
        $this->assertInstanceOf(HasMany::class, $project->purchaseReturns());

        // Relasi PurchaseOrder
        $po = PurchaseOrder::firstOrFail();
        $this->assertInstanceOf(Relation::class, $po->returns());

        // Relasi PurchaseOrderItem
        $poItem = PurchaseOrderItem::firstOrFail();
        $this->assertInstanceOf(HasMany::class, $poItem->deliveryOrderItems());
    }

    /**
     * Test 2: Status invoice yang lunas via PurchasePayment diperbarui dengan benar
     */
    public function test_invoice_recalculate_payment_status_updates_paid_status_correctly(): void
    {
        $project = Project::firstOrFail();
        $supplier = Supplier::firstOrFail();

        $invoice = Invoice::create([
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
            'invoice_number' => 'INV-TEST-FULL-001',
            'invoice_date' => now()->toDateString(),
            'amount' => 5000000,
            'paid_amount' => 0,
            'payment_status' => 'unpaid',
            'attachment_path' => 'dummy.pdf',
            'validated_by' => Auth::id(),
            'status' => 'validated',
        ]);

        $this->assertEquals(5000000, $invoice->net_amount);

        // Buat pembayaran penuh
        $payment = PurchasePayment::create([
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
            'payment_number' => 'PAY-TEST-001',
            'payment_date' => now()->toDateString(),
            'total_amount' => 5000000,
        ]);

        PurchasePaymentItem::create([
            'purchase_payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'amount_paid' => 5000000,
        ]);

        // Panggil rekalkulasi status
        $invoice->recalculatePaymentStatus();

        // Harus menjadi 'paid'
        $this->assertEquals('paid', $invoice->payment_status);
        $this->assertEquals(5000000, (float) $invoice->paid_amount);
    }

    /**
     * Test 3: Cost Realization menghitung aktual belanja nyata dari PO tanpa multiplier fiktif 0.92
     */
    public function test_cost_realization_calculates_actual_po_expenditures_accurately(): void
    {
        $project = Project::firstOrFail();
        $supplier = Supplier::firstOrFail();
        $unit = Unit::firstOrFail();
        $material = Material::firstOrFail();

        $node = RabNode::where('project_id', $project->id)->where('level', 1)->firstOrFail();
        $subNode = RabNode::create([
            'project_id' => $project->id,
            'parent_id' => $node->id,
            'level' => 2,
            'code' => $node->code.'.Z',
            'name' => 'Sub Test Kategori Biaya',
            'sort_order' => 99,
        ]);

        $item = RabItem::create([
            'rab_node_id' => $subNode->id,
            'item_no' => '99.0',
            'name' => 'Pekerjaan Uji Cost Realization',
            'volume' => 10,
            'unit_id' => $unit->id,
            'unit_price' => 100000,
            'total_price' => 1000000,
            'sort_order' => 1,
        ]);

        // Buat PO yang mengalokasikan item RAB ini sebesar Rp 850.000 (bukan dummy 0.92 = 920.000)
        $po = PurchaseOrder::create([
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
            'po_number' => 'PO-COST-TEST-001',
            'po_date' => now()->toDateString(),
            'status' => 'sent',
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'material_id' => $material->id,
            'rab_item_id' => $item->id,
            'qty_ordered' => 8.5,
            'unit_id' => $unit->id,
            'unit_price' => 100000, // 8.5 * 100000 = 850.000
        ]);

        // Halaman cost comparison harus memuat realisasi aktual nyata
        $response = $this->get(route('cost.index', ['project_id' => $project->id]));
        $response->assertStatus(200);

        // Subnode dan node harus menghitung actual cost nyata
        $actualItemCost = (float) $item->actual_cost;
        $this->assertEquals(850000.0, $actualItemCost);

        // View tidak boleh memuat angka dummy 0.92
        $response->assertDontSee('* 0.92');
    }

    /**
     * Test 4: Hapus RAB item atau node harus menyinkronkan planned_qty di MaterialRealization
     */
    public function test_rab_item_and_node_deletion_recalculates_material_realization_baselines(): void
    {
        $project = Project::firstOrFail();
        $unit = Unit::firstOrFail();
        $material = Material::create([
            'code' => 'MAT-SYNC-TEST-001',
            'name' => 'Material Uji Sinkronisasi Hapus',
            'category' => 'bahan_dasar',
            'default_unit_id' => $unit->id,
            'standard_price' => 50000,
            'is_active' => true,
        ]);

        $node = RabNode::where('project_id', $project->id)->where('level', 1)->firstOrFail();
        $item = RabItem::create([
            'rab_node_id' => $node->id,
            'item_no' => '88.0',
            'name' => 'Item Pekerjaan Khusus Material Hapus',
            'volume' => 5,
            'unit_id' => $unit->id,
            'unit_price' => 50000,
            'total_price' => 250000,
        ]);

        RabItemMaterial::create([
            'rab_item_id' => $item->id,
            'material_id' => $material->id,
            'volume' => 150.0,
            'unit_id' => $unit->id,
            'unit_price' => 50000,
            'total_price' => 7500000,
        ]);

        // Material realization harus punya planned_qty 150
        $realization = MaterialRealization::where('project_id', $project->id)
            ->where('material_id', $material->id)
            ->first();
        $this->assertNotNull($realization);
        $this->assertEquals(150.0, (float) $realization->planned_qty);

        // Hapus item via route
        $response = $this->delete(route('rab.items.destroy', $item->id));
        $response->assertRedirect();

        // Planned qty harus kembali 0
        $realizationAfter = MaterialRealization::where('project_id', $project->id)
            ->where('material_id', $material->id)
            ->first();
        $plannedAfter = $realizationAfter ? (float) $realizationAfter->planned_qty : 0.0;
        $this->assertEquals(0.0, $plannedAfter);
    }

    /**
     * Test 5: Pengguna dengan izin invoice_read dapat mengakses root /procurement dan dialihkan ke invoice
     */
    public function test_user_with_invoice_permission_can_access_procurement_root_redirect(): void
    {
        $user = User::factory()->create([
            'role' => 'Custom',
            'permissions' => ['invoice_read'],
            'is_active' => true,
        ]);

        $this->actingAs($user);

        // Akses route root procurement
        $response = $this->get(route('procurement.index'));

        // Harus berhasil dialihkan ke procurement.invoices.index, BUKAN 403 Forbidden
        $response->assertRedirect(route('procurement.invoices.index'));
    }

    /**
     * Test 6: Supplier tidak dapat dihapus jika memiliki Pembayaran atau Retur
     */
    public function test_supplier_cannot_be_deleted_if_it_has_payments_or_returns(): void
    {
        $project = Project::firstOrFail();
        $supplier = Supplier::create([
            'name' => 'Supplier Khusus Uji Proteksi Hapus',
            'contact_person' => 'Budi',
            'phone' => '081234567890',
            'address' => 'Jl. Uji Coba',
        ]);

        PurchasePayment::create([
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
            'payment_number' => 'PAY-PROT-001',
            'payment_date' => now()->toDateString(),
            'total_amount' => 1000000,
            'payment_method' => 'Transfer Bank',
        ]);

        $response = $this->delete(route('suppliers.destroy', $supplier->id));
        $response->assertRedirect(route('suppliers.index'));
        $response->assertSessionHas('error');

        // Supplier tidak boleh terhapus
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    }
}
