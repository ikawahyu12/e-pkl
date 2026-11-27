<?php

namespace Tests\Feature;

use App\Mail\PasswordResetMail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'password_reset.frontends.admin' => 'https://admin.example.test/index.php?page=reset-password',
            'password_reset.frontends.siswa' => 'https://siswa.example.test/index.php?page=reset-password',
            'password_reset.frontends.guru' => 'epkl://guru/reset-password',
            'password_reset.frontends.guru_fallback' => 'https://api.example.test/password-reset/guru',
            'password_reset.request_cooldown_seconds' => 60,
        ]);

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('nama')->unique();
            $table->string('deskripsi')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->string('username')->unique();
            $table->string('email')->nullable()->unique();
            $table->string('password_hash')->nullable();
            $table->string('nama_tampilan');
            $table->string('status')->default('aktif');
            $table->boolean('wajib_ganti_password')->default(false);
            $table->timestamp('email_terverifikasi_at')->nullable();
            $table->timestamp('terakhir_login_at')->nullable();
            $table->timestamps();
        });

        Schema::create('guru', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('nip')->unique();
            $table->string('nama_lengkap');
            $table->string('status_bimbingan')->default('aktif');
            $table->timestamps();
        });

        Schema::create('siswa', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('nis')->unique();
            $table->string('nisn')->nullable();
            $table->string('nama_lengkap')->nullable();
            $table->string('status_siswa')->default('aktif');
            $table->timestamps();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->morphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('epkl_password_reset_tokens', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('email');
            $table->string('role');
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at')->index();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['user_id', 'created_at']);
        });

        foreach (['admin', 'guru', 'siswa'] as $name) {
            Role::query()->create(['nama' => $name]);
        }
    }

    public function test_reset_link_requests_are_generic_and_send_email_for_each_role(): void
    {
        Mail::fake();
        $expectedMessage = 'Jika alamat email terdaftar, instruksi reset kata sandi akan dikirim.';

        foreach (['admin', 'guru', 'siswa'] as $role) {
            $user = $this->createAccount($role);

            $this->postJson('/api/v1/'.$role.'/auth/forgot-password', [
                'email' => $user->email,
            ])->assertAccepted()
                ->assertExactJson([
                    'success' => true,
                    'message' => $expectedMessage,
                    'data' => null,
                ]);
        }

        Mail::assertSent(PasswordResetMail::class, 3);

        $sentMail = Mail::sent(PasswordResetMail::class);
        $this->assertCount(3, $sentMail);

        foreach ($sentMail as $mail) {
            $query = [];
            parse_str((string) parse_url($mail->resetUrl, PHP_URL_QUERY), $query);
            $this->assertSame(64, strlen($query['token'] ?? ''));
            $this->assertDatabaseHas('epkl_password_reset_tokens', [
                'email' => $query['email'],
                'token_hash' => hash('sha256', $query['token']),
            ]);
            $this->assertDatabaseMissing('epkl_password_reset_tokens', [
                'email' => $query['email'],
                'token_hash' => $query['token'],
            ]);
            if ($query['email'] === 'guru@example.test') {
                $this->assertNotNull($mail->browserFallbackUrl);
            }
        }

        $this->postJson('/api/v1/siswa/auth/forgot-password', [
            'email' => 'unknown@example.test',
        ])->assertAccepted()
            ->assertExactJson([
                'success' => true,
                'message' => $expectedMessage,
                'data' => null,
            ]);

        Mail::assertSent(PasswordResetMail::class, 3);
    }

    public function test_reset_link_is_not_sent_for_a_nonactive_teacher_profile(): void
    {
        Mail::fake();
        $teacher = $this->createAccount('guru');
        DB::table('guru')->where('user_id', $teacher->id)->update([
            'status_bimbingan' => 'nonaktif',
        ]);

        $this->postJson('/api/v1/guru/auth/forgot-password', [
            'email' => $teacher->email,
        ])->assertAccepted();

        Mail::assertNothingSent();
        $this->assertDatabaseMissing('epkl_password_reset_tokens', [
            'user_id' => $teacher->id,
        ]);
    }

    public function test_valid_reset_changes_password_once_and_revokes_old_auth_tokens_for_all_roles(): void
    {
        foreach (['admin', 'guru', 'siswa'] as $role) {
            app('cache')->flush();
            $user = $this->createAccount($role);
            $oldToken = $user->createToken('old-device');
            $token = bin2hex(random_bytes(32));
            DB::table('epkl_password_reset_tokens')->insert([
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $role,
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addHour(),
                'created_at' => now(),
            ]);

            $this->postJson('/api/v1/'.$role.'/auth/reset-password', [
                'email' => $user->email,
                'token' => $token,
                'password_baru' => 'NewPassword123',
                'password_baru_confirmation' => 'NewPassword123',
            ])->assertOk()
                ->assertJsonPath('success', true);

            $user->refresh();
            $this->assertTrue(Hash::check('NewPassword123', $user->password_hash));
            $this->assertFalse($user->wajib_ganti_password);
            $this->assertSame(0, $user->tokens()->count());
            $this->assertDatabaseMissing('epkl_password_reset_tokens', [
                'user_id' => $user->id,
            ]);
            $this->assertDatabaseMissing('personal_access_tokens', [
                'id' => $oldToken->accessToken->id,
            ]);

            $this->app['auth']->forgetGuards();
            $login = $this->postJson('/api/v1/'.$role.'/auth/login', [
                'login' => $user->username,
                'password' => 'NewPassword123',
            ]);
            $login->assertOk()
                ->assertJsonPath('data.user.role', $role);

            $this->app['auth']->forgetGuards();
            $this->postJson('/api/v1/'.$role.'/auth/login', [
                'login' => $user->username,
                'password' => 'OldPassword123',
            ])->assertUnauthorized();
        }
    }

    public function test_invalid_expired_and_reused_tokens_do_not_change_password(): void
    {
        $user = $this->createAccount('admin');
        $validToken = str_repeat('a', 64);
        $expiredToken = str_repeat('b', 64);
        $wrongToken = str_repeat('c', 64);
        $this->insertToken($user, $validToken, now()->addHour());
        $this->insertToken($user, $expiredToken, now()->subSecond());

        $this->postJson('/api/v1/admin/auth/reset-password', [
            'email' => $user->email,
            'token' => $wrongToken,
            'password_baru' => 'NewPassword123',
            'password_baru_confirmation' => 'NewPassword123',
        ])->assertUnprocessable();

        $this->postJson('/api/v1/admin/auth/reset-password', [
            'email' => $user->email,
            'token' => $expiredToken,
            'password_baru' => 'NewPassword123',
            'password_baru_confirmation' => 'NewPassword123',
        ])->assertUnprocessable();

        $this->postJson('/api/v1/admin/auth/reset-password', [
            'email' => $user->email,
            'token' => $validToken,
            'password_baru' => 'NewPassword123',
            'password_baru_confirmation' => 'DoesNotMatch123',
        ])->assertUnprocessable();

        $this->assertTrue(Hash::check('OldPassword123', $user->fresh()->password_hash));
    }

    public function test_reset_token_cannot_be_reused_and_other_users_tokens_are_preserved(): void
    {
        $user = $this->createAccount('guru');
        $otherUser = $this->createAccount('siswa');
        $singleUseToken = str_repeat('d', 64);
        $otherUserToken = str_repeat('e', 64);
        $this->insertToken($user, $singleUseToken, now()->addHour());
        $this->insertToken($otherUser, $otherUserToken, now()->addHour());

        $payload = [
            'email' => $user->email,
            'token' => $singleUseToken,
            'password_baru' => 'NewPassword123',
            'password_baru_confirmation' => 'NewPassword123',
        ];

        $this->postJson('/api/v1/guru/auth/reset-password', $payload)->assertOk();
        $this->postJson('/api/v1/guru/auth/reset-password', $payload)->assertUnprocessable();
        $this->assertDatabaseHas('epkl_password_reset_tokens', [
            'user_id' => $otherUser->id,
            'token_hash' => hash('sha256', $otherUserToken),
        ]);
    }

    private function createAccount(string $role): User
    {
        $user = new User;
        $user->role_id = Role::query()->where('nama', $role)->value('id');
        $user->username = 'user-'.$role;
        $user->email = $role.'@example.test';
        $user->password_hash = Hash::make('OldPassword123');
        $user->nama_tampilan = ucfirst($role).' User';
        $user->status = 'aktif';
        $user->wajib_ganti_password = true;
        $user->save();

        if ($role === 'guru') {
            DB::table('guru')->insert([
                'user_id' => $user->id,
                'nip' => 'NIP-'.$user->id,
                'nama_lengkap' => $user->nama_tampilan,
                'status_bimbingan' => 'aktif',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } elseif ($role === 'siswa') {
            DB::table('siswa')->insert([
                'user_id' => $user->id,
                'nis' => 'NIS-'.$user->id,
                'nama_lengkap' => $user->nama_tampilan,
                'status_siswa' => 'aktif',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $user;
    }

    private function insertToken(User $user, string $token, \DateTimeInterface $expiresAt): void
    {
        DB::table('epkl_password_reset_tokens')->insert([
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role->nama,
            'token_hash' => hash('sha256', $token),
            'expires_at' => $expiresAt,
            'created_at' => now(),
        ]);
    }
}
