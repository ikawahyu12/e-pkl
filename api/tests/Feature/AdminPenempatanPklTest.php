<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\Instansi;
use App\Models\PenempatanPkl;
use App\Models\PeriodePkl;
use App\Models\PicInstansi;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminPenempatanPklTest extends TestCase
{
    private User $admin;
    private Siswa $siswa;
    private Guru $guru;
    private Instansi $instansi;
    private PeriodePkl $periode;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
        $this->admin = $this->createUser('admin', 'admin-user');
        $this->siswa = $this->createSiswa('10001', 'Siswa Uji');
        $this->guru = $this->createGuru('20001', 'Guru Uji');
        $this->instansi = $this->createInstansi('Instansi Uji');
        $this->periode = $this->createPeriode();
    }

    public function test_admin_penempatan_api_requires_authentication_and_admin_role(): void
    {
        $this->getJson('/api/v1/admin/penempatan-pkl')->assertUnauthorized();

        $guru = $this->createUser('guru', 'guru-user');
        $this->actingAs($guru, 'sanctum')
            ->getJson('/api/v1/admin/penempatan-pkl')
            ->assertForbidden();
    }

    public function test_reference_endpoint_requires_an_authenticated_admin(): void
    {
        $this->getJson('/api/v1/admin/penempatan-pkl/referensi')->assertUnauthorized();

        $guru = $this->createUser('guru', 'reference-guru-user');
        $this->actingAs($guru, 'sanctum')
            ->getJson('/api/v1/admin/penempatan-pkl/referensi')
            ->assertForbidden();

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/penempatan-pkl/referensi')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_references_only_include_eligible_student_and_teacher_profiles(): void
    {
        $inactiveStudentAccount = $this->createSiswa('10002', 'Siswa Akun Nonaktif');
        $inactiveStudentAccount->user->status = 'nonaktif';
        $inactiveStudentAccount->user->save();
        $inactiveStudentProfile = $this->createSiswa('10003', 'Siswa Profil Nonaktif');
        $inactiveStudentProfile->status_siswa = 'nonaktif';
        $inactiveStudentProfile->save();

        $inactiveTeacherAccount = $this->createGuru('20002', 'Guru Akun Nonaktif');
        $inactiveTeacherAccount->user->status = 'nonaktif';
        $inactiveTeacherAccount->user->save();
        $inactiveTeacherProfile = $this->createGuru('20003', 'Guru Profil Nonaktif');
        $inactiveTeacherProfile->status_bimbingan = 'nonaktif';
        $inactiveTeacherProfile->save();

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/penempatan-pkl/referensi')
            ->assertOk()
            ->assertJsonPath('data.siswa.0.id', $this->siswa->id)
            ->assertJsonPath('data.siswa.0.profile_id', $this->siswa->id)
            ->assertJsonPath('data.guru.0.id', $this->guru->id)
            ->assertJsonPath('data.guru.0.profile_id', $this->guru->id)
            ->assertJsonCount(1, 'data.siswa')
            ->assertJsonCount(1, 'data.guru');
    }

    public function test_reference_search_pagination_and_selected_profile_ids_are_preserved(): void
    {
        $studentTwo = $this->createSiswa('10002', 'Siswa Kedua');
        $studentThree = $this->createSiswa('10003', 'Siswa Ketiga');
        $teacherTwo = $this->createGuru('20002', 'Guru Kedua');
        $teacherThree = $this->createGuru('20003', 'Guru Ketiga');

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/penempatan-pkl/referensi?siswa_page=2&guru_page=2&per_page=1')
            ->assertOk()
            ->assertJsonPath('data.pagination.siswa.current_page', 2)
            ->assertJsonPath('data.pagination.siswa.total', 3)
            ->assertJsonPath('data.siswa.0.id', $studentThree->id)
            ->assertJsonPath('data.pagination.guru.current_page', 2)
            ->assertJsonPath('data.pagination.guru.total', 3)
            ->assertJsonPath('data.guru.0.id', $teacherThree->id);

        $this->getJson('/api/v1/admin/penempatan-pkl/referensi?siswa_search=10002&guru_search=20002&siswa_id='.$studentTwo->id.'&guru_id='.$teacherTwo->id)
            ->assertOk()
            ->assertJsonPath('data.pagination.siswa.total', 1)
            ->assertJsonPath('data.siswa.0.profile_id', $studentTwo->id)
            ->assertJsonPath('data.selected.siswa.id', $studentTwo->id)
            ->assertJsonPath('data.pagination.guru.total', 1)
            ->assertJsonPath('data.guru.0.profile_id', $teacherTwo->id)
            ->assertJsonPath('data.selected.guru.id', $teacherTwo->id);

        $this->getJson('/api/v1/admin/penempatan-pkl/referensi?per_page=101')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');
    }

    public function test_reference_lists_filter_periods_instances_pics_and_quota_by_pair(): void
    {
        $finishedPeriod = PeriodePkl::query()->forceCreate([
            'nama' => 'Periode Selesai',
            'tanggal_mulai' => CarbonImmutable::today()->subYear()->toDateString(),
            'tanggal_selesai' => CarbonImmutable::today()->subMonths(6)->toDateString(),
            'status' => 'selesai',
        ]);
        $inactiveInstance = $this->createInstansi('Instansi Nonaktif');
        $inactiveInstance->status = 'nonaktif';
        $inactiveInstance->save();
        $activePic = PicInstansi::query()->forceCreate([
            'instansi_id' => $this->instansi->id,
            'nama' => 'PIC Aktif',
            'aktif' => true,
        ]);
        $inactivePic = PicInstansi::query()->forceCreate([
            'instansi_id' => $this->instansi->id,
            'nama' => 'PIC Nonaktif',
            'aktif' => false,
        ]);
        $otherInstancePic = PicInstansi::query()->forceCreate([
            'instansi_id' => $inactiveInstance->id,
            'nama' => 'PIC Instansi Lain',
            'aktif' => true,
        ]);
        \App\Models\KuotaInstansiPeriode::query()->create([
            'instansi_id' => $this->instansi->id,
            'periode_id' => $this->periode->id,
            'kuota_siswa' => 3,
        ]);
        $this->createPlacement($this->siswa, $this->instansi, 'aktif');
        $this->createPlacement($this->createSiswa('10004', 'Siswa Dibatalkan'), $this->instansi, 'dibatalkan');

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/penempatan-pkl/referensi?instansi_id='.$this->instansi->id.'&periode_id='.$this->periode->id)
            ->assertOk()
            ->assertJsonCount(1, 'data.periode')
            ->assertJsonCount(1, 'data.instansi')
            ->assertJsonCount(1, 'data.pic_instansi')
            ->assertJsonPath('data.pic_instansi.0.id', $activePic->id)
            ->assertJsonPath('data.kuota.kuota_siswa', 3)
            ->assertJsonPath('data.kuota.terpakai', 1)
            ->assertJsonPath('data.kuota.sisa', 2)
            ->assertJsonPath('data.kuota.tanpa_batas', false);
    }

    public function test_inactive_admin_cannot_access_placement_list_or_detail(): void
    {
        $placement = $this->createPlacement($this->siswa, $this->instansi);
        $this->admin->status = 'nonaktif';
        $this->admin->save();
        $this->actingAs($this->admin, 'sanctum');

        $this->getJson('/api/v1/admin/penempatan-pkl')->assertForbidden();
        $this->getJson('/api/v1/admin/penempatan-pkl/'.$placement->id)->assertForbidden();
    }

    public function test_admin_can_list_and_view_placement_with_related_data_and_history(): void
    {
        $this->actingAs($this->admin, 'sanctum');
        $placement = $this->createPlacement($this->siswa, $this->instansi);
        $this->createHistory($placement, 'belum_mulai', 'aktif', 'reaktivasi');

        $this->getJson('/api/v1/admin/penempatan-pkl?search=10001&periode_id='.$this->periode->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.penempatan.0.siswa.nisn', '10001')
            ->assertJsonPath('data.penempatan.0.periode.id', $this->periode->id)
            ->assertJsonPath('data.penempatan.0.instansi.nama', 'Instansi Uji')
            ->assertJsonPath('data.penempatan.0.guru.nip', '20001')
            ->assertJsonPath('data.penempatan.0.riwayat_status.0.alasan_kode', 'reaktivasi');

        $this->getJson('/api/v1/admin/penempatan-pkl/'.$placement->id)
            ->assertOk()
            ->assertJsonPath('data.penempatan.id', $placement->id)
            ->assertJsonPath('data.penempatan.riwayat_status.0.admin.id', $this->admin->id);
    }

    public function test_placement_detail_includes_teacher_reassignment_history(): void
    {
        $replacement = $this->createGuru('20002', 'Guru Pengganti');
        $placement = $this->createPlacement($this->siswa, $this->instansi);
        \DB::table('riwayat_pergantian_guru')->insert([
            'penempatan_id' => $placement->id,
            'guru_lama_id' => $this->guru->id,
            'guru_baru_id' => $replacement->id,
            'admin_user_id' => $this->admin->id,
            'alasan' => 'Penyesuaian pembimbing',
            'created_at' => now(),
        ]);
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/penempatan-pkl/'.$placement->id)
            ->assertOk()
            ->assertJsonPath('data.penempatan.riwayat_guru.0.guru_lama.id', $this->guru->id)
            ->assertJsonPath('data.penempatan.riwayat_guru.0.guru_baru.id', $replacement->id)
            ->assertJsonPath('data.penempatan.riwayat_guru.0.alasan', 'Penyesuaian pembimbing')
            ->assertJsonPath('data.penempatan.riwayat_guru.0.admin.id', $this->admin->id);
    }

    public function test_list_filters_by_status_instance_and_teacher(): void
    {
        $this->actingAs($this->admin, 'sanctum');
        $matching = $this->createPlacement($this->siswa, $this->instansi, 'aktif');
        $otherInstance = $this->createInstansi('Instansi Lain');
        $otherGuru = $this->createGuru('20002', 'Guru Kedua');
        $this->createPlacement(
            $this->createSiswa('10002', 'Siswa Kedua'),
            $otherInstance,
            'dibatalkan',
            ['guru_id' => $otherGuru->id]
        );

        $base = '/api/v1/admin/penempatan-pkl?';
        $this->getJson($base.'status=aktif')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.penempatan.0.id', $matching->id);
        $this->getJson($base.'instansi_id='.$this->instansi->id)
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.penempatan.0.instansi.id', $this->instansi->id);
        $this->getJson($base.'guru_id='.$this->guru->id)
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.penempatan.0.guru.id', $this->guru->id);
    }

    public function test_list_paginates_and_caps_page_size_at_100(): void
    {
        $this->actingAs($this->admin, 'sanctum');
        for ($index = 1; $index <= 3; $index++) {
            $student = $this->createSiswa('nisn-'.$index, 'Siswa '.$index);
            $this->createPlacement($student, $this->instansi);
        }

        $this->getJson('/api/v1/admin/penempatan-pkl?per_page=2&page=2')
            ->assertOk()
            ->assertJsonPath('data.pagination.current_page', 2)
            ->assertJsonPath('data.pagination.per_page', 2)
            ->assertJsonPath('data.pagination.total', 3)
            ->assertJsonCount(1, 'data.penempatan');

        $this->getJson('/api/v1/admin/penempatan-pkl?per_page=100')
            ->assertOk()
            ->assertJsonPath('data.pagination.per_page', 100);
        $this->getJson('/api/v1/admin/penempatan-pkl?per_page=101')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');
    }

    public function test_pic_is_returned_when_selected_and_null_when_absent(): void
    {
        $this->actingAs($this->admin, 'sanctum');
        $pic = PicInstansi::query()->forceCreate([
            'instansi_id' => $this->instansi->id,
            'nama' => 'PIC Uji',
            'jabatan' => 'Pembimbing Perusahaan',
            'no_hp' => '08123456789',
            'email' => 'pic@example.test',
            'aktif' => true,
        ]);
        $withPic = $this->createPlacement($this->siswa, $this->instansi, 'aktif', [
            'pic_instansi_id' => $pic->id,
        ]);
        $withoutPic = $this->createPlacement(
            $this->createSiswa('10003', 'Siswa Tanpa PIC'),
            $this->instansi,
            'aktif'
        );

        $this->getJson('/api/v1/admin/penempatan-pkl/'.$withPic->id)
            ->assertOk()
            ->assertJsonPath('data.penempatan.pic.id', $pic->id)
            ->assertJsonPath('data.penempatan.pic.nama', 'PIC Uji');
        $this->getJson('/api/v1/admin/penempatan-pkl/'.$withoutPic->id)
            ->assertOk()
            ->assertJsonPath('data.penempatan.pic', null);
    }

    public function test_admin_can_create_placement_and_request_cannot_supply_actor_or_status(): void
    {
        $this->actingAs($this->admin, 'sanctum');

        $this->postJson('/api/v1/admin/penempatan-pkl', $this->placementPayload())
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.penempatan.siswa.id', $this->siswa->id)
            ->assertJsonPath('data.penempatan.status', 'aktif');

        $this->assertDatabaseHas('riwayat_status_penempatan', [
            'admin_user_id' => $this->admin->id,
            'alasan_kode' => 'penempatan_dibuat',
        ]);

        $this->postJson('/api/v1/admin/penempatan-pkl', $this->placementPayload([
            'admin_user_id' => $this->admin->id,
            'status' => 'dibatalkan',
        ]))->assertUnprocessable();
    }

    public function test_admin_can_cancel_placement_and_reason_is_logged(): void
    {
        $this->actingAs($this->admin, 'sanctum');
        $placement = $this->createPlacement($this->siswa, $this->instansi);

        $this->postJson('/api/v1/admin/penempatan-pkl/'.$placement->id.'/batalkan', [
            'alasan_kode' => 'siswa_mengundurkan_diri',
        ])
            ->assertOk()
            ->assertJsonPath('data.penempatan.status', 'dibatalkan');

        $this->assertDatabaseHas('riwayat_status_penempatan', [
            'penempatan_id' => $placement->id,
            'status_lama' => 'aktif',
            'status_baru' => 'dibatalkan',
            'alasan_kode' => 'siswa_mengundurkan_diri',
            'admin_user_id' => $this->admin->id,
        ]);
    }

    public function test_cancel_validation_requires_detail_for_other_reason(): void
    {
        $this->actingAs($this->admin, 'sanctum');
        $placement = $this->createPlacement($this->siswa, $this->instansi);

        $this->postJson('/api/v1/admin/penempatan-pkl/'.$placement->id.'/batalkan', [
            'alasan_kode' => 'lainnya',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('alasan_detail');

        $this->assertSame('aktif', $placement->fresh()->status);
    }

    public function test_reactivation_requires_reason_and_activates_canceled_placement(): void
    {
        $this->actingAs($this->admin, 'sanctum');
        $placement = $this->createPlacement($this->siswa, $this->instansi, 'dibatalkan');

        $this->postJson('/api/v1/admin/penempatan-pkl/'.$placement->id.'/aktifkan-kembali')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('alasan');

        $this->postJson('/api/v1/admin/penempatan-pkl/'.$placement->id.'/aktifkan-kembali', [
            'alasan' => 'Jadwal PKL dikonfirmasi',
        ])
            ->assertOk()
            ->assertJsonPath('data.penempatan.status', 'aktif')
            ->assertJsonPath('data.penempatan.riwayat_status.0.alasan_kode', 'reaktivasi');
    }

    public function test_move_creates_replacement_and_logs_old_placement_cancellation(): void
    {
        $this->actingAs($this->admin, 'sanctum');
        $old = $this->createPlacement($this->siswa, $this->instansi);
        $target = $this->createInstansi('Instansi Tujuan');

        $this->postJson('/api/v1/admin/penempatan-pkl/'.$old->id.'/pindahkan', [
            'instansi_id' => $target->id,
            'guru_id' => $this->guru->id,
            'tanggal_mulai' => CarbonImmutable::today()->subDay()->toDateString(),
            'tanggal_selesai' => CarbonImmutable::today()->addDay()->toDateString(),
            'alasan_kode' => 'perubahan_penempatan_dari_sekolah',
        ])
            ->assertCreated()
            ->assertJsonPath('data.penempatan.instansi.id', $target->id);

        $this->assertSame('dibatalkan', $old->fresh()->status);
        $this->assertDatabaseHas('riwayat_status_penempatan', [
            'penempatan_id' => $old->id,
            'status_baru' => 'dibatalkan',
            'alasan_kode' => 'perubahan_penempatan_dari_sekolah',
        ]);
        $this->assertDatabaseCount('penempatan_pkl', 2);
    }

    public function test_invalid_request_and_missing_placement_return_expected_errors(): void
    {
        $this->actingAs($this->admin, 'sanctum');

        $this->postJson('/api/v1/admin/penempatan-pkl', [
            'siswa_id' => 'invalid',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['siswa_id', 'periode_id', 'instansi_id', 'guru_id']);
    }

    public function test_all_missing_placement_endpoints_return_the_same_json_404(): void
    {
        $this->actingAs($this->admin, 'sanctum');
        $expected = [
            'success' => false,
            'message' => 'Penempatan PKL tidak ditemukan.',
        ];

        $this->getJson('/api/v1/admin/penempatan-pkl/999')
            ->assertNotFound()
            ->assertExactJson($expected);
        $this->postJson('/api/v1/admin/penempatan-pkl/999/batalkan', [
            'alasan_kode' => 'siswa_mengundurkan_diri',
        ])
            ->assertNotFound()
            ->assertExactJson($expected);
        $this->postJson('/api/v1/admin/penempatan-pkl/999/aktifkan-kembali', [
            'alasan' => 'Alasan reaktivasi',
        ])
            ->assertNotFound()
            ->assertExactJson($expected);
        $this->postJson('/api/v1/admin/penempatan-pkl/999/pindahkan', [
            'instansi_id' => $this->createInstansi('Instansi Tujuan')->id,
            'guru_id' => $this->guru->id,
            'tanggal_mulai' => CarbonImmutable::today()->subDay()->toDateString(),
            'tanggal_selesai' => CarbonImmutable::today()->addDay()->toDateString(),
            'alasan_kode' => 'perubahan_penempatan_dari_sekolah',
        ])
            ->assertNotFound()
            ->assertExactJson($expected);
    }

    public function test_admin_with_required_password_change_is_blocked(): void
    {
        $this->admin->wajib_ganti_password = true;
        $this->admin->save();
        $this->actingAs($this->admin, 'sanctum');

        $this->getJson('/api/v1/admin/penempatan-pkl')
            ->assertForbidden()
            ->assertJsonPath('code', 'PASSWORD_CHANGE_REQUIRED');
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
        Schema::create('siswa', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('nisn');
            $table->string('nis')->nullable();
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
            $table->string('jabatan')->nullable();
            $table->string('no_hp')->nullable();
            $table->string('email')->nullable();
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
            $table->unsignedBigInteger('admin_user_id');
            $table->timestamp('created_at');
        });
        Schema::create('riwayat_pergantian_guru', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('penempatan_id');
            $table->unsignedBigInteger('guru_lama_id');
            $table->unsignedBigInteger('guru_baru_id');
            $table->unsignedBigInteger('admin_user_id');
            $table->text('alasan');
            $table->timestamp('created_at')->nullable();
        });
    }

    private function createUser(string $roleName, string $username, string $status = 'aktif'): User
    {
        $role = Role::query()->create(['nama' => $roleName]);

        return User::query()->forceCreate([
            'role_id' => $role->id,
            'username' => $username,
            'nama_tampilan' => ucfirst($roleName).' Uji',
            'status' => $status,
        ]);
    }

    private function createSiswa(string $nisn, string $name): Siswa
    {
        return Siswa::query()->forceCreate([
            'user_id' => $this->createUser('siswa', 'siswa-'.$nisn)->id,
            'nisn' => $nisn,
            'nis' => 'nis-'.$nisn,
            'nama_lengkap' => $name,
            'status_siswa' => 'aktif',
        ]);
    }

    private function createGuru(string $nip, string $name): Guru
    {
        return Guru::query()->forceCreate([
            'user_id' => $this->createUser('guru', 'guru-'.$nip)->id,
            'nip' => $nip,
            'nama_lengkap' => $name,
            'status_bimbingan' => 'aktif',
        ]);
    }

    private function createInstansi(string $name): Instansi
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

    private function createPlacement(
        Siswa $siswa,
        Instansi $instansi,
        string $status = 'aktif',
        array $overrides = []
    ): PenempatanPkl
    {
        return PenempatanPkl::query()->forceCreate(array_merge([
            'siswa_id' => $siswa->id,
            'periode_id' => $this->periode->id,
            'instansi_id' => $instansi->id,
            'guru_id' => $this->guru->id,
            'tanggal_mulai' => CarbonImmutable::today()->subDay()->toDateString(),
            'tanggal_selesai' => CarbonImmutable::today()->addDay()->toDateString(),
            'status' => $status,
        ], $overrides));
    }

    private function createHistory(PenempatanPkl $placement, string $old, string $new, string $reason): void
    {
        \App\Models\RiwayatStatusPenempatan::query()->create([
            'penempatan_id' => $placement->id,
            'status_lama' => $old,
            'status_baru' => $new,
            'alasan_kode' => $reason,
            'admin_user_id' => $this->admin->id,
            'created_at' => now(),
        ]);
    }

    private function placementPayload(array $overrides = []): array
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
