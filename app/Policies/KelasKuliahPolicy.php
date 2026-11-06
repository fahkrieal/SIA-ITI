<?php

namespace App\Policies;

use App\Models\KelasKuliah;
use App\Models\User;

class KelasKuliahPolicy
{
    /**
     * Dosen hanya dapat melihat detail kelas yang diampunya.
     */
    public function view(User $user, KelasKuliah $kelas): bool
    {
        if (! $user->isDosen() || ! $user->dosen) {
            return false;
        }

        return (int) $user->dosen->id === (int) $kelas->dosen_id;
    }

    /**
     * Dosen hanya dapat mengelola nilai pada kelas yang diampunya.
     */
    public function manageNilai(User $user, KelasKuliah $kelas): bool
    {
        if (! $user->isDosen() || ! $user->dosen) {
            return false;
        }

        return (int) $user->dosen->id === (int) $kelas->dosen_id;
    }

    /**
     * Dosen hanya dapat mempublikasikan nilai pada kelas yang diampunya.
     */
    public function publish(User $user, KelasKuliah $kelas): bool
    {
        if (! $user->isDosen() || ! $user->dosen) {
            return false;
        }

        return (int) $user->dosen->id === (int) $kelas->dosen_id;
    }
}
