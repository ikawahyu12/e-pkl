<?php

namespace Tests\Feature;

use App\Mail\GuruEmailChangeVerificationMail;
use App\Models\Guru;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GuruEmailChangeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array']);

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('nama')->unique();
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->string('username')->nullable();
            $table->string('email')->unique();
            $table->string('nama_tampilan');
            $table->string('password_hash');
            $table->string('status')->default('aktif');
            $table->boolean('wajib_ganti_password')->default(false);
            $table->timestamp('email_terverifikasi_at')->nullable();
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

        $role = Role::query()->create(['nama' => 'guru']);
        $user = User::query()->forceCreate([
            'role_id' => $role->id,
            'username' => 'guru-uji',
            'email' => 'lama@example.test',
            'nama_tampilan' => 'Guru Uji',
            'password_hash' => 'not-a-real-password',
            'status' => 'aktif',
            'wajib_ganti_password' => false,
        ]);
        Guru::query()->forceCreate([
            'user_id' => $user->id,
            'nip' => 'nip-uji',
            'nama_lengkap' => 'Guru Uji',
            'status_bimbingan' => 'aktif',
        ]);

        $this->actingAs($user, 'sanctum');
    }

    public function test_email_changes_only_after_signed_verification_and_link_is_single_use(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/guru/profil/email-change', [
            'email' => 'baru@example.test',
        ])->assertAccepted();

        $this->assertDatabaseHas('users', [
            'email' => 'lama@example.test',
            'email_terverifikasi_at' => null,
        ]);

        $verificationUrl = null;
        Mail::assertSent(
            GuruEmailChangeVerificationMail::class,
            function (GuruEmailChangeVerificationMail $mail) use (&$verificationUrl): bool {
                $verificationUrl = $mail->verificationUrl;

                return true;
            }
        );
        $this->assertNotNull($verificationUrl);

        $this->getJson($verificationUrl)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('users', [
            'email' => 'baru@example.test',
        ]);
        $this->assertNotNull(User::query()->firstOrFail()->email_terverifikasi_at);

        $this->getJson($verificationUrl)->assertStatus(410);
    }

    public function test_expired_signed_email_change_link_does_not_change_email(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/guru/profil/email-change', [
            'email' => 'baru@example.test',
        ])->assertAccepted();

        $verificationUrl = null;
        Mail::assertSent(
            GuruEmailChangeVerificationMail::class,
            function (GuruEmailChangeVerificationMail $mail) use (&$verificationUrl): bool {
                $verificationUrl = $mail->verificationUrl;

                return true;
            }
        );

        $this->travel(31)->minutes();
        $this->getJson($verificationUrl)->assertForbidden();

        $this->assertDatabaseHas('users', ['email' => 'lama@example.test']);
        Cache::flush();
        $this->travelBack();
    }
}
