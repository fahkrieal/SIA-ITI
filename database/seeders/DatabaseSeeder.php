<?php

namespace Database\Seeders;

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
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Akun Dosen 1 (Pengampu Utama)
        $userDosen1 = User::create([
            'name' => 'Dr. Budi Santoso, M.Kom.',
            'email' => 'dosen@iti.ac.id',
            'password' => Hash::make('password'),
            'role' => 'dosen',
        ]);

        $dosen1 = Dosen::create([
            'user_id' => $userDosen1->id,
            'nidn' => '0412345601',
            'nama' => 'Dr. Budi Santoso',
            'gelar' => 'M.Kom.',
        ]);

        // 2. Akun Dosen 2 (Dosen Lain untuk Otorisasi)
        $userDosen2 = User::create([
            'name' => 'Ir. Siti Aminah, M.T.',
            'email' => 'dosen2@iti.ac.id',
            'password' => Hash::make('password'),
            'role' => 'dosen',
        ]);

        $dosen2 = Dosen::create([
            'user_id' => $userDosen2->id,
            'nidn' => '0498765402',
            'nama' => 'Ir. Siti Aminah',
            'gelar' => 'M.T.',
        ]);

        // 3. Akun Mahasiswa 1
        $userMhs1 = User::create([
            'name' => 'Ahmad Fauzi',
            'email' => 'mahasiswa@iti.ac.id',
            'password' => Hash::make('password'),
            'role' => 'mahasiswa',
        ]);

        $mhs1 = Mahasiswa::create([
            'user_id' => $userMhs1->id,
            'nim' => '2021101001',
            'nama' => 'Ahmad Fauzi',
            'program_studi' => 'Teknik Informatika',
            'angkatan' => 2021,
        ]);

        // 4. Akun Mahasiswa 2 (Mahasiswa Lain)
        $userMhs2 = User::create([
            'name' => 'Rina Wardani',
            'email' => 'rina@iti.ac.id',
            'password' => Hash::make('password'),
            'role' => 'mahasiswa',
        ]);

        $mhs2 = Mahasiswa::create([
            'user_id' => $userMhs2->id,
            'nim' => '2021101002',
            'nama' => 'Rina Wardani',
            'program_studi' => 'Teknik Informatika',
            'angkatan' => 2021,
        ]);

        // 5. Semester
        $sem1 = Semester::create([
            'kode_semester' => '20231',
            'nama_semester' => 'Ganjil 2023/2024',
            'tahun_ajaran' => '2023/2024',
            'tipe' => 'ganjil',
            'is_active' => false,
        ]);

        $sem2 = Semester::create([
            'kode_semester' => '20232',
            'nama_semester' => 'Genap 2023/2024',
            'tahun_ajaran' => '2023/2024',
            'tipe' => 'genap',
            'is_active' => false,
        ]);

        $sem3 = Semester::create([
            'kode_semester' => '20241',
            'nama_semester' => 'Ganjil 2024/2025',
            'tahun_ajaran' => '2024/2025',
            'tipe' => 'ganjil',
            'is_active' => true,
        ]);

        // 6. Mata Kuliah
        $mk1 = MataKuliah::create([
            'kode_mk' => 'IF101',
            'nama_mk' => 'Pemrograman Dasar',
            'sks' => 3,
        ]);

        $mk2 = MataKuliah::create([
            'kode_mk' => 'IF102',
            'nama_mk' => 'Matematika Diskrit',
            'sks' => 3,
        ]);

        $mk3 = MataKuliah::create([
            'kode_mk' => 'IF201',
            'nama_mk' => 'Algoritma & Struktur Data',
            'sks' => 3,
        ]);

        $mk4 = MataKuliah::create([
            'kode_mk' => 'IF202',
            'nama_mk' => 'Basis Data',
            'sks' => 3,
        ]);

        $mk5 = MataKuliah::create([
            'kode_mk' => 'IF301',
            'nama_mk' => 'Rekayasa Perangkat Lunak',
            'sks' => 3,
        ]);

        // 7. Semester 20231 - Kelas 1: IF101 (Dosen Budi) -> Published
        $kelas1 = KelasKuliah::create([
            'mata_kuliah_id' => $mk1->id,
            'dosen_id' => $dosen1->id,
            'semester_id' => $sem1->id,
            'nama_kelas' => 'A',
            'status_publikasi' => 'published',
            'tanggal_publikasi' => now()->subMonths(10),
        ]);

        $komp1_1 = KomponenNilai::create(['kelas_kuliah_id' => $kelas1->id, 'nama_komponen' => 'Tugas', 'bobot_persen' => 20.00]);
        $komp1_2 = KomponenNilai::create(['kelas_kuliah_id' => $kelas1->id, 'nama_komponen' => 'UTS', 'bobot_persen' => 30.00]);
        $komp1_3 = KomponenNilai::create(['kelas_kuliah_id' => $kelas1->id, 'nama_komponen' => 'UAS', 'bobot_persen' => 50.00]);

        $peserta1_mhs1 = PesertaKuliah::create(['kelas_kuliah_id' => $kelas1->id, 'mahasiswa_id' => $mhs1->id]);
        NilaiKomponen::create(['peserta_kuliah_id' => $peserta1_mhs1->id, 'komponen_nilai_id' => $komp1_1->id, 'nilai' => 80.00]);
        NilaiKomponen::create(['peserta_kuliah_id' => $peserta1_mhs1->id, 'komponen_nilai_id' => $komp1_2->id, 'nilai' => 85.00]);
        NilaiKomponen::create(['peserta_kuliah_id' => $peserta1_mhs1->id, 'komponen_nilai_id' => $komp1_3->id, 'nilai' => 90.00]);
        // Nilai Akhir: 80*0.2 + 85*0.3 + 90*0.5 = 16 + 25.5 + 45 = 86.50 -> A (4.00)
        NilaiAkhir::create([
            'peserta_kuliah_id' => $peserta1_mhs1->id,
            'nilai_angka' => 86.50,
            'nilai_huruf' => 'A',
            'bobot_mutu' => 4.00,
            'is_published' => true,
            'tanggal_publikasi' => $kelas1->tanggal_publikasi,
        ]);

        $peserta1_mhs2 = PesertaKuliah::create(['kelas_kuliah_id' => $kelas1->id, 'mahasiswa_id' => $mhs2->id]);
        NilaiKomponen::create(['peserta_kuliah_id' => $peserta1_mhs2->id, 'komponen_nilai_id' => $komp1_1->id, 'nilai' => 75.00]);
        NilaiKomponen::create(['peserta_kuliah_id' => $peserta1_mhs2->id, 'komponen_nilai_id' => $komp1_2->id, 'nilai' => 70.00]);
        NilaiKomponen::create(['peserta_kuliah_id' => $peserta1_mhs2->id, 'komponen_nilai_id' => $komp1_3->id, 'nilai' => 80.00]);
        // Nilai Akhir: 75*0.2 + 70*0.3 + 80*0.5 = 15 + 21 + 40 = 76.00 -> B+ (3.50)
        NilaiAkhir::create([
            'peserta_kuliah_id' => $peserta1_mhs2->id,
            'nilai_angka' => 76.00,
            'nilai_huruf' => 'B+',
            'bobot_mutu' => 3.50,
            'is_published' => true,
            'tanggal_publikasi' => $kelas1->tanggal_publikasi,
        ]);

        // 8. Semester 20231 - Kelas 2: IF102 (Dosen Siti) -> Published
        $kelas2 = KelasKuliah::create([
            'mata_kuliah_id' => $mk2->id,
            'dosen_id' => $dosen2->id,
            'semester_id' => $sem1->id,
            'nama_kelas' => 'A',
            'status_publikasi' => 'published',
            'tanggal_publikasi' => now()->subMonths(10),
        ]);

        $komp2_1 = KomponenNilai::create(['kelas_kuliah_id' => $kelas2->id, 'nama_komponen' => 'Tugas', 'bobot_persen' => 30.00]);
        $komp2_2 = KomponenNilai::create(['kelas_kuliah_id' => $kelas2->id, 'nama_komponen' => 'UTS', 'bobot_persen' => 30.00]);
        $komp2_3 = KomponenNilai::create(['kelas_kuliah_id' => $kelas2->id, 'nama_komponen' => 'UAS', 'bobot_persen' => 40.00]);

        $peserta2_mhs1 = PesertaKuliah::create(['kelas_kuliah_id' => $kelas2->id, 'mahasiswa_id' => $mhs1->id]);
        NilaiKomponen::create(['peserta_kuliah_id' => $peserta2_mhs1->id, 'komponen_nilai_id' => $komp2_1->id, 'nilai' => 70.00]);
        NilaiKomponen::create(['peserta_kuliah_id' => $peserta2_mhs1->id, 'komponen_nilai_id' => $komp2_2->id, 'nilai' => 75.00]);
        NilaiKomponen::create(['peserta_kuliah_id' => $peserta2_mhs1->id, 'komponen_nilai_id' => $komp2_3->id, 'nilai' => 70.00]);
        // Nilai Akhir: 70*0.3 + 75*0.3 + 70*0.4 = 21 + 22.5 + 28 = 71.50 -> B (3.00)
        NilaiAkhir::create([
            'peserta_kuliah_id' => $peserta2_mhs1->id,
            'nilai_angka' => 71.50,
            'nilai_huruf' => 'B',
            'bobot_mutu' => 3.00,
            'is_published' => true,
            'tanggal_publikasi' => $kelas2->tanggal_publikasi,
        ]);

        // 9. Semester 20232 - Kelas 3: IF201 (Dosen Budi) -> Published
        $kelas3 = KelasKuliah::create([
            'mata_kuliah_id' => $mk3->id,
            'dosen_id' => $dosen1->id,
            'semester_id' => $sem2->id,
            'nama_kelas' => 'A',
            'status_publikasi' => 'published',
            'tanggal_publikasi' => now()->subMonths(5),
        ]);

        $komp3_1 = KomponenNilai::create(['kelas_kuliah_id' => $kelas3->id, 'nama_komponen' => 'Tugas', 'bobot_persen' => 20.00]);
        $komp3_2 = KomponenNilai::create(['kelas_kuliah_id' => $kelas3->id, 'nama_komponen' => 'UTS', 'bobot_persen' => 40.00]);
        $komp3_3 = KomponenNilai::create(['kelas_kuliah_id' => $kelas3->id, 'nama_komponen' => 'UAS', 'bobot_persen' => 40.00]);

        $peserta3_mhs1 = PesertaKuliah::create(['kelas_kuliah_id' => $kelas3->id, 'mahasiswa_id' => $mhs1->id]);
        NilaiKomponen::create(['peserta_kuliah_id' => $peserta3_mhs1->id, 'komponen_nilai_id' => $komp3_1->id, 'nilai' => 85.00]);
        NilaiKomponen::create(['peserta_kuliah_id' => $peserta3_mhs1->id, 'komponen_nilai_id' => $komp3_2->id, 'nilai' => 80.00]);
        NilaiKomponen::create(['peserta_kuliah_id' => $peserta3_mhs1->id, 'komponen_nilai_id' => $komp3_3->id, 'nilai' => 85.00]);
        // Nilai Akhir: 85*0.2 + 80*0.4 + 85*0.4 = 17 + 32 + 34 = 83.00 -> A- (3.75)
        NilaiAkhir::create([
            'peserta_kuliah_id' => $peserta3_mhs1->id,
            'nilai_angka' => 83.00,
            'nilai_huruf' => 'A-',
            'bobot_mutu' => 3.75,
            'is_published' => true,
            'tanggal_publikasi' => $kelas3->tanggal_publikasi,
        ]);

        // 10. Semester 20232 - Kelas 4: IF202 (Dosen Siti) -> Published
        $kelas4 = KelasKuliah::create([
            'mata_kuliah_id' => $mk4->id,
            'dosen_id' => $dosen2->id,
            'semester_id' => $sem2->id,
            'nama_kelas' => 'A',
            'status_publikasi' => 'published',
            'tanggal_publikasi' => now()->subMonths(5),
        ]);

        $komp4_1 = KomponenNilai::create(['kelas_kuliah_id' => $kelas4->id, 'nama_komponen' => 'Tugas', 'bobot_persen' => 25.00]);
        $komp4_2 = KomponenNilai::create(['kelas_kuliah_id' => $kelas4->id, 'nama_komponen' => 'UTS', 'bobot_persen' => 35.00]);
        $komp4_3 = KomponenNilai::create(['kelas_kuliah_id' => $kelas4->id, 'nama_komponen' => 'UAS', 'bobot_persen' => 40.00]);

        $peserta4_mhs1 = PesertaKuliah::create(['kelas_kuliah_id' => $kelas4->id, 'mahasiswa_id' => $mhs1->id]);
        NilaiKomponen::create(['peserta_kuliah_id' => $peserta4_mhs1->id, 'komponen_nilai_id' => $komp4_1->id, 'nilai' => 78.00]);
        NilaiKomponen::create(['peserta_kuliah_id' => $peserta4_mhs1->id, 'komponen_nilai_id' => $komp4_2->id, 'nilai' => 82.00]);
        NilaiKomponen::create(['peserta_kuliah_id' => $peserta4_mhs1->id, 'komponen_nilai_id' => $komp4_3->id, 'nilai' => 80.00]);
        // Nilai Akhir: 78*0.25 + 82*0.35 + 80*0.40 = 19.5 + 28.7 + 32 = 80.20 -> A- (3.75)
        NilaiAkhir::create([
            'peserta_kuliah_id' => $peserta4_mhs1->id,
            'nilai_angka' => 80.20,
            'nilai_huruf' => 'A-',
            'bobot_mutu' => 3.75,
            'is_published' => true,
            'tanggal_publikasi' => $kelas4->tanggal_publikasi,
        ]);

        // 11. Semester 20241 (Aktif) - Kelas 5: IF301 (Dosen Budi) -> DRAFT (Belum Publikasi)
        $kelas5 = KelasKuliah::create([
            'mata_kuliah_id' => $mk5->id,
            'dosen_id' => $dosen1->id,
            'semester_id' => $sem3->id,
            'nama_kelas' => 'A',
            'status_publikasi' => 'draft',
            'tanggal_publikasi' => null,
        ]);

        $komp5_1 = KomponenNilai::create(['kelas_kuliah_id' => $kelas5->id, 'nama_komponen' => 'Tugas', 'bobot_persen' => 30.00]);
        $komp5_2 = KomponenNilai::create(['kelas_kuliah_id' => $kelas5->id, 'nama_komponen' => 'UTS', 'bobot_persen' => 30.00]);
        $komp5_3 = KomponenNilai::create(['kelas_kuliah_id' => $kelas5->id, 'nama_komponen' => 'UAS', 'bobot_persen' => 40.00]);

        $peserta5_mhs1 = PesertaKuliah::create(['kelas_kuliah_id' => $kelas5->id, 'mahasiswa_id' => $mhs1->id]);
        NilaiKomponen::create(['peserta_kuliah_id' => $peserta5_mhs1->id, 'komponen_nilai_id' => $komp5_1->id, 'nilai' => 85.00]);
        NilaiKomponen::create(['peserta_kuliah_id' => $peserta5_mhs1->id, 'komponen_nilai_id' => $komp5_2->id, 'nilai' => 80.00]);
        NilaiKomponen::create(['peserta_kuliah_id' => $peserta5_mhs1->id, 'komponen_nilai_id' => $komp5_3->id, 'nilai' => 82.00]);
        // Nilai Akhir: Draft (is_published = false)
        NilaiAkhir::create([
            'peserta_kuliah_id' => $peserta5_mhs1->id,
            'nilai_angka' => 82.30,
            'nilai_huruf' => 'A-',
            'bobot_mutu' => 3.75,
            'is_published' => false,
            'tanggal_publikasi' => null,
        ]);

        $peserta5_mhs2 = PesertaKuliah::create(['kelas_kuliah_id' => $kelas5->id, 'mahasiswa_id' => $mhs2->id]);
        NilaiKomponen::create(['peserta_kuliah_id' => $peserta5_mhs2->id, 'komponen_nilai_id' => $komp5_1->id, 'nilai' => 90.00]);
        NilaiKomponen::create(['peserta_kuliah_id' => $peserta5_mhs2->id, 'komponen_nilai_id' => $komp5_2->id, 'nilai' => 88.00]);
    }
}
