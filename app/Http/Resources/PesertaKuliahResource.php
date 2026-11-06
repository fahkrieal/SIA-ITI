<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PesertaKuliahResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $mhs = $this->mahasiswa;
        $na = $this->nilaiAkhir;

        $nilaiKomponenData = $this->nilaiKomponen->map(function ($nk) {
            return [
                'komponen_id' => $nk->komponen_nilai_id,
                'nama_komponen' => $nk->komponenNilai->nama_komponen ?? null,
                'bobot_persen' => (float) ($nk->komponenNilai->bobot_persen ?? 0),
                'nilai' => (float) $nk->nilai,
            ];
        });

        return [
            'peserta_id' => $this->id,
            'mahasiswa' => [
                'id' => $mhs->id,
                'nim' => $mhs->nim,
                'nama' => $mhs->nama,
                'program_studi' => $mhs->program_studi,
                'angkatan' => $mhs->angkatan,
            ],
            'nilai_komponen' => $nilaiKomponenData,
            'nilai_akhir' => $na ? [
                'nilai_angka' => (float) $na->nilai_angka,
                'nilai_huruf' => $na->nilai_huruf,
                'bobot_mutu' => (float) $na->bobot_mutu,
                'is_published' => (bool) $na->is_published,
                'tanggal_publikasi' => $na->tanggal_publikasi?->toIso8601String(),
            ] : null,
        ];
    }
}
