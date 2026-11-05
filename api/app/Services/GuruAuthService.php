<?php

namespace App\Services;

use App\Exceptions\TokenStorageUnavailable;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class GuruAuthService
{
    /**
     * @return array{token: string, user: array<string, mixed>}|null
     */
    public function login(string $login, string $password): ?array
    {
        $user = User::query()
            ->with(['role', 'guru'])
            ->where(function ($query) use ($login): void {
                $query->where('username', $login)
                    ->orWhere('email', $login);
            })
            ->first();

        if (
            ! $user
            || ! $this->isEligibleGuru($user)
            || ! $user->password_hash
            || ! Hash::check($password, $user->password_hash)
        ) {
            return null;
        }

        if (! Schema::hasTable('personal_access_tokens')) {
            throw new TokenStorageUnavailable;
        }

        $newToken = $user->createToken('flutter-guru', ['guru'], now()->addHours(12));

        return [
            'token' => $newToken->plainTextToken,
            'user' => $this->userData($user),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function authenticatedGuruData(User $user): ?array
    {
        $user->loadMissing(['role', 'guru']);

        if (! $this->isEligibleGuru($user)) {
            return null;
        }

        return $this->userData($user);
    }

    public function isEligibleGuru(User $user): bool
    {
        return $user->role?->nama === 'guru'
            && $user->status === 'aktif'
            && $user->guru !== null;
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
            'guru' => [
                'id' => $user->guru->id,
                'nip' => $user->guru->nip,
                'nama_lengkap' => $user->guru->nama_lengkap,
                'mata_pelajaran' => $user->guru->mata_pelajaran,
                'status_bimbingan' => $user->guru->status_bimbingan,
            ],
        ];
    }
}
