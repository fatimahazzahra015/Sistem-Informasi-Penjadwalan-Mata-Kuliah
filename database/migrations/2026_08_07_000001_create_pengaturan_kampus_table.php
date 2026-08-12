<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaturan_kampus', function (Blueprint $table) {
            $table->id();
            $table->integer('max_kelas_per_semester')->default(3);
            $table->integer('total_ruangan')->default(15);
            $table->boolean('is_released')->default(false); // Release alokasi kelas ke dosen (Tahap 2)
            $table->boolean('is_schedule_published')->default(false); // Publish jadwal final ke publik & lock dosen dashboard
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan_kampus');
    }
};
