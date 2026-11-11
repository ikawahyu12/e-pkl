<?php

namespace App\Http\Controllers\Api\V1\Siswa;

use App\Http\Controllers\Controller;
use App\Models\PenempatanPkl;
use App\Models\User;
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

        $siswa->load('kelas');

        $penempatan = PenempatanPkl::with(['instansi', 'pic', 'guru'])
            ->where('siswa_id', $siswa->id)
            ->where('status', '!=', 'dibatalkan')
            ->orderByDesc('tanggal_mulai')
            ->first();

        $pkl = null;
        $guruPembimbing = null;

        if ($penempatan) {
            $pkl = [
                'status'              => $penempatan->status,
                'nama_perusahaan'     => $penempatan->instansi?->nama,
                'alamat_instansi'     => $penempatan->instansi?->alamat,
                'pembimbing_lapangan' => $penempatan->pic?->nama,
                'kontak_pembimbing'   => $penempatan->pic?->no_hp,
                'tanggal_mulai'       => $penempatan->tanggal_mulai?->toDateString(),
                'tanggal_selesai'     => $penempatan->tanggal_selesai?->toDateString(),
            ];

            if ($penempatan->guru) {
                $guruUser = User::find($penempatan->guru->user_id);
                $guruPembimbing = [
                    'nama'  => $penempatan->guru->nama_lengkap,
                    'no_hp' => $penempatan->guru->no_hp,
                    'email' => $guruUser?->email,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'username'        => $user->username,
                'email'           => $user->email,
                'nama_tampilan'   => $user->nama_tampilan,
                'foto_url'        => $user->foto_profil
                    ? Storage::disk('public')->url($user->foto_profil)
                    : null,
                'kelas'           => $siswa->kelas?->nama,
                'siswa'           => $siswa,
                'pkl'             => $pkl,
                'guru_pembimbing' => $guruPembimbing,
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