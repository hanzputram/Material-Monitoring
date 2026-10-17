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
        if (Schema::hasTable('purchase_orders') && ! Schema::hasColumn('purchase_orders', 'delivery_order_number')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->string('delivery_order_number', 100)->nullable()->after('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('purchase_orders') && Schema::hasColumn('purchase_orders', 'delivery_order_number')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->dropColumn('delivery_order_number');
            });
        }
    }
};
