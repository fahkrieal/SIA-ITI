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

class ContractEnvelopeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper untuk memverifikasi 100% kepatuhan terhadap JSON Envelope Response Schema (Tim 7 & Tim 8).
     * Root keys: success (bool), message (string), data (object|array|null), errors (object|null), meta (object|null)
     */
    private function assertMatchesEnvelopeSchema($response, bool $expectedSuccess): void
    {
        $json = $response->json();

        // 1. Memastikan 5 mandatory keys selalu ada
        $this->assertArrayHasKey('success', $json, 'Envelope wajib memiliki key: success');
        $this->assertArrayHasKey('message', $json, 'Envelope wajib memiliki key: message');
        $this->assertArrayHasKey('data', $json, 'Envelope wajib memiliki key: data');
        $this->assertArrayHasKey('errors', $json, 'Envelope wajib memiliki key: errors');
        $this->assertArrayHasKey('meta', $json, 'Envelope wajib memiliki key: meta');

        // 2. Memastikan tipe data key root sesuai skema
        $this->assertIsBool($json['success'], 'Field success harus bertipe boolean');
        $this->assertEquals($expectedSuccess, $json['success'], "Field success harus bernilai " . ($expectedSuccess ? 'true' : 'false'));
        $this->assertIsString($json['message'], 'Field message harus bertipe string');

        if ($expectedSuccess) {
            // Pada response sukses: errors wajib null
            $this->assertNull($json['errors'], 'Pada response sukses, errors harus null');
            if ($json['meta'] !== null) {
                $this->assertIsArray($json['meta'], 'Meta jika ada harus bertipe object/array');
                $this->assertArrayHasKey('page', $json['meta']);
                $this->assertArrayHasKey('limit', $json['meta']);
                $this->assertArrayHasKey('total', $json['meta']);
            }
        } else {
            // Pada response error: data wajib null, errors wajib bertipe object/array
            $this->assertNull($json['data'], 'Pada response error, data harus null');
            $this->assertNotNull($json['errors'], 'Pada response error, errors tidak boleh null');
            $this->assertIsArray($json['errors'], 'Errors harus bertipe object/array');
            $this->assertNull($json['meta'], 'Pada response error, meta harus null');
        }
    }

    /**
     * Test US-T8-001 & SEC-001: 401 Unauthorized Envelope Verification
     */
    public function test_unauthenticated_request_returns_envelope_401(): void
    {
        $response = $this->getJson('/api/mahasiswa/nilai');

        $response->assertStatus(401);
        $this->assertMatchesEnvelopeSchema($response, expectedSuccess: false);
        $this->assertEquals('UNAUTHENTICATED', $response->json('errors.code'));
    }

    /**
     * Test US-T8-001 & SEC-002: 403 Forbidden Envelope Verification
     */
    public function test_forbidden_role_access_returns_envelope_403(): void
    {
        $userMhs = User::factory()->create(['role' => 'mahasiswa']);
        Mahasiswa::factory()->create(['user_id' => $userMhs->id]);

        Sanctum::actingAs($userMhs);

        // Mahasiswa mencoba mengakses rute Dosen
        $response = $this->getJson('/api/dosen/kelas');

        $response->assertStatus(403);
        $this->assertMatchesEnvelopeSchema($response, expectedSuccess: false);
        $this->assertEquals('FORBIDDEN_ACCESS', $response->json('errors.code'));
    }

    /**
     * Test US-T8-001: 404 Not Found Envelope Verification
     */
    public function test_not_found_returns_envelope_404(): void
    {
        $userMhs = User::factory()->create(['role' => 'mahasiswa']);
        Mahasiswa::factory()->create(['user_id' => $userMhs->id]);

        Sanctum::actingAs($userMhs);

        $response = $this->getJson('/api/mahasiswa/route-tidak-ada');

        $response->assertStatus(404);
        $this->assertMatchesEnvelopeSchema($response, expectedSuccess: false);
        $this->assertEquals('RESOURCE_NOT_FOUND', $response->json('errors.code'));
    }

    /**
     * Test US-T8-001: 422 Unprocessable Entity Envelope Verification
     */
    public function test_validation_error_returns_envelope_422(): void
    {
        // Login payload kosong
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(422);
        $this->assertMatchesEnvelopeSchema($response, expectedSuccess: false);
        $this->assertEquals('VALIDATION_ERROR', $response->json('errors.code'));
        $this->assertArrayHasKey('email', $response->json('errors'));
        $this->assertArrayHasKey('password', $response->json('errors'));
    }

    /**
     * Test Endpoint Tim 5: Mahasiswa Nilai (200 OK Envelope)
     */
    public function test_mahasiswa_nilai_endpoint_complies_with_envelope(): void
    {
        $userMhs = User::factory()->create(['role' => 'mahasiswa']);
        $mhs = Mahasiswa::factory()->create(['user_id' => $userMhs->id]);

        $userDosen = User::factory()->create(['role' => 'dosen']);
        $dosen = Dosen::factory()->create(['user_id' => $userDosen->id]);

        $sem = Semester::factory()->create(['kode_semester' => '20241']);
        $mk = MataKuliah::factory()->create(['kode_mk' => 'IF2110', 'sks' => 3]);
        $kelas = KelasKuliah::factory()->create([
            'mata_kuliah_id' => $mk->id,
            'dosen_id' => $dosen->id,
            'semester_id' => $sem->id,
            'status_publikasi' => 'published',
        ]);
        $peserta = PesertaKuliah::factory()->create([
            'kelas_kuliah_id' => $kelas->id,
            'mahasiswa_id' => $mhs->id,
        ]);
        NilaiAkhir::factory()->published()->create([
            'peserta_kuliah_id' => $peserta->id,
            'nilai_angka' => 85.50,
            'nilai_huruf' => 'A',
            'bobot_mutu' => 4.00,
        ]);

        Sanctum::actingAs($userMhs);

        $response = $this->getJson('/api/mahasiswa/nilai');

        $response->assertStatus(200);
        $this->assertMatchesEnvelopeSchema($response, expectedSuccess: true);
        $this->assertIsArray($response->json('data'));
        $this->assertNotNull($response->json('meta'));
    }

    /**
     * Test Endpoint Tim 5: Mahasiswa KHS (200 OK Envelope)
     */
    public function test_mahasiswa_khs_endpoint_complies_with_envelope(): void
    {
        $userMhs = User::factory()->create(['role' => 'mahasiswa']);
        $mhs = Mahasiswa::factory()->create(['user_id' => $userMhs->id]);
        $sem = Semester::factory()->create(['kode_semester' => '20241']);

        Sanctum::actingAs($userMhs);

        $response = $this->getJson("/api/mahasiswa/khs/{$sem->kode_semester}");

        $response->assertStatus(200);
        $this->assertMatchesEnvelopeSchema($response, expectedSuccess: true);
        $this->assertArrayHasKey('semester', $response->json('data'));
        $this->assertArrayHasKey('ringkasan', $response->json('data'));
    }

    /**
     * Test Endpoint Tim 5: Mahasiswa IPK & Transkrip (200 OK Envelope)
     */
    public function test_mahasiswa_ipk_and_transcript_comply_with_envelope(): void
    {
        $userMhs = User::factory()->create(['role' => 'mahasiswa']);
        $mhs = Mahasiswa::factory()->create(['user_id' => $userMhs->id]);

        Sanctum::actingAs($userMhs);

        // Test IPK
        $resIpk = $this->getJson('/api/mahasiswa/ipk');
        $resIpk->assertStatus(200);
        $this->assertMatchesEnvelopeSchema($resIpk, expectedSuccess: true);

        // Test Transkrip
        $resTranskrip = $this->getJson('/api/mahasiswa/transkrip');
        $resTranskrip->assertStatus(200);
        $this->assertMatchesEnvelopeSchema($resTranskrip, expectedSuccess: true);
    }

    /**
     * Test Endpoint Tim 5: Dosen Kelas & Publikasi Nilai (200 OK Envelope)
     */
    public function test_dosen_endpoints_comply_with_envelope(): void
    {
        $userDosen = User::factory()->create(['role' => 'dosen']);
        $dosen = Dosen::factory()->create(['user_id' => $userDosen->id]);

        $sem = Semester::factory()->create();
        $mk = MataKuliah::factory()->create();
        $kelas = KelasKuliah::factory()->create([
            'mata_kuliah_id' => $mk->id,
            'dosen_id' => $dosen->id,
            'semester_id' => $sem->id,
        ]);

        Sanctum::actingAs($userDosen);

        $resKelas = $this->getJson('/api/dosen/kelas');
        $resKelas->assertStatus(200);
        $this->assertMatchesEnvelopeSchema($resKelas, expectedSuccess: true);

        $resMhs = $this->getJson("/api/dosen/kelas/{$kelas->id}/mahasiswa");
        $resMhs->assertStatus(200);
        $this->assertMatchesEnvelopeSchema($resMhs, expectedSuccess: true);

        $resKomp = $this->getJson("/api/dosen/kelas/{$kelas->id}/komponen-nilai");
        $resKomp->assertStatus(200);
        $this->assertMatchesEnvelopeSchema($resKomp, expectedSuccess: true);
    }
}
