<?php

namespace Tests\Feature;

use App\Models\Instansi;
use App\Models\PenempatanPkl;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SiswaPresensiGeofenceTest extends TestCase
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
            $table->string('username')->nullable();
            $table->string('email')->nullable();
            $table->string('password_hash')->nullable();
            $table->string('nama_tampilan');
            $table->string('status')->default('aktif');
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

        Schema::create('siswa', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('nisn')->unique();
            $table->string('nama_lengkap');
            $table->timestamps();
        });

        Schema::create('instansi', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->text('alamat');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
        });

        Schema::create('penempatan_pkl', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('siswa_id');
            $table->unsignedBigInteger('instansi_id');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('pengaturan_presensi', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('instansi_id');
            $table->boolean('aktif')->default(true);
            $table->unsignedInteger('radius_meter')->default(100);
            $table->timestamps();
        });

        Schema::create('sesi_presensi', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pengaturan_id');
            $table->date('tanggal');
            $table->string('nama_shift');
            $table->time('batas_toleransi')->nullable();
            $table->time('jam_masuk_selesai');
            $table->time('jam_pulang_mulai');
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('presensi', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('sesi_id');
            $table->unsignedBigInteger('penempatan_id');
            $table->date('tanggal');
            $table->dateTime('waktu_masuk')->nullable();
            $table->decimal('latitude_masuk', 10, 7)->nullable();
            $table->decimal('longitude_masuk', 10, 7)->nullable();
            $table->decimal('jarak_masuk_meter', 10, 2)->nullable();
            $table->string('status_masuk')->nullable();
            $table->dateTime('waktu_pulang')->nullable();
            $table->decimal('latitude_pulang', 10, 7)->nullable();
            $table->decimal('longitude_pulang', 10, 7)->nullable();
            $table->decimal('jarak_pulang_meter', 10, 2)->nullable();
            $table->string('status_pulang')->nullable();
            $table->string('status_harian')->default('belum_lengkap');
            $table->timestamps();
            $table->unique(['sesi_id', 'penempatan_id']);
        });

        $role = Role::query()->create(['nama' => 'siswa']);
        $user = User::query()->forceCreate([
            'role_id' => $role->id,
            'username' => 'siswa-test',
            'email' => 'siswa@example.test',
            'nama_tampilan' => 'Siswa Uji',
            'status' => 'aktif',
        ]);
        $siswa = Siswa::query()->create([
            'user_id' => $user->id,
            'nisn' => 'siswa-uji',
            'nama_lengkap' => 'Siswa Uji',
        ]);

        $instansi = $this->createInstansi('Instansi Penempatan', 0, 0);
        $penempatan = $this->createPenempatan($siswa->id, $instansi->id);
        $pengaturan = DB::table('pengaturan_presensi')->insertGetId([
            'instansi_id' => $instansi->id,
            'aktif' => true,
            'radius_meter' => 100,
        ]);
        DB::table('sesi_presensi')->insert([
            'pengaturan_id' => $pengaturan,
            'tanggal' => now('Asia/Jakarta')->toDateString(),
            'nama_shift' => 'Reguler',
            'jam_masuk_selesai' => '08:30:00',
            'jam_pulang_mulai' => '15:00:00',
            'status' => 'dibuka',
        ]);

        $this->actingAs($user, 'sanctum');
    }

    public function test_student_attendance_is_saved_when_within_100_meters_of_their_assigned_instance(): void
    {
        $this->postJson('/api/v1/siswa/presensi', [
            'type' => 'datang',
            'latitude' => 0.0005,
            'longitude' => 0,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.radius_meters', 100);

        $this->assertDatabaseHas('presensi', [
            'latitude_masuk' => 0.0005,
            'longitude_masuk' => 0,
            'status_harian' => 'hadir',
        ]);
    }

    public function test_student_attendance_is_rejected_when_more_than_100_meters_away(): void
    {
        $this->postJson('/api/v1/siswa/presensi', [
            'type' => 'datang',
            'latitude' => 0.001,
            'longitude' => 0,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Anda berada di luar radius 100 meter dari lokasi instansi PKL.');

        $this->assertDatabaseCount('presensi', 0);
    }

    public function test_student_at_the_100_meter_boundary_is_accepted(): void
    {
        $latitude = rad2deg(100 / 6371000);

        $this->postJson('/api/v1/siswa/presensi', [
            'type' => 'datang',
            'latitude' => $latitude,
            'longitude' => 0,
        ])
            ->assertOk()
            ->assertJsonPath('data.distance_meters', 100);
    }

    public function test_geofence_uses_the_instance_assigned_to_the_student(): void
    {
        $otherInstansi = $this->createInstansi('Instansi Lain', 1, 1);

        $this->postJson('/api/v1/siswa/presensi', [
            'type' => 'datang',
            'latitude' => (float) $otherInstansi->latitude,
            'longitude' => (float) $otherInstansi->longitude,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false);

        $this->assertDatabaseCount('presensi', 0);
    }

    private function createInstansi(string $nama, float $latitude, float $longitude)
    {
        return Instansi::query()->forceCreate([
            'nama' => $nama,
            'alamat' => 'Alamat uji',
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
    }

    private function createPenempatan(int $siswaId, int $instansiId)
    {
        return PenempatanPkl::query()->forceCreate([
            'siswa_id' => $siswaId,
            'instansi_id' => $instansiId,
            'tanggal_mulai' => now('Asia/Jakarta')->toDateString(),
            'tanggal_selesai' => now('Asia/Jakarta')->addDay()->toDateString(),
            'status' => 'aktif',
        ]);
    }
}
 