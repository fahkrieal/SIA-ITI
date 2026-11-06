<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PesertaKuliah extends Model
{
    use HasFactory;

    protected $table = 'peserta_kuliah';

    protected $fillable = [
        'kelas_kuliah_id',
        'mahasiswa_id',
    ];

    public function kelasKuliah(): BelongsTo
    {
        return $this->belongsTo(KelasKuliah::class, 'kelas_kuliah_id');
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class, 'mahasiswa_id');
    }

    public function nilaiKomponen(): HasMany
    {
        return $this->hasMany(NilaiKomponen::class, 'peserta_kuliah_id');
    }

    public function nilaiAkhir(): HasOne
    {
        return $this->hasOne(NilaiAkhir::class, 'peserta_kuliah_id');
    }
}
