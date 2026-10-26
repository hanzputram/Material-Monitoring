<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'down_payment_id')) {
                $table->dropForeign(['down_payment_id']);
                $table->dropColumn('down_payment_id');
            }
            if (Schema::hasColumn('invoices', 'down_payment_amount')) {
                $table->dropColumn('down_payment_amount');
            }
        });

        Schema::dropIfExists('purchase_down_payments');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('purchase_down_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->string('dp_number');
            $table->date('dp_date');
            $table->decimal('amount', 18, 2);
            $table->string('payment_method')->default('Transfer Bank');
            $table->string('bank_name')->nullable();
            $table->string('status')->default('paid');
            $table->string('attachment_path')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('down_payment_id')->nullable()->constrained('purchase_down_payments')->nullOnDelete();
            $table->decimal('down_payment_amount', 18, 2)->default(0);
        });
    }
};
