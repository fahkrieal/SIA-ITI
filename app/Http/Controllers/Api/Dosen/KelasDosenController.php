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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class KelasDosenController extends Controller
{
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
            return response()->json([
                'success' => false,
                'message' => 'Data profil dosen tidak ditemukan.',
            ], 404);
        }

        $kelasList = KelasKuliah::query()
            ->where('dosen_id', $dosen->id)
            ->with(['mataKuliah', 'semester', 'komponenNilai'])
            ->withCount('pesertaKuliah')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar kelas yang diampu berhasil diambil.',
            'data' => KelasKuliahResource::collection($kelasList),
        ], 200);
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

        return response()->json([
            'success' => true,
            'message' => 'Daftar mahasiswa peserta kelas berhasil diambil.',
            'data' => [
                'kelas' => new KelasKuliahResource($kelas),
                'peserta' => PesertaKuliahResource::collection($pesertaList),
            ],
        ], 200);
    }

    /**
     * Menampilkan komponen penilaian pada suatu kelas kuliah.
     */
    public function komponenNilai(Request $request, KelasKuliah $kelas): JsonResponse
    {
        Gate::authorize('view', $kelas);

        $komponen = $kelas->komponenNilai()->get();
        $totalBobot = (float) $komponen->sum('bobot_persen');

        return response()->json([
            'success' => true,
            'message' => 'Komponen penilaian kelas berhasil diambil.',
            'data' => [
                'kelas_id' => $kelas->id,
                'total_bobot_persen' => $totalBobot,
                'is_complete' => abs($totalBobot - (float) config('academic.required_total_weight', 100)) < 0.01,
                'komponen' => KomponenNilaiResource::collection($komponen),
            ],
        ], 200);
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

        return response()->json([
            'success' => true,
            'message' => 'Nilai mahasiswa berhasil disimpan.',
            'data' => new PesertaKuliahResource($result['peserta']),
        ], 200);
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

            return response()->json([
                'success' => true,
                'message' => 'Nilai kelas berhasil dipublikasikan secara resmi.',
                'data' => new KelasKuliahResource($updatedKelas),
            ], 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
