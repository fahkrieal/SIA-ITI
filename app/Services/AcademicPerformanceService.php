<?php

namespace App\Services;

use App\Models\Mahasiswa;
use App\Models\PesertaKuliah;
use App\Models\Semester;
use Illuminate\Support\Collection;

class AcademicPerformanceService
{
    /**
     * Hitung Indeks Prestasi Semester (IPS).
     *
     * Rumus: IPS = SUM(SKS * Bobot Mutu) / SUM(SKS)
     * Hanya memperhitungkan mata kuliah pada semester terkait yang telah dipublikasikan.
     *
     * @return array{total_sks: int, total_mutu: float, ips: float}
     */
    public function calculateIps(Mahasiswa $mahasiswa, Semester $semester): array
    {
        $pesertaList = PesertaKuliah::query()
            ->where('mahasiswa_id', $mahasiswa->id)
            ->whereHas('kelasKuliah', function ($q) use ($semester) {
                $q->where('semester_id', $semester->id);
            })
            ->whereHas('nilaiAkhir', function ($q) {
                $q->where('is_published', true);
            })
            ->with(['kelasKuliah.mataKuliah', 'nilaiAkhir'])
            ->get();

        $totalSks = 0;
        $totalMutu = 0.0;

        foreach ($pesertaList as $peserta) {
            $sks = (int) $peserta->kelasKuliah->mataKuliah->sks;
            $bobotMutu = (float) $peserta->nilaiAkhir->bobot_mutu;

            $totalSks += $sks;
            $totalMutu += ($sks * $bobotMutu);
        }

        $ips = $totalSks > 0 ? round($totalMutu / $totalSks, 2) : 0.00;

        return [
            'total_sks' => $totalSks,
            'total_mutu' => round($totalMutu, 2),
            'ips' => $ips,
        ];
    }

    /**
     * Ambil daftar mata kuliah yang diperhitungkan ke dalam IPK kumulatif
     * dengan menerapkan kebijakan pengulangan mata kuliah (retake policy).
     */
    public function getAccountedCourses(Mahasiswa $mahasiswa): Collection
    {
        // Ambil seluruh peserta kuliah yang nilainya telah dipublikasikan
        $allPeserta = PesertaKuliah::query()
            ->where('mahasiswa_id', $mahasiswa->id)
            ->whereHas('nilaiAkhir', function ($q) {
                $q->where('is_published', true);
            })
            ->with(['kelasKuliah.mataKuliah', 'kelasKuliah.semester', 'nilaiAkhir'])
            ->get();

        $policy = config('academic.retake_policy', 'highest');

        // Kelompokkan berdasarkan ID mata kuliah untuk menangani pengulangan
        $grouped = $allPeserta->groupBy(function ($peserta) {
            return $peserta->kelasKuliah->mata_kuliah_id;
        });

        $accounted = collect();

        foreach ($grouped as $mataKuliahId => $attempts) {
            if ($attempts->count() === 1) {
                $accounted->push($attempts->first());
                continue;
            }

            // Jika ada pengulangan mata kuliah
            if ($policy === 'latest') {
                // Ambil nilai dari semester paling baru (berdasarkan kode semester atau created_at)
                $selected = $attempts->sortByDesc(function ($attempt) {
                    return $attempt->kelasKuliah->semester->kode_semester ?? $attempt->id;
                })->first();
            } else {
                // Default: 'highest' -> ambil bobot mutu tertinggi, jika sama ambil yang nilai angkanya lebih besar
                $selected = $attempts->sort(function ($a, $b) {
                    $mutuCompare = $b->nilaiAkhir->bobot_mutu <=> $a->nilaiAkhir->bobot_mutu;
                    if ($mutuCompare !== 0) {
                        return $mutuCompare;
                    }
                    return $b->nilaiAkhir->nilai_angka <=> $a->nilaiAkhir->nilai_angka;
                })->first();
            }

            $accounted->push($selected);
        }

        return $accounted;
    }

    /**
     * Hitung Indeks Prestasi Kumulatif (IPK).
     *
     * Rumus: IPK = SUM(SKS * Bobot Mutu seluruh mata kuliah yang diperhitungkan)
     *              / SUM(SKS seluruh mata kuliah yang diperhitungkan)
     *
     * @return array{total_sks: int, total_mutu: float, ipk: float, total_mata_kuliah: int}
     */
    public function calculateIpk(Mahasiswa $mahasiswa): array
    {
        $courses = $this->getAccountedCourses($mahasiswa);

        $totalSks = 0;
        $totalMutu = 0.0;

        foreach ($courses as $peserta) {
            $sks = (int) $peserta->kelasKuliah->mataKuliah->sks;
            $bobotMutu = (float) $peserta->nilaiAkhir->bobot_mutu;

            $totalSks += $sks;
            $totalMutu += ($sks * $bobotMutu);
        }

        $ipk = $totalSks > 0 ? round($totalMutu / $totalSks, 2) : 0.00;

        return [
            'total_sks' => $totalSks,
            'total_mutu' => round($totalMutu, 2),
            'ipk' => $ipk,
            'total_mata_kuliah' => $courses->count(),
        ];
    }
}
