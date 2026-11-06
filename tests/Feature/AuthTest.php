<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Pembuktian 1: Login mahasiswa berhasil.
     */
    public function test_mahasiswa_login_berhasil(): void
    {
        $user = User::factory()->create([
            'email' => 'mahasiswa@iti.ac.id',
            'password' => Hash::make('password123'),
            'role' => 'mahasiswa',
        ]);

        $mahasiswa = Mahasiswa::factory()->create([
            'user_id' => $user->id,
            'nim' => '2021101001',
            'nama' => 'Ahmad Fauzi',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'mahasiswa@iti.ac.id',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login berhasil.',
            ])
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'token_type',
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'profile' => [
                            'id',
                            'nim',
                            'nama',
                        ],
                    ],
                ],
            ]);

        $this->assertEquals('mahasiswa', $response->json('data.user.role'));
        $this->assertEquals('2021101001', $response->json('data.user.profile.nim'));
    }

    /**
     * Pembuktian 2: Login dosen berhasil.
     */
    public function test_dosen_login_berhasil(): void
    {
        $user = User::factory()->create([
            'email' => 'dosen@iti.ac.id',
            'password' => Hash::make('password123'),
            'role' => 'dosen',
        ]);

        $dosen = Dosen::factory()->create([
            'user_id' => $user->id,
            'nidn' => '0412345601',
            'nama' => 'Dr. Budi Santoso',
            'gelar' => 'M.Kom.',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'dosen@iti.ac.id',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login berhasil.',
            ])
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'token_type',
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'profile' => [
                            'id',
                            'nidn',
                            'nama',
                            'gelar',
                        ],
                    ],
                ],
            ]);

        $this->assertEquals('dosen', $response->json('data.user.role'));
        $this->assertEquals('0412345601', $response->json('data.user.profile.nidn'));
    }

    /**
     * Pengujian login dengan kredensial salah menghasilkan HTTP 401.
     */
    public function test_login_dengan_password_salah_ditolak_401(): void
    {
        User::factory()->create([
            'email' => 'user@iti.ac.id',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@iti.ac.id',
            'password' => 'salah123',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Email atau password yang Anda masukkan salah.',
            ]);
    }

    /**
     * Pengujian akses tanpa token autentikasi menghasilkan HTTP 401.
     */
    public function test_akses_tanpa_token_menghasilkan_401(): void
    {
        $response = $this->getJson('/api/mahasiswa/nilai');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }
}
