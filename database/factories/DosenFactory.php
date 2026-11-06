<?php

namespace Database\Factories;

use App\Models\Dosen;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DosenFactory extends Factory
{
    protected $model = Dosen::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => 'dosen']),
            'nidn' => fake()->unique()->numerify('04########'),
            'nama' => fake()->name(),
            'gelar' => 'M.Kom.',
        ];
    }
}
