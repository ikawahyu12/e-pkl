<?php

namespace App\Services;

use App\Exceptions\TokenStorageUnavailable;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class AdminAuthService
{
    /**
     * @return array{token: string, user: array<string, mixed>}|null
     */
    public function login(string $login, string $password): ?array
    {
        $user = User::query()
            ->with('role')
            ->where(function ($query) use ($login): void {
                $query->where('username', $login)
                    ->orWhere('email', $login);
            })
            ->first();

        if (
            ! $user
            || ! $this->isEligibleAdmin($user)
            || ! $user->password_hash
            || ! Hash::check($password, $user->password_hash)
        ) {
            return null;
        }

        if (! Schema::hasTable('personal_access_tokens')) {
            throw new TokenStorageUnavailable;
        }

        $newToken = $user->createToken(
            'web-admin',
            ['admin'],
            now()->addHours(12)
        );

        return [
            'token' => $newToken->plainTextToken,
            'user' => $this->userData($user),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function authenticatedAdminData(User $user): ?array
    {
        $user->loadMissing('role');

        if (! $this->isEligibleAdmin($user)) {
            return null;
        }

        return $this->userData($user);
    }

    public function isEligibleAdmin(User $user): bool
    {
        return $user->role?->nama === 'admin'
            && $user->status === 'aktif';
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
        ];
    }
}
