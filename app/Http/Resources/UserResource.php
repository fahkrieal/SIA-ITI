<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profile = null;

        if ($this->isMahasiswa() && $this->mahasiswa) {
            $profile = [
                'id' => $this->mahasiswa->id,
                'nim' => $this->mahasiswa->nim,
                'nama' => $this->mahasiswa->nama,
                'program_studi' => $this->mahasiswa->program_studi,
                'angkatan' => $this->mahasiswa->angkatan,
            ];
        } elseif ($this->isDosen() && $this->dosen) {
            $profile = [
                'id' => $this->dosen->id,
                'nidn' => $this->dosen->nidn,
                'nama' => $this->dosen->nama,
                'gelar' => $this->dosen->gelar,
            ];
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'profile' => $profile,
        ];
    }
}
