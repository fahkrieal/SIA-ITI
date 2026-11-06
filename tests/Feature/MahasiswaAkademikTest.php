<?php

namespace Tests\Feature;

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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MahasiswaAkademikTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Pembuktian 10: KHS hanya menampilkan nilai yang dipublikasikan.
     * Nilai berstatus draft tidak ditampilkan sebagai nilai final (null) dan tidak dihitung ke IPS.
     */
    public function test_khs_hanya_menampilkan_nilai_yang_dipublikasikan(): void
    {
        $userMhs = User::factory()->create(['role' => 'mahasiswa']);
        $mhs = Mahasiswa::factory()->create(['user_id' => $userMhs->id]);

        $userDosen = User::factory()->create(['role' => 'dosen']);
        $dosen = Dosen::factory()->create(['user_id' => $userDosen->id]);

        $sem = Semester::factory()->create(['kode_semester' => '20241']);

        // MK 1 (3 SKS): Sudah dipublikasikan -> A (4.00) -> Mutu 12.00
        $mk1 = MataKuliah::factory()->create(['kode_mk' => 'IF101', 'sks' => 3]);
        $kelas1 = KelasKuliah::factory()->create([
            'mata_kuliah_id' => $mk1->id,
            'dosen_id' => $dosen->id,
            'semester_id' => $sem->id,
            'status_publikasi' => 'published',
        ]);
        $peserta1 = PesertaKuliah::factory()->create(['kelas_kuliah_id' => $kelas1->id, 'mahasiswa_id' => $mhs->id]);
        NilaiAkhir::factory()->published()->create([
            'peserta_kuliah_id' => $peserta1->id,
            'nilai_angka' => 88.00,
            'nilai_huruf' => 'A',
            'bobot_mutu' => 4.00,
        ]);

        // MK 2 (3 SKS): Masih DRAFT (Belum dipublikasikan) -> Tidak boleh masuk IPS
        $mk2 = MataKuliah::factory()->create(['kode_mk' => 'IF102', 'sks' => 3]);
        $kelas2 = KelasKuliah::factory()->create([
            'mata_kuliah_id' => $mk2->id,
            'dosen_id' => $dosen->id,
            'semester_id' => $sem->id,
            'status_publikasi' => 'draft',
        ]);
        $peserta2 = PesertaKuliah::factory()->create(['kelas_kuliah_id' => $kelas2->id, 'mahasiswa_id' => $mhs->id]);
        NilaiAkhir::factory()->create([
            'peserta_kuliah_id' => $peserta2->id,
            'nilai_angka' => 60.00,
            'nilai_huruf' => 'C+',
            'bobot_mutu' => 2.25,
            'is_published' => false,
        ]);

        Sanctum::actingAs($userMhs);

        $response = $this->getJson("/api/mahasiswa/khs/{$sem->kode_semester}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'ringkasan' => [
                        'total_sks_diambil' => 6,
                        'total_sks_dinilai' => 3, // Hanya MK1
                        'ips' => 4.00, // 12.00 / 3 = 4.00
                    ],
                ],
            ]);

        $mataKuliah = $response->json('data.mata_kuliah');

        // MK 1 (Published) tampil nilai lengkap
        $this->assertEquals(88.00, $mataKuliah[0]['nilai_angka']);
        $this->assertEquals('A', $mataKuliah[0]['nilai_huruf']);
        $this->assertEquals(4.00, $mataKuliah[0]['bobot_mutu']);
        $this->assertTrue($mataKuliah[0]['is_published']);

        // MK 2 (Draft) nilai disembunyikan (null)
        $this->assertNull($mataKuliah[1]['nilai_angka']);
        $this->assertNull($mataKuliah[1]['nilai_huruf']);
        $this->assertNull($mataKuliah[1]['bobot_mutu']);
        $this->assertFalse($mataKuliah[1]['is_published']);
    }

    /**
     * Pembuktian 11: Transkrip mengambil seluruh data nilai sah lintas semester.
     */
    public function test_transkrip_mengambil_data_lintas_semester(): void
    {
        $userMhs = User::factory()->create(['role' => 'mahasiswa']);
        $mhs = Mahasiswa::factory()->create(['user_id' => $userMhs->id]);

        $userDosen = User::factory()->create(['role' => 'dosen']);
        $dosen = Dosen::factory()->create(['user_id' => $userDosen->id]);

        $sem1 = Semester::factory()->create(['kode_semester' => '20231', 'nama_semester' => 'Ganjil 2023/2024']);
        $sem2 = Semester::factory()->create(['kode_semester' => '20232', 'nama_semester' => 'Genap 2023/2024']);

        // Semester 1: MK1 (3 SKS, A = 4.00 -> Mutu 12.00)
        $mk1 = MataKuliah::factory()->create(['kode_mk' => 'IF101', 'nama_mk' => 'Pemrograman Dasar', 'sks' => 3]);
        $k1 = KelasKuliah::factory()->create(['mata_kuliah_id' => $mk1->id, 'dosen_id' => $dosen->id, 'semester_id' => $sem1->id]);
        $p1 = PesertaKuliah::factory()->create(['kelas_kuliah_id' => $k1->id, 'mahasiswa_id' => $mhs->id]);
        NilaiAkhir::factory()->published()->create(['peserta_kuliah_id' => $p1->id, 'nilai_angka' => 88.00, 'nilai_huruf' => 'A', 'bobot_mutu' => 4.00]);

        // Semester 2: MK2 (3 SKS, B = 3.00 -> Mutu 9.00)
        $mk2 = MataKuliah::factory()->create(['kode_mk' => 'IF201', 'nama_mk' => 'Basis Data', 'sks' => 3]);
        $k2 = KelasKuliah::factory()->create(['mata_kuliah_id' => $mk2->id, 'dosen_id' => $dosen->id, 'semester_id' => $sem2->id]);
        $p2 = PesertaKuliah::factory()->create(['kelas_kuliah_id' => $k2->id, 'mahasiswa_id' => $mhs->id]);
        NilaiAkhir::factory()->published()->create(['peserta_kuliah_id' => $p2->id, 'nilai_angka' => 72.00, 'nilai_huruf' => 'B', 'bobot_mutu' => 3.00]);

        Sanctum::actingAs($userMhs);

        $response = $this->getJson('/api/mahasiswa/transkrip');

        // Total SKS = 6, Total Mutu = 21.00, IPK = 21.00 / 6 = 3.50
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'kumulatif' => [
                        'total_sks' => 6,
                        'total_mutu' => 21.00,
                        'ipk' => 3.50,
                        'total_mata_kuliah_diperhitungkan' => 2,
                    ],
                ],
            ])
            ->assertJsonCount(2, 'data.semester');
    }

    /**
     * Pembuktian 12: Error API memberikan HTTP status code yang tepat.
     */
    public function test_error_api_memberikan_status_code_yang_tepat(): void
    {
        $userMhs = User::factory()->create(['role' => 'mahasiswa']);
        Mahasiswa::factory()->create(['user_id' => $userMhs->id]);

        // 1. Error 401: Belum login
        $res401 = $this->getJson('/api/mahasiswa/ipk');
        $res401->assertStatus(401);

        // 2. Error 403: Role salah (dosen akses endpoint mahasiswa)
        $userDosen = User::factory()->create(['role' => 'dosen']);
        Dosen::factory()->create(['user_id' => $userDosen->id]);
        Sanctum::actingAs($userDosen);
        $res403 = $this->getJson('/api/mahasiswa/ipk');
        $res403->assertStatus(403);

        // 3. Error 404: Semester tidak ditemukan
        Sanctum::actingAs($userMhs);
        $res404 = $this->getJson('/api/mahasiswa/khs/99999');
        $res404->assertStatus(404);

        // 4. Error 422: Validasi form gagal pada login
        $res422 = $this->postJson('/api/auth/login', [
            'email' => 'bukan-email',
            // password dikosongkan
        ]);
        $res422->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }
}
