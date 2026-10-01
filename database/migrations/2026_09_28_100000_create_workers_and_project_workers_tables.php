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
        // 1. Master Workers / Craftsmen (Katalog Master Tenaga Kerja / Tukang)
        Schema::create('workers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->string('trade', 100); // Mandor, Tukang Batu, Tukang Besi, Tukang Kayu, Tukang Cat, Tukang Listrik/ME, Tukang Plafon/Gypsum, Tukang Las, Pekerja/Kenek
            $table->string('phone', 50)->nullable();
            $table->string('nik', 50)->nullable();
            $table->decimal('daily_rate', 15, 2)->default(0); // Tarif upah standar per hari
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Project Workers (Alokasi & Penugasan Pekerja ke Proyek Terkait)
        Schema::create('project_workers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('worker_id')->constrained('workers')->cascadeOnDelete();
            $table->string('assigned_trade', 100)->nullable(); // Posisi/keahlian khusus pada proyek ini
            $table->decimal('daily_wage', 15, 2)->default(0); // Nominal upah harian yang disepakati untuk proyek ini
            $table->string('status', 50)->default('active'); // active, standby, completed
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('notes')->nullable(); // Zona/Lantai kerja atau catatan penugasan
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'worker_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_workers');
        Schema::dropIfExists('workers');
    }
};
