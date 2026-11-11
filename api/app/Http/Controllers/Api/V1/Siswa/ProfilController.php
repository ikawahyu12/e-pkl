<?php

namespace App\Http\Controllers\Api\V1\Siswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfilController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $siswa = $user->siswa;

        if (! $siswa) {
            return response()->json([
                'success' => false,
                'message' => 'Data siswa tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'username'      => $user->username,
                'email'         => $user->email,
                'nama_tampilan' => $user->nama_tampilan,
                'foto_url'      => $user->foto_profil
                    ? Storage::disk('public')->url($user->foto_profil)
                    : null,
                'siswa'         => $siswa,
            ],
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'no_hp'  => 'nullable|string|max:20',
            'alamat' => 'nullable|string',
            'foto'   => 'nullable|image|max:2048',
        ]);

        $user = $request->user();
        $siswa = $user->siswa;

        if (! $siswa) {
            return response()->json([
                'success' => false,
                'message' => 'Data siswa tidak ditemukan',
            ], 404);
        }

        $siswa->update([
            'no_hp'  => $data['no_hp'] ?? $siswa->no_hp,
            'alamat' => $data['alamat'] ?? $siswa->alamat,
        ]);

        if ($request->hasFile('foto')) {
            if ($user->foto_profil) {
                Storage::disk('public')->delete($user->foto_profil);
            }
            $user->foto_profil = $request->file('foto')->store('foto-siswa', 'public');
            $user->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui',
            'data'    => $siswa->fresh(),
        ]);
    }
}