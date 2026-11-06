<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Dosen\KelasDosenController;
use App\Http\Controllers\Api\Mahasiswa\NilaiMahasiswaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Sistem Informasi Akademik (SIA)
|--------------------------------------------------------------------------
*/

// Authentication Routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
    });
});

// Mahasiswa Routes
Route::prefix('mahasiswa')
    ->middleware(['auth:sanctum', 'role:mahasiswa'])
    ->group(function () {
        Route::get('/nilai', [NilaiMahasiswaController::class, 'index'])->name('mahasiswa.nilai');
        Route::get('/khs/{semester}', [NilaiMahasiswaController::class, 'khs'])->name('mahasiswa.khs');
        Route::get('/ipk', [NilaiMahasiswaController::class, 'ipk'])->name('mahasiswa.ipk');
        Route::get('/transkrip', [NilaiMahasiswaController::class, 'transcript'])->name('mahasiswa.transkrip');
    });

// Dosen Routes
Route::prefix('dosen')
    ->middleware(['auth:sanctum', 'role:dosen'])
    ->group(function () {
        Route::get('/kelas', [KelasDosenController::class, 'index'])->name('dosen.kelas');
        Route::get('/kelas/{kelas}/mahasiswa', [KelasDosenController::class, 'mahasiswa'])->name('dosen.kelas.mahasiswa');
        Route::get('/kelas/{kelas}/komponen-nilai', [KelasDosenController::class, 'komponenNilai'])->name('dosen.kelas.komponen');
        Route::put('/peserta/{peserta}/nilai', [KelasDosenController::class, 'updateNilai'])->name('dosen.peserta.nilai.update');
        Route::post('/kelas/{kelas}/publikasi-nilai', [KelasDosenController::class, 'publikasiNilai'])->name('dosen.kelas.publikasi');
    });
