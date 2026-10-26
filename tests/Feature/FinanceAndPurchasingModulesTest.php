<?php

namespace Tests\Feature;

use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Models\Material;
use App\Models\Project;
use App\Models\PurchaseOrder;
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

class FinanceAndPurchasingModulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

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

    public function test_invoice_creation(): void
    {
        $project = Project::first();
        $supplier = Supplier::first();

        $file = UploadedFile::fake()->create('invoice_tagihan.pdf', 150);

        $response = $this->post(route('procurement.invoice.store'), [
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
            'invoice_number' => 'INV-TEST-999',
            'invoice_date' => '2026-09-28',
            'due_date' => '2026-10-15',
            'amount' => 10000000,
            'attachment' => $file,
            'notes' => 'Tagihan resmi supplier',
        ]);

        $response->assertRedirect(route('procurement.invoices.index', ['project_id' => $project->id]));

        $this->assertDatabaseHas('invoices', [
            'invoice_number' => 'INV-TEST-999',
            'amount' => 10000000,
            'payment_status' => 'unpaid',
        ]);

        $invoice = Invoice::where('invoice_number', 'INV-TEST-999')->first();
        $this->assertEquals(10000000, $invoice->net_amount);
    }

    public function test_purchase_payment_settles_invoice(): void
    {
        $project = Project::first();
        $supplier = Supplier::first();
        $file = UploadedFile::fake()->create('inv_doc.pdf', 100);

        $invoice = Invoice::create([
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
            'invoice_number' => 'INV-PAY-001',
            'invoice_date' => '2026-09-25',
            'amount' => 7500000,
            'attachment_path' => 'invoices/dummy.pdf',
            'validated_by' => auth()->id(),
            'payment_status' => 'unpaid',
        ]);

        $response = $this->post(route('finance.payments.store'), [
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
            'payment_number' => 'BKK-2026-001',
            'payment_date' => '2026-09-28',
            'payment_method' => 'Transfer Bank',
            'bank_name' => 'Mandiri Rek. 987654321',
            'items' => [
                [
                    'invoice_id' => $invoice->id,
                    'amount_paid' => 7500000,
                ],
            ],
            'notes' => 'Pelunasan tagihan supplier',
        ]);

        $response->assertRedirect(route('finance.payments.index', ['project_id' => $project->id]));

        $this->assertDatabaseHas('purchase_payments', [
            'payment_number' => 'BKK-2026-001',
            'total_amount' => 7500000,
        ]);

        $this->assertDatabaseHas('purchase_payment_items', [
            'invoice_id' => $invoice->id,
            'amount_paid' => 7500000,
        ]);

        $this->assertEquals('paid', $invoice->fresh()->payment_status);
        $this->assertEquals(7500000, (float) $invoice->fresh()->paid_amount);
    }

    public function test_purchase_return_creation(): void
    {
        $project = Project::first();
        $supplier = Supplier::first();
        $material = Material::first();
        $unit = Unit::first();

        $response = $this->post(route('procurement.returns.store'), [
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
            'return_number' => 'RTR-2026-001',
            'return_date' => '2026-09-28',
            'compensation_type' => 'potong_tagihan',
            'notes' => 'Retur keramik pecah',
            'items' => [
                [
                    'material_id' => $material->id,
                    'unit_id' => $unit->id,
                    'qty_returned' => 10,
                    'unit_price' => 50000,
                    'reason' => 'Cacat/Rusak',
                ],
            ],
        ]);

        $response->assertRedirect(route('procurement.returns.index', ['project_id' => $project->id]));

        $this->assertDatabaseHas('purchase_returns', [
            'return_number' => 'RTR-2026-001',
            'compensation_type' => 'potong_tagihan',
        ]);

        $this->assertDatabaseHas('purchase_return_items', [
            'material_id' => $material->id,
            'qty_returned' => 10,
            'total_price' => 500000,
        ]);
    }

    public function test_cross_project_supplier_summary_aggregates_across_projects(): void
    {
        $project1 = Project::first();
        $project2 = Project::create([
            'name' => 'Proyek Gedung B (Beda Proyek)',
            'code' => 'PRJ-B-2026',
            'budget_year' => '2026',
            'contract_value' => 500000000,
            'status' => 'in_progress',
        ]);

        $supplier = Supplier::create([
            'name' => 'CV Sumber Material Jaya Lintas',
            'contact_person' => 'Hendra',
            'phone' => '085566778899',
            'address' => 'Semarang',
        ]);

        // Transaksi di Proyek 1: PO & Faktur
        $po1 = PurchaseOrder::create([
            'project_id' => $project1->id,
            'supplier_id' => $supplier->id,
            'po_number' => 'PO-PRJ1-001',
            'po_date' => '2026-09-10',
            'status' => 'approved',
        ]);
        Invoice::create([
            'project_id' => $project1->id,
            'supplier_id' => $supplier->id,
            'purchase_order_id' => $po1->id,
            'invoice_number' => 'INV-PRJ1-001',
            'invoice_date' => '2026-09-15',
            'amount' => 15000000,
            'paid_amount' => 15000000,
            'attachment_path' => 'invoices/test1.pdf',
            'validated_by' => auth()->id(),
            'payment_status' => 'paid',
        ]);

        // Transaksi di Proyek 2: PO & Faktur Belum Lunas
        $po2 = PurchaseOrder::create([
            'project_id' => $project2->id,
            'supplier_id' => $supplier->id,
            'po_number' => 'PO-PRJ2-001',
            'po_date' => '2026-09-20',
            'status' => 'approved',
        ]);
        Invoice::create([
            'project_id' => $project2->id,
            'supplier_id' => $supplier->id,
            'purchase_order_id' => $po2->id,
            'invoice_number' => 'INV-PRJ2-001',
            'invoice_date' => '2026-09-25',
            'amount' => 20000000,
            'paid_amount' => 5000000,
            'attachment_path' => 'invoices/test2.pdf',
            'validated_by' => auth()->id(),
            'payment_status' => 'partial',
        ]);

        $response = $this->get(route('finance.supplier-summary.index'));
        $response->assertStatus(200);
        $response->assertSee('CV Sumber Material Jaya Lintas');
        $response->assertSee('2 Proyek');
        $response->assertSee('Rekap Pembelian Toko Lintas Proyek');
        $response->assertSee('Ada Hutang');
    }

    public function test_outstanding_po_dashboard_loads_and_displays_pending_po(): void
    {
        $project = Project::first();
        $response = $this->get(route('procurement.outstanding-po', ['project_id' => $project->id]));
        $response->assertStatus(200);
        $response->assertSee('Monitoring PO Menggantung');
        $response->assertSee('Total PO Menggantung');
    }

    public function test_received_do_number_cannot_be_reinput(): void
    {
        $project = Project::first();
        $supplier = Supplier::first();
        $material = Material::first();
        $unit = Unit::first();

        // 1. First DO submission
        $file = UploadedFile::fake()->create('sj1.pdf', 100, 'application/pdf');
        $postData = [
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
            'do_number' => 'SJ-DUPLICATE-CHECK-001',
            'do_date' => '2026-10-08',
            'attachment' => $file,
            'items' => [
                [
                    'material_id' => $material->id,
                    'qty_received' => 5,
                    'unit_id' => $unit->id,
                ],
            ],
        ];

        $response1 = $this->post(route('procurement.do.store'), $postData);
        $response1->assertRedirect();
        $this->assertDatabaseHas('delivery_orders', [
            'project_id' => $project->id,
            'do_number' => 'SJ-DUPLICATE-CHECK-001',
        ]);

        // 2. Second DO submission with the SAME do_number should be rejected
        $file2 = UploadedFile::fake()->create('sj2.pdf', 100, 'application/pdf');
        $postData['attachment'] = $file2;

        $response2 = $this->post(route('procurement.do.store'), $postData);
        $response2->assertSessionHas('error');
        $this->assertEquals(1, DeliveryOrder::where('project_id', $project->id)->where('do_number', 'SJ-DUPLICATE-CHECK-001')->count());
    }

    public function test_used_do_number_is_excluded_from_scheduled_deliveries_in_do_index(): void
    {
        $project = Project::first();
        $supplier = Supplier::first();
        $material = Material::first();
        $unit = Unit::first();

        // Create PO with delivery_order_number
        $po = PurchaseOrder::create([
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
            'po_number' => 'PO-SCHEDULED-TEST-99',
            'po_date' => '2026-10-01',
            'status' => 'approved',
            'delivery_order_number' => 'SJ-SCHEDULED-TEST-99',
        ]);
        $po->items()->create([
            'material_id' => $material->id,
            'qty_ordered' => 10,
            'unit_id' => $unit->id,
            'unit_price' => 50000,
        ]);

        // Before DO receipt: scheduledDeliveries MUST include it
        $resBefore = $this->get(route('procurement.do.index', ['project_id' => $project->id]));
        $scheduledBefore = collect($resBefore->viewData('scheduledDeliveries'))->pluck('do_number')->toArray();
        $this->assertContains('SJ-SCHEDULED-TEST-99', $scheduledBefore);

        // Record DO for this number
        $file = UploadedFile::fake()->create('sj.pdf', 100, 'application/pdf');
        $this->post(route('procurement.do.store'), [
            'project_id' => $project->id,
            'purchase_order_id' => $po->id,
            'supplier_id' => $supplier->id,
            'do_number' => 'SJ-SCHEDULED-TEST-99',
            'do_date' => '2026-10-08',
            'attachment' => $file,
            'items' => [
                [
                    'material_id' => $material->id,
                    'purchase_order_id' => $po->id,
                    'qty_received' => 10,
                    'unit_id' => $unit->id,
                ],
            ],
        ]);

        // After DO receipt: scheduledDeliveries MUST NOT include it anymore
        $resAfter = $this->get(route('procurement.do.index', ['project_id' => $project->id]));
        $scheduledAfter = collect($resAfter->viewData('scheduledDeliveries'))->pluck('do_number')->toArray();
        $this->assertNotContains('SJ-SCHEDULED-TEST-99', $scheduledAfter);
    }

    public function test_updating_po_do_number_rejects_already_used_do_number(): void
    {
        $project = Project::first();
        $supplier = Supplier::first();
        $material = Material::first();
        $unit = Unit::first();

        // Create an existing DO
        DeliveryOrder::create([
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
            'do_number' => 'SJ-ALREADY-USED-001',
            'do_date' => '2026-10-05',
            'attachment_path' => 'delivery_orders/test.pdf',
            'received_by' => auth()->id(),
            'status' => 'validated',
        ]);

        $po = PurchaseOrder::create([
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
            'po_number' => 'PO-UPDATE-DO-TEST',
            'po_date' => '2026-10-01',
            'status' => 'approved',
        ]);

        // Attempting to set an already used DO number should fail
        $responseFail = $this->post(route('procurement.po.update_do', $po), [
            'delivery_order_number' => 'SJ-ALREADY-USED-001',
        ]);
        $responseFail->assertSessionHas('error');

        // Setting a new unused DO number should succeed
        $responseSuccess = $this->post(route('procurement.po.update_do', $po), [
            'delivery_order_number' => 'SJ-NEW-BRAND-002',
        ]);
        $responseSuccess->assertSessionHas('success');
        $this->assertEquals('SJ-NEW-BRAND-002', $po->refresh()->delivery_order_number);
    }
}
