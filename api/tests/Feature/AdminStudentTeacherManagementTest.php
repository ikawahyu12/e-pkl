<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminStudentTeacherManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('nama')->unique();
            $table->string('deskripsi')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->string('username', 60)->nullable()->unique();
            $table->string('email', 150)->nullable()->unique();
            $table->string('password_hash')->nullable();
            $table->string('nama_tampilan', 120);
            $table->string('status')->default('aktif');
            $table->boolean('wajib_ganti_password')->default(false);
            $table->timestamp('terakhir_login_at')->nullable();
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

        Schema::create('kelas', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
        });

        Schema::create('siswa', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('nisn')->unique();
            $table->string('nis')->nullable();
            $table->string('nama_lengkap', 120);
            $table->unsignedBigInteger('kelas_id')->nullable();
            $table->string('status_siswa')->default('aktif');
            $table->timestamps();
        });

        Schema::create('guru', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('nip')->unique();
            $table->string('nama_lengkap', 120);
            $table->string('status_bimbingan')->default('aktif');
            $table->timestamps();
        });

        Schema::create('penempatan_pkl', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('siswa_id')->nullable();
            $table->unsignedBigInteger('guru_id')->nullable();
        });

        Role::query()->create(['nama' => 'admin']);
        Role::query()->create(['nama' => 'siswa']);
        Role::query()->create(['nama' => 'guru']);
    }

    public function test_admin_can_create_list_update_and_delete_student_accounts(): void
    {
        $admin = $this->createUser('admin', 'root-admin');
        $classId = \DB::table('kelas')->insertGetId(['nama' => 'XII RPL 1']);
        $this->actingAs($admin, 'sanctum');

        $created = $this->postJson('/api/v1/admin/students', [
            'nama' => 'Siswa Baru',
            'username' => '1234567890',
            'password' => 'password-siswa-aman',
            'kelas_id' => $classId,
            'status' => 'Aktif',
        ])->assertCreated()
            ->assertJsonPath('data.nama', 'Siswa Baru')
            ->assertJsonPath('data.kelas', 'XII RPL 1')
            ->assertJsonMissingPath('data.password_hash');

        $studentId = $created->json('data.id');
        $studentUser = User::query()->findOrFail($studentId);
        $this->assertSame('siswa', $studentUser->role->nama);
        $this->assertTrue(Hash::check('password-siswa-aman', $studentUser->password_hash));

        $this->getJson('/api/v1/admin/students?search=Siswa')
            ->assertOk()
            ->assertJsonPath('data.users.0.id', $studentId)
            ->assertJsonPath('data.users.0.username', '1234567890');

        $this->patchJson('/api/v1/admin/students/'.$studentId, [
            'nama' => 'Nama Siswa Diperbarui',
            'status' => 'nonaktif',
        ])->assertOk()
            ->assertJsonPath('data.nama', 'Nama Siswa Diperbarui')
            ->assertJsonPath('data.status', 'nonaktif');

        $this->deleteJson('/api/v1/admin/students/'.$studentId)
            ->assertOk()
            ->assertJsonPath('data', null);

        $this->assertDatabaseMissing('users', ['id' => $studentId]);
        $this->assertDatabaseMissing('siswa', ['user_id' => $studentId]);
    }

    public function test_admin_can_create_list_update_and_delete_teacher_accounts(): void
    {
        $admin = $this->createUser('admin', 'root-admin');
        $this->actingAs($admin, 'sanctum');

        $created = $this->postJson('/api/v1/admin/teachers', [
            'nama' => 'Guru Baru',
            'username' => '19870001',
            'password' => 'password-guru-aman',
            'status' => 'Aktif',
        ])->assertCreated()
            ->assertJsonPath('data.nama', 'Guru Baru')
            ->assertJsonPath('data.username', '19870001')
            ->assertJsonMissingPath('data.password_hash');

        $teacherId = $created->json('data.id');
        $teacherUser = User::query()->findOrFail($teacherId);
        $this->assertSame('guru', $teacherUser->role->nama);
        $this->assertTrue(Hash::check('password-guru-aman', $teacherUser->password_hash));

        $this->getJson('/api/v1/admin/teachers?search=19870001')
            ->assertOk()
            ->assertJsonPath('data.users.0.id', $teacherId);

        $this->patchJson('/api/v1/admin/teachers/'.$teacherId, [
            'username' => '19870002',
            'status' => 'nonaktif',
        ])->assertOk()
            ->assertJsonPath('data.username', '19870002')
            ->assertJsonPath('data.status', 'nonaktif');

        $this->deleteJson('/api/v1/admin/teachers/'.$teacherId)
            ->assertOk()
            ->assertJsonPath('data', null);

        $this->assertDatabaseMissing('users', ['id' => $teacherId]);
        $this->assertDatabaseMissing('guru', ['user_id' => $teacherId]);
    }

    public function test_student_account_can_be_created_before_classes_are_configured(): void
    {
        $admin = $this->createUser('admin', 'root-admin');
        $this->actingAs($admin, 'sanctum');

        $created = $this->postJson('/api/v1/admin/students', [
            'nama' => 'Siswa Tanpa Kelas',
            'username' => '1234567891',
            'password' => 'password-siswa-aman',
            'status' => 'aktif',
        ])->assertCreated()
            ->assertJsonPath('data.kelas_id', null)
            ->assertJsonPath('data.kelas', null);

        $this->patchJson('/api/v1/admin/students/'.$created->json('data.id'), [
            'nama' => 'Siswa Tanpa Kelas Diperbarui',
            'kelas_id' => null,
        ])->assertOk()
            ->assertJsonPath('data.kelas_id', null);
    }

    public function test_student_and_teacher_management_requires_admin_authentication(): void
    {
        $this->getJson('/api/v1/admin/students')->assertUnauthorized();
        $this->getJson('/api/v1/admin/teachers')->assertUnauthorized();
    }

    private function createUser(string $roleName, string $username): User
    {
        $role = Role::query()->where('nama', $roleName)->firstOrFail();

        $user = new User;
        $user->role_id = $role->id;
        $user->username = $username;
        $user->password_hash = Hash::make('password-aman');
        $user->nama_tampilan = $username;
        $user->status = 'aktif';
        $user->save();

        return $user;
    }
}
