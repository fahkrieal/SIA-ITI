<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\KelasKuliah;
use App\Models\KomponenNilai;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\PesertaKuliah;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Pembuktian 3: Mahasiswa tidak bisa mengakses endpoint dosen (Otorisasi Role Mahasiswa vs Dosen).
     */
    public function test_mahasiswa_tidak_bisa_mengakses_endpoint_dosen(): void
    {
        $userMhs = User::factory()->create(['role' => 'mahasiswa']);
        Mahasiswa::factory()->create(['user_id' => $userMhs->id]);

        Sanctum::actingAs($userMhs);

        $response = $this->getJson('/api/dosen/kelas');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Pembuktian 4: Dosen tidak bisa mengakses atau mengubah nilai kelas dosen lain.
     */
    public function test_dosen_tidak_bisa_mengubah_nilai_kelas_dosen_lain(): void
    {
        // Dosen 1 (Pengampu Asli)
        $userDosen1 = User::factory()->create(['role' => 'dosen']);
        $dosen1 = Dosen::factory()->create(['user_id' => $userDosen1->id]);

        // Dosen 2 (Dosen Lain yang Mencoba Mengubah)
        $userDosen2 = User::factory()->create(['role' => 'dosen']);
        $dosen2 = Dosen::factory()->create(['user_id' => $userDosen2->id]);

        $mk = MataKuliah::factory()->create(['sks' => 3]);
        $sem = Semester::factory()->create(['kode_semester' => '20241']);

        // Kelas milik Dosen 1
        $kelas = KelasKuliah::factory()->create([
            'mata_kuliah_id' => $mk->id,
            'dosen_id' => $dosen1->id,
            'semester_id' => $sem->id,
            'status_publikasi' => 'draft',
        ]);

        $komponen = KomponenNilai::factory()->create([
            'kelas_kuliah_id' => $kelas->id,
            'nama_komponen' => 'Tugas',
            'bobot_persen' => 100.00,
        ]);

        $userMhs = User::factory()->create(['role' => 'mahasiswa']);
        $mhs = Mahasiswa::factory()->create(['user_id' => $userMhs->id]);

        $peserta = PesertaKuliah::factory()->create([
            'kelas_kuliah_id' => $kelas->id,
            'mahasiswa_id' => $mhs->id,
        ]);

        // Login sebagai Dosen 2
        Sanctum::actingAs($userDosen2);

        // Coba lihat daftar mahasiswa kelas Dosen 1 -> Ditolak 403
        $resView = $this->getJson("/api/dosen/kelas/{$kelas->id}/mahasiswa");
        $resView->assertStatus(403);

        // Coba ubah nilai peserta di kelas Dosen 1 -> Ditolak 403
        $resUpdate = $this->putJson("/api/dosen/peserta/{$peserta->id}/nilai", [
            'nilai' => [
                [
                    'komponen_nilai_id' => $komponen->id,
                    'nilai' => 90.00,
                ],
            ],
        ]);
        $resUpdate->assertStatus(403);

        // Coba publikasi kelas milik Dosen 1 -> Ditolak 403
        $resPublish = $this->postJson("/api/dosen/kelas/{$kelas->id}/publikasi-nilai");
        $resPublish->assertStatus(403);
    }

    /**
     * Dosen tidak bisa mengakses endpoint khusus mahasiswa.
     */
    public function test_dosen_tidak_bisa_mengakses_endpoint_mahasiswa(): void
    {
        $userDosen = User::factory()->create(['role' => 'dosen']);
        Dosen::factory()->create(['user_id' => $userDosen->id]);

        Sanctum::actingAs($userDosen);

        $response = $this->getJson('/api/mahasiswa/nilai');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }
}
