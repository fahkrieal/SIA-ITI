<?php

namespace Database\Factories;

use App\Models\KomponenNilai;
use App\Models\NilaiKomponen;
use App\Models\PesertaKuliah;
use Illuminate\Database\Eloquent\Factories\Factory;

class NilaiKomponenFactory extends Factory
{
    protected $model = NilaiKomponen::class;

    public function definition(): array
    {
        return [
            'peserta_kuliah_id' => PesertaKuliah::factory(),
            'komponen_nilai_id' => KomponenNilai::factory(),
            'nilai' => fake()->randomFloat(2, 60, 95),
        ];
    }
}
