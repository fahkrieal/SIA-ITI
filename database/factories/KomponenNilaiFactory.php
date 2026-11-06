<?php

namespace Database\Factories;

use App\Models\KelasKuliah;
use App\Models\KomponenNilai;
use Illuminate\Database\Eloquent\Factories\Factory;

class KomponenNilaiFactory extends Factory
{
    protected $model = KomponenNilai::class;

    public function definition(): array
    {
        return [
            'kelas_kuliah_id' => KelasKuliah::factory(),
            'nama_komponen' => fake()->unique()->word(),
            'bobot_persen' => 20.00,
        ];
    }
}
