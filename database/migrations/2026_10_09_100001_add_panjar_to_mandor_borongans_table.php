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
        Schema::table('mandor_borongans', function (Blueprint $table) {
            $table->decimal('panjar_amount', 15, 2)->default(0)->after('contract_amount');
        });

        Schema::create('mandor_borongan_panjars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mandor_borongan_id')->constrained('mandor_borongans')->cascadeOnDelete();
            $table->decimal('amount', 15, 2)->default(0);
            $table->date('panjar_date')->nullable();
            $table->string('reference_no', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mandor_borongan_panjars');

        Schema::table('mandor_borongans', function (Blueprint $table) {
            $table->dropColumn('panjar_amount');
        });
    }
};
