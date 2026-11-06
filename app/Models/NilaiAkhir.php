<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NilaiAkhir extends Model
{
    use HasFactory;

    protected $table = 'nilai_akhir';

    protected $fillable = [
        'peserta_kuliah_id',
        'nilai_angka',
        'nilai_huruf',
        'bobot_mutu',
        'is_published',
        'tanggal_publikasi',
    ];

    protected function casts(): array
    {
        return [
            'nilai_angka' => 'decimal:2',
            'bobot_mutu' => 'decimal:2',
            'is_published' => 'boolean',
            'tanggal_publikasi' => 'datetime',
        ];
    }

    public function pesertaKuliah(): BelongsTo
    {
        return $this->belongsTo(PesertaKuliah::class, 'peserta_kuliah_id');
    }
}
