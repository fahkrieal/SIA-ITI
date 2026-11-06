<?php

namespace App\Http\Requests\Dosen;

use App\Models\KomponenNilai;
use App\Models\PesertaKuliah;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNilaiRequest extends FormRequest
{
    public function authorize(): bool
    {
        $peserta = $this->route('peserta');

        if (! $peserta instanceof PesertaKuliah) {
            $peserta = PesertaKuliah::find($peserta);
        }

        if (! $peserta) {
            return false;
        }

        return $this->user()->can('updateNilai', $peserta);
    }

    public function rules(): array
    {
        $min = config('academic.min_score', 0);
        $max = config('academic.max_score', 100);

        return [
            'nilai' => ['required', 'array', 'min:1'],
            'nilai.*.komponen_nilai_id' => [
                'required',
                'integer',
                'exists:komponen_nilai,id',
            ],
            'nilai.*.nilai' => [
                'required',
                'numeric',
                "min:{$min}",
                "max:{$max}",
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $peserta = $this->route('peserta');

            if (! $peserta instanceof PesertaKuliah) {
                $peserta = PesertaKuliah::find($peserta);
            }

            if (! $peserta) {
                return;
            }

            // Larang perubahan nilai jika kelas sudah dipublikasikan tanpa proses pembukaan kembali
            if ($peserta->kelasKuliah->isPublished()) {
                $validator->errors()->add(
                    'kelas',
                    'Nilai tidak dapat diubah karena kelas ini sudah dipublikasikan secara resmi.'
                );
                return;
            }

            // Pastikan semua komponen_nilai_id memang milik kelas yang bersangkutan
            $validKomponenIds = KomponenNilai::where('kelas_kuliah_id', $peserta->kelas_kuliah_id)
                ->pluck('id')
                ->toArray();

            $inputNilai = $this->input('nilai', []);

            foreach ($inputNilai as $index => $item) {
                if (isset($item['komponen_nilai_id']) && ! in_array($item['komponen_nilai_id'], $validKomponenIds, true)) {
                    $validator->errors()->add(
                        "nilai.{$index}.komponen_nilai_id",
                        "Komponen nilai dengan ID {$item['komponen_nilai_id']} bukan milik kelas mata kuliah ini."
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        $min = config('academic.min_score', 0);
        $max = config('academic.max_score', 100);

        return [
            'nilai.required' => 'Daftar nilai komponen wajib dikirimkan.',
            'nilai.array' => 'Format daftar nilai harus berupa array.',
            'nilai.min' => 'Minimal satu nilai komponen harus dikirimkan.',
            'nilai.*.komponen_nilai_id.required' => 'ID komponen nilai wajib diisi.',
            'nilai.*.komponen_nilai_id.exists' => 'Komponen nilai tidak ditemukan.',
            'nilai.*.nilai.required' => 'Nilai wajib diisi.',
            'nilai.*.nilai.numeric' => 'Nilai harus berupa angka.',
            'nilai.*.nilai.min' => "Nilai minimal adalah {$min}.",
            'nilai.*.nilai.max' => "Nilai maksimal adalah {$max}.",
        ];
    }
}
