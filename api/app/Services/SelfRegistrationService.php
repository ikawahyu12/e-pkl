<?php

namespace App\Services;

use App\Exceptions\SelfRegistrationUnavailable;
use App\Exceptions\VerificationEmailDeliveryFailed;
use App\Mail\AccountVerificationMail;
use App\Models\Guru;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SelfRegistrationService
{
    private const TABLE = 'pending_account_verifications';

    private const ROLES = ['siswa', 'guru'];

    private const TOKEN_EXPIRY_MINUTES = 60;

    private const ACCOUNT_LIFETIME_DAYS = 7;

    public function register(string $roleName, array $input): void
    {
        $this->assertValidRole($roleName);
        $this->assertSchemaReady();

        try {
            [$user, $token] = DB::transaction(function () use ($roleName, $input): array {
                $role = Role::query()
                    ->whereRaw('LOWER(nama) = ?', [$roleName])
                    ->lockForUpdate()
                    ->first();

                if (! $role) {
                    throw new SelfRegistrationUnavailable('Role registrasi belum tersedia.');
                }

                $profile = $this->findAvailableMasterProfile($roleName, $input);
                if (! $profile) {
                    throw new \InvalidArgumentException(
                        'Data identitas dan nama tidak cocok dengan master aktif atau profil sudah terhubung.'
                    );
                }

                $email = Str::lower(trim($input['email']));
                if (User::query()->whereRaw('LOWER(email) = ?', [$email])->exists()) {
                    throw new \InvalidArgumentException('Email sudah digunakan.');
                }

                $identity = trim($input[$roleName === 'siswa' ? 'nisn' : 'nip']);
                if (User::query()->where('username', $identity)->exists()) {
                    throw new \InvalidArgumentException('Identitas sudah terhubung ke akun lain.');
                }

                $user = new User;
                $user->role_id = $role->getKey();
                $user->username = $identity;
                $user->email = $email;
                $user->nama_tampilan = trim($profile->nama_lengkap);
                $user->password_hash = Hash::make($input['password']);
                $user->status = 'pending';
                $user->wajib_ganti_password = false;
                $user->email_terverifikasi_at = null;
                $user->save();

                $token = bin2hex(random_bytes(32));
                DB::table(self::TABLE)->insert([
                    'user_id' => $user->getKey(),
                    'role_id' => $role->getKey(),
                    'siswa_id' => $roleName === 'siswa' ? $profile->getKey() : null,
                    'guru_id' => $roleName === 'guru' ? $profile->getKey() : null,
                    'token_hash' => hash('sha256', $token),
                    'expires_at' => now()->addMinutes(self::TOKEN_EXPIRY_MINUTES),
                    'created_at' => now(),
                ]);

                return [$user, $token];
            }, attempts: 3);
        } catch (QueryException $exception) {
            $sqlState = $exception->errorInfo[0] ?? null;
            $driverCode = (int) ($exception->errorInfo[1] ?? 0);
            $isUniqueViolation = $sqlState === '23000'
                && (
                    (DB::connection()->getDriverName() === 'mysql' && $driverCode === 1062)
                    || (
                        DB::connection()->getDriverName() === 'sqlite'
                        && str_contains(strtolower($exception->getMessage()), 'unique constraint failed')
                    )
                );

            if (
                $isUniqueViolation
            ) {
                throw new \InvalidArgumentException('Email atau identitas sudah digunakan.', previous: $exception);
            }

            throw $exception;
        }

        $this->sendVerificationEmail($user, $roleName, $token);
    }

    public function resend(string $roleName, string $email): void
    {
        $this->assertValidRole($roleName);
        $this->assertSchemaReady();
        $email = Str::lower(trim($email));

        $verification = DB::transaction(function () use ($roleName, $email): ?array {
            $userId = User::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->where('status', 'pending')
                ->value('id');

            if (! $userId) {
                return null;
            }

            $pending = DB::table(self::TABLE)
                ->where('user_id', $userId)
                ->whereNotNull('created_at')
                ->where('created_at', '>', now()->subDays(self::ACCOUNT_LIFETIME_DAYS))
                ->lockForUpdate()
                ->first();

            if (! $pending) {
                return null;
            }

            $user = User::query()
                ->with('role')
                ->lockForUpdate()
                ->find($userId);

            if (
                ! $user
                || $user->status !== 'pending'
                || $user->email_terverifikasi_at !== null
                || Str::lower(trim((string) $user->email)) !== $email
                || $user->role?->nama !== $roleName
                || (int) $pending->role_id !== (int) $user->role_id
            ) {
                return null;
            }

            $profile = $roleName === 'siswa'
                ? Siswa::query()->lockForUpdate()->find($pending->siswa_id)
                : Guru::query()->lockForUpdate()->find($pending->guru_id);

            if (
                ! $profile
                || ! $this->pendingProfileMatches($pending, $roleName, $profile->getKey())
                || ($profile->user_id !== null && (int) $profile->user_id !== (int) $user->getKey())
                || ($roleName === 'siswa' && $profile->status_siswa !== 'aktif')
                || trim((string) $user->username) !== trim((string) ($roleName === 'siswa' ? $profile->nisn : $profile->nip))
                || ! $this->sameMasterName($user->nama_tampilan, $profile->nama_lengkap)
            ) {
                return null;
            }

            $token = bin2hex(random_bytes(32));
            DB::table(self::TABLE)->where('user_id', $user->getKey())->update([
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addMinutes(self::TOKEN_EXPIRY_MINUTES),
            ]);

            return [$user, $token];
        }, attempts: 3);

        if ($verification !== null) {
            try {
                $this->sendVerificationEmail($verification[0], $roleName, $verification[1]);
            } catch (VerificationEmailDeliveryFailed) {
                // Keep the response generic for registered and unregistered addresses.
            }
        }
    }

    public function verify(string $roleName, string $token): bool
    {
        if (! in_array($roleName, self::ROLES, true) || $token === '') {
            return false;
        }

        $this->assertSchemaReady();
        $tokenHash = hash('sha256', $token);

        return DB::transaction(function () use ($roleName, $tokenHash): bool {
            $pending = DB::table(self::TABLE)
                ->where('token_hash', $tokenHash)
                ->where('expires_at', '>', now())
                ->whereNotNull('created_at')
                ->where('created_at', '>', now()->subDays(self::ACCOUNT_LIFETIME_DAYS))
                ->lockForUpdate()
                ->first();

            if (! $pending) {
                return false;
            }

            $user = User::query()->with('role')->lockForUpdate()->find($pending->user_id);
            if (
                ! $user
                || $user->status !== 'pending'
                || $user->email_terverifikasi_at !== null
                || $user->role?->nama !== $roleName
                || (int) $pending->role_id !== (int) $user->role_id
            ) {
                return false;
            }

            if ($roleName === 'siswa') {
                $profile = Siswa::query()->lockForUpdate()->find($pending->siswa_id);
                if (
                    ! $profile
                    || $pending->guru_id !== null
                    || ($profile->user_id !== null && (int) $profile->user_id !== (int) $user->getKey())
                    || $profile->status_siswa !== 'aktif'
                    || trim((string) $user->username) !== trim((string) $profile->nisn)
                    || ! $this->sameMasterName($user->nama_tampilan, $profile->nama_lengkap)
                ) {
                    return false;
                }
            } else {
                $profile = Guru::query()->lockForUpdate()->find($pending->guru_id);
                if (
                    ! $profile
                    || $pending->siswa_id !== null
                    || ($profile->user_id !== null && (int) $profile->user_id !== (int) $user->getKey())
                    || trim((string) $user->username) !== trim((string) $profile->nip)
                    || ! $this->sameMasterName($user->nama_tampilan, $profile->nama_lengkap)
                ) {
                    return false;
                }
            }

            $profile->user_id = $user->getKey();
            $profile->save();

            $user->email_terverifikasi_at = now();
            $user->status = 'aktif';
            $user->save();
            DB::table(self::TABLE)->where('id', $pending->id)->delete();

            return true;
        }, attempts: 3);
    }

    private function assertSchemaReady(): void
    {
        foreach (['users', 'roles', 'siswa', 'guru', self::TABLE] as $table) {
            if (! Schema::hasTable($table)) {
                throw new SelfRegistrationUnavailable("Tabel $table belum tersedia.");
            }
        }

        $userColumns = collect(Schema::getColumns('users'))->keyBy('name');
        $statusType = strtolower((string) ($userColumns->get('status')['type'] ?? ''));
        if (
            ! $userColumns->has('email_terverifikasi_at')
            || (Schema::getConnection()->getDriverName() === 'mysql'
                && (! str_starts_with($statusType, 'enum(') || ! str_contains($statusType, "'pending'")))
        ) {
            throw new SelfRegistrationUnavailable(
                'Skema users belum mendukung akun pending dan verifikasi email.'
            );
        }

        $pendingColumns = collect(Schema::getColumns(self::TABLE))->pluck('name')->all();
        foreach (['user_id', 'role_id', 'siswa_id', 'guru_id', 'token_hash', 'expires_at', 'created_at'] as $column) {
            if (! in_array($column, $pendingColumns, true)) {
                throw new SelfRegistrationUnavailable(
                    'Skema pending_account_verifications belum sesuai target registrasi.'
                );
            }
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            $checks = DB::select(
                "SELECT tc.CONSTRAINT_NAME, cc.CHECK_CLAUSE, tc.ENFORCED
                FROM information_schema.TABLE_CONSTRAINTS tc
                INNER JOIN information_schema.CHECK_CONSTRAINTS cc
                    ON cc.CONSTRAINT_SCHEMA = tc.CONSTRAINT_SCHEMA
                    AND cc.CONSTRAINT_NAME = tc.CONSTRAINT_NAME
                WHERE tc.TABLE_SCHEMA = DATABASE()
                    AND tc.TABLE_NAME = ?
                    AND tc.CONSTRAINT_TYPE = 'CHECK'",
                [self::TABLE],
            );
            if (
                count($checks) !== 1
                || $checks[0]->CONSTRAINT_NAME !== 'chk_pending_role_master'
                || strtoupper((string) $checks[0]->ENFORCED) !== 'YES'
                || ! $this->isTargetProfileCheck((string) $checks[0]->CHECK_CLAUSE)
            ) {
                throw new SelfRegistrationUnavailable(
                    'CHECK registrasi belum menegakkan XOR profil tanpa ketergantungan role_id.'
                );
            }
        } else {
            $tableMetadata = DB::selectOne(
                "SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?",
                [self::TABLE],
            );
            $createSql = (string) ($tableMetadata->sql ?? '');
            preg_match('/check\s*\((.*)\)\s*\)$/is', trim($createSql), $checkMatch);
            if (
                $createSql === ''
                || ! isset($checkMatch[1])
                || ! $this->isTargetProfileCheck($checkMatch[1])
            ) {
                throw new SelfRegistrationUnavailable(
                    'CHECK registrasi belum menegakkan XOR profil pada database pengujian.'
                );
            }
        }
    }

    private function isTargetProfileCheck(string $expression): bool
    {
        $normalized = strtolower((string) preg_replace('/[\s`()]/', '', $expression));

        return $normalized === 'siswa_idisnotnullandguru_idisnullorsiswa_idisnullandguru_idisnotnull';
    }

    private function assertValidRole(string $roleName): void
    {
        if (! in_array($roleName, self::ROLES, true)) {
            throw new \InvalidArgumentException('Role registrasi tidak valid.');
        }
    }

    private function findAvailableMasterProfile(string $roleName, array $input): ?Model
    {
        if ($roleName === 'siswa') {
            $profile = Siswa::query()
                ->where('nisn', trim($input['nisn']))
                ->whereRaw('LOWER(TRIM(nama_lengkap)) = ?', [Str::lower(trim($input['nama_lengkap']))])
                ->where('status_siswa', 'aktif')
                ->lockForUpdate()
                ->first();
        } else {
            $profile = Guru::query()
                ->where('nip', trim($input['nip']))
                ->whereRaw('LOWER(TRIM(nama_lengkap)) = ?', [Str::lower(trim($input['nama_lengkap']))])
                ->lockForUpdate()
                ->first();
        }

        if (! $profile || $profile->user_id !== null) {
            return null;
        }

        $profileReserved = DB::table(self::TABLE)
            ->where($roleName === 'siswa' ? 'siswa_id' : 'guru_id', $profile->getKey())
            ->exists();
        if ($profileReserved) {
            return null;
        }

        return $profile;
    }

    private function pendingProfileMatches(object $pending, string $roleName, int $profileId): bool
    {
        return $roleName === 'siswa'
            ? (int) $pending->siswa_id === $profileId && $pending->guru_id === null
            : (int) $pending->guru_id === $profileId && $pending->siswa_id === null;
    }

    private function sameMasterName(?string $accountName, ?string $masterName): bool
    {
        return Str::lower(trim((string) $accountName)) === Str::lower(trim((string) $masterName));
    }

    private function sendVerificationEmail(User $user, string $roleName, string $token): void
    {
        $verificationUrl = config('self_registration.verification_url');
        if (! is_string($verificationUrl) || trim($verificationUrl) === '') {
            Log::error('Self-registration verification URL is not configured.', ['role' => $roleName]);
            throw new VerificationEmailDeliveryFailed('URL verifikasi belum dikonfigurasi.');
        }
        if (str_starts_with($verificationUrl, '/')) {
            $verificationUrl = url($verificationUrl);
        }

        if (app()->environment('production') && $this->mailerCanLog((string) config('mail.default'))) {
            Log::error('Self-registration verification refused because production mailer is log.');
            throw new VerificationEmailDeliveryFailed('Mail transport production belum tersedia.');
        }

        $url = rtrim($verificationUrl, '/').'?'.http_build_query([
            'role' => $roleName,
            'token' => $token,
        ]);

        try {
            Mail::to($user->email)->send(new AccountVerificationMail(
                $url,
                self::TOKEN_EXPIRY_MINUTES,
                $roleName === 'siswa' ? 'Siswa' : 'Guru',
            ));
        } catch (\Throwable $exception) {
            Log::error('Self-registration verification email delivery failed.', [
                'exception' => $exception::class,
                'role' => $roleName,
            ]);
            throw new VerificationEmailDeliveryFailed('Email verifikasi gagal dikirim.', previous: $exception);
        }
    }

    private function mailerCanLog(string $mailerName, array $visited = []): bool
    {
        if (in_array($mailerName, $visited, true)) {
            return false;
        }

        $visited[] = $mailerName;
        $mailer = config('mail.mailers.'.$mailerName, []);
        if (($mailer['transport'] ?? null) === 'log') {
            return true;
        }

        foreach ($mailer['mailers'] ?? [] as $fallbackMailer) {
            if ($this->mailerCanLog((string) $fallbackMailer, $visited)) {
                return true;
            }
        }

        return false;
    }
}
