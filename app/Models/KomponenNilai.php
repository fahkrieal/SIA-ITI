<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KomponenNilai extends Model
{
    use HasFactory;

    protected $table = 'komponen_nilai';

    protected $fillable = [
        'kelas_kuliah_id',
        'nama_komponen',
        'bobot_persen',
    ];

    protected function casts(): array
    {
        return [
            'bobot_persen' => 'decimal:2',
        ];
    }

    public function kelasKuliah(): BelongsTo
    {
        return $this->belongsTo(KelasKuliah::class, 'kelas_kuliah_id');
    }

    public function nilaiKomponen(): HasMany
    {
        return $this->hasMany(NilaiKomponen::class, 'komponen_nilai_id');
    }
}
