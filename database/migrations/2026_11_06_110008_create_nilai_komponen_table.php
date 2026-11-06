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
        Schema::create('nilai_komponen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peserta_kuliah_id')->constrained('peserta_kuliah')->cascadeOnDelete();
            $table->foreignId('komponen_nilai_id')->constrained('komponen_nilai')->cascadeOnDelete();
            $table->decimal('nilai', 5, 2);
            $table->timestamps();

            $table->unique(['peserta_kuliah_id', 'komponen_nilai_id'], 'unique_peserta_komponen');
            $table->index('peserta_kuliah_id');
            $table->index('komponen_nilai_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nilai_komponen');
    }
};
