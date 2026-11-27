<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminDashboardMonitoringTest extends TestCase
{
    private User $admin;
    private int $instanceId;
    private int $settingsId;
    private int $teacherUserId;
    private int $teacherId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-11-20 10:00:00', 'Asia/Jakarta'));
        $this->createSchema();

        $adminRole = Role::query()->create(['nama' => 'admin']);
        $this->admin = User::query()->forceCreate([
            'role_id' => $adminRole->id,
            'username' => 'admin-dashboard',
            'nama_tampilan' => 'Admin Dashboard',
            'status' => 'aktif',
            'wajib_ganti_password' => false,
        ]);
        $teacherRole = Role::query()->create(['nama' => 'guru']);
        $teacher = User::query()->forceCreate([
            'role_id' => $teacherRole->id,
            'username' => 'teacher-dashboard',
            'nama_tampilan' => 'Guru Pembimbing',
            'status' => 'aktif',
            'wajib_ganti_password' => false,
        ]);
        $this->teacherUserId = (int) $teacher->id;
        $this->teacherId = (int) DB::table('guru')->insertGetId(['user_id' => $teacher->id]);

        $this->instanceId = DB::table('instansi')->insertGetId(['nama' => 'Instansi Utama']);
        $this->settingsId = DB::table('pengaturan_presensi')->insertGetId([
            'instansi_id' => $this->instanceId,
            'aktif' => true,
        ]);
    }

    public function test_monitoring_requires_admin_and_returns_live_unique_student_statuses(): void
    {
        $this->getJson('/api/v1/admin/dashboard-monitoring')->assertUnauthorized();

        $this->actingAs(User::query()->findOrFail($this->teacherUserId), 'sanctum')
            ->getJson('/api/v1/admin/dashboard-monitoring')
            ->assertForbidden();

        $hadir = $this->createPlacement('Siswa Hadir', '1001');
        $izin = $this->createPlacement('Siswa Izin', '1002');
        $sakit = $this->createPlacement('Siswa Sakit', '1007');
        $alpha = $this->createPlacement('Siswa Alpha', '1003');
        $closedSessionNoAttendance = $this->createPlacement('Siswa Tidak Hadir', '1004');
        $openSession = $this->createPlacement('Siswa Belum Presensi', '1005');
        $this->createStudent('Siswa Tanpa Penempatan', '1006');
        $this->createStudent('Siswa Nonaktif', '1008', studentStatus: 'nonaktif');

        $openSessionId = $this->createSession('dibuka', [$hadir, $izin, $sakit, $openSession]);
        $closedSessionId = $this->createSession('ditutup', [$hadir, $alpha, $closedSessionNoAttendance]);

        DB::table('presensi')->insert([
            [
                'sesi_id' => $openSessionId,
                'penempatan_id' => $hadir,
                'tanggal' => '2026-11-20',
                'waktu_masuk' => '2026-11-20 08:03:00',
                'status_harian' => 'hadir',
            ],
            [
                'sesi_id' => $closedSessionId,
                'penempatan_id' => $hadir,
                'tanggal' => '2026-11-20',
                'waktu_masuk' => '2026-11-20 07:51:00',
                'status_harian' => 'hadir',
            ],
            [
                'sesi_id' => $closedSessionId,
                'penempatan_id' => $alpha,
                'tanggal' => '2026-11-20',
                'waktu_masuk' => null,
                'status_harian' => 'alpha',
            ],
        ]);

        foreach ([
            [$izin, 'izin', 'menunggu'],
            [$izin, 'izin', 'disetujui'],
            [$sakit, 'sakit', 'disetujui'],
        ] as [$placementId, $kind, $status]) {
            DB::table('pengajuan_izin')->insert([
                'penempatan_id' => $placementId,
                'jenis' => $kind,
                'tanggal_mulai' => '2026-11-20',
                'tanggal_selesai' => '2026-11-20',
                'status' => $status,
            ]);
        }

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/dashboard-monitoring')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.tanggal', '2026-11-20')
            ->assertJsonPath('data.generated_at', '2026-11-20T10:00:00+07:00')
            ->assertJsonPath('data.statistics.total_siswa', 7)
            ->assertJsonPath('data.statistics.hadir', 1)
            ->assertJsonPath('data.statistics.izin', 1)
            ->assertJsonPath('data.statistics.tidak_hadir', 2)
            ->assertJsonCount(6, 'data.rows');

        $rows = collect($response->json('data.rows'))->keyBy('name');
        $this->assertSame('07:51', $rows['Siswa Hadir']['time']);
        $this->assertSame('Hadir', $rows['Siswa Hadir']['status']);
        $this->assertSame('Izin', $rows['Siswa Izin']['status']);
        $this->assertSame('Sakit', $rows['Siswa Sakit']['status']);
        $this->assertSame('Tidak Hadir', $rows['Siswa Alpha']['status']);
        $this->assertSame('Tidak Hadir', $rows['Siswa Tidak Hadir']['status']);
        $this->assertSame('Belum Presensi', $rows['Siswa Belum Presensi']['status']);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    private function createStudent(
        string $name,
        string $nis,
        string $studentStatus = 'aktif'
    ): int {
        $role = Role::query()->firstOrCreate(['nama' => 'siswa']);
        $user = User::query()->forceCreate([
            'role_id' => $role->id,
            'username' => 'user-'.$nis,
            'nama_tampilan' => $name,
            'status' => 'aktif',
            'wajib_ganti_password' => false,
        ]);
        $student = Siswa::query()->forceCreate([
            'user_id' => $user->id,
            'nisn' => 'nisn-'.$nis,
            'nis' => $nis,
            'nama_lengkap' => $name,
            'status_siswa' => $studentStatus,
        ]);

        return (int) $student->id;
    }

    private function createPlacement(string $name, string $nis): int
    {
        $studentId = $this->createStudent($name, $nis);

        return (int) DB::table('penempatan_pkl')->insertGetId([
            'siswa_id' => $studentId,
            'instansi_id' => $this->instanceId,
            'guru_id' => $this->teacherId,
            'tanggal_mulai' => '2026-11-01',
            'tanggal_selesai' => '2026-11-30',
            'status' => 'aktif',
        ]);
    }

    /**
     * @param array<int, int> $placementIds
     */
    private function createSession(string $status, array $placementIds): int
    {
        $sessionId = DB::table('sesi_presensi')->insertGetId([
            'pengaturan_id' => $this->settingsId,
            'tanggal' => '2026-11-20',
            'status' => $status,
            'dibuat_oleh' => $this->teacherUserId,
        ]);

        DB::table('sesi_presensi_penempatan')->insert(
            array_map(
                static fn (int $placementId): array => [
                    'sesi_id' => $sessionId,
                    'penempatan_id' => $placementId,
                ],
                $placementIds
            )
        );

        return $sessionId;
    }

    private function createSchema(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('nama')->unique();
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->string('username')->unique();
            $table->string('nama_tampilan');
            $table->string('status')->default('aktif');
            $table->boolean('wajib_ganti_password')->default(false);
            $table->timestamps();
        });
        Schema::create('siswa', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('nisn')->unique();
            $table->string('nis')->nullable();
            $table->string('nama_lengkap');
            $table->string('status_siswa')->default('aktif');
            $table->timestamps();
        });
        Schema::create('instansi', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
        });
        Schema::create('guru', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
        });
        Schema::create('penempatan_pkl', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('siswa_id');
            $table->unsignedBigInteger('instansi_id');
            $table->unsignedBigInteger('guru_id');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->string('status');
        });
        Schema::create('pengaturan_presensi', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('instansi_id');
            $table->boolean('aktif')->default(true);
        });
        Schema::create('sesi_presensi', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pengaturan_id');
            $table->date('tanggal');
            $table->string('status');
            $table->unsignedBigInteger('dibuat_oleh')->nullable();
        });
        Schema::create('sesi_presensi_penempatan', function (Blueprint $table): void {
            $table->unsignedBigInteger('sesi_id');
            $table->unsignedBigInteger('penempatan_id');
        });
        Schema::create('presensi', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('sesi_id');
            $table->unsignedBigInteger('penempatan_id');
            $table->date('tanggal');
            $table->dateTime('waktu_masuk')->nullable();
            $table->string('status_harian')->default('belum_lengkap');
        });
        Schema::create('pengajuan_izin', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('penempatan_id');
            $table->string('jenis');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->string('status')->default('menunggu');
        });
    }
}
