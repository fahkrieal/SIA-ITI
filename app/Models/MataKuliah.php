<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MataKuliah extends Model
{
    use HasFactory;

    protected $table = 'mata_kuliah';

    protected $fillable = [
        'kode_mk',
        'nama_mk',
        'sks',
    ];

    protected function casts(): array
    {
        return [
            'sks' => 'integer',
        ];
    }

    public function kelasKuliah(): HasMany
    {
        return $this->hasMany(KelasKuliah::class, 'mata_kuliah_id');
    }
}
