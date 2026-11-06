<?php

namespace App\Services;

use App\Models\KelasKuliah;
use App\Models\NilaiAkhir;
use App\Models\PesertaKuliah;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class NilaiCalculationService
{
    /**
     * Hitung nilai akhir angka berdasarkan akumulasi komponen:
     * Nilai Akhir = SUM(Nilai Komponen * (Bobot Komponen / 100))
     */
    public function calculateFinalScore(PesertaKuliah $peserta): float
    {
        $nilaiKomponens = $peserta->nilaiKomponen()->with('komponenNilai')->get();

        $totalScore = 0.0;

        foreach ($nilaiKomponens as $nk) {
            $bobot = (float) $nk->komponenNilai->bobot_persen;
            $nilai = (float) $nk->nilai;

            $totalScore += $nilai * ($bobot / 100.0);
        }

        return $totalScore;
    }

    /**
     * Konversi nilai angka ke nilai huruf dan bobot mutu berdasarkan konfigurasi akademik.
     *
     * @return array{letter: string, grade_point: float}
     */
    public function convertScoreToGrade(float $score): array
    {
        $scales = config('academic.grade_scale', []);

        foreach ($scales as $scale) {
            $min = (float) $scale['min'];
            $max = (float) $scale['max'];

            // Bandingkan dengan toleransi presisi floating-point
            if ($score >= ($min - 0.0001) && $score <= ($max + 0.0001)) {
                return [
                    'letter' => $scale['letter'],
                    'grade_point' => (float) $scale['grade_point'],
                ];
            }
        }

        // Fallback default jika tidak ada skala yang cocok
        return [
            'letter' => 'E',
            'grade_point' => 0.00,
        ];
    }

    /**
     * Validasi apakah total bobot komponen dalam satu kelas berjumlah 100%.
     */
    public function validateClassComponentsTotalWeight(KelasKuliah $kelas): bool
    {
        $totalWeight = (float) $kelas->komponenNilai()->sum('bobot_persen');
        $requiredWeight = (float) config('academic.required_total_weight', 100.00);

        // Toleransi perbedaan floating-point 0.01%
        return abs($totalWeight - $requiredWeight) < 0.01;
    }

    /**
     * Update atau simpan nilai akhir satu peserta kuliah.
     */
    public function updateStudentFinalScore(PesertaKuliah $peserta, bool $isPublished = false): NilaiAkhir
    {
        $numericScore = $this->calculateFinalScore($peserta);
        $grade = $this->convertScoreToGrade($numericScore);

        return NilaiAkhir::updateOrCreate(
            ['peserta_kuliah_id' => $peserta->id],
            [
                'nilai_angka' => round($numericScore, 2),
                'nilai_huruf' => $grade['letter'],
                'bobot_mutu' => $grade['grade_point'],
                'is_published' => $isPublished,
                'tanggal_publikasi' => $isPublished ? now() : null,
            ]
        );
    }

    /**
     * Publikasi nilai kelas:
     * - Memvalidasi total bobot komponen == 100%
     * - Menghitung nilai akhir seluruh peserta
     * - Mengubah status publikasi kelas menjadi 'published'
     * - Menandai nilai akhir setiap mahasiswa sebagai is_published = true
     */
    public function publishClassGrades(KelasKuliah $kelas): KelasKuliah
    {
        if (! $this->validateClassComponentsTotalWeight($kelas)) {
            $currentTotal = (float) $kelas->komponenNilai()->sum('bobot_persen');
            throw new InvalidArgumentException(
                "Total bobot komponen belum mencapai 100% (saat ini: {$currentTotal}%). Nilai tidak dapat dipublikasikan."
            );
        }

        return DB::transaction(function () use ($kelas) {
            $now = now();

            // 1. Update status publikasi kelas
            $kelas->update([
                'status_publikasi' => 'published',
                'tanggal_publikasi' => $now,
            ]);

            // 2. Hitung dan publikasikan nilai akhir seluruh mahasiswa di kelas
            $pesertas = $kelas->pesertaKuliah()->with('nilaiKomponen.komponenNilai')->get();

            foreach ($pesertas as $peserta) {
                $numericScore = $this->calculateFinalScore($peserta);
                $grade = $this->convertScoreToGrade($numericScore);

                NilaiAkhir::updateOrCreate(
                    ['peserta_kuliah_id' => $peserta->id],
                    [
                        'nilai_angka' => round($numericScore, 2),
                        'nilai_huruf' => $grade['letter'],
                        'bobot_mutu' => $grade['grade_point'],
                        'is_published' => true,
                        'tanggal_publikasi' => $now,
                    ]
                );
            }

            return $kelas->fresh(['pesertaKuliah.nilaiAkhir']);
        });
    }
}
