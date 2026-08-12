<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas_dibuka', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semester_id')->nullable()->constrained('semester')->onDelete('cascade');
            $table->foreignId('mata_kuliah_id')->constrained('mata_kuliah')->onDelete('cascade');
            $table->foreignId('dosen_id')->nullable()->constrained('dosen')->onDelete('set null');
            $table->string('nama_kelas');
            $table->string('status')->default('open');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas_dibuka');
    }
};
