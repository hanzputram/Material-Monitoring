<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Units of Measure
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('group')->default('satuan_hitung'); // panjang, luas, volume, berat, satuan_hitung
            $table->boolean('is_base_unit')->default(false);
            $table->timestamps();
        });

        // 2. Projects
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('prototype_type')->nullable(); // e.g. "Barak Rembunai"
            $table->integer('floor_count')->default(1);
            $table->string('budget_year', 10)->nullable();
            $table->string('location_kds')->nullable();
            $table->string('foundation_type')->nullable(); // e.g. "Bored Pile"
            $table->string('status')->default('active'); // draft, active, closed
            $table->decimal('alert_over_threshold_pct', 6, 2)->default(10.00); // 10%
            $table->decimal('alert_under_threshold_pct', 6, 2)->default(5.00);  // 5%
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // 3. Project User Pivot
        Schema::create('project_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role_in_project')->nullable();
            $table->timestamps();
        });

        // 4. Materials Master
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('category')->default('bahan_dasar'); // bahan_dasar, bahan_jadi, alat_bantu
            $table->foreignId('default_unit_id')->constrained('units');
            $table->decimal('standard_price', 18, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 5. Material Unit Conversions
        Schema::create('material_unit_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->foreignId('from_unit_id')->constrained('units');
            $table->foreignId('to_unit_id')->constrained('units');
            $table->decimal('factor', 18, 6);
            $table->timestamps();
        });

        // 6. RAB Nodes (Hierarchy Level 1-3: Kategori, Sub Kategori, Sub-Sub Kategori)
        Schema::create('rab_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('rab_nodes')->cascadeOnDelete();
            $table->unsignedTinyInteger('level')->default(1); // 1=Kategori, 2=Sub, 3=Sub-Sub
            $table->string('code')->nullable(); // "I", "I.A", "A.1"
            $table->string('name');
            $table->integer('sort_order')->default(0);
            $table->decimal('subtotal_cache', 18, 2)->default(0);
            $table->timestamps();
            $table->index(['project_id', 'parent_id']);
        });

        // 7. RAB Items (Level 4: Item Pekerjaan)
        Schema::create('rab_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rab_node_id')->constrained('rab_nodes')->cascadeOnDelete();
            $table->string('item_no')->nullable(); // "1.0", "2.0"
            $table->text('name');
            $table->decimal('volume', 18, 4)->default(0);
            $table->foreignId('unit_id')->constrained('units');
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('total_price', 18, 2)->default(0);
            $table->boolean('is_composite')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->index('rab_node_id');
        });

        // 8. RAB Item Materials (Level 5: Breakdown Material Dasar / BOM)
        Schema::create('rab_item_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rab_item_id')->constrained('rab_items')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->decimal('volume', 18, 4)->default(0);
            $table->foreignId('unit_id')->constrained('units');
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('total_price', 18, 2)->default(0);
            $table->foreignId('input_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('rab_item_id');
        });

        // 9. Suppliers
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        // 10. Purchase Orders
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->string('po_number')->unique();
            $table->date('po_date');
            $table->string('status')->default('draft'); // draft, sent, partial, completed
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 11. Purchase Order Items
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials');
            $table->decimal('qty_ordered', 18, 4);
            $table->foreignId('unit_id')->constrained('units');
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->timestamps();
        });

        // 12. Delivery Orders (DO - Pengawas Lapangan)
        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->string('do_number');
            $table->date('do_date');
            $table->string('attachment_path'); // foto/scan DO wajib
            $table->foreignId('received_by')->constrained('users'); // Pengawas
            $table->string('status')->default('pending'); // pending, validated, rejected
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 13. Delivery Order Items
        Schema::create('delivery_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_id')->constrained('delivery_orders')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials');
            $table->decimal('qty_received', 18, 4);
            $table->foreignId('unit_id')->constrained('units');
            $table->timestamps();
        });

        // 14. Invoices (Invoice - Purchasing)
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->string('invoice_number');
            $table->date('invoice_date');
            $table->decimal('amount', 18, 2)->default(0);
            $table->string('attachment_path'); // scan invoice wajib
            $table->foreignId('validated_by')->constrained('users'); // Purchasing
            $table->string('status')->default('pending'); // pending, validated, rejected
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 15. Material Realizations (Baseline Monitoring Planned vs Actual)
        Schema::create('material_realizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->decimal('planned_qty', 18, 4)->default(0);
            $table->decimal('actual_qty', 18, 4)->default(0);
            $table->decimal('variance_qty', 18, 4)->default(0);
            $table->decimal('variance_pct', 8, 2)->default(0);
            $table->string('status')->default('normal'); // normal, kekurangan, kelebihan
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();
            $table->unique(['project_id', 'material_id']);
        });

        // 16. Material Variance Validations (Dual Approval: DO + Invoice Wajib)
        Schema::create('material_variance_validations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_realization_id')->constrained('material_realizations')->cascadeOnDelete();
            $table->foreignId('delivery_order_id')->nullable()->constrained('delivery_orders')->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            
            // Pengawas validation
            $table->foreignId('pengawas_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('pengawas_validated_at')->nullable();
            $table->text('pengawas_notes')->nullable();

            // Purchasing validation
            $table->foreignId('purchasing_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('purchasing_validated_at')->nullable();
            $table->text('purchasing_notes')->nullable();

            $table->string('status')->default('pending'); // pending, waiting_pengawas, waiting_purchasing, fully_validated, rejected
            $table->timestamps();
        });

        // 17. Alerts
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('type'); // material_over, material_under, cost_over_budget
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('message');
            $table->string('severity')->default('warning'); // info, warning, critical
            $table->string('status')->default('unread'); // unread, read, resolved
            $table->timestamps();
        });

        // 18. Equipment Categories
        Schema::create('equipment_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Peralatan, Mesin, dll
            $table->foreignId('parent_id')->nullable()->constrained('equipment_categories')->cascadeOnDelete();
            $table->timestamps();
        });

        // 19. Equipment Master (Global Pre-seeded Catalog)
        Schema::create('equipment_master', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_category_id')->constrained('equipment_categories');
            $table->string('code')->unique();
            $table->string('name');
            $table->foreignId('default_unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->text('spec')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 20. Project Equipment (Fast Bulk Input per Project)
        Schema::create('project_equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('equipment_master_id')->constrained('equipment_master')->cascadeOnDelete();
            $table->integer('qty')->default(1);
            $table->string('source')->default('milik_sendiri'); // milik_sendiri, sewa
            $table->string('condition')->default('layak_pakai'); // baru, layak_pakai, perlu_perbaikan
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 21. Cost Realizations (Separated from RAB Items)
        Schema::create('cost_realizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('rab_item_id')->constrained('rab_items')->cascadeOnDelete();
            $table->decimal('budget_amount', 18, 2)->default(0);
            $table->decimal('actual_amount', 18, 2)->default(0);
            $table->decimal('variance_amount', 18, 2)->default(0);
            $table->decimal('variance_pct', 8, 2)->default(0);
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();
            $table->unique(['project_id', 'rab_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_realizations');
        Schema::dropIfExists('project_equipment');
        Schema::dropIfExists('equipment_master');
        Schema::dropIfExists('equipment_categories');
        Schema::dropIfExists('alerts');
        Schema::dropIfExists('material_variance_validations');
        Schema::dropIfExists('material_realizations');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('delivery_order_items');
        Schema::dropIfExists('delivery_orders');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('rab_item_materials');
        Schema::dropIfExists('rab_items');
        Schema::dropIfExists('rab_nodes');
        Schema::dropIfExists('material_unit_conversions');
        Schema::dropIfExists('materials');
        Schema::dropIfExists('project_user');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('units');
    }
};
