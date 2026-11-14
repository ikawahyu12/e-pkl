<?php

// Simpan di: app/Http/Controllers/Api/V1/Guru/ProfilController.php
//
// Route (routes/api.php), di dalam grup 'v1/guru' yang sudah ada:
//   Route::get('/profil', [GuruProfilController::class, 'show']);
//   Route::post('/profil', [GuruProfilController::class, 'update']);   // JSON atau multipart (foto)
//   Route::post('/profil/password', [GuruProfilController::class, 'updatePassword']);
//
// Foto tampil lewat Storage::url(), jadi jalankan sekali: php artisan storage:link
// dan pastikan APP_URL di .env bisa dijangkau dari HP/emulator.

namespace App\Http\Controllers\Api\V1\Guru;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\PenempatanPkl;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfilController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $guru = Guru::where('user_id', $user->id)->first();

        if (! $guru) {
            return $this->guruTidakDitemukan();
        }

        return response()->json([
            'success' => true,
            'data' => $this->payload($user, $guru),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $guru = Guru::where('user_id', $user->id)->first();

        if (! $guru) {
            return $this->guruTidakDitemukan();
        }

        $data = $request->validate([
            'nama'  => ['required', 'string', 'max:120'],
            'email' => [
                'nullable', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'foto'  => ['nullable', 'image', 'max:2048'],
        ]);

        $nama = trim($data['nama']);

        $guru->update([
            'nama_lengkap' => $nama,
            'no_hp'        => $data['no_hp'] ?? $guru->no_hp,
        ]);

        $user->nama_tampilan = $nama;
        if (! empty($data['email'])) {
            $user->email = $data['email'];
        }

        if ($request->hasFile('foto')) {
            if ($user->foto_profil) {
                Storage::disk('public')->delete($user->foto_profil);
            }
            $user->foto_profil = $request->file('foto')->store('foto-guru', 'public');
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui',
            'data'    => $this->payload($user, $guru->fresh()),
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'password_lama' => ['required', 'string'],
            'password_baru' => ['required', 'string', 'min:8', 'different:password_lama'],
        ]);

        $user = $request->user();

        if (! Hash::check($data['password_lama'], $user->password_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'Kata sandi lama salah.',
            ], 422);
        }

        $user->password_hash = Hash::make($data['password_baru']);
        $user->wajib_ganti_password = false;
        $user->save();

        // Logout perangkat lain, token yang sedang dipakai tetap hidup.
        $currentId = $user->currentAccessToken()?->id;
        $user->tokens()
            ->when($currentId, fn ($q) => $q->where('id', '!=', $currentId))
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kata sandi berhasil diubah.',
        ]);
    }

    private function guruTidakDitemukan(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Data guru tidak ditemukan',
        ], 404);
    }

    /** Bentuk data profil; key disamakan dengan ProfilGuru.fromJson di Flutter. */
    private function payload(User $user, Guru $guru): array
    {
        $siswaIds = PenempatanPkl::where('guru_id', $guru->id)
            ->where('status', '!=', 'dibatalkan')
            ->pluck('siswa_id')
            ->unique();

        $siswa = Siswa::with('kelas')->whereIn('id', $siswaIds)->get();

        $kelas = $siswa->map(fn ($s) => $s->kelas?->nama)
            ->filter()
            ->unique()
            ->sort()
            ->values();

        // Contoh hasil: "12 siswa • XI RPL 1, XI RPL 2"
        $bimbingan = $siswa->count() . ' siswa'
            . ($kelas->isNotEmpty() ? ' • ' . $kelas->implode(', ') : '');

        return [
            'nama'         => $guru->nama_lengkap,
            'peran'        => 'Guru Pembimbing PKL',
            'nip'          => $guru->nip,
            'sekolah'      => config('app.nama_sekolah', '-'),
            'bimbingan'    => $bimbingan,
            'email'        => $user->email,
            'no_hp'        => $guru->no_hp,
           'foto_url' => $user->foto_profil
    ? url('/api/v1/foto/' . $user->foto_profil)
    : null,
            'jumlah_siswa' => $siswa->count(),
            'kelas'        => $kelas,
        ];
    }
}