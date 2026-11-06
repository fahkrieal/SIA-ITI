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
        Schema::create('peserta_kuliah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_kuliah_id')->constrained('kelas_kuliah')->cascadeOnDelete();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['kelas_kuliah_id', 'mahasiswa_id'], 'unique_peserta_kelas');
            $table->index('mahasiswa_id');
            $table->index('kelas_kuliah_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peserta_kuliah');
    }
};
