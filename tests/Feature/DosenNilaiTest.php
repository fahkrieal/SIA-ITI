<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\KelasKuliah;
use App\Models\KomponenNilai;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\NilaiAkhir;
use App\Models\PesertaKuliah;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DosenNilaiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Dosen dapat melihat daftar kelas yang diampunya.
     */
    public function test_dosen_bisa_melihat_kelas_yang_diampu(): void
    {
        $userDosen = User::factory()->create(['role' => 'dosen']);
        $dosen = Dosen::factory()->create(['user_id' => $userDosen->id]);

        $sem = Semester::factory()->create(['kode_semester' => '20241']);
        $mk1 = MataKuliah::factory()->create(['kode_mk' => 'IF101']);
        $mk2 = MataKuliah::factory()->create(['kode_mk' => 'IF102']);

        KelasKuliah::factory()->create(['dosen_id' => $dosen->id, 'mata_kuliah_id' => $mk1->id, 'semester_id' => $sem->id]);
        KelasKuliah::factory()->create(['dosen_id' => $dosen->id, 'mata_kuliah_id' => $mk2->id, 'semester_id' => $sem->id]);

        Sanctum::actingAs($userDosen);

        $response = $this->getJson('/api/dosen/kelas');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(2, 'data');
    }

    /**
     * Pembuktian 7: Nilai di luar rentang (misal < 0 atau > 100) ditolak HTTP 422.
     */
    public function test_nilai_di_luar_rentang_ditolak_422(): void
    {
        $userDosen = User::factory()->create(['role' => 'dosen']);
        $dosen = Dosen::factory()->create(['user_id' => $userDosen->id]);

        $sem = Semester::factory()->create(['kode_semester' => '20241']);
        $mk = MataKuliah::factory()->create();
        $kelas = KelasKuliah::factory()->create([
            'dosen_id' => $dosen->id,
            'mata_kuliah_id' => $mk->id,
            'semester_id' => $sem->id,
            'status_publikasi' => 'draft',
        ]);

        $komponen = KomponenNilai::factory()->create([
            'kelas_kuliah_id' => $kelas->id,
            'bobot_persen' => 100.00,
        ]);

        $userMhs = User::factory()->create(['role' => 'mahasiswa']);
        $mhs = Mahasiswa::factory()->create(['user_id' => $userMhs->id]);
        $peserta = PesertaKuliah::factory()->create([
            'kelas_kuliah_id' => $kelas->id,
            'mahasiswa_id' => $mhs->id,
        ]);

        Sanctum::actingAs($userDosen);

        // Kasus 1: Nilai lebih dari 100
        $resOver = $this->putJson("/api/dosen/peserta/{$peserta->id}/nilai", [
            'nilai' => [
                [
                    'komponen_nilai_id' => $komponen->id,
                    'nilai' => 105.00,
                ],
            ],
        ]);

        $resOver->assertStatus(422)
            ->assertJsonValidationErrors(['nilai.0.nilai']);

        // Kasus 2: Nilai negatif (< 0)
        $resNegative = $this->putJson("/api/dosen/peserta/{$peserta->id}/nilai", [
            'nilai' => [
                [
                    'komponen_nilai_id' => $komponen->id,
                    'nilai' => -10.00,
                ],
            ],
        ]);

        $resNegative->assertStatus(422)
            ->assertJsonValidationErrors(['nilai.0.nilai']);
    }

    /**
     * Dosen berhasil menginput nilai valid dan nilai akhir terhitung otomatis.
     */
    public function test_dosen_berhasil_menginput_nilai_komponen(): void
    {
        $userDosen = User::factory()->create(['role' => 'dosen']);
        $dosen = Dosen::factory()->create(['user_id' => $userDosen->id]);

        $sem = Semester::factory()->create(['kode_semester' => '20241']);
        $mk = MataKuliah::factory()->create();
        $kelas = KelasKuliah::factory()->create([
            'dosen_id' => $dosen->id,
            'mata_kuliah_id' => $mk->id,
            'semester_id' => $sem->id,
            'status_publikasi' => 'draft',
        ]);

        $k1 = KomponenNilai::factory()->create(['kelas_kuliah_id' => $kelas->id, 'nama_komponen' => 'Tugas', 'bobot_persen' => 40.00]);
        $k2 = KomponenNilai::factory()->create(['kelas_kuliah_id' => $kelas->id, 'nama_komponen' => 'UAS', 'bobot_persen' => 60.00]);

        $userMhs = User::factory()->create(['role' => 'mahasiswa']);
        $mhs = Mahasiswa::factory()->create(['user_id' => $userMhs->id]);
        $peserta = PesertaKuliah::factory()->create([
            'kelas_kuliah_id' => $kelas->id,
            'mahasiswa_id' => $mhs->id,
        ]);

        Sanctum::actingAs($userDosen);

        $response = $this->putJson("/api/dosen/peserta/{$peserta->id}/nilai", [
            'nilai' => [
                ['komponen_nilai_id' => $k1->id, 'nilai' => 80.00],
                ['komponen_nilai_id' => $k2->id, 'nilai' => 90.00],
            ],
        ]);

        // (80 * 0.40) + (90 * 0.60) = 32 + 54 = 86.00 -> A
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'nilai_akhir' => [
                        'nilai_angka' => 86.00,
                        'nilai_huruf' => 'A',
                        'bobot_mutu' => 4.00,
                        'is_published' => false,
                    ],
                ],
            ]);
    }

    /**
     * Pembuktian 6 (API): Bobot komponen tidak 100% ditolak saat publikasi HTTP 422.
     */
    public function test_publikasi_nilai_ditolak_jika_bobot_kurang_dari_100_persen(): void
    {
        $userDosen = User::factory()->create(['role' => 'dosen']);
        $dosen = Dosen::factory()->create(['user_id' => $userDosen->id]);

        $sem = Semester::factory()->create(['kode_semester' => '20241']);
        $mk = MataKuliah::factory()->create();
        $kelas = KelasKuliah::factory()->create([
            'dosen_id' => $dosen->id,
            'mata_kuliah_id' => $mk->id,
            'semester_id' => $sem->id,
            'status_publikasi' => 'draft',
        ]);

        // Hanya 50%
        KomponenNilai::factory()->create(['kelas_kuliah_id' => $kelas->id, 'bobot_persen' => 50.00]);

        Sanctum::actingAs($userDosen);

        $response = $this->postJson("/api/dosen/kelas/{$kelas->id}/publikasi-nilai");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Dosen berhasil mempublikasikan nilai ketika bobot komponen mencapai 100%.
     */
    public function test_publikasi_nilai_berhasil_ketika_bobot_100_persen(): void
    {
        $userDosen = User::factory()->create(['role' => 'dosen']);
        $dosen = Dosen::factory()->create(['user_id' => $userDosen->id]);

        $sem = Semester::factory()->create(['kode_semester' => '20241']);
        $mk = MataKuliah::factory()->create();
        $kelas = KelasKuliah::factory()->create([
            'dosen_id' => $dosen->id,
            'mata_kuliah_id' => $mk->id,
            'semester_id' => $sem->id,
            'status_publikasi' => 'draft',
        ]);

        $k1 = KomponenNilai::factory()->create(['kelas_kuliah_id' => $kelas->id, 'bobot_persen' => 50.00]);
        $k2 = KomponenNilai::factory()->create(['kelas_kuliah_id' => $kelas->id, 'bobot_persen' => 50.00]);

        $userMhs = User::factory()->create(['role' => 'mahasiswa']);
        $mhs = Mahasiswa::factory()->create(['user_id' => $userMhs->id]);
        $peserta = PesertaKuliah::factory()->create([
            'kelas_kuliah_id' => $kelas->id,
            'mahasiswa_id' => $mhs->id,
        ]);

        Sanctum::actingAs($userDosen);

        $response = $this->postJson("/api/dosen/kelas/{$kelas->id}/publikasi-nilai");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status_publikasi' => 'published',
                ],
            ]);

        $this->assertEquals('published', $kelas->fresh()->status_publikasi);
        $this->assertTrue(NilaiAkhir::where('peserta_kuliah_id', $peserta->id)->value('is_published'));
    }
}
