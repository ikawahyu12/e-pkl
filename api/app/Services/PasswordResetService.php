<?php

namespace App\Services;

use App\Mail\PasswordResetMail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordResetService
{
    private const TABLE = 'epkl_password_reset_tokens';

    private const ROLES = ['admin', 'guru', 'siswa'];

    public function sendResetLink(string $role, string $email): void
    {
        $email = Str::lower(trim($email));
        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->with(['role', 'guru', 'siswa'])
            ->first();

        if (! $user || ! $this->isEligible($user, $role)) {
            return;
        }

        $now = now();
        $table = DB::table(self::TABLE);

        if ($table->where('user_id', $user->getKey())
            ->where('created_at', '>=', $now->copy()->subSeconds(
                (int) config('password_reset.request_cooldown_seconds', 60)
            ))
            ->exists()) {
            return;
        }

        $frontendUrl = config('password_reset.frontends.'.$role);
        $fallbackUrl = $role === 'guru'
            ? config('password_reset.frontends.guru_fallback')
            : null;

        if (! is_string($frontendUrl) || trim($frontendUrl) === '') {
            Log::error('Password reset frontend URL is not configured.', ['role' => $role]);

            return;
        }

        if (app()->environment('production') && config('mail.default') === 'log') {
            Log::error('Password reset email refused because production mailer is log.');

            return;
        }

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresInMinutes = (int) config('password_reset.expire_minutes', 60);
        $expiresAt = $now->copy()->addMinutes($expiresInMinutes);

        DB::transaction(function () use ($user, $email, $role, $tokenHash, $now, $expiresAt): void {
            DB::table(self::TABLE)->where('user_id', $user->getKey())->delete();
            DB::table(self::TABLE)->insert([
                'user_id' => $user->getKey(),
                'email' => $email,
                'role' => $role,
                'token_hash' => $tokenHash,
                'expires_at' => $expiresAt,
                'created_at' => $now,
            ]);
        });

        try {
            Mail::to($email)->send(new PasswordResetMail(
                $this->withResetParameters($frontendUrl, $email, $token),
                $expiresInMinutes,
                is_string($fallbackUrl) && $fallbackUrl !== ''
                    ? $this->withResetParameters($fallbackUrl, $email, $token)
                    : null,
            ));
        } catch (\Throwable $exception) {
            DB::table(self::TABLE)->where('user_id', $user->getKey())
                ->where('token_hash', $tokenHash)->delete();
            Log::error('Password reset email delivery failed.', [
                'exception' => $exception::class,
                'role' => $role,
            ]);
        }
    }

    public function resetPassword(
        string $role,
        string $email,
        string $token,
        string $newPassword,
    ): bool {
        if (! in_array($role, self::ROLES, true)) {
            return false;
        }

        $tokenHash = hash('sha256', $token);
        $email = Str::lower(trim($email));

        return DB::transaction(function () use ($role, $email, $tokenHash, $newPassword): bool {
            $reset = DB::table(self::TABLE)
                ->where('email', $email)
                ->where('role', $role)
                ->where('token_hash', $tokenHash)
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();

            if (! $reset) {
                return false;
            }

            $user = User::query()->lockForUpdate()->find($reset->user_id);

            if (
                ! $user
                || ! hash_equals(Str::lower((string) $user->email), $email)
                || ! $this->isEligible($user, $role)
            ) {
                return false;
            }

            $user->password_hash = Hash::make($newPassword);
            $user->wajib_ganti_password = false;
            $user->save();
            $user->tokens()->delete();

            DB::table(self::TABLE)->where('user_id', $user->getKey())->delete();

            return true;
        });
    }

    private function isEligible(User $user, string $role): bool
    {
        if (! in_array($role, self::ROLES, true)
            || $user->role?->nama !== $role
            || $user->status !== 'aktif') {
            return false;
        }

        return match ($role) {
            'guru' => $user->guru !== null,
            'siswa' => $user->siswa !== null && $user->siswa->status_siswa === 'aktif',
            default => true,
        };
    }

    private function withResetParameters(string $url, string $email, string $token): string
    {
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.http_build_query([
            'email' => $email,
            'token' => $token,
        ]);
    }
}
