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
        Schema::create('nilai_akhir', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peserta_kuliah_id')->unique()->constrained('peserta_kuliah')->cascadeOnDelete();
            $table->decimal('nilai_angka', 5, 2);
            $table->string('nilai_huruf', 5);
            $table->decimal('bobot_mutu', 4, 2);
            $table->boolean('is_published')->default(false);
            $table->timestamp('tanggal_publikasi')->nullable();
            $table->timestamps();

            $table->index('is_published');
            $table->index(['peserta_kuliah_id', 'is_published']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nilai_akhir');
    }
};
