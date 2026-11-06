<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Batas Nilai Minimum dan Maksimum
    |--------------------------------------------------------------------------
    |
    | Menentukan rentang valid untuk input nilai komponen mata kuliah.
    |
    */
    'min_score' => 0.00,
    'max_score' => 100.00,

    /*
    |--------------------------------------------------------------------------
    | Total Bobot Komponen Penilaian (%)
    |--------------------------------------------------------------------------
    |
    | Total bobot komponen dalam satu kelas harus genap 100% sebelum nilai
    | dapat dipublikasikan.
    |
    */
    'required_total_weight' => 100.00,

    /*
    |--------------------------------------------------------------------------
    | Skala Konversi Nilai Akademik
    |--------------------------------------------------------------------------
    |
    | Pemetaan dari rentang nilai angka ke nilai huruf dan bobot mutu (grade point).
    | Dapat disesuaikan dengan pedoman dan SK akademik masing-masing institusi.
    |
    */
    'grade_scale' => [
        [
            'min' => 85.00,
            'max' => 100.00,
            'letter' => 'A',
            'grade_point' => 4.00,
        ],
        [
            'min' => 80.00,
            'max' => 84.99,
            'letter' => 'A-',
            'grade_point' => 3.75,
        ],
        [
            'min' => 75.00,
            'max' => 79.99,
            'letter' => 'B+',
            'grade_point' => 3.50,
        ],
        [
            'min' => 70.00,
            'max' => 74.99,
            'letter' => 'B',
            'grade_point' => 3.00,
        ],
        [
            'min' => 65.00,
            'max' => 69.99,
            'letter' => 'B-',
            'grade_point' => 2.75,
        ],
        [
            'min' => 60.00,
            'max' => 64.99,
            'letter' => 'C+',
            'grade_point' => 2.25,
        ],
        [
            'min' => 55.00,
            'max' => 59.99,
            'letter' => 'C',
            'grade_point' => 2.00,
        ],
        [
            'min' => 40.00,
            'max' => 54.99,
            'letter' => 'D',
            'grade_point' => 1.00,
        ],
        [
            'min' => 0.00,
            'max' => 39.99,
            'letter' => 'E',
            'grade_point' => 0.00,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Kebijakan Pengulangan Mata Kuliah (Retake Policy)
    |--------------------------------------------------------------------------
    |
    | Menentukan nilai mana yang diambil saat mahasiswa mengulang mata kuliah yang sama:
    | - 'highest' : Mengambil nilai dengan bobot mutu tertinggi.
    | - 'latest'  : Mengambil nilai pada semester paling terakhir/terbaru.
    |
    */
    'retake_policy' => env('ACADEMIC_RETAKE_POLICY', 'highest'),
];
