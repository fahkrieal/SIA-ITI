<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NilaiKomponen extends Model
{
    use HasFactory;

    protected $table = 'nilai_komponen';

    protected $fillable = [
        'peserta_kuliah_id',
        'komponen_nilai_id',
        'nilai',
    ];

    protected function casts(): array
    {
        return [
            'nilai' => 'decimal:2',
        ];
    }

    public function pesertaKuliah(): BelongsTo
    {
        return $this->belongsTo(PesertaKuliah::class, 'peserta_kuliah_id');
    }

    public function komponenNilai(): BelongsTo
    {
        return $this->belongsTo(KomponenNilai::class, 'komponen_nilai_id');
    }
}
