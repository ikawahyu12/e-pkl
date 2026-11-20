<?php

namespace Tests\Feature;

use App\Mail\AccountVerificationMail;
use App\Models\Role;
use App\Models\User;
use App\Services\SelfRegistrationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class SelfRegistrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        app('cache')->flush();

        config([
            'self_registration.verification_url' => 'http://localhost/verify-account',
        ]);

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('nama')->unique();
            $table->string('deskripsi')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password_hash');
            $table->string('nama_tampilan');
            $table->string('status')->default('aktif');
            $table->boolean('wajib_ganti_password')->default(false);
            $table->timestamp('email_terverifikasi_at')->nullable();
            $table->timestamp('terakhir_login_at')->nullable();
            $table->timestamps();
        });

        Schema::create('siswa', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->unique();
            $table->string('nisn')->unique();
            $table->string('nis')->nullable()->unique();
            $table->string('nama_lengkap');
            $table->unsignedBigInteger('kelas_id')->nullable();
            $table->string('status_siswa')->default('aktif');
            $table->timestamps();
        });

        Schema::create('guru', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->unique();
            $table->string('nip')->unique();
            $table->string('nama_lengkap');
            $table->string('status_bimbingan')->default('aktif');
            $table->timestamps();
        });

        Schema::create('kelas', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
        });

        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        DB::statement(
            'CREATE TABLE pending_account_verifications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL UNIQUE,
                role_id INTEGER NOT NULL,
                siswa_id INTEGER NULL,
                guru_id INTEGER NULL,
                token_hash VARCHAR(64) NOT NULL UNIQUE,
                expires_at DATETIME NOT NULL,
                created_at DATETIME NULL,
                UNIQUE(siswa_id),
                UNIQUE(guru_id),
                CHECK ((siswa_id IS NOT NULL AND guru_id IS NULL) OR
                    (siswa_id IS NULL AND guru_id IS NOT NULL))
            )'
        );

        foreach (['admin', 'guru', 'siswa'] as $name) {
            Role::query()->create(['nama' => $name]);
        }
    }

    public function test_siswa_and_guru_registration_send_one_time_verification_links(): void
    {
        Mail::fake();
        $profiles = [
            'siswa' => ['nisn' => '1234567890', 'nama_lengkap' => 'Siti Siswa'],
            'guru' => ['nip' => '198201012010011001', 'nama_lengkap' => 'Guntur Guru'],
        ];

        foreach ($profiles as $role => $identity) {
            $roleProfile = $role === 'siswa'
                ? ['nisn' => $identity['nisn'], 'nis' => 'S-100', 'nama_lengkap' => $identity['nama_lengkap']]
                : [
                    'nip' => $identity['nip'],
                    'nama_lengkap' => $identity['nama_lengkap'],
                    'status_bimbingan' => 'aktif',
                ];
            $role === 'siswa'
                ? DB::table('siswa')->insert($roleProfile + ['status_siswa' => 'aktif'])
                : DB::table('guru')->insert($roleProfile);
            $profileId = DB::table($role)->value('id');

            $email = $role.'@example.test';
            $response = $this->postJson('/api/v1/'.$role.'/auth/register', $identity + [
                'email' => $email,
                'password' => 'SecurePassword123',
                'password_confirmation' => 'SecurePassword123',
            ]);
            $response->assertAccepted()->assertJsonPath('success', true);

            $user = User::query()->where('email', $email)->firstOrFail();
            $this->assertSame('pending', $user->status);
            $this->assertNull($user->email_terverifikasi_at);
            $this->assertNull(DB::table($role)->where('id', $profileId)->value('user_id'));
            $this->assertDatabaseHas('pending_account_verifications', [
                'user_id' => $user->id,
                'role_id' => $user->role_id,
                $role.'_id' => $profileId,
            ]);
        }

        Mail::assertSent(AccountVerificationMail::class, 2);

        $messages = Mail::sent(AccountVerificationMail::class);
        foreach ($messages as $mail) {
            parse_str((string) parse_url($mail->verificationUrl, PHP_URL_QUERY), $query);
            $role = $query['role'];
            $token = $query['token'];
            $this->assertSame(64, strlen($token));

            $this->get('/verify-account?'.http_build_query($query))->assertOk();
            $this->assertSame(60, $mail->expiresInMinutes);
            $this->assertDatabaseHas('pending_account_verifications', [
                'token_hash' => hash('sha256', $token),
            ]);
            $this->assertNotSame($token, DB::table('pending_account_verifications')
                ->where('user_id', User::query()->where('email', $role.'@example.test')->value('id'))
                ->value('token_hash'));

            $this->postJson('/api/v1/'.$role.'/auth/verify-email', ['token' => $token])
                ->assertOk()
                ->assertJsonPath('success', true);

            $user = User::query()->where('email', $role.'@example.test')->firstOrFail();
            $this->assertSame('aktif', $user->status);
            $this->assertNotNull($user->email_terverifikasi_at);
            $this->assertSame(
                $user->id,
                DB::table($role)->where($role === 'siswa' ? 'nisn' : 'nip', $user->username)->value('user_id'),
            );
            $this->assertDatabaseMissing('pending_account_verifications', ['user_id' => $user->id]);
            $this->postJson('/api/v1/'.$role.'/auth/login', [
                'login' => $user->username,
                'password' => 'SecurePassword123',
            ])->assertOk()->assertJsonPath('success', true);
            $this->postJson('/api/v1/'.$role.'/auth/verify-email', ['token' => $token])
                ->assertUnprocessable()
                ->assertJsonPath('success', false);
        }
    }

    public function test_production_registration_rejects_non_https_verification_urls(): void
    {
        Mail::fake();
        $this->app->detectEnvironment(fn (): string => 'production');
        config([
            'mail.default' => 'array',
            'self_registration.verification_url' => 'http://localhost/verify-account',
        ]);
        DB::table('guru')->insert([
            'nip' => '198201012010019006',
            'nama_lengkap' => 'Guru Uji HTTPS',
            'status_bimbingan' => 'aktif',
        ]);

        $this->postJson('/api/v1/guru/auth/register', [
            'nip' => '198201012010019006',
            'nama_lengkap' => 'Guru Uji HTTPS',
            'email' => 'guru-http@example.test',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
        ])->assertServiceUnavailable();

        $this->assertDatabaseHas('users', [
            'email' => 'guru-http@example.test',
            'status' => 'pending',
        ]);
        Mail::assertNothingSent();
    }

    public function test_registration_does_not_create_account_when_master_identity_and_name_do_not_match(): void
    {
        Mail::fake();
        DB::table('siswa')->insert([
            'nisn' => '1234567890',
            'nis' => 'S-100',
            'nama_lengkap' => 'Siti Siswa',
            'status_siswa' => 'aktif',
        ]);

        $this->postJson('/api/v1/siswa/auth/register', [
            'nisn' => '1234567890',
            'nama_lengkap' => 'Nama Tidak Cocok',
            'email' => 'siswa@example.test',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
        ])->assertAccepted()->assertJsonPath('success', true);

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('pending_account_verifications', 0);
        Mail::assertNothingSent();
    }

    public function test_guru_registration_rejects_missing_mismatched_or_linked_master_data(): void
    {
        Mail::fake();
        $cases = [
            [
                'nip' => '198201012010019001',
                'email' => 'missing-guru@example.test',
                'master_name' => null,
                'submitted_name' => 'Guru Uji',
                'linked' => false,
            ],
            [
                'nip' => '198201012010019002',
                'email' => 'mismatched-guru@example.test',
                'master_name' => 'Nama Master',
                'submitted_name' => 'Nama Tidak Cocok',
                'linked' => false,
            ],
            [
                'nip' => '198201012010019003',
                'email' => 'linked-guru@example.test',
                'master_name' => 'Guru Terhubung',
                'submitted_name' => 'Guru Terhubung',
                'linked' => true,
            ],
        ];

        foreach ($cases as $case) {
            $linkedAccount = null;
            if ($case['master_name'] !== null) {
                if ($case['linked']) {
                    $linkedAccount = $this->createAccount('guru', $case['nip'], $case['master_name']);
                }

                DB::table('guru')->insert([
                    'user_id' => $linkedAccount?->id,
                    'nip' => $case['nip'],
                    'nama_lengkap' => $case['master_name'],
                    'status_bimbingan' => 'aktif',
                ]);
            }

            $this->postJson('/api/v1/guru/auth/register', [
                'nip' => $case['nip'],
                'nama_lengkap' => $case['submitted_name'],
                'email' => $case['email'],
                'password' => 'SecurePassword123',
                'password_confirmation' => 'SecurePassword123',
            ])->assertAccepted()->assertJsonPath('success', true);

            $this->assertDatabaseMissing('users', ['email' => $case['email']]);
            if ($linkedAccount !== null) {
                $this->assertDatabaseHas('guru', [
                    'nip' => $case['nip'],
                    'user_id' => $linkedAccount->id,
                ]);
            }
        }

        $this->assertDatabaseCount('pending_account_verifications', 0);
        Mail::assertNothingSent();
    }

    public function test_guru_registration_rejects_nonactive_mentoring_profiles(): void
    {
        Mail::fake();
        DB::table('guru')->insert([
            'nip' => '198201012010019004',
            'nama_lengkap' => 'Guru Nonaktif',
            'status_bimbingan' => 'nonaktif',
        ]);

        $this->postJson('/api/v1/guru/auth/register', [
            'nip' => '198201012010019004',
            'nama_lengkap' => 'Guru Nonaktif',
            'email' => 'nonactive-guru@example.test',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
        ])->assertAccepted()->assertJsonPath('success', true);

        $this->assertDatabaseMissing('users', ['email' => 'nonactive-guru@example.test']);
        $this->assertDatabaseCount('pending_account_verifications', 0);
        Mail::assertNothingSent();
    }

    public function test_guru_verification_and_resend_require_active_mentoring_status(): void
    {
        Mail::fake();
        $registration = $this->registerPendingProfile(
            'guru',
            '198201012010019005',
            'Guru Uji',
            'pending-guru@example.test',
        );
        DB::table('guru')
            ->where('id', $registration['profile_id'])
            ->update(['status_bimbingan' => 'nonaktif']);

        $this->postJson('/api/v1/guru/auth/resend-verification', [
            'email' => 'pending-guru@example.test',
        ])->assertAccepted();
        Mail::assertSent(AccountVerificationMail::class, 1);

        $this->postJson('/api/v1/guru/auth/verify-email', [
            'token' => $registration['token'],
        ])->assertUnprocessable();
        $this->assertDatabaseHas('users', [
            'id' => $registration['user_id'],
            'status' => 'pending',
        ]);
        $this->assertNull(
            DB::table('guru')->where('id', $registration['profile_id'])->value('user_id'),
        );
    }

    public function test_register_rejects_invalid_role_before_database_access(): void
    {
        $queryCount = 0;
        DB::listen(function () use (&$queryCount): void {
            $queryCount++;
        });

        try {
            app(SelfRegistrationService::class)->register('admin', []);
            $this->fail('An invalid role should be rejected.');
        } catch (InvalidArgumentException) {
            // Expected for a role outside the supported registration roles.
        }

        $this->assertSame(0, $queryCount);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('pending_account_verifications', 0);
    }

    public function test_resend_rejects_invalid_role_before_database_access(): void
    {
        $queryCount = 0;
        DB::listen(function () use (&$queryCount): void {
            $queryCount++;
        });

        try {
            app(SelfRegistrationService::class)->resend('admin', 'nobody@example.test');
            $this->fail('An invalid role should be rejected.');
        } catch (InvalidArgumentException) {
            // Expected for a role outside the supported registration roles.
        }

        $this->assertSame(0, $queryCount);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('pending_account_verifications', 0);
    }

    public function test_duplicate_email_does_not_link_another_master_profile(): void
    {
        Mail::fake();
        DB::table('siswa')->insert([
            [
                'nisn' => '1234567890',
                'nis' => 'S-100',
                'nama_lengkap' => 'Siti Siswa',
                'status_siswa' => 'aktif',
            ],
            [
                'nisn' => '1234567891',
                'nis' => 'S-101',
                'nama_lengkap' => 'Siti Kedua',
                'status_siswa' => 'aktif',
            ],
        ]);

        foreach ([
            ['nisn' => '1234567890', 'nama_lengkap' => 'Siti Siswa'],
            ['nisn' => '1234567891', 'nama_lengkap' => 'Siti Kedua'],
        ] as $identity) {
            $this->postJson('/api/v1/siswa/auth/register', $identity + [
                'email' => 'shared@example.test',
                'password' => 'SecurePassword123',
                'password_confirmation' => 'SecurePassword123',
            ])->assertAccepted()->assertJsonPath('success', true);
        }

        $this->assertDatabaseCount('users', 1);
        $this->assertSame(0, DB::table('siswa')->whereNotNull('user_id')->count());
        $this->assertDatabaseCount('pending_account_verifications', 1);
        Mail::assertSent(AccountVerificationMail::class, 1);
    }

    public function test_a_master_profile_can_only_be_reserved_once(): void
    {
        Mail::fake();
        DB::table('siswa')->insert([
            'nisn' => '1234567890',
            'nis' => 'S-100',
            'nama_lengkap' => 'Siti Siswa',
            'status_siswa' => 'aktif',
        ]);

        foreach (['first@example.test', 'second@example.test'] as $email) {
            $this->postJson('/api/v1/siswa/auth/register', [
                'nisn' => '1234567890',
                'nama_lengkap' => 'Siti Siswa',
                'email' => $email,
                'password' => 'SecurePassword123',
                'password_confirmation' => 'SecurePassword123',
            ])->assertAccepted();
        }

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('pending_account_verifications', 1);
        $this->assertNull(DB::table('siswa')->value('user_id'));
        Mail::assertSent(AccountVerificationMail::class, 1);
    }

    public function test_master_identity_or_name_changes_are_rejected_during_verification(): void
    {
        Mail::fake();
        $admin = $this->createAccount('admin', 'master-admin', 'Admin');
        $this->actingAs($admin, 'sanctum');
        $cases = [
            ['siswa', 'nisn', '1234567890', 'Siti Siswa', '1234567899', 'Siti Siswa'],
            ['siswa', 'nisn', '1234567891', 'Siti Kedua', '1234567891', 'Nama Baru'],
            ['guru', 'nip', '198201012010011001', 'Guntur Guru', '198201012010011099', 'Guntur Guru'],
            ['guru', 'nip', '198201012010011002', 'Guru Kedua', '198201012010011002', 'Nama Baru'],
        ];

        foreach ($cases as $index => [$role, $identityKey, $identity, $name, $changedIdentity, $changedName]) {
            $email = 'changed-'.$index.'@example.test';
            $registration = $this->registerPendingProfile($role, $identity, $name, $email);
            $update = $changedIdentity !== $identity
                ? ['username' => $changedIdentity]
                : ['nama' => $changedName];
            $this->patchJson('/api/v1/admin/'.($role === 'siswa' ? 'students' : 'teachers').'/'.$registration['user_id'], $update)
                ->assertOk()
                ->assertJsonPath('success', true);

            $profile = DB::table($role)->where('id', $registration['profile_id'])->first();
            $this->assertSame(
                $changedIdentity,
                $identityKey === 'nisn' ? $profile->nisn : $profile->nip,
            );
            $this->assertSame($changedName, $profile->nama_lengkap);
            $this->assertDatabaseHas($role, [
                'id' => $registration['profile_id'],
                $identityKey => $changedIdentity,
                'nama_lengkap' => $changedName,
            ]);
            $this->assertDatabaseMissing('users', ['id' => $registration['user_id']]);
            $this->assertDatabaseMissing('pending_account_verifications', [
                'user_id' => $registration['user_id'],
            ]);

            $this->postJson('/api/v1/'.$role.'/auth/verify-email', ['token' => $registration['token']])
                ->assertUnprocessable()
                ->assertJsonPath('success', false);

            $this->assertNull(DB::table($role)->where('id', $registration['profile_id'])->value('user_id'));
        }
    }

    public function test_master_attribute_change_keeps_pending_reservation(): void
    {
        Mail::fake();
        $admin = $this->createAccount('admin', 'class-admin', 'Admin');
        $this->actingAs($admin, 'sanctum');
        $classId = DB::table('kelas')->insertGetId(['nama' => 'XII RPL']);
        $registration = $this->registerPendingProfile(
            'siswa',
            '1234567890',
            'Siti Siswa',
            'class-siswa@example.test',
        );
        $createdAt = DB::table('pending_account_verifications')
            ->where('user_id', $registration['user_id'])
            ->value('created_at');

        $this->patchJson('/api/v1/admin/students/'.$registration['user_id'], ['kelas_id' => $classId])
            ->assertOk();

        $this->assertDatabaseHas('users', ['id' => $registration['user_id'], 'status' => 'pending']);
        $this->assertDatabaseHas('pending_account_verifications', [
            'user_id' => $registration['user_id'],
            'created_at' => $createdAt,
        ]);
        $this->postJson('/api/v1/siswa/auth/verify-email', ['token' => $registration['token']])
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_resend_preserves_registration_age_and_rejects_mismatched_master(): void
    {
        Mail::fake();
        $registration = $this->registerPendingProfile(
            'siswa',
            '1234567890',
            'Siti Siswa',
            'resend-siswa@example.test',
        );
        $createdAt = DB::table('pending_account_verifications')
            ->where('user_id', $registration['user_id'])
            ->value('created_at');

        $this->postJson('/api/v1/siswa/auth/resend-verification', [
            'email' => 'resend-siswa@example.test',
        ])->assertAccepted();

        $pending = DB::table('pending_account_verifications')
            ->where('user_id', $registration['user_id'])
            ->first();
        $this->assertSame($createdAt, $pending->created_at);
        $this->assertTrue(
            Carbon::parse($pending->expires_at)->between(
                now()->addMinutes(59),
                now()->addMinutes(60),
            ),
        );
        $newToken = $this->mailToken(Mail::sent(AccountVerificationMail::class)->last());
        $this->postJson('/api/v1/siswa/auth/verify-email', ['token' => $registration['token']])
            ->assertUnprocessable();
        $this->postJson('/api/v1/siswa/auth/verify-email', ['token' => $newToken])
            ->assertOk();

        $mismatched = $this->registerPendingProfile(
            'guru',
            '198201012010011001',
            'Guntur Guru',
            'changed-guru@example.test',
        );
        DB::table('guru')->where('id', $mismatched['profile_id'])->update(['nip' => '198201012010011099']);
        $sentBefore = Mail::sent(AccountVerificationMail::class)->count();
        $this->postJson('/api/v1/guru/auth/resend-verification', [
            'email' => 'changed-guru@example.test',
        ])->assertAccepted();
        $this->assertCount($sentBefore, Mail::sent(AccountVerificationMail::class));
        $this->assertDatabaseHas('pending_account_verifications', ['user_id' => $mismatched['user_id']]);
    }

    public function test_accounts_at_seven_day_limit_cannot_be_resent_or_verified(): void
    {
        Mail::fake();
        $registration = $this->registerPendingProfile(
            'siswa',
            '1234567890',
            'Siti Siswa',
            'old-siswa@example.test',
        );
        DB::table('pending_account_verifications')
            ->where('user_id', $registration['user_id'])
            ->update(['created_at' => now()->subDays(7)]);
        $sentBefore = Mail::sent(AccountVerificationMail::class)->count();

        $this->postJson('/api/v1/siswa/auth/resend-verification', [
            'email' => 'old-siswa@example.test',
        ])->assertAccepted();
        $this->assertCount($sentBefore, Mail::sent(AccountVerificationMail::class));

        $this->postJson('/api/v1/siswa/auth/verify-email', ['token' => $registration['token']])
            ->assertUnprocessable();
        $this->assertDatabaseHas('users', ['id' => $registration['user_id'], 'status' => 'pending']);
    }

    public function test_cleanup_deletes_only_stale_pending_accounts_and_preserves_profiles(): void
    {
        Mail::fake();
        $stale = $this->registerPendingProfile(
            'siswa',
            '1234567890',
            'Siti Siswa',
            'stale-siswa@example.test',
        );
        DB::table('pending_account_verifications')
            ->where('user_id', $stale['user_id'])
            ->update(['created_at' => now()->subDays(7)]);
        DB::table('siswa')->where('id', $stale['profile_id'])->update(['user_id' => $stale['user_id']]);

        $active = $this->createAccount('guru', 'active-guru', 'Guru Aktif');
        DB::table('guru')->insert([
            'user_id' => $active->id,
            'nip' => $active->username,
            'nama_lengkap' => 'Guru Aktif',
            'status_bimbingan' => 'aktif',
        ]);
        $activeProfileId = DB::table('guru')->where('user_id', $active->id)->value('id');
        DB::table('pending_account_verifications')->insert([
            'user_id' => $active->id,
            'role_id' => $active->role_id,
            'siswa_id' => null,
            'guru_id' => $activeProfileId,
            'token_hash' => hash('sha256', 'active-stale-reservation'),
            'expires_at' => now()->subDays(6),
            'created_at' => now()->subDays(7),
        ]);

        $verified = $this->createAccount('siswa', 'verified-siswa', 'Siswa Terverifikasi');
        $verified->status = 'pending';
        $verified->email_terverifikasi_at = now();
        $verified->save();
        DB::table('siswa')->insert([
            'user_id' => $verified->id,
            'nisn' => $verified->username,
            'nis' => 'S-VERIFIED',
            'nama_lengkap' => 'Siswa Terverifikasi',
            'status_siswa' => 'aktif',
        ]);
        $verifiedProfileId = DB::table('siswa')->where('user_id', $verified->id)->value('id');
        DB::table('pending_account_verifications')->insert([
            'user_id' => $verified->id,
            'role_id' => $verified->role_id,
            'siswa_id' => $verifiedProfileId,
            'guru_id' => null,
            'token_hash' => hash('sha256', 'verified-stale-reservation'),
            'expires_at' => now()->subDays(6),
            'created_at' => now()->subDays(7),
        ]);

        $unknownAge = $this->createAccount('guru', 'unknown-age-guru', 'Guru Tanpa Waktu');
        $unknownAge->status = 'pending';
        $unknownAge->save();
        DB::table('pending_account_verifications')->insert([
            'user_id' => $unknownAge->id,
            'role_id' => $unknownAge->role_id,
            'siswa_id' => null,
            'guru_id' => DB::table('guru')->insertGetId([
                'nip' => $unknownAge->username,
                'nama_lengkap' => 'Guru Tanpa Waktu',
                'status_bimbingan' => 'aktif',
            ]),
            'token_hash' => hash('sha256', 'unknown-age-reservation'),
            'expires_at' => now()->subDays(6),
            'created_at' => null,
        ]);

        $this->assertSame(0, Artisan::call('epkl:cleanup-pending-accounts'));
        $this->assertStringContainsString('Cleaned: 1; skipped: 3.', Artisan::output());
        $this->assertDatabaseMissing('users', ['id' => $stale['user_id']]);
        $this->assertDatabaseMissing('pending_account_verifications', ['user_id' => $stale['user_id']]);
        $this->assertDatabaseHas('siswa', ['id' => $stale['profile_id'], 'user_id' => null]);
        $this->assertDatabaseHas('users', ['id' => $active->id, 'status' => 'aktif']);
        $this->assertDatabaseHas('guru', ['id' => $activeProfileId, 'user_id' => $active->id]);
        $this->assertDatabaseHas('pending_account_verifications', ['user_id' => $active->id]);
        $this->assertDatabaseHas('users', [
            'id' => $verified->id,
            'status' => 'pending',
        ]);
        $this->assertNotNull($verified->fresh()->email_terverifikasi_at);
        $this->assertDatabaseHas('siswa', ['id' => $verifiedProfileId, 'user_id' => $verified->id]);
        $this->assertDatabaseHas('pending_account_verifications', ['user_id' => $unknownAge->id]);

        $this->assertSame(0, Artisan::call('epkl:cleanup-pending-accounts'));
        $this->assertStringContainsString('Cleaned: 0; skipped: 3.', Artisan::output());
        $this->assertDatabaseHas('users', ['id' => $active->id, 'status' => 'aktif']);
    }

    public function test_email_delivery_failure_keeps_registration_pending(): void
    {
        DB::table('siswa')->insert([
            'nisn' => '1234567890',
            'nis' => 'S-100',
            'nama_lengkap' => 'Siti Siswa',
            'status_siswa' => 'aktif',
        ]);
        Mail::shouldReceive('to')
            ->once()
            ->with('delivery-failure@example.test')
            ->andThrow(new RuntimeException('SMTP unavailable'));

        $this->postJson('/api/v1/siswa/auth/register', [
            'nisn' => '1234567890',
            'nama_lengkap' => 'Siti Siswa',
            'email' => 'delivery-failure@example.test',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
        ])->assertServiceUnavailable();

        $user = User::query()->where('email', 'delivery-failure@example.test')->firstOrFail();
        $this->assertSame('pending', $user->status);
        $this->assertNull($user->email_terverifikasi_at);
        $this->assertNull(DB::table('siswa')->value('user_id'));
        $this->assertDatabaseHas('pending_account_verifications', ['user_id' => $user->id]);
    }

    public function test_production_mailer_with_log_fallback_is_rejected(): void
    {
        Mail::fake();
        app()->detectEnvironment(fn (): string => 'production');
        config([
            'mail.default' => 'failover',
            'mail.mailers.failover' => [
                'transport' => 'failover',
                'mailers' => ['smtp', 'log'],
            ],
        ]);
        DB::table('siswa')->insert([
            'nisn' => '1234567890',
            'nis' => 'S-100',
            'nama_lengkap' => 'Siti Siswa',
            'status_siswa' => 'aktif',
        ]);

        $this->postJson('/api/v1/siswa/auth/register', [
            'nisn' => '1234567890',
            'nama_lengkap' => 'Siti Siswa',
            'email' => 'production-siswa@example.test',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
        ])->assertServiceUnavailable();

        Mail::assertNothingSent();
        $this->assertDatabaseHas('users', [
            'email' => 'production-siswa@example.test',
            'status' => 'pending',
        ]);
    }

    public function test_verification_cannot_take_over_a_profile_linked_to_another_account(): void
    {
        Mail::fake();
        foreach ([
            ['siswa', '1234567890', 'Siti Siswa'],
            ['guru', '198201012010011001', 'Guntur Guru'],
        ] as [$role, $identity, $name]) {
            $registration = $this->registerPendingProfile(
                $role,
                $identity,
                $name,
                'pending-'.$role.'@example.test',
            );
            $otherAccount = $this->createAccount($role, 'other-'.$role, 'Akun Lain');
            DB::table($role)->where('id', $registration['profile_id'])->update([
                'user_id' => $otherAccount->id,
            ]);

            $this->postJson('/api/v1/'.$role.'/auth/verify-email', ['token' => $registration['token']])
                ->assertUnprocessable()
                ->assertJsonPath('success', false);

            $this->assertDatabaseHas('users', ['id' => $registration['user_id'], 'status' => 'pending']);
            $this->assertDatabaseHas($role, [
                'id' => $registration['profile_id'],
                'user_id' => $otherAccount->id,
            ]);
        }
    }

    public function test_verification_can_finish_for_a_legacy_profile_linked_to_the_same_pending_account(): void
    {
        Mail::fake();
        $registration = $this->registerPendingProfile(
            'siswa',
            '1234567890',
            'Siti Siswa',
            'legacy-link-siswa@example.test',
        );
        DB::table('siswa')
            ->where('id', $registration['profile_id'])
            ->update(['user_id' => $registration['user_id']]);

        $this->postJson('/api/v1/siswa/auth/verify-email', ['token' => $registration['token']])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('users', ['id' => $registration['user_id'], 'status' => 'aktif']);
        $this->assertDatabaseHas('siswa', [
            'id' => $registration['profile_id'],
            'user_id' => $registration['user_id'],
        ]);
    }

    public function test_inactive_student_master_profile_cannot_be_activated(): void
    {
        Mail::fake();
        $registration = $this->registerPendingProfile(
            'siswa',
            '1234567890',
            'Siti Siswa',
            'inactive-siswa@example.test',
        );
        DB::table('siswa')->where('id', $registration['profile_id'])->update(['status_siswa' => 'nonaktif']);

        $this->postJson('/api/v1/siswa/auth/verify-email', ['token' => $registration['token']])
            ->assertUnprocessable()
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('users', ['id' => $registration['user_id'], 'status' => 'pending']);
        $this->assertNull(DB::table('siswa')->where('id', $registration['profile_id'])->value('user_id'));
    }

    public function test_legacy_active_accounts_can_login_without_email_verification_timestamp(): void
    {
        $student = $this->createAccount('siswa', 'legacy-student', 'Siswa Lama');
        $teacher = $this->createAccount('guru', 'legacy-teacher', 'Guru Lama');
        DB::table('siswa')->insert([
            'user_id' => $student->id,
            'nisn' => $student->username,
            'nis' => 'S-LAMA',
            'nama_lengkap' => 'Siswa Lama',
            'status_siswa' => 'aktif',
        ]);
        DB::table('guru')->insert([
            'user_id' => $teacher->id,
            'nip' => $teacher->username,
            'nama_lengkap' => 'Guru Lama',
            'status_bimbingan' => 'aktif',
        ]);

        $this->postJson('/api/v1/siswa/auth/login', [
            'login' => $student->username,
            'password' => 'LegacyPassword123',
        ])->assertOk()->assertJsonPath('success', true);
        $this->postJson('/api/v1/guru/auth/login', [
            'login' => $teacher->username,
            'password' => 'LegacyPassword123',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertNull($student->fresh()->email_terverifikasi_at);
        $this->assertNull($teacher->fresh()->email_terverifikasi_at);
    }

    public function test_teacher_with_nonactive_mentoring_status_cannot_login(): void
    {
        $teacher = $this->createAccount('guru', 'inactive-legacy-teacher', 'Guru Nonaktif');
        DB::table('guru')->insert([
            'user_id' => $teacher->id,
            'nip' => $teacher->username,
            'nama_lengkap' => 'Guru Nonaktif',
            'status_bimbingan' => 'nonaktif',
        ]);

        $this->postJson('/api/v1/guru/auth/login', [
            'login' => $teacher->username,
            'password' => 'LegacyPassword123',
        ])->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_guru_can_login_with_nip_or_case_insensitive_email(): void
    {
        $teacher = $this->createAccount('guru', '198201012010019007', 'Guru Login');
        $teacher->email = 'guru.login@example.test';
        $teacher->email_terverifikasi_at = now();
        $teacher->save();
        DB::table('guru')->insert([
            'user_id' => $teacher->id,
            'nip' => '198201012010019007',
            'nama_lengkap' => 'Guru Login',
            'status_bimbingan' => 'aktif',
        ]);

        foreach (['198201012010019007', 'GURU.LOGIN@EXAMPLE.TEST'] as $identifier) {
            $this->postJson('/api/v1/guru/auth/login', [
                'login' => $identifier,
                'password' => 'LegacyPassword123',
            ])->assertOk()->assertJsonPath('success', true);
        }
    }

    public function test_pending_accounts_cannot_login_and_resend_response_is_generic(): void
    {
        Mail::fake();
        $expected = [
            'success' => true,
            'message' => 'Jika ada akun yang menunggu verifikasi, tautan baru akan dikirim.',
            'data' => null,
        ];
        foreach ([
            ['siswa', '1234567890', 'Siti Siswa'],
            ['guru', '198201012010011001', 'Guntur Guru'],
        ] as [$role, $identity, $name]) {
            $email = $role.'@example.test';
            $registration = $this->registerPendingProfile($role, $identity, $name, $email);

            $this->postJson('/api/v1/'.$role.'/auth/login', [
                'login' => $identity,
                'password' => 'SecurePassword123',
            ])->assertUnauthorized();

            $unknownResponse = $this->postJson('/api/v1/'.$role.'/auth/resend-verification', [
                'email' => 'unknown-'.$role.'@example.test',
            ])->assertAccepted()->assertExactJson($expected);
            $this->postJson('/api/v1/'.$role.'/auth/resend-verification', [
                'email' => $email,
            ])->assertAccepted()->assertExactJson($expected);
            $this->assertSame($unknownResponse->json(), $expected);

            $newToken = $this->mailToken(Mail::sent(AccountVerificationMail::class)->last());
            $this->assertNotSame($registration['token'], $newToken);
            $this->postJson('/api/v1/'.$role.'/auth/verify-email', ['token' => $registration['token']])
                ->assertUnprocessable();
            $this->postJson('/api/v1/'.$role.'/auth/verify-email', ['token' => $newToken])
                ->assertOk()
                ->assertJsonPath('success', true);
        }
    }

    public function test_expired_verification_token_is_rejected(): void
    {
        Mail::fake();
        foreach ([
            ['siswa', '1234567890', 'Siti Siswa'],
            ['guru', '198201012010011001', 'Guntur Guru'],
        ] as [$role, $identity, $name]) {
            $registration = $this->registerPendingProfile(
                $role,
                $identity,
                $name,
                'expired-'.$role.'@example.test',
            );

            $this->postJson('/api/v1/'.$role.'/auth/verify-email', ['token' => 'invalid-token'])
                ->assertUnprocessable()
                ->assertJsonPath('success', false);
            $this->postJson('/api/v1/'.($role === 'siswa' ? 'guru' : 'siswa').'/auth/verify-email', [
                'token' => $registration['token'],
            ])->assertUnprocessable();

            DB::table('pending_account_verifications')
                ->where('user_id', $registration['user_id'])
                ->update(['expires_at' => now()->subMinute()]);

            $this->postJson('/api/v1/'.$role.'/auth/verify-email', ['token' => $registration['token']])
                ->assertUnprocessable()
                ->assertJsonPath('success', false);
            $this->assertDatabaseHas('users', [
                'id' => $registration['user_id'],
                'status' => 'pending',
            ]);
            $this->assertNull(DB::table($role)->where('id', $registration['profile_id'])->value('user_id'));
        }
    }

    private function registerPendingProfile(string $role, string $identity, string $name, string $email): array
    {
        $identityKey = $role === 'siswa' ? 'nisn' : 'nip';
        $profile = [
            $identityKey => $identity,
            'nama_lengkap' => $name,
        ];
        if ($role === 'siswa') {
            $profile['nis'] = 'S-'.substr($identity, -4);
            $profile['status_siswa'] = 'aktif';
        }
        DB::table($role)->insert($profile);
        $profileId = DB::table($role)->where($identityKey, $identity)->value('id');

        $this->postJson('/api/v1/'.$role.'/auth/register', [
            $identityKey => $identity,
            'nama_lengkap' => $name,
            'email' => $email,
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
        ])->assertAccepted();

        $user = User::query()->where('email', $email)->firstOrFail();
        $mail = Mail::sent(AccountVerificationMail::class)->last();

        return [
            'profile_id' => $profileId,
            'user_id' => $user->id,
            'token' => $this->mailToken($mail),
        ];
    }

    private function mailToken(AccountVerificationMail $mail): string
    {
        parse_str((string) parse_url($mail->verificationUrl, PHP_URL_QUERY), $query);

        return $query['token'];
    }

    private function createAccount(string $roleName, string $username, string $name): User
    {
        $role = Role::query()->where('nama', $roleName)->firstOrFail();
        $user = new User;
        $user->role_id = $role->id;
        $user->username = $username;
        $user->email = $username.'@example.test';
        $user->password_hash = Hash::make('LegacyPassword123');
        $user->nama_tampilan = $name;
        $user->status = 'aktif';
        $user->email_terverifikasi_at = null;
        $user->save();

        return $user;
    }
}
