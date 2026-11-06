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
        Schema::create('kelas_kuliah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mata_kuliah_id')->constrained('mata_kuliah')->cascadeOnDelete();
            $table->foreignId('dosen_id')->constrained('dosen')->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained('semester')->cascadeOnDelete();
            $table->string('nama_kelas', 20);
            $table->enum('status_publikasi', ['draft', 'published'])->default('draft');
            $table->timestamp('tanggal_publikasi')->nullable();
            $table->timestamps();

            $table->unique(['mata_kuliah_id', 'semester_id', 'nama_kelas'], 'unique_kelas_per_semester');
            $table->index(['dosen_id', 'semester_id']);
            $table->index('status_publikasi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kelas_kuliah');
    }
};
