<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KomponenNilaiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kelas_kuliah_id' => $this->kelas_kuliah_id,
            'nama_komponen' => $this->nama_komponen,
            'bobot_persen' => (float) $this->bobot_persen,
        ];
    }
}
