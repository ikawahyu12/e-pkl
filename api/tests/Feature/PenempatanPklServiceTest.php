<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\Instansi;
use App\Models\KuotaInstansiPeriode;
use App\Models\PenempatanPkl;
use App\Models\PeriodePkl;
use App\Models\PicInstansi;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use App\Services\PenempatanPklService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PenempatanPklServiceTest extends TestCase
{
    private PenempatanPklService $service;
    private User $admin;
    private Siswa $siswa;
    private Guru $guru;
    private Instansi $instansi;
    private PeriodePkl $periode;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
        $this->service = new PenempatanPklService;
        $this->admin = $this->createUser('admin');
        $this->siswa = $this->createSiswa();
        $this->guru = $this->createGuru();
        $this->instansi = $this->createInstansi();
        $this->periode = $this->createPeriode();
    }

    public function test_valid_placement_is_created_with_status_and_initial_history(): void
    {
        $placement = $this->service->create($this->admin, $this->placementData());

        $this->assertSame('aktif', $placement->status);
        $this->assertDatabaseHas('riwayat_status_penempatan', [
            'penempatan_id' => $placement->id,
            'status_lama' => null,
            'status_baru' => 'aktif',
            'alasan_kode' => 'penempatan_dibuat',
            'admin_user_id' => $this->admin->id,
        ]);
    }

    public function test_inactive_student_is_rejected_without_creating_placement(): void
    {
        $this->siswa->status_siswa = 'lulus';
        $this->siswa->save();

        $this->expectException(ValidationException::class);
        try {
            $this->service->create($this->admin, $this->placementData());
        } finally {
            $this->assertDatabaseCount('penempatan_pkl', 0);
        }
    }

    public function test_student_with_nonactive_profile_is_rejected(): void
    {
        $this->siswa->status_siswa = 'nonaktif';
        $this->siswa->save();

        $this->expectException(ValidationException::class);
        $this->service->create($this->admin, $this->placementData());
    }

    public function test_student_with_nonactive_account_is_rejected(): void
    {
        $account = User::query()->findOrFail($this->siswa->user_id);
        $account->status = 'nonaktif';
        $account->save();

        $this->expectException(ValidationException::class);
        $this->service->create($this->admin, $this->placementData());
    }

    public function test_inactive_teacher_is_rejected(): void
    {
        $this->guru->status_bimbingan = 'nonaktif';
        $this->guru->save();

        $this->expectException(ValidationException::class);
        $this->service->create($this->admin, $this->placementData());
    }

    public function test_teacher_with_nonactive_account_is_rejected(): void
    {
        $account = User::query()->findOrFail($this->guru->user_id);
        $account->status = 'nonaktif';
        $account->save();

        $this->expectException(ValidationException::class);
        $this->service->create($this->admin, $this->placementData());
    }

    public function test_inactive_instance_is_rejected(): void
    {
        $this->instansi->status = 'nonaktif';
        $this->instansi->save();

        $this->expectException(ValidationException::class);
        $this->service->create($this->admin, $this->placementData());
    }

    public function test_pic_from_another_instance_is_rejected(): void
    {
        $other = $this->createInstansi('Instansi Lain');
        $pic = $this->createPic($other);

        $this->expectException(ValidationException::class);
        $this->service->create($this->admin, $this->placementData(['pic_instansi_id' => $pic->id]));
    }

    public function test_dates_outside_the_period_are_rejected(): void
    {
        $data = $this->placementData([
            'tanggal_mulai' => CarbonImmutable::today()->subMonths(2)->toDateString(),
        ]);

        $this->expectException(ValidationException::class);
        $this->service->create($this->admin, $data);
    }

    public function test_completed_period_rejects_new_placement(): void
    {
        $this->periode->status = 'selesai';
        $this->periode->save();

        $this->expectException(ValidationException::class);
        $this->service->create($this->admin, $this->placementData());
    }

    public function test_full_quota_rejects_new_placement(): void
    {
        $this->createQuota($this->instansi, 1);
        $this->createPlacement($this->createSiswa(), $this->instansi, 'aktif');

        $this->expectException(ValidationException::class);
        $this->service->create($this->admin, $this->placementData());
    }

    public function test_null_quota_allows_placement(): void
    {
        $this->createQuota($this->instansi, null);
        $this->createPlacement($this->createSiswa(), $this->instansi, 'aktif');

        $placement = $this->service->create($this->admin, $this->placementData());

        $this->assertSame('aktif', $placement->status);
    }

    public function test_completed_or_canceled_placements_do_not_use_quota(): void
    {
        $this->createQuota($this->instansi, 1);
        $this->createPlacement($this->createSiswa(), $this->instansi, 'selesai');
        $this->createPlacement($this->createSiswa(), $this->instansi, 'dibatalkan');

        $placement = $this->service->create($this->admin, $this->placementData());

        $this->assertSame('aktif', $placement->status);
    }

    public function test_duplicate_active_student_placement_in_period_is_rejected(): void
    {
        $this->createPlacement($this->siswa, $this->instansi, 'belum_mulai');

        $this->expectException(ValidationException::class);
        $this->service->create($this->admin, $this->placementData());
    }

    public function test_cancel_records_status_reason_and_admin(): void
    {
        $placement = $this->createPlacement($this->siswa, $this->instansi, 'aktif');

        $canceled = $this->service->cancel(
            $this->admin,
            $placement,
            'siswa_mengundurkan_diri'
        );

        $this->assertSame('dibatalkan', $canceled->status);
        $this->assertDatabaseHas('riwayat_status_penempatan', [
            'penempatan_id' => $placement->id,
            'status_lama' => 'aktif',
            'status_baru' => 'dibatalkan',
            'alasan_kode' => 'siswa_mengundurkan_diri',
            'admin_user_id' => $this->admin->id,
        ]);
    }

    public function test_cancel_with_other_reason_requires_a_detail(): void
    {
        $placement = $this->createPlacement($this->siswa, $this->instansi, 'aktif');

        $this->expectException(ValidationException::class);
        try {
            $this->service->cancel($this->admin, $placement, 'lainnya');
        } finally {
            $this->assertSame('aktif', $placement->fresh()->status);
            $this->assertDatabaseCount('riwayat_status_penempatan', 0);
        }
    }

    public function test_reactivation_requires_a_written_reason(): void
    {
        $placement = $this->createPlacement($this->siswa, $this->instansi, 'dibatalkan');

        $this->expectException(ValidationException::class);
        $this->service->reactivate($this->admin, $placement, '  ');
    }

    public function test_reactivation_after_end_date_is_rejected(): void
    {
        $placement = $this->createPlacement($this->siswa, $this->instansi, 'dibatalkan', [
            'tanggal_mulai' => CarbonImmutable::today()->subDays(10)->toDateString(),
            'tanggal_selesai' => CarbonImmutable::today()->subDay()->toDateString(),
        ]);

        $this->expectException(ValidationException::class);
        $this->service->reactivate($this->admin, $placement, 'Kebutuhan sekolah');
    }

    public function test_reactivation_checks_quota_and_preserves_canceled_state_if_full(): void
    {
        $this->createQuota($this->instansi, 1);
        $canceled = $this->createPlacement($this->siswa, $this->instansi, 'dibatalkan');
        $this->createPlacement($this->createSiswa(), $this->instansi, 'aktif');

        $this->expectException(ValidationException::class);
        try {
            $this->service->reactivate($this->admin, $canceled, 'Kebutuhan sekolah');
        } finally {
            $this->assertSame('dibatalkan', $canceled->fresh()->status);
        }
    }

    public function test_reactivation_sets_status_from_placement_dates_and_logs_reason(): void
    {
        $placement = $this->createPlacement($this->siswa, $this->instansi, 'dibatalkan');

        $reactivated = $this->service->reactivate($this->admin, $placement, 'Jadwal PKL dikonfirmasi');

        $this->assertSame('aktif', $reactivated->status);
        $this->assertDatabaseHas('riwayat_status_penempatan', [
            'penempatan_id' => $placement->id,
            'status_lama' => 'dibatalkan',
            'status_baru' => 'aktif',
            'alasan_kode' => 'reaktivasi',
            'alasan_detail' => 'Jadwal PKL dikonfirmasi',
            'admin_user_id' => $this->admin->id,
        ]);
    }

    public function test_reactivation_before_start_date_sets_not_started_status(): void
    {
        $placement = $this->createPlacement($this->siswa, $this->instansi, 'dibatalkan', [
            'tanggal_mulai' => CarbonImmutable::today()->addDays(2)->toDateString(),
            'tanggal_selesai' => CarbonImmutable::today()->addDays(5)->toDateString(),
        ]);

        $reactivated = $this->service->reactivate($this->admin, $placement, 'Jadwal belum dimulai');

        $this->assertSame('belum_mulai', $reactivated->status);
    }

    public function test_reactivation_rejects_another_active_placement_for_the_student(): void
    {
        $canceled = $this->createPlacement($this->siswa, $this->instansi, 'dibatalkan');
        $this->createPlacement($this->siswa, $this->createInstansi('Instansi Lain'), 'aktif');

        $this->expectException(ValidationException::class);
        $this->service->reactivate($this->admin, $canceled, 'Penempatan dikonfirmasi');
    }

    public function test_move_cancels_old_placement_and_creates_new_one(): void
    {
        $old = $this->createPlacement($this->siswa, $this->instansi, 'aktif');
        $target = $this->createInstansi('Instansi Tujuan');

        $new = $this->service->move(
            $this->admin,
            $old,
            $this->placementData(['instansi_id' => $target->id]),
            'perubahan_penempatan_dari_sekolah'
        );

        $this->assertSame('dibatalkan', $old->fresh()->status);
        $this->assertSame($target->id, $new->instansi_id);
        $this->assertDatabaseCount('penempatan_pkl', 2);
    }

    public function test_move_is_rejected_when_student_has_another_active_placement_in_same_period(): void
    {
        $old = $this->createPlacement($this->siswa, $this->instansi, 'aktif');
        $otherActive = $this->createPlacement(
            $this->siswa,
            $this->createInstansi('Instansi Aktif Lain'),
            'belum_mulai'
        );
        $target = $this->createInstansi('Instansi Tujuan');

        $this->expectException(ValidationException::class);
        try {
            $this->service->move(
                $this->admin,
                $old,
                $this->placementData(['instansi_id' => $target->id]),
                'perubahan_penempatan_dari_sekolah'
            );
        } finally {
            $this->assertSame('aktif', $old->fresh()->status);
            $this->assertSame('belum_mulai', $otherActive->fresh()->status);
            $this->assertDatabaseCount('penempatan_pkl', 2);
            $this->assertDatabaseCount('riwayat_status_penempatan', 0);
        }
    }

    public function test_move_rejects_student_with_nonactive_account_without_changing_old_placement(): void
    {
        $old = $this->createPlacement($this->siswa, $this->instansi, 'aktif');
        $target = $this->createInstansi('Instansi Tujuan');
        $account = User::query()->findOrFail($this->siswa->user_id);
        $account->status = 'nonaktif';
        $account->save();

        $this->expectException(ValidationException::class);
        try {
            $this->service->move(
                $this->admin,
                $old,
                $this->placementData(['instansi_id' => $target->id]),
                'perubahan_penempatan_dari_sekolah'
            );
        } finally {
            $this->assertSame('aktif', $old->fresh()->status);
            $this->assertDatabaseCount('penempatan_pkl', 1);
        }
    }

    public function test_move_rolls_back_old_cancellation_when_new_placement_fails(): void
    {
        $old = $this->createPlacement($this->siswa, $this->instansi, 'aktif');
        $target = $this->createInstansi('Instansi Penuh');
        $this->createQuota($target, 1);
        $this->createPlacement($this->createSiswa(), $target, 'aktif');

        $this->expectException(ValidationException::class);
        try {
            $this->service->move(
                $this->admin,
                $old,
                $this->placementData(['instansi_id' => $target->id]),
                'perubahan_penempatan_dari_sekolah'
            );
        } finally {
            $this->assertSame('aktif', $old->fresh()->status);
            $this->assertDatabaseCount('penempatan_pkl', 2);
        }
    }

    public function test_scheduled_status_sync_starts_and_completes_placements_with_system_history(): void
    {
        $today = CarbonImmutable::today('Asia/Jakarta');
        $starting = $this->createPlacement($this->siswa, $this->instansi, 'belum_mulai', [
            'tanggal_mulai' => $today->toDateString(),
            'tanggal_selesai' => $today->addDays(3)->toDateString(),
        ]);
        $expired = $this->createPlacement($this->createSiswa(), $this->createInstansi('Instansi Kedua'), 'aktif', [
            'tanggal_mulai' => $today->subDays(3)->toDateString(),
            'tanggal_selesai' => $today->subDay()->toDateString(),
        ]);
        $future = $this->createPlacement($this->createSiswa(), $this->createInstansi('Instansi Ketiga'), 'belum_mulai', [
            'tanggal_mulai' => $today->addDay()->toDateString(),
            'tanggal_selesai' => $today->addDays(4)->toDateString(),
        ]);

        Artisan::call('epkl:sync-placement-statuses');

        $this->assertSame('aktif', $starting->fresh()->status);
        $this->assertSame('selesai', $expired->fresh()->status);
        $this->assertSame('belum_mulai', $future->fresh()->status);
        $this->assertDatabaseHas('riwayat_status_penempatan', [
            'penempatan_id' => $starting->id,
            'status_lama' => 'belum_mulai',
            'status_baru' => 'aktif',
            'alasan_kode' => 'otomatis_mulai',
            'admin_user_id' => null,
        ]);
        $this->assertDatabaseHas('riwayat_status_penempatan', [
            'penempatan_id' => $expired->id,
            'status_lama' => 'aktif',
            'status_baru' => 'selesai',
            'alasan_kode' => 'otomatis_selesai',
            'admin_user_id' => null,
        ]);

        Artisan::call('epkl:sync-placement-statuses');
        $this->assertDatabaseCount('riwayat_status_penempatan', 2);
    }

    public function test_non_admin_cannot_manage_placements(): void
    {
        $siswaUser = $this->createUser('siswa');

        $this->expectException(ValidationException::class);
        $this->service->create($siswaUser, $this->placementData());
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
            $table->timestamps();
        });
        Schema::create('siswa', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('nisn');
            $table->string('nama_lengkap');
            $table->string('status_siswa')->default('aktif');
            $table->timestamps();
        });
        Schema::create('guru', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('nip');
            $table->string('nama_lengkap');
            $table->string('status_bimbingan')->default('aktif');
            $table->timestamps();
        });
        Schema::create('instansi', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->string('status')->default('aktif');
            $table->timestamps();
        });
        Schema::create('pic_instansi', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('instansi_id');
            $table->string('nama');
            $table->boolean('aktif')->default(true);
        });
        Schema::create('periode_pkl', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tahun_ajaran_id')->nullable();
            $table->string('nama');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->string('status');
        });
        Schema::create('penempatan_pkl', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('siswa_id');
            $table->unsignedBigInteger('periode_id');
            $table->unsignedBigInteger('instansi_id');
            $table->unsignedBigInteger('guru_id');
            $table->unsignedBigInteger('pic_instansi_id')->nullable();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->string('status');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
        Schema::create('kuota_instansi_periode', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('instansi_id');
            $table->unsignedBigInteger('periode_id');
            $table->unsignedInteger('kuota_siswa')->nullable();
            $table->timestamps();
        });
        Schema::create('riwayat_status_penempatan', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('penempatan_id');
            $table->string('status_lama', 20)->nullable();
            $table->string('status_baru', 20);
            $table->string('alasan_kode', 60);
            $table->text('alasan_detail')->nullable();
            $table->unsignedBigInteger('admin_user_id')->nullable();
            $table->timestamp('created_at');
        });
        Schema::create('riwayat_perubahan_penempatan', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('penempatan_id');
            $table->unsignedBigInteger('admin_user_id');
            $table->json('perubahan');
            $table->text('alasan');
            $table->timestamp('created_at');
        });
    }

    private function createUser(string $roleName): User
    {
        $role = Role::query()->create(['nama' => $roleName]);

        return User::query()->forceCreate([
            'role_id' => $role->id,
            'nama_tampilan' => ucfirst($roleName) . ' Uji',
            'status' => 'aktif',
        ]);
    }

    private function createSiswa(): Siswa
    {
        return Siswa::query()->forceCreate([
            'user_id' => $this->createUser('siswa')->id,
            'nisn' => 'nisn-' . uniqid(),
            'nama_lengkap' => 'Siswa Uji',
            'status_siswa' => 'aktif',
        ]);
    }

    private function createGuru(): Guru
    {
        return Guru::query()->forceCreate([
            'user_id' => $this->createUser('guru')->id,
            'nip' => 'nip-' . uniqid(),
            'nama_lengkap' => 'Guru Uji',
            'status_bimbingan' => 'aktif',
        ]);
    }

    private function createInstansi(string $name = 'Instansi Uji'): Instansi
    {
        return Instansi::query()->forceCreate([
            'nama' => $name,
            'status' => 'aktif',
        ]);
    }

    private function createPeriode(): PeriodePkl
    {
        return PeriodePkl::query()->forceCreate([
            'nama' => 'Periode Uji',
            'tanggal_mulai' => CarbonImmutable::today()->subMonth()->toDateString(),
            'tanggal_selesai' => CarbonImmutable::today()->addMonth()->toDateString(),
            'status' => 'berjalan',
        ]);
    }

    private function createPic(Instansi $instansi): PicInstansi
    {
        return PicInstansi::query()->forceCreate([
            'instansi_id' => $instansi->id,
            'nama' => 'PIC Uji',
            'aktif' => true,
        ]);
    }

    private function createQuota(Instansi $instansi, ?int $quota): KuotaInstansiPeriode
    {
        return KuotaInstansiPeriode::query()->create([
            'instansi_id' => $instansi->id,
            'periode_id' => $this->periode->id,
            'kuota_siswa' => $quota,
        ]);
    }

    private function createPlacement(
        Siswa $siswa,
        Instansi $instansi,
        string $status,
        array $overrides = []
    ): PenempatanPkl {
        return PenempatanPkl::query()->forceCreate(array_merge([
            'siswa_id' => $siswa->id,
            'periode_id' => $this->periode->id,
            'instansi_id' => $instansi->id,
            'guru_id' => $this->guru->id,
            'tanggal_mulai' => CarbonImmutable::today()->subDays(2)->toDateString(),
            'tanggal_selesai' => CarbonImmutable::today()->addDays(2)->toDateString(),
            'status' => $status,
        ], $overrides));
    }

    private function placementData(array $overrides = []): array
    {
        return array_merge([
            'siswa_id' => $this->siswa->id,
            'periode_id' => $this->periode->id,
            'instansi_id' => $this->instansi->id,
            'guru_id' => $this->guru->id,
            'tanggal_mulai' => CarbonImmutable::today()->subDay()->toDateString(),
            'tanggal_selesai' => CarbonImmutable::today()->addDay()->toDateString(),
        ], $overrides);
    }
}
