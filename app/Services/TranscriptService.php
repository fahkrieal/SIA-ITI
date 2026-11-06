<?php

namespace App\Services;

use App\Models\Mahasiswa;
use App\Models\PesertaKuliah;

class TranscriptService
{
    public function __construct(
        protected AcademicPerformanceService $performanceService
    ) {}

    /**
     * Ambil data Transkrip Akademik mahasiswa.
     * Mengelompokkan mata kuliah per semester dengan nilai final yang telah dipublikasikan,
     * serta menghitung total SKS kumulatif dan IPK sesuai kebijakan pengulangan mata kuliah.
     */
    public function getTranscript(Mahasiswa $mahasiswa): array
    {
        // 1. Ambil seluruh mata kuliah yang bernilai final sah (published)
        $allPeserta = PesertaKuliah::query()
            ->where('mahasiswa_id', $mahasiswa->id)
            ->whereHas('nilaiAkhir', function ($q) {
                $q->where('is_published', true);
            })
            ->with(['kelasKuliah.mataKuliah', 'kelasKuliah.semester', 'nilaiAkhir'])
            ->get();

        // 2. Kelompokkan per semester
        $groupedBySemester = $allPeserta->groupBy(function ($peserta) {
            return $peserta->kelasKuliah->semester->kode_semester ?? 'UNKNOWN';
        });

        $semestersData = [];

        foreach ($groupedBySemester as $kodeSemester => $pesertaInSemester) {
            $semesterModel = $pesertaInSemester->first()->kelasKuliah->semester;

            $coursesList = $pesertaInSemester->map(function ($peserta) {
                $mk = $peserta->kelasKuliah->mataKuliah;
                $na = $peserta->nilaiAkhir;

                return [
                    'kode_mk' => $mk->kode_mk,
                    'nama_mk' => $mk->nama_mk,
                    'sks' => (int) $mk->sks,
                    'nilai_angka' => (float) $na->nilai_angka,
                    'nilai_huruf' => $na->nilai_huruf,
                    'bobot_mutu' => (float) $na->bobot_mutu,
                    'nilai_mutu' => round(((int) $mk->sks) * ((float) $na->bobot_mutu), 2),
                ];
            })->values();

            $sksSemester = $coursesList->sum('sks');
            $mutuSemester = $coursesList->sum('nilai_mutu');
            $ipsSemester = $sksSemester > 0 ? round($mutuSemester / $sksSemester, 2) : 0.00;

            $semestersData[] = [
                'semester_id' => $semesterModel->id,
                'kode_semester' => $semesterModel->kode_semester,
                'nama_semester' => $semesterModel->nama_semester,
                'tahun_ajaran' => $semesterModel->tahun_ajaran,
                'sks_semester' => $sksSemester,
                'ips_semester' => $ipsSemester,
                'mata_kuliah' => $coursesList,
            ];
        }

        // 3. Hitung kumulatif terpusat melalui AcademicPerformanceService (menerapkan retake policy)
        $cumulative = $this->performanceService->calculateIpk($mahasiswa);

        return [
            'mahasiswa' => [
                'nim' => $mahasiswa->nim,
                'nama' => $mahasiswa->nama,
                'program_studi' => $mahasiswa->program_studi,
                'angkatan' => $mahasiswa->angkatan,
            ],
            'kumulatif' => [
                'total_sks' => $cumulative['total_sks'],
                'total_mutu' => $cumulative['total_mutu'],
                'ipk' => $cumulative['ipk'],
                'total_mata_kuliah_diperhitungkan' => $cumulative['total_mata_kuliah'],
            ],
            'semester' => $semestersData,
        ];
    }
}
