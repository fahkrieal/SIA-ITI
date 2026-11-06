<?php

namespace Database\Factories;

use App\Models\KelasKuliah;
use App\Models\Mahasiswa;
use App\Models\PesertaKuliah;
use Illuminate\Database\Eloquent\Factories\Factory;

class PesertaKuliahFactory extends Factory
{
    protected $model = PesertaKuliah::class;

    public function definition(): array
    {
        return [
            'kelas_kuliah_id' => KelasKuliah::factory(),
            'mahasiswa_id' => Mahasiswa::factory(),
        ];
    }
}
