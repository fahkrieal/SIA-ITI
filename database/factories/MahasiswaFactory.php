<?php

namespace Database\Factories;

use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MahasiswaFactory extends Factory
{
    protected $model = Mahasiswa::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => 'mahasiswa']),
            'nim' => fake()->unique()->numerify('2024######'),
            'nama' => fake()->name(),
            'program_studi' => 'Teknik Informatika',
            'angkatan' => 2024,
        ];
    }
}
