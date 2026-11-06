<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MahasiswaNilaiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $mk = $this->kelasKuliah->mataKuliah;
        $semester = $this->kelasKuliah->semester;
        $dosen = $this->kelasKuliah->dosen;
        $na = $this->nilaiAkhir;
        $isPublished = $na && $na->is_published;

        return [
            'peserta_id' => $this->id,
            'kelas_id' => $this->kelasKuliah->id,
            'nama_kelas' => $this->kelasKuliah->nama_kelas,
            'semester' => [
                'id' => $semester->id,
                'kode_semester' => $semester->kode_semester,
                'nama_semester' => $semester->nama_semester,
                'tahun_ajaran' => $semester->tahun_ajaran,
            ],
            'mata_kuliah' => [
                'kode_mk' => $mk->kode_mk,
                'nama_mk' => $mk->nama_mk,
                'sks' => (int) $mk->sks,
            ],
            'dosen' => [
                'nama' => $dosen->nama.($dosen->gelar ? ', '.$dosen->gelar : ''),
            ],
            'status_publikasi' => $isPublished ? 'published' : 'draft',
            'is_published' => (bool) $isPublished,
            'nilai_angka' => $isPublished ? (float) $na->nilai_angka : null,
            'nilai_huruf' => $isPublished ? $na->nilai_huruf : null,
            'bobot_mutu' => $isPublished ? (float) $na->bobot_mutu : null,
        ];
    }
}
