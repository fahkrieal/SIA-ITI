<?php

namespace Database\Factories;

use App\Models\Semester;
use Illuminate\Database\Eloquent\Factories\Factory;

class SemesterFactory extends Factory
{
    protected $model = Semester::class;

    public function definition(): array
    {
        $year = fake()->numberBetween(2022, 2026);
        $type = fake()->randomElement(['ganjil', 'genap']);
        $code = $year.($type === 'ganjil' ? '1' : '2');

        return [
            'kode_semester' => $code,
            'nama_semester' => ucfirst($type).' '.$year.'/'.($year + 1),
            'tahun_ajaran' => $year.'/'.($year + 1),
            'tipe' => $type,
            'is_active' => false,
        ];
    }
}
