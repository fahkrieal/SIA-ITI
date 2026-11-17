<?php

namespace App\Http\Controllers\Api\Dosen;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dosen\UpdateNilaiRequest;
use App\Http\Resources\KelasKuliahResource;
use App\Http\Resources\KomponenNilaiResource;
use App\Http\Resources\PesertaKuliahResource;
use App\Models\KelasKuliah;
use App\Models\NilaiKomponen;
use App\Models\PesertaKuliah;
use App\Services\NilaiCalculationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class KelasDosenController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected NilaiCalculationService $calculationService
    ) {}

    /**
     * Menampilkan daftar kelas yang diampu oleh dosen login.
     */
    public function index(Request $request): JsonResponse
    {
        $dosen = $request->user()->dosen;

        if (! $dosen) {
            return $this->errorResponse(
                message: 'Data profil dosen tidak ditemukan.',
                errors: ['code' => 'LECTURER_NOT_FOUND'],
                statusCode: 404
            );
        }

        $kelasList = KelasKuliah::query()
            ->where('dosen_id', $dosen->id)
            ->with(['mataKuliah', 'semester', 'komponenNilai'])
            ->withCount('pesertaKuliah')
            ->get();

        $collection = KelasKuliahResource::collection($kelasList);

        return $this->successResponse(
            data: $collection,
            message: 'Daftar kelas yang diampu berhasil diambil.',
            meta: [
                'page'  => 1,
                'limit' => $collection->count(),
                'total' => $collection->count(),
            ],
            statusCode: 200
        );
    }

    /**
     * Menampilkan daftar mahasiswa peserta dalam suatu kelas kuliah.
     */
    public function mahasiswa(Request $request, KelasKuliah $kelas): JsonResponse
    {
        // Otorisasi: Dosen tidak boleh melihat kelas dosen lain
        Gate::authorize('view', $kelas);

        $pesertaList = $kelas->pesertaKuliah()
            ->with(['mahasiswa', 'nilaiAkhir', 'nilaiKomponen.komponenNilai'])
            ->get();

        return $this->successResponse(
            data: [
                'kelas' => new KelasKuliahResource($kelas),
                'peserta' => PesertaKuliahResource::collection($pesertaList),
            ],
            message: 'Daftar mahasiswa peserta kelas berhasil diambil.',
            statusCode: 200
        );
    }

    /**
     * Menampilkan komponen penilaian pada suatu kelas kuliah.
     */
    public function komponenNilai(Request $request, KelasKuliah $kelas): JsonResponse
    {
        Gate::authorize('view', $kelas);

        $komponen = $kelas->komponenNilai()->get();
        $totalBobot = (float) $komponen->sum('bobot_persen');

        return $this->successResponse(
            data: [
                'kelas_id' => $kelas->id,
                'total_bobot_persen' => $totalBobot,
                'is_complete' => abs($totalBobot - (float) config('academic.required_total_weight', 100)) < 0.01,
                'komponen' => KomponenNilaiResource::collection($komponen),
            ],
            message: 'Komponen penilaian kelas berhasil diambil.',
            statusCode: 200
        );
    }

    /**
     * Menginput atau mengubah nilai komponen mahasiswa,
     * serta memperbarui nilai akhir otomatis.
     */
    public function updateNilai(UpdateNilaiRequest $request, PesertaKuliah $peserta): JsonResponse
    {
        $inputNilai = $request->validated('nilai');

        $result = DB::transaction(function () use ($peserta, $inputNilai) {
            foreach ($inputNilai as $item) {
                NilaiKomponen::updateOrCreate(
                    [
                        'peserta_kuliah_id' => $peserta->id,
                        'komponen_nilai_id' => $item['komponen_nilai_id'],
                    ],
                    [
                        'nilai' => $item['nilai'],
                    ]
                );
            }

            // Hitung ulang nilai akhir mahasiswa (draft jika belum dipublikasikan)
            $isPublished = $peserta->kelasKuliah->isPublished();
            $nilaiAkhir = $this->calculationService->updateStudentFinalScore($peserta, $isPublished);

            return [
                'peserta' => $peserta->fresh(['mahasiswa', 'nilaiKomponen.komponenNilai', 'nilaiAkhir']),
                'nilai_akhir' => $nilaiAkhir,
            ];
        });

        return $this->successResponse(
            data: new PesertaKuliahResource($result['peserta']),
            message: 'Nilai mahasiswa berhasil disimpan.',
            statusCode: 200
        );
    }

    /**
     * Mempublikasikan nilai kelas secara resmi:
     * - Memvalidasi total bobot komponen 100%
     * - Menghitung nilai akhir seluruh mahasiswa
     * - Mengubah status publikasi menjadi 'published'
     */
    public function publikasiNilai(Request $request, KelasKuliah $kelas): JsonResponse
    {
        Gate::authorize('publish', $kelas);

        try {
            $updatedKelas = $this->calculationService->publishClassGrades($kelas);

            return $this->successResponse(
                data: new KelasKuliahResource($updatedKelas),
                message: 'Nilai kelas berhasil dipublikasikan secara resmi.',
                statusCode: 200
            );
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse(
                message: $e->getMessage(),
                errors: [
                    'code' => 'INVALID_GRADE_WEIGHT',
                    'detail' => $e->getMessage(),
                ],
                statusCode: 422
            );
        }
    }
}
