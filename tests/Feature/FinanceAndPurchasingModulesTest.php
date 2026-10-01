<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Material;
use App\Models\Project;
use App\Models\PurchaseDownPayment;
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

    public function test_down_payments_index_loads_and_down_payment_can_be_created(): void
    {
        $project = Project::first();
        $supplier = Supplier::first() ?? Supplier::create([
            'name' => 'Toko Besi & Bangunan Abadi',
            'contact_person' => 'Budi',
            'phone' => '08123456789',
        ]);

        $response = $this->get(route('finance.down-payments.index', ['project_id' => $project->id]));
        $response->assertStatus(200);
        $response->assertSee('Uang Muka Pembelian');
        $response->assertSee('Catat Uang Muka (DP)');

        $dpResponse = $this->post(route('finance.down-payments.store'), [
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
            'dp_number' => 'DP-2026-TEST-001',
            'dp_date' => '2026-09-28',
            'amount' => 5000000,
            'payment_method' => 'Transfer Bank',
            'bank_name' => 'BCA Rek. 1234567890',
            'notes' => 'Uang muka pengadaan besi',
        ]);

        $dpResponse->assertRedirect(route('finance.down-payments.index', ['project_id' => $project->id]));
        $this->assertDatabaseHas('purchase_down_payments', [
            'dp_number' => 'DP-2026-TEST-001',
            'amount' => 5000000,
            'status' => 'paid',
        ]);
    }

    public function test_invoice_creation_with_down_payment_deduction(): void
    {
        $project = Project::first();
        $supplier = Supplier::first();

        $dp = PurchaseDownPayment::create([
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
            'dp_number' => 'DP-DEDUCT-001',
            'dp_date' => '2026-09-20',
            'amount' => 2000000,
            'payment_method' => 'Transfer Bank',
            'status' => 'paid',
        ]);

        $file = UploadedFile::fake()->create('invoice_tagihan.pdf', 150);

        $response = $this->post(route('procurement.invoice.store'), [
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
            'invoice_number' => 'INV-DEDUCT-999',
            'invoice_date' => '2026-09-28',
            'due_date' => '2026-10-15',
            'amount' => 10000000,
            'down_payment_id' => $dp->id,
            'attachment' => $file,
            'notes' => 'Tagihan dengan potongan uang muka',
        ]);

        $response->assertRedirect(route('procurement.invoices.index', ['project_id' => $project->id]));

        $this->assertDatabaseHas('invoices', [
            'invoice_number' => 'INV-DEDUCT-999',
            'down_payment_id' => $dp->id,
            'down_payment_amount' => 2000000,
            'payment_status' => 'unpaid',
        ]);

        $invoice = Invoice::where('invoice_number', 'INV-DEDUCT-999')->first();
        $this->assertEquals(8000000, $invoice->net_amount);
        $this->assertEquals('applied', $dp->fresh()->status);
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
}
