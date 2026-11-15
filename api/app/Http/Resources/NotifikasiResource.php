<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotifikasiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'judul' => $this->judul,
            
            // Ubah nama kolom DB 'pesan' menjadi 'isi' untuk Flutter
            'isi' => $this->pesan, 
            
            // Ubah created_at menjadi waktu format ISO
            'waktu' => $this->created_at?->toIso8601String(), 
            
            'tipe' => $this->tipe,
            
            // Konversi dibaca_at (timestamp) menjadi boolean true/false
            'dibaca' => $this->dibaca_at !== null, 
            
            // Ambil siswa_id dari tautan jika ada (misal: /guru/siswa/s05)
            'siswa_id' => $this->extractSiswaId($this->tautan),
        ];
    }

    private function extractSiswaId(?string $tautan): ?string
    {
        if (blank($tautan)) return null;
        if (preg_match('#/siswa/([^/]+)/?$#', $tautan, $m)) return $m[1];
        return null;
    }
}