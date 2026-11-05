<?php

namespace App\Services;

use App\Exceptions\TokenStorageUnavailable;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class SiswaAuthService
{
    /**
     * @return array{token: string, user: array<string, mixed>}|null
     */
    public function login(string $login, string $password): ?array
    {
        $user = User::query()
            ->with(['role', 'siswa'])
            ->where(function ($query) use ($login): void {
                $query->where('username', $login)
                    ->orWhere('email', $login);
            })
            ->first();

        if (
            ! $user
            || ! $this->isEligibleSiswa($user)
            || ! $user->password_hash
            || ! Hash::check($password, $user->password_hash)
        ) {
            return null;
        }

        if (! Schema::hasTable('personal_access_tokens')) {
            throw new TokenStorageUnavailable;
        }

        $newToken = $user->createToken('web-siswa', ['siswa'], now()->addHours(12));

        return [
            'token' => $newToken->plainTextToken,
            'user' => $this->userData($user),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function authenticatedSiswaData(User $user): ?array
    {
        $user->loadMissing(['role', 'siswa']);

        if (! $this->isEligibleSiswa($user)) {
            return null;
        }

        return $this->userData($user);
    }

    public function isEligibleSiswa(User $user): bool
    {
        return $user->role?->nama === 'siswa'
            && $user->status === 'aktif'
            && $user->siswa !== null
            && $user->siswa->status_siswa !== 'keluar';
    }

    /**
     * @return array<string, mixed>
     */
    private function userData(User $user): array
    {
        return [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'nama_tampilan' => $user->nama_tampilan,
            'role' => $user->role->nama,
            'siswa' => [
                'id' => $user->siswa->id,
                'nisn' => $user->siswa->nisn,
                'nis' => $user->siswa->nis,
                'nama_lengkap' => $user->siswa->nama_lengkap,
                'kelas_id' => $user->siswa->kelas_id,
                'status_siswa' => $user->siswa->status_siswa,
            ],
        ];
    }
}
