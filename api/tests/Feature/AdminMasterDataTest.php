<?php

namespace Tests\Feature;

use App\Models\Instansi;
use App\Models\PeriodePkl;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminMasterDataTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createSchema();

        $role = Role::query()->create(['nama' => 'admin']);
        $this->admin = User::query()->forceCreate([
            'role_id' => $role->id,
            'username' => 'master-admin',
            'nama_tampilan' => 'Admin Master',
            'status' => 'aktif',
            'wajib_ganti_password' => false,
        ]);
        $this->actingAs($this->admin, 'sanctum');
    }

    public function test_master_data_endpoints_require_admin_authentication(): void
    {
        auth()->forgetGuards();
        $this->getJson('/api/v1/admin/master-data/instansi')->assertUnauthorized();

        $guruRole = Role::query()->create(['nama' => 'guru']);
        $guru = User::query()->forceCreate([
            'role_id' => $guruRole->id,
            'username' => 'master-guru',
            'nama_tampilan' => 'Guru',
            'status' => 'aktif',
            'wajib_ganti_password' => false,
        ]);
        $this->actingAs($guru, 'sanctum')
            ->getJson('/api/v1/admin/master-data/instansi')
            ->assertForbidden();
    }

    public function test_instance_crud_and_deactivation_protect_active_placements(): void
    {
        $created = $this->postJson('/api/v1/admin/master-data/instansi', [
            'nama' => 'Mitra Uji',
            'alamat' => 'Jalan Uji',
            'jenis' => 'Perusahaan',
            'telepon' => '08123456789',
            'status' => 'aktif',
            'latitude' => -7.7543,
            'longitude' => 111.9025,
            'radius_geofence_meter' => 100,
        ])->assertCreated()
            ->assertJsonPath('data.instansi.nama', 'Mitra Uji')
            ->assertJsonPath('data.instansi.radius_geofence_meter', 100);
        $instanceId = $created->json('data.instansi.id');
        $this->assertDatabaseHas('instansi', [
            'id' => $instanceId,
            'latitude' => -7.7543,
            'longitude' => 111.9025,
            'radius_geofence_meter' => 100,
        ]);

        $year = $this->createYear();
        $period = $this->createPeriod($year->id);
        $this->createPlacement($instanceId, $period->id, 'aktif');

        $this->patchJson("/api/v1/admin/master-data/instansi/$instanceId/status", [
            'status' => 'nonaktif',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('status');
        $this->assertDatabaseHas('instansi', ['id' => $instanceId, 'status' => 'aktif']);

        $this->patchJson("/api/v1/admin/master-data/instansi/$instanceId", [
            'nama' => 'Mitra Uji',
            'alamat' => 'Jalan Uji',
            'status' => 'nonaktif',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->patchJson("/api/v1/admin/master-data/instansi/$instanceId", [
            'nama' => 'Mitra Diperbarui',
            'alamat' => 'Jalan Baru',
            'status' => 'aktif',
        ])->assertOk()
            ->assertJsonPath('data.instansi.nama', 'Mitra Diperbarui');

        \Illuminate\Support\Facades\DB::table('penempatan_pkl')
            ->where('instansi_id', $instanceId)
            ->update(['status' => 'selesai']);
        $this->patchJson("/api/v1/admin/master-data/instansi/$instanceId/status", [
            'status' => 'nonaktif',
        ])->assertOk();
        $this->assertDatabaseHas('instansi', ['id' => $instanceId, 'status' => 'nonaktif']);
    }

    public function test_geofence_coordinates_must_be_submitted_together(): void
    {
        $this->postJson('/api/v1/admin/master-data/instansi', [
            'nama' => 'Instansi Koordinat Parsial',
            'alamat' => 'Jalan Uji',
            'latitude' => -7.7543,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['longitude', 'radius_geofence_meter']);
    }

    public function test_instance_detail_endpoint_returns_instance_data(): void
    {
        $instance = $this->createInstance();

        $this->getJson("/api/v1/admin/master-data/instansi/{$instance->id}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.instansi.id', $instance->id)
            ->assertJsonPath('data.instansi.nama', 'Mitra Uji');
    }

    public function test_instance_detail_includes_active_students_from_api(): void
    {
        $instance = $this->createInstance();
        $year = $this->createYear();
        $period = $this->createPeriod($year->id);
        $classId = \Illuminate\Support\Facades\DB::table('kelas')->insertGetId(['nama' => 'X RPL 1']);
        $studentId = \Illuminate\Support\Facades\DB::table('siswa')->insertGetId([
            'nis' => '12345',
            'nisn' => '98765',
            'nama_lengkap' => 'Siswa Uji',
            'kelas_id' => $classId,
            'no_hp' => '08123456789',
            'status_siswa' => 'aktif',
        ]);
        \Illuminate\Support\Facades\DB::table('penempatan_pkl')->insert([
            'periode_id' => $period->id,
            'instansi_id' => $instance->id,
            'siswa_id' => $studentId,
            'tanggal_mulai' => '2026-08-01',
            'tanggal_selesai' => '2026-11-30',
            'status' => 'aktif',
        ]);

        $this->getJson("/api/v1/admin/master-data/instansi/{$instance->id}")
            ->assertOk()
            ->assertJsonPath('data.instansi.penempatan.0.siswa.nama_lengkap', 'Siswa Uji')
            ->assertJsonPath('data.instansi.penempatan.0.siswa.kelas.nama', 'X RPL 1');
    }

    public function test_instance_index_returns_quota_statistics_and_filled_count(): void
    {
        $instance = $this->createInstance();
        $period = $this->createPeriod($this->createYear()->id);
        \Illuminate\Support\Facades\DB::table('kuota_instansi_periode')->insert([
            'instansi_id' => $instance->id,
            'periode_id' => $period->id,
            'kuota_siswa' => 4,
        ]);
        $this->createPlacement($instance->id, $period->id, 'aktif');
        $this->createPlacement($instance->id, $period->id, 'selesai');

        $this->getJson('/api/v1/admin/master-data/instansi')
            ->assertOk()
            ->assertJsonPath('data.statistics.total_instansi', 1)
            ->assertJsonPath('data.statistics.total_kuota', 4)
            ->assertJsonPath('data.statistics.terisi', 1)
            ->assertJsonPath('data.instansi.0.terisi', 1)
            ->assertJsonPath('data.instansi.0.total_kuota_periode', 4);
    }

    public function test_year_and_period_dates_are_validated_against_each_other_and_placements(): void
    {
        $year = $this->createYear();

        $this->postJson('/api/v1/admin/master-data/periode-pkl', [
            'tahun_ajaran_id' => $year->id,
            'nama' => 'Periode Di Luar Tahun',
            'tanggal_mulai' => '2025-01-01',
            'tanggal_selesai' => '2025-06-01',
            'status' => 'persiapan',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('tanggal_mulai');

        $periodResponse = $this->postJson('/api/v1/admin/master-data/periode-pkl', [
            'tahun_ajaran_id' => $year->id,
            'nama' => 'Periode Uji',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-12-01',
            'status' => 'persiapan',
        ])->assertCreated();
        $periodId = $periodResponse->json('data.periode_pkl.id');

        $this->patchJson('/api/v1/admin/master-data/tahun-ajaran/'.$year->id, [
            'nama' => $year->nama,
            'tanggal_mulai' => '2026-08-01',
            'tanggal_selesai' => '2027-06-30',
            'aktif' => true,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('tanggal_mulai');

        $instance = $this->createInstance();
        $this->createPlacement($instance->id, $periodId, 'aktif');
        $this->patchJson("/api/v1/admin/master-data/periode-pkl/$periodId", [
            'tahun_ajaran_id' => $year->id,
            'nama' => 'Periode Uji',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-12-01',
            'status' => 'selesai',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_pic_belongs_to_instance_and_quota_is_pair_scoped_and_cannot_drop_below_usage(): void
    {
        $instance = $this->createInstance();
        $otherInstance = $this->createInstance('Mitra Lain');
        $pic = $this->postJson("/api/v1/admin/master-data/instansi/{$instance->id}/pic", [
            'nama' => 'PIC Uji',
            'jabatan' => 'Koordinator',
            'no_hp' => '08123456789',
            'email' => 'pic@example.test',
        ])->assertCreated()
            ->assertJsonPath('data.pic_instansi.instansi_id', $instance->id)
            ->json('data.pic_instansi.id');

        $this->patchJson("/api/v1/admin/master-data/instansi/{$instance->id}/pic/$pic/status", [
            'aktif' => false,
        ])->assertOk()
            ->assertJsonPath('data.pic_instansi.aktif', 0);
        $this->patchJson("/api/v1/admin/master-data/instansi/{$otherInstance->id}/pic/$pic", [
            'nama' => 'PIC Salah Instansi',
        ])->assertNotFound();

        $period = $this->createPeriod($this->createYear()->id);
        $this->createPlacement($instance->id, $period->id, 'belum_mulai', $pic);
        $this->putJson("/api/v1/admin/master-data/kuota/{$instance->id}/{$period->id}", [
            'kuota_siswa' => 0,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('kuota_siswa');

        $this->putJson("/api/v1/admin/master-data/kuota/{$instance->id}/{$period->id}", [
            'kuota_siswa' => null,
        ])->assertOk()
            ->assertJsonPath('data.kuota.kuota_siswa', null);
        $this->putJson("/api/v1/admin/master-data/kuota/{$instance->id}/{$period->id}", [
            'kuota_siswa' => 2,
        ])->assertOk();
        $otherPeriod = $this->createPeriod($this->createYear('2027/2028')->id);
        $this->putJson("/api/v1/admin/master-data/kuota/{$otherInstance->id}/{$otherPeriod->id}", [
            'kuota_siswa' => 5,
        ])->assertOk();
        $this->assertDatabaseCount('kuota_instansi_periode', 2);
        $this->assertDatabaseHas('kuota_instansi_periode', [
            'instansi_id' => $instance->id,
            'periode_id' => $period->id,
            'kuota_siswa' => 2,
        ]);
    }

    private function createSchema(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
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
            $table->boolean('wajib_ganti_password')->default(false);
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
        Schema::create('instansi', function (Blueprint $table): void {
            $table->id();
            $table->string('nama', 160);
            $table->string('jenis', 80)->nullable();
            $table->text('alamat');
            $table->string('kota', 100)->nullable();
            $table->string('provinsi', 100)->nullable();
            $table->string('telepon', 25)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('nama_penanggung_jawab', 120)->nullable();
            $table->unsignedInteger('kuota_siswa')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('radius_geofence_meter')->nullable();
            $table->string('status')->default('aktif');
            $table->timestamps();
        });
        Schema::create('tahun_ajaran', function (Blueprint $table): void {
            $table->id();
            $table->string('nama', 20)->unique();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->boolean('aktif')->default(false);
        });
        Schema::create('periode_pkl', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tahun_ajaran_id')->nullable();
            $table->string('nama', 100);
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->string('status');
        });
        Schema::create('pic_instansi', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('instansi_id');
            $table->string('nama', 120);
            $table->string('jabatan', 100)->nullable();
            $table->string('no_hp', 25)->nullable();
            $table->string('email', 150)->nullable();
            $table->boolean('aktif')->default(true);
        });
        Schema::create('penempatan_pkl', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('periode_id');
            $table->unsignedBigInteger('instansi_id');
            $table->unsignedBigInteger('siswa_id')->nullable();
            $table->unsignedBigInteger('pic_instansi_id')->nullable();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->string('status');
        });
        Schema::create('kuota_instansi_periode', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('instansi_id');
            $table->unsignedBigInteger('periode_id');
            $table->unsignedInteger('kuota_siswa')->nullable();
            $table->timestamps();
        });
        Schema::create('kelas', function (Blueprint $table): void {
            $table->id();
            $table->string('nama', 100);
        });
        Schema::create('siswa', function (Blueprint $table): void {
            $table->id();
            $table->string('nis', 30)->nullable();
            $table->string('nisn', 30)->nullable();
            $table->string('nama_lengkap', 160);
            $table->unsignedBigInteger('kelas_id')->nullable();
            $table->string('no_hp', 25)->nullable();
            $table->string('status_siswa', 30)->nullable();
        });
    }

    private function createYear(string $name = '2026/2027'): \App\Models\TahunAjaran
    {
        return \App\Models\TahunAjaran::query()->forceCreate([
            'nama' => $name,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2027-06-30',
            'aktif' => true,
        ]);
    }

    private function createPeriod(int $yearId): PeriodePkl
    {
        return PeriodePkl::query()->forceCreate([
            'tahun_ajaran_id' => $yearId,
            'nama' => 'PKL Semester Ganjil',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-12-01',
            'status' => 'persiapan',
        ]);
    }

    private function createInstance(string $name = 'Mitra Uji'): Instansi
    {
        $instance = new Instansi();
        $instance->forceFill([
            'nama' => $name,
            'alamat' => 'Jalan Uji',
            'status' => 'aktif',
        ])->save();

        return $instance;
    }

    private function createPlacement(int $instanceId, int $periodId, string $status, ?int $picId = null): void
    {
        \Illuminate\Support\Facades\DB::table('penempatan_pkl')->insert([
            'periode_id' => $periodId,
            'instansi_id' => $instanceId,
            'pic_instansi_id' => $picId,
            'tanggal_mulai' => '2026-08-01',
            'tanggal_selesai' => '2026-11-30',
            'status' => $status,
        ]);
    }
}
