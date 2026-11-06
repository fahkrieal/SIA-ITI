<?php

namespace Tests\Feature;

use App\Models\KelasKuliah;
use App\Models\KomponenNilai;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\NilaiAkhir;
use App\Models\PesertaKuliah;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PostgreSqlSpecificTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Pembuktian 1: Membuktikan koneksi aktif benar-benar menggunakan driver PostgreSQL (pgsql).
     */
    public function test_koneksi_database_menggunakan_driver_postgresql(): void
    {
        $driverName = DB::connection()->getDriverName();
        $this->assertEquals('pgsql', $driverName, 'Koneksi database aktif harus PostgreSQL.');

        $version = DB::select('SELECT version()');
        $this->assertNotEmpty($version);
        $this->assertStringContainsString('PostgreSQL', $version[0]->version);
    }

    /**
     * Pembuktian 3 & 14: Membuktikan Foreign Key constraint berjalan pada PostgreSQL.
     */
    public function test_foreign_key_constraint_berjalan_pada_postgresql(): void
    {
        $this->expectException(QueryException::class);

        // Coba insert Mahasiswa dengan user_id yang tidak ada (999999)
        Mahasiswa::create([
            'user_id' => 999999,
            'nim' => '2024000999',
            'nama' => 'Test Mahasiswa Invalid',
            'program_studi' => 'Teknik Informatika',
            'angkatan' => 2024,
        ]);
    }

    /**
     * Pembuktian 4: Membuktikan Unique constraint berjalan pada PostgreSQL.
     */
    public function test_unique_constraint_berjalan_pada_postgresql(): void
    {
        $user1 = User::factory()->create(['role' => 'mahasiswa']);
        Mahasiswa::factory()->create([
            'user_id' => $user1->id,
            'nim' => '2021101999',
        ]);

        $this->expectException(QueryException::class);

        // Coba insert Mahasiswa lain dengan NIM yang sama
        $user2 = User::factory()->create(['role' => 'mahasiswa']);
        Mahasiswa::create([
            'user_id' => $user2->id,
            'nim' => '2021101999', // Duplikat
            'nama' => 'Mahasiswa Duplikat',
            'program_studi' => 'Teknik Informatika',
            'angkatan' => 2021,
        ]);
    }

    /**
     * Pembuktian 13: Membuktikan Database Transaction dapat melakukan rollback pada PostgreSQL.
     */
    public function test_database_transaction_melakukan_rollback_pada_postgresql(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $initialMahasiswaCount = Mahasiswa::count();

        try {
            DB::transaction(function () use ($user) {
                // Operasi 1: Buat mahasiswa
                Mahasiswa::create([
                    'user_id' => $user->id,
                    'nim' => '2024111222',
                    'nama' => 'Mahasiswa Rollback Test',
                    'program_studi' => 'Teknik Informatika',
                    'angkatan' => 2024,
                ]);

                // Operasi 2: Simulasi error mendadak di tengah proses transaksi
                throw new \RuntimeException('Simulasi kegagalan sistem di tengah transaksi.');
            });
        } catch (\RuntimeException $e) {
            // Tangkap exception yang sengaja dilempar
        }

        // Buktikan bahwa data tidak ada yang tersimpan sebagian (rollback berhasil di PostgreSQL)
        $this->assertEquals($initialMahasiswaCount, Mahasiswa::count());
        $this->assertDatabaseMissing('mahasiswa', ['nim' => '2024111222']);
    }
}
