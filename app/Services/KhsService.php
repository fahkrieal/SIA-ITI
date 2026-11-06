<?php

namespace App\Services;

use App\Models\Mahasiswa;
use App\Models\PesertaKuliah;
use App\Models\Semester;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class KhsService
{
    public function __construct(
        protected AcademicPerformanceService $performanceService
    ) {}

    /**
     * Ambil data Kartu Hasil Studi (KHS) mahasiswa berdasarkan semester.
     *
     * @param  int|string  $semesterIdentifier ID atau kode_semester (contoh: '20231')
     * @return array
     *
     * @throws ModelNotFoundException Jika semester tidak ditemukan
     */
    public function getKhs(Mahasiswa $mahasiswa, int|string $semesterIdentifier): array
    {
        // Cari semester berdasarkan ID atau kode_semester
        $semester = Semester::query()
            ->where('id', $semesterIdentifier)
            ->orWhere('kode_semester', (string) $semesterIdentifier)
            ->first();

        if (! $semester) {
            throw (new ModelNotFoundException)->setModel(Semester::class, [$semesterIdentifier]);
        }

        // Ambil mata kuliah yang diambil mahasiswa di semester tersebut
        $pesertaList = PesertaKuliah::query()
            ->where('mahasiswa_id', $mahasiswa->id)
            ->whereHas('kelasKuliah', function ($q) use ($semester) {
                $q->where('semester_id', $semester->id);
            })
            ->with(['kelasKuliah.mataKuliah', 'kelasKuliah.dosen', 'nilaiAkhir'])
            ->get();

        $daftarNilai = $pesertaList->map(function ($peserta) {
            $mk = $peserta->kelasKuliah->mataKuliah;
            $nilaiAkhir = $peserta->nilaiAkhir;
            $isPublished = $nilaiAkhir && $nilaiAkhir->is_published;

            return [
                'peserta_id' => $peserta->id,
                'kelas_id' => $peserta->kelasKuliah->id,
                'nama_kelas' => $peserta->kelasKuliah->nama_kelas,
                'kode_mk' => $mk->kode_mk,
                'nama_mk' => $mk->nama_mk,
                'sks' => (int) $mk->sks,
                'dosen_pengampu' => $peserta->kelasKuliah->dosen->nama.($peserta->kelasKuliah->dosen->gelar ? ', '.$peserta->kelasKuliah->dosen->gelar : ''),
                'status_publikasi' => $isPublished ? 'published' : 'draft',
                'is_published' => (bool) $isPublished,
                // Nilai yang belum dipublikasikan tidak boleh ditampilkan sebagai nilai final
                'nilai_angka' => $isPublished ? (float) $nilaiAkhir->nilai_angka : null,
                'nilai_huruf' => $isPublished ? $nilaiAkhir->nilai_huruf : null,
                'bobot_mutu' => $isPublished ? (float) $nilaiAkhir->bobot_mutu : null,
            ];
        });

        // Hitung total SKS yang diambil
        $totalSksDiambil = $pesertaList->sum(fn ($p) => (int) $p->kelasKuliah->mataKuliah->sks);

        // Hitung IPS dan total SKS yang telah dipublikasikan
        $academicSummary = $this->performanceService->calculateIps($mahasiswa, $semester);

        return [
            'mahasiswa' => [
                'nim' => $mahasiswa->nim,
                'nama' => $mahasiswa->nama,
                'program_studi' => $mahasiswa->program_studi,
                'angkatan' => $mahasiswa->angkatan,
            ],
            'semester' => [
                'id' => $semester->id,
                'kode_semester' => $semester->kode_semester,
                'nama_semester' => $semester->nama_semester,
                'tahun_ajaran' => $semester->tahun_ajaran,
            ],
            'ringkasan' => [
                'total_sks_diambil' => $totalSksDiambil,
                'total_sks_dinilai' => $academicSummary['total_sks'],
                'total_bobot_mutu' => $academicSummary['total_mutu'],
                'ips' => $academicSummary['ips'],
            ],
            'mata_kuliah' => $daftarNilai,
        ];
    }
}
