<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Material;
use App\Models\MaterialRealization;
use App\Models\Project;
use App\Models\PurchaseOrder;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BugfixRegressionTest extends TestCase
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

        $this->actingAs(User::where('email', 'superadmin@sirisolab.com')->firstOrFail());
    }

    public function test_clone_bom_keeps_breakdown_hierarchy_without_double_counting(): void
    {
        $project = Project::firstOrFail();
        $unit = Unit::firstOrFail();
        $node = RabNode::where('project_id', $project->id)->whereNull('parent_id')->firstOrFail();
        [$parentMaterial, $childMaterial] = [
            Material::create(['code' => 'MAT-CLN-P', 'name' => 'Beton Jadi Uji Clone', 'category' => 'bahan_jadi', 'default_unit_id' => $unit->id]),
            Material::create(['code' => 'MAT-CLN-C', 'name' => 'Semen Uji Clone', 'category' => 'bahan_dasar', 'default_unit_id' => $unit->id]),
        ];

        $source = RabItem::create(['rab_node_id' => $node->id, 'name' => 'Sumber BOM', 'volume' => 1, 'unit_id' => $unit->id, 'unit_price' => 0, 'total_price' => 0]);
        $target = RabItem::create(['rab_node_id' => $node->id, 'name' => 'Target BOM', 'volume' => 1, 'unit_id' => $unit->id, 'unit_price' => 0, 'total_price' => 0]);

        $parent = RabItemMaterial::create(['rab_item_id' => $source->id, 'material_id' => $parentMaterial->id, 'volume' => 2, 'unit_id' => $unit->id]);
        RabItemMaterial::create(['rab_item_id' => $source->id, 'material_id' => $childMaterial->id, 'parent_id' => $parent->id, 'volume' => 7, 'unit_id' => $unit->id]);

        $this->post(route('rab.items.clone_bom', $target), ['source_item_id' => $source->id])->assertRedirect();

        $cloned = RabItemMaterial::where('rab_item_id', $target->id)->get();
        $this->assertCount(2, $cloned);
        $clonedParent = $cloned->firstWhere('material_id', $parentMaterial->id);
        $clonedChild = $cloned->firstWhere('material_id', $childMaterial->id);
        $this->assertNull($clonedParent->parent_id);
        $this->assertSame($clonedParent->id, $clonedChild->parent_id);
        $this->assertTrue($target->fresh()->is_composite);

        // Kuota leaf = 7 (sumber) + 7 (salinan); material induk ber-breakdown tidak dihitung
        $this->assertEquals(14.0, (float) MaterialRealization::where('project_id', $project->id)->where('material_id', $childMaterial->id)->value('planned_qty'));
        $this->assertNull(MaterialRealization::where('project_id', $project->id)->where('material_id', $parentMaterial->id)->first());
    }

    public function test_payment_allocation_cannot_exceed_invoice_remaining_balance(): void
    {
        $project = Project::firstOrFail();
        $supplier = Supplier::firstOrFail();
        $invoice = Invoice::create([
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
            'invoice_number' => 'INV-OVERPAY-1',
            'invoice_date' => now()->toDateString(),
            'amount' => 1000000,
            'paid_amount' => 0,
            'payment_status' => 'unpaid',
            'attachment_path' => 'dummy.pdf',
            'validated_by' => auth()->id(),
            'status' => 'validated',
        ]);

        $this->post(route('finance.payments.store'), [
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
            'payment_number' => 'PAY-OVER-1',
            'payment_date' => now()->toDateString(),
            'payment_method' => 'Transfer Bank',
            'items' => [['invoice_id' => $invoice->id, 'amount_paid' => 1500000]],
        ])->assertSessionHas('error');

        $this->assertDatabaseMissing('purchase_payments', ['payment_number' => 'PAY-OVER-1']);
        $this->assertSame('unpaid', $invoice->fresh()->payment_status);
    }

    public function test_pending_po_count_matches_fulfillment_details(): void
    {
        $project = Project::firstOrFail();
        $pos = PurchaseOrder::with('items')->where('project_id', $project->id)->where('status', '!=', 'cancelled')->get();

        $expected = $pos->filter(fn ($po) => $po->getFulfillmentDetails()['has_pending'])->count();

        $this->assertSame($expected, PurchaseOrder::pendingCountForProject($project->id));
        $this->assertSame(
            $expected,
            PurchaseOrder::attachFulfillment($pos)->filter(fn ($po) => $po->fulfillment['has_pending'])->count()
        );
    }

    public function test_material_write_routes_require_materials_permission(): void
    {
        $rabUser = User::factory()->create(['role' => 'Custom', 'permissions' => ['rab'], 'is_active' => true]);
        $this->actingAs($rabUser);

        // Pengguna RAB boleh melihat katalog (sesuai menu sidebar), tetapi tidak boleh mengubah
        $this->get(route('materials.index'))->assertOk();
        $this->post(route('materials.store'), [
            'name' => 'Material Tanpa Izin',
            'category' => 'bahan_dasar',
            'default_unit_id' => Unit::firstOrFail()->id,
        ])->assertForbidden();
    }

    public function test_layout_renders_for_user_without_alerts_permission(): void
    {
        $user = User::factory()->create(['role' => 'Custom', 'permissions' => ['dashboard'], 'is_active' => true]);
        $this->actingAs($user);

        // Sebelumnya: "Undefined variable $unreadAlerts" (500) di setiap halaman
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_unimplemented_resource_routes_are_not_registered(): void
    {
        $project = Project::firstOrFail();

        // Sebelumnya 500 (method controller tidak ada); kini ditolak router sebagai client error
        $this->get('/projects/'.$project->id)->assertClientError();
        $this->get('/materials/create')->assertClientError();
        $this->get('/suppliers/create')->assertClientError();
    }
}
