<?php

namespace App\Http\Controllers\Api\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Http\Resources\MahasiswaNilaiResource;
use App\Models\PesertaKuliah;
use App\Services\AcademicPerformanceService;
use App\Services\KhsService;
use App\Services\TranscriptService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NilaiMahasiswaController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AcademicPerformanceService $performanceService,
        protected KhsService $khsService,
        protected TranscriptService $transcriptService
    ) {}

    /**
     * Melihat seluruh nilai mata kuliah miliknya.
     */
    public function index(Request $request): JsonResponse
    {
        $mahasiswa = $request->user()->mahasiswa;

        if (! $mahasiswa) {
            return $this->errorResponse(
                message: 'Data mahasiswa tidak ditemukan untuk akun ini.',
                errors: ['code' => 'STUDENT_NOT_FOUND'],
                statusCode: 404
            );
        }

        $daftarNilai = PesertaKuliah::query()
            ->where('mahasiswa_id', $mahasiswa->id)
            ->with(['kelasKuliah.mataKuliah', 'kelasKuliah.semester', 'kelasKuliah.dosen', 'nilaiAkhir'])
            ->get();

        $collection = MahasiswaNilaiResource::collection($daftarNilai);

        return $this->successResponse(
            data: $collection,
            message: 'Daftar nilai mahasiswa berhasil diambil.',
            meta: [
                'page'  => 1,
                'limit' => $collection->count(),
                'total' => $collection->count(),
            ],
            statusCode: 200
        );
    }

    /**
     * Melihat KHS berdasarkan semester (ID semester atau kode semester).
     */
    public function khs(Request $request, string $semester): JsonResponse
    {
        $mahasiswa = $request->user()->mahasiswa;

        if (! $mahasiswa) {
            return $this->errorResponse(
                message: 'Data mahasiswa tidak ditemukan untuk akun ini.',
                errors: ['code' => 'STUDENT_NOT_FOUND'],
                statusCode: 404
            );
        }

        $khsData = $this->khsService->getKhs($mahasiswa, $semester);

        return $this->successResponse(
            data: $khsData,
            message: 'Kartu Hasil Studi (KHS) berhasil diambil.',
            statusCode: 200
        );
    }

    /**
     * Melihat IPK kumulatif dan ringkasan akademik.
     */
    public function ipk(Request $request): JsonResponse
    {
        $mahasiswa = $request->user()->mahasiswa;

        if (! $mahasiswa) {
            return $this->errorResponse(
                message: 'Data mahasiswa tidak ditemukan untuk akun ini.',
                errors: ['code' => 'STUDENT_NOT_FOUND'],
                statusCode: 404
            );
        }

        $ipkData = $this->performanceService->calculateIpk($mahasiswa);

        return $this->successResponse(
            data: [
                'mahasiswa' => [
                    'nim' => $mahasiswa->nim,
                    'nama' => $mahasiswa->nama,
                    'program_studi' => $mahasiswa->program_studi,
                    'angkatan' => $mahasiswa->angkatan,
                ],
                'akademik' => $ipkData,
            ],
            message: 'Data Indeks Prestasi Kumulatif (IPK) berhasil diambil.',
            statusCode: 200
        );
    }

    /**
     * Melihat transkrip akademik lengkap.
     */
    public function transcript(Request $request): JsonResponse
    {
        $mahasiswa = $request->user()->mahasiswa;

        if (! $mahasiswa) {
            return $this->errorResponse(
                message: 'Data mahasiswa tidak ditemukan untuk akun ini.',
                errors: ['code' => 'STUDENT_NOT_FOUND'],
                statusCode: 404
            );
        }

        $transcriptData = $this->transcriptService->getTranscript($mahasiswa);

        return $this->successResponse(
            data: $transcriptData,
            message: 'Transkrip akademik berhasil diambil.',
            statusCode: 200
        );
    }
}
