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
        // 1. Uang Muka Pembelian (Purchase Down Payment)
        Schema::create('purchase_down_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->string('dp_number');
            $table->date('dp_date');
            $table->decimal('amount', 18, 2);
            $table->string('payment_method')->default('Transfer Bank'); // Transfer Bank, Kas/Tunai, Cek/Giro
            $table->string('bank_name')->nullable();
            $table->string('status')->default('paid'); // paid, applied
            $table->string('attachment_path')->nullable(); // bukti bayar / kuitansi
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 2. Modifikasi Invoices untuk mencatat sisa bayar, potongan DP, dan status pelunasan
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('down_payment_id')->nullable()->constrained('purchase_down_payments')->nullOnDelete();
            $table->decimal('down_payment_amount', 18, 2)->default(0);
            $table->decimal('paid_amount', 18, 2)->default(0);
            $table->string('payment_status')->default('unpaid'); // unpaid, partial, paid
            $table->date('due_date')->nullable();
        });

        // 3. Pembayaran Pembelian (Purchase Payment)
        Schema::create('purchase_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->string('payment_number');
            $table->date('payment_date');
            $table->string('payment_method')->default('Transfer Bank');
            $table->string('bank_name')->nullable();
            $table->decimal('total_amount', 18, 2);
            $table->string('attachment_path')->nullable(); // bukti transfer
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 4. Item Rincian Faktur yang Dibayar dalam Pembayaran Pembelian
        Schema::create('purchase_payment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_payment_id')->constrained('purchase_payments')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->decimal('amount_paid', 18, 2);
            $table->timestamps();
        });

        // 5. Retur Pembelian (Purchase Return)
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('delivery_order_id')->nullable()->constrained('delivery_orders')->nullOnDelete();
            $table->string('return_number');
            $table->date('return_date');
            $table->string('compensation_type')->default('potong_tagihan'); // potong_tagihan, ganti_barang, pengembalian_dana
            $table->string('status')->default('completed'); // draft, completed
            $table->string('attachment_path')->nullable(); // bukti surat jalan retur
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 6. Item Rincian Material yang Diretur
        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_return_id')->constrained('purchase_returns')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->decimal('qty_returned', 18, 4);
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('total_price', 18, 2)->default(0);
            $table->string('reason')->nullable(); // Cacat/Rusak, Tidak Sesuai Spesifikasi, Kuantiti Berlebih, Lainnya
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
        Schema::dropIfExists('purchase_returns');
        Schema::dropIfExists('purchase_payment_items');
        Schema::dropIfExists('purchase_payments');

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['down_payment_id']);
            $table->dropColumn(['down_payment_id', 'down_payment_amount', 'paid_amount', 'payment_status', 'due_date']);
        });

        Schema::dropIfExists('purchase_down_payments');
    }
};
