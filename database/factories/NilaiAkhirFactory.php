<?php

namespace Database\Factories;

use App\Models\NilaiAkhir;
use App\Models\PesertaKuliah;
use Illuminate\Database\Eloquent\Factories\Factory;

class NilaiAkhirFactory extends Factory
{
    protected $model = NilaiAkhir::class;

    public function definition(): array
    {
        return [
            'peserta_kuliah_id' => PesertaKuliah::factory(),
            'nilai_angka' => 85.00,
            'nilai_huruf' => 'A',
            'bobot_mutu' => 4.00,
            'is_published' => false,
            'tanggal_publikasi' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'is_published' => true,
            'tanggal_publikasi' => now(),
        ]);
    }
}
