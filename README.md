# Backend Sistem Informasi Akademik (SIA) - ITI (PostgreSQL Edition)

Backend REST API Sistem Informasi Akademik (SIA) yang dibangun dengan standar arsitektur clean architecture, memisahkan tanggung jawab (*Separation of Concerns*) antara Controller, Form Request, Service Layer, Model Eloquent, API Resource, dan Policy Otorisasi, menggunakan **PostgreSQL 17.2** sebagai basis data relasional utama.

---

## 1. Lingkungan & Kompatibilitas Database

| Komponen | Spesifikasi / Versi | Catatan Teknis |
|---|---|---|
| **Database Server** | PostgreSQL `17.2` | Port `5432`, Encoding: `UTF8` |
| **PHP Extension** | `pdo_pgsql` & `pgsql` | Terintegrasi pada PHP `8.5.12` |
| **Framework** | Laravel `13.40.0` | Driver `pgsql`, Schema `public` |
| **Testing Database** | `sia_akademik_testing` | Pengujian otomatis langsung pada PostgreSQL |
| **Production/Dev Database** | `sia_akademik` | Migrasi & Seeder aktif di PostgreSQL |

---

## 2. Fitur Utama

1. **Pengelolaan Nilai Mahasiswa (Dosen)**:
   - Dosen melihat kelas yang diampu beserta mahasiswa dan komponen penilaian.
   - Input dan perubahan nilai tugas, UTS, UAS, kuis, atau praktikum dengan validasi rentang (0.00 - 100.00).
   - Perhitungan otomatis nilai akhir: $\text{Nilai Akhir} = \sum (\text{Nilai Komponen} \times \frac{\text{Bobot Komponen}}{100})$.
   - Konversi otomatis nilai angka ke huruf dan bobot mutu berdasarkan konfigurasi akademik kampus.
   - Validasi bobot komponen wajib genap 100% sebelum nilai kelas dapat dipublikasikan.
   - Nilai draft tidak dipublikasikan ke mahasiswa sampai dosen melakukan aksi publikasi.

2. **Kartu Hasil Studi / KHS (Mahasiswa)**:
   - Mahasiswa melihat KHS berdasarkan semester (ID atau kode semester, contoh: `20231`).
   - Hanya menampilkan nilai yang telah sah dipublikasikan. Nilai draft disembunyikan (`null`) dan tidak diperhitungkan ke dalam IPS.
   - Menghitung total SKS diambil, total SKS dinilai, total mutu, dan IPS semester.

3. **Perhitungan Terpusat IPS dan IPK**:
   - Dilayani oleh `AcademicPerformanceService`.
   - Rumus IPS: $\text{IPS} = \frac{\sum (\text{SKS} \times \text{Bobot Mutu})}{\sum \text{SKS}}$.
   - Rumus IPK: $\text{IPK} = \frac{\sum (\text{SKS} \times \text{Bobot Mutu seluruh mata kuliah yang diperhitungkan})}{\sum \text{SKS seluruh mata kuliah yang diperhitungkan}}$.
   - Penanganan otomatis aturan pengulangan mata kuliah (kebijakan `highest` atau `latest` melalui config).
   - Presisi `NUMERIC(5, 2)` dan `NUMERIC(4, 2)` di PostgreSQL tanpa pembulatan bertahap; pembulatan 2 desimal dilakukan di hasil akhir.
   - Proteksi pembagian dengan nol.

4. **Transkrip Akademik Kumulatif (Mahasiswa)**:
   - Mengambil seluruh nilai final sah lintas semester.
   - Dikelompokkan per semester lengkap dengan kode, nama mata kuliah, SKS, nilai angka, nilai huruf, dan bobot mutu.
   - Total SKS kumulatif dan IPK kumulatif konsisten dengan kebijakan akademik.

5. **Keamanan, Integritas & Otorisasi PostgreSQL**:
   - **Foreign Keys & Cascade**: Relasi berintegritas tinggi dengan `ON DELETE CASCADE`.
   - **Native Unique Constraints**: Mencegah duplikasi NIM, identitas dosen, peserta per kelas, komponen per kelas, nilai komponen per peserta, dan nilai akhir per peserta.
   - **Native Check Constraints**: Memvalidasi enum status publikasi (`draft`, `published`) di level engine database.
   - **Database Transactions (`DB::transaction`)**: Menjamin konsistensi penyimpanan nilai dan publikasi kelas. Bila terjadi error di tengah proses, PostgreSQL otomatis me-rollback seluruh operasi.

---

## 3. Kredensial Akun Pengujian (Seeder)

Database `sia_akademik` di PostgreSQL telah diisi melalui seeder:

| Role | Nama | Email | Password | Identitas |
|---|---|---|---|---|
| **Dosen (Pengampu Utama)** | Dr. Budi Santoso, M.Kom. | `dosen@iti.ac.id` | `password` | NIDN: `0412345601` |
| **Dosen (Dosen Lain)** | Ir. Siti Aminah, M.T. | `dosen2@iti.ac.id` | `password` | NIDN: `0498765402` |
| **Mahasiswa 1** | Ahmad Fauzi | `mahasiswa@iti.ac.id` | `password` | NIM: `2021101001` (TI 2021) |
| **Mahasiswa 2** | Rina Wardani | `rina@iti.ac.id` | `password` | NIM: `2021101002` (TI 2021) |

---

## 4. Konfigurasi Database `.env`

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sia_akademik
DB_USERNAME=postgres
DB_PASSWORD=
```

---

## 5. Perintah Migrasi & Seeding

```bash
# Migrasi dan seeding ke database PostgreSQL
php artisan migrate:fresh --seed
```

---

## 6. Pengujian Otomatis pada PostgreSQL (Automated Testing)

Automated testing dikonfigurasi langsung pada database PostgreSQL `sia_akademik_testing`:

```bash
php artisan test --testdox
```

### Hasil Uji Aktual (23 Tests, 100 Assertions, 100% Passed):
- `Auth`: Login mahasiswa & dosen berhasil, 401 saat salah kredensial.
- `Authorization`: 403 saat mahasiswa akses dosen / dosen kelola kelas dosen lain.
- `DosenNilai`: Dosen input nilai valid, 422 saat nilai di luar range (0-100), 422 saat publikasi dengan bobot != 100%.
- `MahasiswaAkademik`: KHS sembunyikan nilai draft, transkrip lintas semester akurat, validasi error 401, 403, 404, 422.
- `NilaiCalculation`: Perhitungan bobot akurat, rumus IPS & IPK terbukti presisi dengan pengulangan mata kuliah.
- `PostgreSqlSpecific`:
  - Terbukti koneksi aktif menggunakan driver `pgsql` dan server PostgreSQL 17.2.
  - Terbukti PostgreSQL foreign key constraint menolak child invalid.
  - Terbukti PostgreSQL unique constraint menolak duplikasi NIM / komponen.
  - Terbukti PostgreSQL `DB::transaction` melakukan rollback sempurna saat terjadi exception.

---

## 7. Pengujian dengan Postman

Impor file `sia_iti_postman_collection.json` ke dalam aplikasi Postman. File ini telah dilengkapi request login, bearer token environment, dan endpoint SIA lengkap.
