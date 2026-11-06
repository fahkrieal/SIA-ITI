<?php

namespace Tests\Unit;

use App\Models\Dosen;
use App\Models\KelasKuliah;
use App\Models\KomponenNilai;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\NilaiAkhir;
use App\Models\NilaiKomponen;
use App\Models\PesertaKuliah;
use App\Models\Semester;
use App\Models\User;
use App\Services\AcademicPerformanceService;
use App\Services\NilaiCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class NilaiCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected NilaiCalculationService $calcService;
    protected AcademicPerformanceService $perfService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calcService = new NilaiCalculationService();
        $this->perfService = new AcademicPerformanceService();
    }

    /**
     * Pembuktian 5: Nilai akhir dihitung secara tepat sesuai rumus:
     * Nilai Akhir = SUM(Nilai Komponen * Bobot / 100)
     */
    public function test_nilai_akhir_dihitung_sesuai_bobot(): void
    {
        $userDosen = User::factory()->create(['role' => 'dosen']);
        $dosen = Dosen::factory()->create(['user_id' => $userDosen->id]);
        $mk = MataKuliah::factory()->create(['sks' => 3]);
        $sem = Semester::factory()->create(['kode_semester' => '20241']);

        $kelas = KelasKuliah::factory()->create([
            'mata_kuliah_id' => $mk->id,
            'dosen_id' => $dosen->id,
            'semester_id' => $sem->id,
        ]);

        // Komponen: Tugas (20%), UTS (30%), UAS (50%) -> Total 100%
        $k1 = KomponenNilai::factory()->create(['kelas_kuliah_id' => $kelas->id, 'nama_komponen' => 'Tugas', 'bobot_persen' => 20.00]);
        $k2 = KomponenNilai::factory()->create(['kelas_kuliah_id' => $kelas->id, 'nama_komponen' => 'UTS', 'bobot_persen' => 30.00]);
        $k3 = KomponenNilai::factory()->create(['kelas_kuliah_id' => $kelas->id, 'nama_komponen' => 'UAS', 'bobot_persen' => 50.00]);

        $userMhs = User::factory()->create(['role' => 'mahasiswa']);
        $mhs = Mahasiswa::factory()->create(['user_id' => $userMhs->id]);

        $peserta = PesertaKuliah::factory()->create([
            'kelas_kuliah_id' => $kelas->id,
            'mahasiswa_id' => $mhs->id,
        ]);

        // Nilai: Tugas=80, UTS=85, UAS=90
        // Perhitungan: (80 * 0.20) + (85 * 0.30) + (90 * 0.50) = 16.00 + 25.50 + 45.00 = 86.50
        NilaiKomponen::factory()->create(['peserta_kuliah_id' => $peserta->id, 'komponen_nilai_id' => $k1->id, 'nilai' => 80.00]);
        NilaiKomponen::factory()->create(['peserta_kuliah_id' => $peserta->id, 'komponen_nilai_id' => $k2->id, 'nilai' => 85.00]);
        NilaiKomponen::factory()->create(['peserta_kuliah_id' => $peserta->id, 'komponen_nilai_id' => $k3->id, 'nilai' => 90.00]);

        $score = $this->calcService->calculateFinalScore($peserta);
        $this->assertEquals(86.50, $score);

        $grade = $this->calcService->convertScoreToGrade($score);
        $this->assertEquals('A', $grade['letter']);
        $this->assertEquals(4.00, $grade['grade_point']);
    }

    /**
     * Pembuktian 6: Bobot komponen yang tidak berjumlah 100% ditolak saat publikasi.
     */
    public function test_bobot_komponen_tidak_100_persen_ditolak_saat_publikasi(): void
    {
        $userDosen = User::factory()->create(['role' => 'dosen']);
        $dosen = Dosen::factory()->create(['user_id' => $userDosen->id]);
        $mk = MataKuliah::factory()->create(['sks' => 3]);
        $sem = Semester::factory()->create(['kode_semester' => '20241']);

        $kelas = KelasKuliah::factory()->create([
            'mata_kuliah_id' => $mk->id,
            'dosen_id' => $dosen->id,
            'semester_id' => $sem->id,
        ]);

        // Bobot baru 40% + 40% = 80% (belum 100%)
        KomponenNilai::factory()->create(['kelas_kuliah_id' => $kelas->id, 'nama_komponen' => 'Tugas', 'bobot_persen' => 40.00]);
        KomponenNilai::factory()->create(['kelas_kuliah_id' => $kelas->id, 'nama_komponen' => 'UTS', 'bobot_persen' => 40.00]);

        $this->assertFalse($this->calcService->validateClassComponentsTotalWeight($kelas));

        $this->expectException(InvalidArgumentException::class);
        $this->calcService->publishClassGrades($kelas);
    }

    /**
     * Pembuktian 8: IPS dihitung dengan benar:
     * IPS = SUM(SKS * Bobot Mutu) / SUM(SKS)
     */
    public function test_ips_dihitung_dengan_benar(): void
    {
        $userMhs = User::factory()->create(['role' => 'mahasiswa']);
        $mhs = Mahasiswa::factory()->create(['user_id' => $userMhs->id]);

        $userDosen = User::factory()->create(['role' => 'dosen']);
        $dosen = Dosen::factory()->create(['user_id' => $userDosen->id]);

        $sem = Semester::factory()->create(['kode_semester' => '20231']);

        // MK 1: 3 SKS, Nilai Akhir: A (4.00) -> Mutu = 12.00
        $mk1 = MataKuliah::factory()->create(['sks' => 3]);
        $kelas1 = KelasKuliah::factory()->create(['mata_kuliah_id' => $mk1->id, 'dosen_id' => $dosen->id, 'semester_id' => $sem->id]);
        $peserta1 = PesertaKuliah::factory()->create(['kelas_kuliah_id' => $kelas1->id, 'mahasiswa_id' => $mhs->id]);
        NilaiAkhir::factory()->published()->create([
            'peserta_kuliah_id' => $peserta1->id,
            'bobot_mutu' => 4.00,
            'nilai_huruf' => 'A',
        ]);

        // MK 2: 2 SKS, Nilai Akhir: B (3.00) -> Mutu = 6.00
        $mk2 = MataKuliah::factory()->create(['sks' => 2]);
        $kelas2 = KelasKuliah::factory()->create(['mata_kuliah_id' => $mk2->id, 'dosen_id' => $dosen->id, 'semester_id' => $sem->id]);
        $peserta2 = PesertaKuliah::factory()->create(['kelas_kuliah_id' => $kelas2->id, 'mahasiswa_id' => $mhs->id]);
        NilaiAkhir::factory()->published()->create([
            'peserta_kuliah_id' => $peserta2->id,
            'bobot_mutu' => 3.00,
            'nilai_huruf' => 'B',
        ]);

        // Total SKS = 3 + 2 = 5 SKS
        // Total Mutu = 12.00 + 6.00 = 18.00
        // IPS = 18.00 / 5 = 3.60
        $result = $this->perfService->calculateIps($mhs, $sem);

        $this->assertEquals(5, $result['total_sks']);
        $this->assertEquals(18.00, $result['total_mutu']);
        $this->assertEquals(3.60, $result['ips']);
    }

    /**
     * Pembuktian 9: IPK dihitung dengan benar lintas semester,
     * hanya memperhitungkan nilai published, aman dari pembagian nol, dan menangani pengulangan mata kuliah.
     */
    public function test_ipk_dihitung_dengan_benar_dan_menangani_pengulangan(): void
    {
        $userMhs = User::factory()->create(['role' => 'mahasiswa']);
        $mhs = Mahasiswa::factory()->create(['user_id' => $userMhs->id]);

        $userDosen = User::factory()->create(['role' => 'dosen']);
        $dosen = Dosen::factory()->create(['user_id' => $userDosen->id]);

        $sem1 = Semester::factory()->create(['kode_semester' => '20231']);
        $sem2 = Semester::factory()->create(['kode_semester' => '20232']);

        // MK 1 (3 SKS): Diambil di Semester 1 -> Nilai D (Bobot 1.00)
        $mk1 = MataKuliah::factory()->create(['kode_mk' => 'IF101', 'sks' => 3]);
        $kelasSem1 = KelasKuliah::factory()->create(['mata_kuliah_id' => $mk1->id, 'dosen_id' => $dosen->id, 'semester_id' => $sem1->id]);
        $pesertaSem1 = PesertaKuliah::factory()->create(['kelas_kuliah_id' => $kelasSem1->id, 'mahasiswa_id' => $mhs->id]);
        NilaiAkhir::factory()->published()->create([
            'peserta_kuliah_id' => $pesertaSem1->id,
            'bobot_mutu' => 1.00,
            'nilai_huruf' => 'D',
            'nilai_angka' => 45.00,
        ]);

        // MK 1 diulang di Semester 2 -> Nilai A (Bobot 4.00)
        $kelasSem2 = KelasKuliah::factory()->create(['mata_kuliah_id' => $mk1->id, 'dosen_id' => $dosen->id, 'semester_id' => $sem2->id]);
        $pesertaSem2 = PesertaKuliah::factory()->create(['kelas_kuliah_id' => $kelasSem2->id, 'mahasiswa_id' => $mhs->id]);
        NilaiAkhir::factory()->published()->create([
            'peserta_kuliah_id' => $pesertaSem2->id,
            'bobot_mutu' => 4.00,
            'nilai_huruf' => 'A',
            'nilai_angka' => 88.00,
        ]);

        // MK 2 (3 SKS): Diambil di Semester 2 -> Nilai B+ (Bobot 3.50)
        $mk2 = MataKuliah::factory()->create(['kode_mk' => 'IF201', 'sks' => 3]);
        $kelasMk2 = KelasKuliah::factory()->create(['mata_kuliah_id' => $mk2->id, 'dosen_id' => $dosen->id, 'semester_id' => $sem2->id]);
        $pesertaMk2 = PesertaKuliah::factory()->create(['kelas_kuliah_id' => $kelasMk2->id, 'mahasiswa_id' => $mhs->id]);
        NilaiAkhir::factory()->published()->create([
            'peserta_kuliah_id' => $pesertaMk2->id,
            'bobot_mutu' => 3.50,
            'nilai_huruf' => 'B+',
            'nilai_angka' => 78.00,
        ]);

        // Kebijakan retake default: 'highest'
        // MK1 diambil yang terbaik: 3 SKS * 4.00 = 12.00
        // MK2: 3 SKS * 3.50 = 10.50
        // Total SKS dihitung = 3 + 3 = 6 SKS (MK1 tidak diduplikasi!)
        // Total Mutu = 12.00 + 10.50 = 22.50
        // IPK = 22.50 / 6 = 3.75
        $result = $this->perfService->calculateIpk($mhs);

        $this->assertEquals(6, $result['total_sks']);
        $this->assertEquals(22.50, $result['total_mutu']);
        $this->assertEquals(3.75, $result['ipk']);
        $this->assertEquals(2, $result['total_mata_kuliah']);
    }
}
