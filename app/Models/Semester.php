<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Semester extends Model
{
    use HasFactory;

    protected $table = 'semester';

    protected $fillable = [
        'kode_semester',
        'nama_semester',
        'tahun_ajaran',
        'tipe',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function kelasKuliah(): HasMany
    {
        return $this->hasMany(KelasKuliah::class, 'semester_id');
    }
}
