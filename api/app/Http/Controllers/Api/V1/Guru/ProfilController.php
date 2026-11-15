<?php

namespace App\Http\Controllers\Api\V1\Guru;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\PenempatanPkl;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProfilController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing(['role', 'guru']);
        if (! $this->isEligibleGuru($user)) {
            return $this->notFound();
        }

        return response()->json([
            'success' => true,
            'data' => $this->payload($user, $user->guru),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'min:3', 'max:120'],
            'no_hp' => ['sometimes', 'nullable', 'string', 'max:25'],
            'email' => ['prohibited'],
            'foto' => ['sometimes', 'nullable', 'image', 'max:2048'],
        ]);

        $user = $request->user();
        $newPhotoPath = null;
        $oldPhotoPath = null;

        try {
            $profile = DB::transaction(function () use (
                $request,
                $user,
                $data,
                &$newPhotoPath,
                &$oldPhotoPath
            ): Guru {
                $lockedUser = User::query()->with('role')->lockForUpdate()->findOrFail($user->id);
                $guru = Guru::query()->where('user_id', $lockedUser->id)->lockForUpdate()->first();
                if (! $this->isEligibleGuru($lockedUser, $guru)) {
                    abort(404);
                }

                $guru->nama_lengkap = trim($data['nama']);
                if (array_key_exists('no_hp', $data)) {
                    $guru->no_hp = $data['no_hp'];
                }
                $lockedUser->nama_tampilan = trim($data['nama']);

                if ($request->hasFile('foto')) {
                    $oldPhotoPath = $lockedUser->foto_profil;
                    $newPhotoPath = $request->file('foto')->store('foto-guru', 'public');
                    if ($newPhotoPath === false) {
                        throw new \RuntimeException('Foto profil Guru gagal disimpan.');
                    }
                    $lockedUser->foto_profil = $newPhotoPath;
                }

                $guru->save();
                $lockedUser->save();

                return $guru->fresh();
            });
        } catch (\Throwable $exception) {
            if (is_string($newPhotoPath) && ! Storage::disk('public')->delete($newPhotoPath)) {
                throw new \RuntimeException(
                    'Foto profil baru tidak dapat dibersihkan setelah pembaruan gagal.',
                    previous: $exception
                );
            }

            throw $exception;
        }

        if ($oldPhotoPath !== null && $oldPhotoPath !== $newPhotoPath) {
            if (! Storage::disk('public')->delete($oldPhotoPath)) {
                throw new \RuntimeException('Foto profil lama tidak dapat dihapus setelah pembaruan berhasil.');
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Profil Guru berhasil diperbarui.',
            'data' => $this->payload($user->fresh(['role']), $profile),
        ]);
    }

    private function isEligibleGuru(User $user, ?Guru $guru = null): bool
    {
        $guru ??= $user->guru;

        return $user->role?->nama === 'guru'
            && $user->status === 'aktif'
            && $guru !== null
            && $guru->status_bimbingan === 'aktif';
    }

    private function payload(User $user, Guru $guru): array
    {
        $placements = PenempatanPkl::query()
            ->with('siswa.kelas')
            ->where('guru_id', $guru->id)
            ->whereIn('status', ['belum_mulai', 'aktif'])
            ->get();
        $students = $placements->pluck('siswa')->filter()->unique('id');
        $classes = $students->map(fn ($student) => $student->kelas?->nama)
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return [
            'nama' => $guru->nama_lengkap,
            'peran' => 'Guru Pembimbing PKL',
            'nip' => $guru->nip,
            'sekolah' => config('app.nama_sekolah', '-'),
            'bimbingan' => $students->count().' siswa'
                .($classes->isNotEmpty() ? ' • '.$classes->implode(', ') : ''),
            'email' => $user->email,
            'no_hp' => $guru->no_hp,
            'foto_url' => $user->foto_profil ? url('/api/v1/foto/'.$user->foto_profil) : null,
            'jumlah_siswa' => $students->count(),
            'kelas' => $classes,
        ];
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Data Guru tidak ditemukan atau tidak aktif.',
        ], 404);
    }
}
