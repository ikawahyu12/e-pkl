<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SelfRegistrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('nama')->unique();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->string('username')->unique();
            $table->string('email')->nullable()->unique();
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
            $table->string('nis')->nullable();
            $table->string('nama_lengkap');
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

        foreach (['admin', 'guru', 'siswa'] as $name) {
            Role::query()->create(['nama' => $name]);
        }
    }

    public function test_self_registration_and_email_verification_endpoints_are_unavailable(): void
    {
        $before = User::query()->count();

        foreach (['siswa', 'guru'] as $role) {
            $this->postJson('/api/v1/'.$role.'/auth/register', [])->assertNotFound();
            $this->postJson('/api/v1/'.$role.'/auth/resend-verification', [])->assertNotFound();
            $this->postJson('/api/v1/'.$role.'/auth/verify-email', [
                'token' => 'unused-token',
            ])->assertNotFound();
        }

        $this->assertSame($before, User::query()->count());
    }

    public function test_admin_provisioned_accounts_can_login_without_self_registration_or_email_verification(): void
    {
        $student = $this->createAccount('siswa', 'student-account', '1234567890');
        $teacher = $this->createAccount('guru', 'teacher-account', '198201012010011001');

        $this->postJson('/api/v1/siswa/auth/login', [
            'login' => '1234567890',
            'password' => 'InitialPassword123',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.wajib_ganti_password', true);

        $this->postJson('/api/v1/guru/auth/login', [
            'login' => '198201012010011001',
            'password' => 'InitialPassword123',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.wajib_ganti_password', true);

        $this->assertNull($student->fresh()->email_terverifikasi_at);
        $this->assertNull($teacher->fresh()->email_terverifikasi_at);

        DB::table('users')->where('id', $student->id)->update(['status' => 'pending']);
        $this->postJson('/api/v1/siswa/auth/login', [
            'login' => '1234567890',
            'password' => 'InitialPassword123',
        ])->assertUnauthorized();
    }

    public function test_profiles_without_admin_created_user_accounts_cannot_login(): void
    {
        DB::table('siswa')->insert([
            'nisn' => 'profile-only-student',
            'nis' => 'NIS-profile-only',
            'nama_lengkap' => 'Profil Siswa Saja',
            'status_siswa' => 'aktif',
        ]);
        DB::table('guru')->insert([
            'nip' => 'profile-only-teacher',
            'nama_lengkap' => 'Profil Guru Saja',
            'status_bimbingan' => 'aktif',
        ]);

        $this->postJson('/api/v1/siswa/auth/login', [
            'login' => 'profile-only-student',
            'password' => 'InitialPassword123',
        ])->assertUnauthorized();

        $this->postJson('/api/v1/guru/auth/login', [
            'login' => 'profile-only-teacher',
            'password' => 'InitialPassword123',
        ])->assertUnauthorized();
    }

    private function createAccount(string $roleName, string $username, string $identity): User
    {
        $user = new User;
        $user->role_id = Role::query()->where('nama', $roleName)->value('id');
        $user->username = $username;
        $user->email = $username.'@example.test';
        $user->password_hash = Hash::make('InitialPassword123');
        $user->nama_tampilan = ucfirst($roleName).' Test';
        $user->status = 'aktif';
        $user->wajib_ganti_password = true;
        $user->save();

        if ($roleName === 'siswa') {
            DB::table('siswa')->insert([
                'user_id' => $user->id,
                'nisn' => $identity,
                'nis' => 'NIS-'.$identity,
                'nama_lengkap' => $user->nama_tampilan,
                'status_siswa' => 'aktif',
            ]);
        } else {
            DB::table('guru')->insert([
                'user_id' => $user->id,
                'nip' => $identity,
                'nama_lengkap' => $user->nama_tampilan,
                'status_bimbingan' => 'aktif',
            ]);
        }

        return $user;
    }
}
