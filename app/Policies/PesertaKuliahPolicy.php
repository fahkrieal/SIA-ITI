<?php

namespace App\Policies;

use App\Models\PesertaKuliah;
use App\Models\User;

class PesertaKuliahPolicy
{
    /**
     * Dosen hanya dapat mengubah nilai peserta pada kelas yang diampunya.
     */
    public function updateNilai(User $user, PesertaKuliah $peserta): bool
    {
        if (! $user->isDosen() || ! $user->dosen) {
            return false;
        }

        return (int) $user->dosen->id === (int) $peserta->kelasKuliah->dosen_id;
    }
}
