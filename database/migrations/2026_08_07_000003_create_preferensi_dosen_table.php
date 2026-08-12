<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preferensi_dosen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dosen_id')->constrained('dosen')->onDelete('cascade');
            $table->foreignId('kelas_dibuka_id')->constrained('kelas_dibuka')->onDelete('cascade');
            $table->string('hari');
            $table->integer('sesi');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preferensi_dosen');
    }
};
