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
        Schema::table('rab_item_materials', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('material_id')
                ->constrained('rab_item_materials')
                ->cascadeOnDelete();
            $table->string('notes')->nullable()->after('total_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rab_item_materials', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'notes']);
        });
    }
};
