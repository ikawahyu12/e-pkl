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
    private int $sessionId;

    private int $placementId;

    private int $guruUserId;

    private int $instansiId;

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
            $table->string('status_siswa')->default('aktif');
            $table->timestamps();
        });

        Schema::create('guru', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('nama_lengkap');
            $table->timestamps();
        });

        Schema::create('instansi', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->text('alamat');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('radius_geofence_meter')->nullable();
            $table->timestamps();
        });

        Schema::create('penempatan_pkl', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('siswa_id');
            $table->unsignedBigInteger('instansi_id');
            $table->unsignedBigInteger('guru_id');
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
            $table->time('jam_masuk_mulai');
            $table->time('jam_masuk_selesai');
            $table->time('batas_toleransi')->nullable();
            $table->time('jam_pulang_mulai');
            $table->time('jam_pulang_selesai');
            $table->string('status');
            $table->unsignedBigInteger('dibuat_oleh')->nullable();
            $table->timestamps();
        });
        Schema::create('sesi_presensi_penempatan', function (Blueprint $table): void {
            $table->unsignedBigInteger('sesi_id');
            $table->unsignedBigInteger('penempatan_id');
            $table->primary(['sesi_id', 'penempatan_id']);
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
            $table->text('catatan')->nullable();
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

        $guruRole = Role::query()->create(['nama' => 'guru']);
        $guruUser = User::query()->forceCreate([
            'role_id' => $guruRole->id,
            'username' => 'guru-test',
            'email' => 'guru@example.test',
            'nama_tampilan' => 'Guru Uji',
            'status' => 'aktif',
        ]);
        $guruId = DB::table('guru')->insertGetId([
            'user_id' => $guruUser->id,
            'nama_lengkap' => 'Guru Uji',
        ]);
        $this->guruUserId = $guruUser->id;

        $instansi = $this->createInstansi('Instansi Penempatan', 0, 0);
        $this->instansiId = $instansi->id;
        $penempatan = $this->createPenempatan($siswa->id, $instansi->id, $guruId);
        $this->placementId = $penempatan->id;
        $pengaturan = DB::table('pengaturan_presensi')->insertGetId([
            'instansi_id' => $instansi->id,
            'aktif' => true,
            'radius_meter' => 20,
        ]);

        $now = now('Asia/Jakarta');
        $this->sessionId = DB::table('sesi_presensi')->insertGetId([
            'pengaturan_id' => $pengaturan,
            'tanggal' => $now->toDateString(),
            'nama_shift' => 'Reguler',
            'jam_masuk_mulai' => $now->copy()->subMinute()->format('H:i:s'),
            'jam_masuk_selesai' => $now->copy()->addMinutes(5)->format('H:i:s'),
            'jam_pulang_mulai' => $now->copy()->subMinute()->format('H:i:s'),
            'jam_pulang_selesai' => $now->copy()->addMinutes(5)->format('H:i:s'),
            'batas_toleransi' => $now->copy()->addMinutes(5)->format('H:i:s'),
            'status' => 'dibuka',
            'dibuat_oleh' => $guruUser->id,
        ]);

        $this->actingAs($user, 'sanctum');
    }

    public function test_student_attendance_is_saved_when_within_100_meters_of_their_assigned_instance(): void
    {
        $this->postJson('/api/v1/siswa/presensi', [
            'session_id' => $this->sessionId,
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

    public function test_assigned_session_is_not_visible_to_other_students(): void
    {
        $otherUser = User::query()->forceCreate([
            'role_id' => Role::query()->where('nama', 'siswa')->value('id'),
            'username' => 'siswa-lain',
            'email' => 'siswa-lain@example.test',
            'nama_tampilan' => 'Siswa Lain',
            'status' => 'aktif',
        ]);
        $otherStudent = Siswa::query()->create([
            'user_id' => $otherUser->id,
            'nisn' => 'siswa-lain',
            'nama_lengkap' => 'Siswa Lain',
        ]);
        $otherPlacement = $this->createPenempatan(
            $otherStudent->id,
            $this->instansiId,
            DB::table('guru')->where('user_id', $this->guruUserId)->value('id'),
        );
        DB::table('sesi_presensi_penempatan')->insert([
            'sesi_id' => $this->sessionId,
            'penempatan_id' => $otherPlacement->id,
        ]);

        $this->getJson('/api/v1/siswa/presensi')
            ->assertOk()
            ->assertJsonCount(0, 'data.sesi');
    }

    public function test_student_attendance_is_rejected_when_more_than_100_meters_away(): void
    {
        $this->postJson('/api/v1/siswa/presensi', [
            'session_id' => $this->sessionId,
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
            'session_id' => $this->sessionId,
            'type' => 'datang',
            'latitude' => $latitude,
            'longitude' => 0,
        ])
            ->assertOk()
            ->assertJsonPath('data.distance_meters', 100);
    }

    public function test_student_can_check_out_after_checking_in(): void
    {
        $this->postJson('/api/v1/siswa/presensi', [
            'session_id' => $this->sessionId,
            'type' => 'datang',
            'latitude' => 0,
            'longitude' => 0,
        ])->assertOk();

        $this->postJson('/api/v1/siswa/presensi', [
            'session_id' => $this->sessionId,
            'type' => 'pulang',
            'latitude' => 0,
            'longitude' => 0,
        ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('presensi', [
            'status_pulang' => 'sesuai',
            'status_harian' => 'hadir',
        ]);
    }

    public function test_student_check_in_and_check_out_are_returned_in_their_attendance_history(): void
    {
        foreach (['datang', 'pulang'] as $type) {
            $this->postJson('/api/v1/siswa/presensi', [
                'session_id' => $this->sessionId,
                'type' => $type,
                'latitude' => 0,
                'longitude' => 0,
            ])->assertOk();
        }

        $this->getJson('/api/v1/siswa/riwayat?tipe=presensi')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'tipe' => 'presensi',
                'jenis' => 'masuk',
                'judul' => 'Presensi: Masuk',
            ])
            ->assertJsonFragment([
                'tipe' => 'presensi',
                'jenis' => 'pulang',
                'judul' => 'Presensi: Pulang',
            ]);
    }

    public function test_duplicate_attendance_is_blocked_per_session_but_distinct_daily_sessions_are_allowed(): void
    {
        $settingId = DB::table('sesi_presensi')
            ->where('id', $this->sessionId)
            ->value('pengaturan_id');
        $now = now('Asia/Jakarta');
        $secondSessionId = DB::table('sesi_presensi')->insertGetId([
            'pengaturan_id' => $settingId,
            'tanggal' => $now->toDateString(),
            'nama_shift' => 'Sesi Tambahan',
            'jam_masuk_mulai' => $now->copy()->subMinute()->format('H:i:s'),
            'jam_masuk_selesai' => $now->copy()->addMinutes(5)->format('H:i:s'),
            'jam_pulang_mulai' => $now->copy()->subMinute()->format('H:i:s'),
            'jam_pulang_selesai' => $now->copy()->addMinutes(5)->format('H:i:s'),
            'status' => 'dibuka',
            'dibuat_oleh' => DB::table('sesi_presensi')
                ->where('id', $this->sessionId)
                ->value('dibuat_oleh'),
        ]);
        $payload = [
            'type' => 'datang',
            'latitude' => 0,
            'longitude' => 0,
        ];

        $this->postJson('/api/v1/siswa/presensi', $payload + ['session_id' => $this->sessionId])
            ->assertOk();
        $this->postJson('/api/v1/siswa/presensi', $payload + ['session_id' => $secondSessionId])
            ->assertOk();
        $this->postJson('/api/v1/siswa/presensi', $payload + ['session_id' => $this->sessionId])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Presensi datang sudah tercatat pada sesi ini.');

        $this->assertDatabaseCount('presensi', 2);
    }

    public function test_check_out_respects_geofence_and_cannot_overwrite_or_repeat_attendance(): void
    {
        $this->postJson('/api/v1/siswa/presensi', [
            'session_id' => $this->sessionId,
            'type' => 'datang',
            'latitude' => 0,
            'longitude' => 0,
        ])->assertOk();

        $this->postJson('/api/v1/siswa/presensi', [
            'session_id' => $this->sessionId,
            'type' => 'pulang',
            'latitude' => 0.001,
            'longitude' => 0,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Anda berada di luar radius 100 meter dari lokasi instansi PKL.');

        $beforeCheckOut = DB::table('presensi')->where('sesi_id', $this->sessionId)->firstOrFail();
        $this->assertNull($beforeCheckOut->waktu_pulang);

        $this->postJson('/api/v1/siswa/presensi', [
            'session_id' => $this->sessionId,
            'type' => 'pulang',
            'latitude' => 0.0001,
            'longitude' => 0,
        ])->assertOk();

        $savedAttendance = DB::table('presensi')->where('sesi_id', $this->sessionId)->firstOrFail();
        $this->assertNotNull($savedAttendance->waktu_masuk);
        $this->assertNotNull($savedAttendance->waktu_pulang);
        $this->assertEquals(0, (float) $savedAttendance->latitude_masuk);
        $this->assertEquals(0.0001, (float) $savedAttendance->latitude_pulang);

        $this->postJson('/api/v1/siswa/presensi', [
            'session_id' => $this->sessionId,
            'type' => 'pulang',
            'latitude' => 0.0002,
            'longitude' => 0,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Presensi pulang sudah tercatat pada sesi ini.');

        $attendanceAfterDuplicate = DB::table('presensi')->where('sesi_id', $this->sessionId)->firstOrFail();
        $this->assertSame($savedAttendance->waktu_masuk, $attendanceAfterDuplicate->waktu_masuk);
        $this->assertSame($savedAttendance->waktu_pulang, $attendanceAfterDuplicate->waktu_pulang);
        $this->assertEquals(
            (float) $savedAttendance->latitude_masuk,
            (float) $attendanceAfterDuplicate->latitude_masuk
        );
        $this->assertEquals(
            (float) $savedAttendance->latitude_pulang,
            (float) $attendanceAfterDuplicate->latitude_pulang
        );
        $this->assertDatabaseCount('presensi', 1);
    }

    public function test_geofence_uses_the_instance_assigned_to_the_student(): void
    {
        $otherInstansi = $this->createInstansi('Instansi Lain', 1, 1);

        $this->postJson('/api/v1/siswa/presensi', [
            'session_id' => $this->sessionId,
            'type' => 'datang',
            'latitude' => (float) $otherInstansi->latitude,
            'longitude' => (float) $otherInstansi->longitude,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false);

        $this->assertDatabaseCount('presensi', 0);
    }

    public function test_admin_instance_radius_is_used_instead_of_attendance_setting_radius(): void
    {
        DB::table('instansi')->where('id', 1)->update(['radius_geofence_meter' => 150]);

        $this->postJson('/api/v1/siswa/presensi', [
            'session_id' => $this->sessionId,
            'type' => 'datang',
            'latitude' => 0.001,
            'longitude' => 0,
        ])
            ->assertOk()
            ->assertJsonPath('data.radius_meters', 150);
    }

    public function test_attendance_schedule_returns_teacher_session_and_admin_geofence(): void
    {
        $this->getJson('/api/v1/siswa/presensi')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.instansi.latitude', 0)
            ->assertJsonPath('data.instansi.radius_meters', 100)
            ->assertJsonPath('data.instansi.geofence_configured', true)
            ->assertJsonPath('data.sesi.0.id', $this->sessionId)
            ->assertJsonPath('data.sesi.0.nama_shift', 'Reguler');
    }

    public function test_attendance_is_rejected_outside_the_teacher_schedule_window(): void
    {
        $now = now('Asia/Jakarta');
        DB::table('sesi_presensi')->where('id', $this->sessionId)->update([
            'jam_masuk_mulai' => $now->copy()->subHours(2)->format('H:i:s'),
            'jam_masuk_selesai' => $now->copy()->subHour()->format('H:i:s'),
        ]);

        $this->postJson('/api/v1/siswa/presensi', [
            'session_id' => $this->sessionId,
            'type' => 'datang',
            'latitude' => 0,
            'longitude' => 0,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Presensi datang hanya dapat dilakukan pukul '
                .$now->copy()->subHours(2)->format('H:i').' sampai '
                .$now->copy()->subHour()->format('H:i').' WIB.');

        $this->assertDatabaseCount('presensi', 0);
    }

    public function test_check_out_is_rejected_until_check_in_has_been_recorded(): void
    {
        $this->postJson('/api/v1/siswa/presensi', [
            'session_id' => $this->sessionId,
            'type' => 'pulang',
            'latitude' => 0,
            'longitude' => 0,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Presensi datang harus dilakukan sebelum presensi pulang.');

        $this->assertDatabaseCount('presensi', 0);
    }

    public function test_attendance_is_rejected_when_admin_has_not_configured_a_geofence(): void
    {
        DB::table('instansi')->where('id', 1)->update([
            'latitude' => null,
            'longitude' => null,
            'radius_geofence_meter' => null,
        ]);

        $this->postJson('/api/v1/siswa/presensi', [
            'session_id' => $this->sessionId,
            'type' => 'datang',
            'latitude' => 0,
            'longitude' => 0,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Koordinat atau radius geofencing instansi belum diatur Admin atau tidak valid.');

        $this->assertDatabaseCount('presensi', 0);
    }

    public function test_attendance_rejects_a_session_created_by_another_teacher(): void
    {
        $teacherRole = Role::query()->where('nama', 'guru')->firstOrFail();
        $otherTeacher = User::query()->forceCreate([
            'role_id' => $teacherRole->id,
            'username' => 'guru-lain',
            'email' => 'guru-lain@example.test',
            'nama_tampilan' => 'Guru Lain',
            'status' => 'aktif',
        ]);
        $settingId = DB::table('pengaturan_presensi')->value('id');
        $now = now('Asia/Jakarta');
        $otherSessionId = DB::table('sesi_presensi')->insertGetId([
            'pengaturan_id' => $settingId,
            'tanggal' => $now->toDateString(),
            'nama_shift' => 'Shift Guru Lain',
            'jam_masuk_mulai' => $now->copy()->subMinute()->format('H:i:s'),
            'jam_masuk_selesai' => $now->copy()->addMinutes(5)->format('H:i:s'),
            'jam_pulang_mulai' => $now->copy()->subMinute()->format('H:i:s'),
            'jam_pulang_selesai' => $now->copy()->addMinutes(5)->format('H:i:s'),
            'status' => 'dibuka',
            'dibuat_oleh' => $otherTeacher->id,
        ]);

        $this->postJson('/api/v1/siswa/presensi', [
            'session_id' => $otherSessionId,
            'type' => 'datang',
            'latitude' => 0,
            'longitude' => 0,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Sesi presensi tidak ditemukan, bukan milik Guru pembimbing Anda, atau sudah ditutup.');

        $this->assertDatabaseCount('presensi', 0);
    }

    private function createInstansi(string $nama, float $latitude, float $longitude, int $radius = 100)
    {
        return Instansi::query()->forceCreate([
            'nama' => $nama,
            'alamat' => 'Alamat uji',
            'latitude' => $latitude,
            'longitude' => $longitude,
            'radius_geofence_meter' => $radius,
        ]);
    }

    private function createPenempatan(int $siswaId, int $instansiId, int $guruId)
    {
        return PenempatanPkl::query()->forceCreate([
            'siswa_id' => $siswaId,
            'instansi_id' => $instansiId,
            'guru_id' => $guruId,
            'tanggal_mulai' => now('Asia/Jakarta')->toDateString(),
            'tanggal_selesai' => now('Asia/Jakarta')->addDay()->toDateString(),
            'status' => 'aktif',
        ]);
    }
}