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
        Schema::table('equipment_master', function (Blueprint $table) {
            $table->decimal('price', 15, 2)->default(0)->after('default_unit_id');
        });

        Schema::table('project_equipment', function (Blueprint $table) {
            $table->decimal('rental_rate', 15, 2)->nullable()->after('qty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipment_master', function (Blueprint $table) {
            $table->dropColumn('price');
        });

        Schema::table('project_equipment', function (Blueprint $table) {
            $table->dropColumn('rental_rate');
        });
    }
};
