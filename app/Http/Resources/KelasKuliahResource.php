<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KelasKuliahResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $mk = $this->mataKuliah;
        $sem = $this->semester;
        $totalBobot = (float) $this->komponenNilai()->sum('bobot_persen');

        return [
            'id' => $this->id,
            'nama_kelas' => $this->nama_kelas,
            'status_publikasi' => $this->status_publikasi,
            'tanggal_publikasi' => $this->tanggal_publikasi?->toIso8601String(),
            'total_bobot_komponen' => $totalBobot,
            'is_ready_to_publish' => abs($totalBobot - (float) config('academic.required_total_weight', 100)) < 0.01,
            'total_mahasiswa' => $this->peserta_kuliah_count ?? $this->pesertaKuliah()->count(),
            'mata_kuliah' => [
                'id' => $mk->id,
                'kode_mk' => $mk->kode_mk,
                'nama_mk' => $mk->nama_mk,
                'sks' => (int) $mk->sks,
            ],
            'semester' => [
                'id' => $sem->id,
                'kode_semester' => $sem->kode_semester,
                'nama_semester' => $sem->nama_semester,
                'tahun_ajaran' => $sem->tahun_ajaran,
            ],
        ];
    }
}
