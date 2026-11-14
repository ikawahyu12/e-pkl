<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
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

        Role::query()->create([
            'nama' => 'admin',
            'deskripsi' => 'Administrator sistem',
        ]);
    }

    public function test_admin_can_list_only_admin_accounts(): void
    {
        $admin = $this->createUser('admin', 'aktif', 'root-admin');
        $this->createUser('guru', 'aktif', 'guru-user');
        $this->actingAs($admin, 'sanctum');

        $this->getJson('/api/v1/admin/users')
            ->assertOk()
            ->assertJsonCount(1, 'data.users')
            ->assertJsonPath('data.users.0.username', 'root-admin');
    }

    public function test_admin_can_create_an_account_and_password_is_hashed(): void
    {
        $admin = $this->createUser('admin', 'aktif', 'root-admin');
        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/v1/admin/users', [
            'nama' => 'Admin Baru',
            'username' => 'admin-baru',
            'password' => 'password-aman',
            'status' => 'Aktif',
            'role_id' => 2,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.nama_tampilan', 'Admin Baru')
            ->assertJsonPath('data.status', 'aktif')
            ->assertJsonMissingPath('data.password_hash');

        $created = User::query()->where('username', 'admin-baru')->firstOrFail();
        $this->assertSame('admin', $created->role->nama);
        $this->assertTrue(Hash::check('password-aman', $created->password_hash));
    }

    public function test_admin_can_update_and_delete_an_account(): void
    {
        $admin = $this->createUser('admin', 'aktif', 'root-admin');
        $managedUser = $this->createUser('admin', 'aktif', 'managed-admin');
        $this->actingAs($admin, 'sanctum');

        $this->patchJson('/api/v1/admin/users/'.$managedUser->id, [
            'nama' => 'Nama Baru',
            'status' => 'Nonaktif',
        ])
            ->assertOk()
            ->assertJsonPath('data.nama', 'Nama Baru')
            ->assertJsonPath('data.status', 'nonaktif');

        $this->deleteJson('/api/v1/admin/users/'.$managedUser->id)
            ->assertOk()
            ->assertJsonPath('data', null);

        $this->assertDatabaseMissing('users', ['id' => $managedUser->id]);
    }

    public function test_last_active_admin_cannot_be_disabled_or_deleted(): void
    {
        $admin = $this->createUser('admin', 'aktif', 'root-admin');
        $this->actingAs($admin, 'sanctum');

        $this->patchJson('/api/v1/admin/users/'.$admin->id, [
            'status' => 'nonaktif',
        ])->assertConflict();

        $this->deleteJson('/api/v1/admin/users/'.$admin->id)
            ->assertConflict();
    }

    public function test_admin_management_requires_admin_authentication(): void
    {
        $this->getJson('/api/v1/admin/users')
            ->assertUnauthorized();
    }

    public function test_non_admin_users_cannot_manage_admin_accounts(): void
    {
        $guru = $this->createUser('guru', 'aktif', 'guru-user');
        $this->actingAs($guru, 'sanctum');

        $this->getJson('/api/v1/admin/users')
            ->assertForbidden();
    }

    private function createUser(string $roleName, string $status, string $username): User
    {
        $role = Role::query()->firstOrCreate(['nama' => $roleName]);

        $user = new User;
        $user->role_id = $role->id;
        $user->username = $username;
        $user->password_hash = Hash::make('password-aman');
        $user->nama_tampilan = $username;
        $user->status = $status;
        $user->save();

        return $user;
    }
}
