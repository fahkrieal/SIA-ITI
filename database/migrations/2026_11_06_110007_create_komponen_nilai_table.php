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
        Schema::create('komponen_nilai', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_kuliah_id')->constrained('kelas_kuliah')->cascadeOnDelete();
            $table->string('nama_komponen', 50);
            $table->decimal('bobot_persen', 5, 2);
            $table->timestamps();

            $table->unique(['kelas_kuliah_id', 'nama_komponen'], 'unique_komponen_kelas');
            $table->index('kelas_kuliah_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('komponen_nilai');
    }
};
