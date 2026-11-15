<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PasswordChangeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->string('deskripsi')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password_hash');
            $table->string('nama_tampilan');
            $table->string('foto_profil')->nullable();
            $table->string('status')->default('aktif');
            $table->boolean('wajib_ganti_password')->default(true);
            $table->timestamp('email_terverifikasi_at')->nullable();
            $table->timestamp('terakhir_login_at')->nullable();
            $table->string('terakhir_login_ip')->nullable();
            $table->string('perangkat_terakhir')->nullable();
            $table->timestamps();
        });

        Schema::create('guru', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('nip')->unique();
            $table->string('status_bimbingan')->default('aktif');
            $table->timestamps();
        });

        Schema::create('siswa', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('nis')->unique();
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
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });

        foreach (['admin', 'guru', 'siswa'] as $role) {
            DB::table('roles')->insert([
                'nama' => $role,
                'deskripsi' => null,
            ]);
        }
    }

    public function test_all_roles_can_change_password_and_other_tokens_are_revoked(): void
    {
        $scenarios = [
            [
                'role' => 'admin',
                'role_id' => 1,
                'endpoint' => '/api/v1/admin/auth/password',
            ],
            [
                'role' => 'guru',
                'role_id' => 2,
                'endpoint' => '/api/v1/guru/auth/password',
            ],
            [
                'role' => 'siswa',
                'role_id' => 3,
                'endpoint' => '/api/v1/siswa/profil/password',
            ],
        ];

        foreach ($scenarios as $scenario) {
            $userId = DB::table('users')->insertGetId([
                'role_id' => $scenario['role_id'],
                'username' => 'test.' . $scenario['role'],
                'email' => 'test.' . $scenario['role'] . '@example.test',
                'password_hash' => Hash::make('PasswordLama123'),
                'nama_tampilan' => 'Test ' . ucfirst($scenario['role']),
                'status' => 'aktif',
                'wajib_ganti_password' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($scenario['role'] === 'guru') {
                DB::table('guru')->insert([
                    'user_id' => $userId,
                    'nip' => 'NIP-TEST-001',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($scenario['role'] === 'siswa') {
                DB::table('siswa')->insert([
                    'user_id' => $userId,
                    'nis' => 'NIS-TEST-001',
                    'status_siswa' => 'aktif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $user = User::findOrFail($userId);

            $currentToken = $user->createToken('current-device');
            $user->createToken('other-device');

            // Reset guard agar setiap skenario menggunakan pengguna dari token terbaru.
            $this->app['auth']->forgetGuards();

            $response = $this->withToken($currentToken->plainTextToken)
                ->postJson($scenario['endpoint'], [
                    'password_lama' => 'PasswordLama123',
                    'password_baru' => 'PasswordBaru123',
                    'password_baru_confirmation' => 'PasswordBaru123',
                ]);

            if ($response->status() !== 200) {
                fwrite(STDERR, "\nROLE: " . $scenario['role']);
                fwrite(STDERR, "\nSTATUS: " . $response->status());
                fwrite(STDERR, "\nBODY: " . $response->getContent() . "\n");
            }

            $response
                ->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonPath('message', 'Kata sandi berhasil diubah.');

            $user->refresh();

            $this->assertFalse($user->wajib_ganti_password);
            $this->assertTrue(
                Hash::check('PasswordBaru123', $user->password_hash)
            );

            $this->assertDatabaseHas('personal_access_tokens', [
                'id' => $currentToken->accessToken->id,
            ]);

            $this->assertDatabaseMissing('personal_access_tokens', [
                'name' => 'other-device',
                'tokenable_id' => $userId,
            ]);

            $this->assertSame(1, $user->tokens()->count());
        }
    }

    public function test_student_with_required_password_change_cannot_use_attendance_endpoint(): void
    {
        $userId = DB::table('users')->insertGetId([
            'role_id' => 3,
            'username' => 'forced.student',
            'email' => 'forced.student@example.test',
            'password_hash' => Hash::make('PasswordLama123'),
            'nama_tampilan' => 'Forced Student',
            'status' => 'aktif',
            'wajib_ganti_password' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('siswa')->insert([
            'user_id' => $userId,
            'nis' => 'FORCED-001',
            'status_siswa' => 'aktif',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::query()->findOrFail($userId);
        $token = $user->createToken('forced-student')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/siswa/presensi', [])
            ->assertForbidden()
            ->assertJsonPath('code', 'PASSWORD_CHANGE_REQUIRED');
    }

    public function test_password_change_rejects_password_without_letter_and_number(): void
    {
        $userId = DB::table('users')->insertGetId([
            'role_id' => 1,
            'username' => 'test.validation',
            'email' => 'test.validation@example.test',
            'password_hash' => Hash::make('PasswordLama123'),
            'nama_tampilan' => 'Test Validation',
            'status' => 'aktif',
            'wajib_ganti_password' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::findOrFail($userId);
        $token = $user->createToken('validation-device');

        $this->withToken($token->plainTextToken)
            ->postJson('/api/v1/admin/auth/password', [
                'password_lama' => 'PasswordLama123',
                'password_baru' => '12345678',
                'password_baru_confirmation' => '12345678',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password_baru']);

        $this->assertTrue($user->fresh()->wajib_ganti_password);
    }

    public function test_password_change_rejects_incorrect_old_password(): void
    {
        $userId = DB::table('users')->insertGetId([
            'role_id' => 1,
            'username' => 'test.old-password',
            'email' => 'test.old-password@example.test',
            'password_hash' => Hash::make('PasswordLama123'),
            'nama_tampilan' => 'Test Old Password',
            'status' => 'aktif',
            'wajib_ganti_password' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::findOrFail($userId);
        $token = $user->createToken('old-password-device');

        $this->withToken($token->plainTextToken)
            ->postJson('/api/v1/admin/auth/password', [
                'password_lama' => 'PasswordSalah123',
                'password_baru' => 'PasswordBaru123',
                'password_baru_confirmation' => 'PasswordBaru123',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password_lama']);

        $this->assertTrue($user->fresh()->wajib_ganti_password);
    }

    public function test_required_password_blocks_student_profile_until_changed(): void
    {
        $userId = DB::table('users')->insertGetId([
            'role_id' => 3,
            'username' => 'test.required-password',
            'email' => 'test.required-password@example.test',
            'password_hash' => Hash::make('PasswordLama123'),
            'nama_tampilan' => 'Test Required Password',
            'status' => 'aktif',
            'wajib_ganti_password' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('siswa')->insert([
            'user_id' => $userId,
            'nis' => 'NIS-REQUIRED-001',
            'status_siswa' => 'aktif',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::findOrFail($userId);
        $token = $user->createToken('required-password-device');

        $this->withToken($token->plainTextToken)
            ->getJson('/api/v1/siswa/profil')
            ->assertForbidden()
            ->assertJsonPath('code', 'PASSWORD_CHANGE_REQUIRED');
    }
}
