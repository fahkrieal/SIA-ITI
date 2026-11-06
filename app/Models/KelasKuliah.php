<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KelasKuliah extends Model
{
    use HasFactory;

    protected $table = 'kelas_kuliah';

    protected $fillable = [
        'mata_kuliah_id',
        'dosen_id',
        'semester_id',
        'nama_kelas',
        'status_publikasi',
        'tanggal_publikasi',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_publikasi' => 'datetime',
        ];
    }

    public function mataKuliah(): BelongsTo
    {
        return $this->belongsTo(MataKuliah::class, 'mata_kuliah_id');
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'dosen_id');
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }

    public function pesertaKuliah(): HasMany
    {
        return $this->hasMany(PesertaKuliah::class, 'kelas_kuliah_id');
    }

    public function komponenNilai(): HasMany
    {
        return $this->hasMany(KomponenNilai::class, 'kelas_kuliah_id');
    }

    public function isPublished(): bool
    {
        return $this->status_publikasi === 'published';
    }
}
