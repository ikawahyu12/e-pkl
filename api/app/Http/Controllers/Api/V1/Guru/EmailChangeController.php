<?php

namespace App\Http\Controllers\Api\V1\Guru;

use App\Http\Controllers\Controller;
use App\Mail\GuruEmailChangeVerificationMail;
use App\Models\Guru;
use App\Models\User;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EmailChangeController extends Controller
{
    private const LINK_EXPIRY_MINUTES = 30;

    public function request(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing(['role', 'guru']);
        if (! $this->isEligibleGuru($user)) {
            return $this->notFound();
        }

        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
        ]);
        $email = Str::lower(trim($data['email']));

        if (Str::lower((string) $user->email) === $email) {
            throw ValidationException::withMessages([
                'email' => 'Email tersebut sudah menjadi email aktif akun Anda.',
            ]);
        }

        $nonce = Str::random(64);
        $nonceHash = hash('sha256', $nonce);
        $cacheKey = $this->pendingKey($user->id);
        $url = URL::temporarySignedRoute(
            'guru.email-change.verify',
            now()->addMinutes(self::LINK_EXPIRY_MINUTES),
            [
                'user' => $user->id,
                'nonce' => $nonce,
                'email' => Crypt::encryptString($email),
            ],
            false
        );

        try {
            Cache::lock($this->lockKey($user->id), 30)->block(10, function () use (
                $cacheKey,
                $email,
                $nonceHash,
                $url
            ): void {
                Cache::put($cacheKey, $nonceHash, now()->addMinutes(self::LINK_EXPIRY_MINUTES));
                try {
                    Mail::to($email)->send(new GuruEmailChangeVerificationMail(
                        $url,
                        self::LINK_EXPIRY_MINUTES
                    ));
                } catch (\Throwable $exception) {
                    if (hash_equals((string) Cache::get($cacheKey, ''), $nonceHash)) {
                        Cache::forget($cacheKey);
                    }

                    throw $exception;
                }
            });
        } catch (LockTimeoutException $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Permintaan perubahan email sedang diproses. Silakan coba kembali.',
            ], 503);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tautan verifikasi telah dikirim ke alamat email baru. Email aktif belum berubah.',
        ], 202);
    }

    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user' => ['required', 'integer', 'min:1'],
            'nonce' => ['required', 'string', 'size:64'],
            'email' => ['required', 'string', 'max:2000'],
        ]);
        $userId = (int) $data['user'];
        $nonceHash = hash('sha256', $data['nonce']);
        $cacheKey = $this->pendingKey($userId);

        try {
            $email = Crypt::decryptString($data['email']);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Tautan perubahan email tidak valid atau telah kedaluwarsa.',
            ], 403);
        }

        try {
            return Cache::lock($this->lockKey($userId), 30)->block(10, function () use (
                $data,
                $email,
                $nonceHash,
                $cacheKey,
                $userId
            ): JsonResponse {
                $currentNonceHash = (string) Cache::get($cacheKey, '');
                if ($currentNonceHash === '' || ! hash_equals($currentNonceHash, $nonceHash)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tautan perubahan email sudah digunakan atau bukan permintaan terbaru.',
                    ], 410);
                }

                DB::transaction(function () use ($email, $userId): void {
                    $user = User::query()->with('role')->lockForUpdate()->find($userId);
                    $guru = $user
                        ? Guru::query()->where('user_id', $userId)->lockForUpdate()->first()
                        : null;

                    if (! $user || ! $this->isEligibleGuru($user, $guru)) {
                        abort(404);
                    }

                    if (Str::lower((string) $user->email) === Str::lower($email)) {
                        throw ValidationException::withMessages([
                            'email' => 'Tautan perubahan email sudah digunakan.',
                        ]);
                    }
                    if (User::query()->whereRaw('LOWER(email) = ?', [Str::lower($email)])->exists()) {
                        throw ValidationException::withMessages([
                            'email' => 'Email tersebut sudah digunakan oleh akun lain.',
                        ]);
                    }

                    $user->email = $email;
                    $user->email_terverifikasi_at = now();
                    $user->save();
                }, attempts: 3);

                Cache::forget($cacheKey);

                return response()->json([
                    'success' => true,
                    'message' => 'Email akun Guru berhasil diverifikasi dan diperbarui.',
                ]);
            });
        } catch (LockTimeoutException $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Tautan sedang diproses. Silakan buka kembali beberapa saat lagi.',
            ], 503);
        }
    }

    private function isEligibleGuru(User $user, ?Guru $guru = null): bool
    {
        $guru ??= $user->guru;

        return $user->role?->nama === 'guru'
            && $user->status === 'aktif'
            && $guru !== null
            && $guru->status_bimbingan === 'aktif';
    }

    private function pendingKey(int $userId): string
    {
        return 'guru-email-change:pending:'.$userId;
    }

    private function lockKey(int $userId): string
    {
        return 'guru-email-change:lock:'.$userId;
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Data Guru tidak ditemukan atau tidak aktif.',
        ], 404);
    }
}
