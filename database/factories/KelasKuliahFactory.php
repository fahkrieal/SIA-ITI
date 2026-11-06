<?php

namespace Database\Factories;

use App\Models\Dosen;
use App\Models\KelasKuliah;
use App\Models\MataKuliah;
use App\Models\Semester;
use Illuminate\Database\Eloquent\Factories\Factory;

class KelasKuliahFactory extends Factory
{
    protected $model = KelasKuliah::class;

    public function definition(): array
    {
        return [
            'mata_kuliah_id' => MataKuliah::factory(),
            'dosen_id' => Dosen::factory(),
            'semester_id' => Semester::factory(),
            'nama_kelas' => fake()->randomElement(['A', 'B', 'C']),
            'status_publikasi' => 'draft',
            'tanggal_publikasi' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status_publikasi' => 'published',
            'tanggal_publikasi' => now(),
        ]);
    }
}
