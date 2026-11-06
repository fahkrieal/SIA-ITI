<?php

namespace Database\Factories;

use App\Models\MataKuliah;
use Illuminate\Database\Eloquent\Factories\Factory;

class MataKuliahFactory extends Factory
{
    protected $model = MataKuliah::class;

    public function definition(): array
    {
        return [
            'kode_mk' => strtoupper(fake()->unique()->bothify('IF###')),
            'nama_mk' => fake()->words(3, true),
            'sks' => fake()->numberBetween(2, 4),
        ];
    }
}
