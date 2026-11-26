<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Siswa;
use App\Models\Guru;
use App\Models\User;
use App\Services\GuruAuthService;
use App\Services\SiswaAuthService;
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
            $table->string('jenis_kelamin')->nullable();
            $table->string('no_hp')->nullable();
            $table->string('email_sekolah')->nullable();
            $table->string('alamat')->nullable();
            $table->string('nama_orang_tua')->nullable();
            $table->string('no_hp_orang_tua')->nullable();
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
            $table->unsignedBigInteger('periode_id')->nullable();
            $table->unsignedBigInteger('instansi_id')->nullable();
            $table->unsignedBigInteger('pic_instansi_id')->nullable();
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->string('status')->default('selesai');
            $table->timestamps();
        });

        Schema::create('instansi', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->string('status')->default('aktif');
        });

        Schema::create('periode_pkl', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->string('status')->default('berjalan');
        });

        Schema::create('riwayat_status_penempatan', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('penempatan_id');
            $table->string('status_lama')->nullable();
            $table->string('status_baru');
            $table->string('alasan_kode');
            $table->text('alasan_detail')->nullable();
            $table->unsignedBigInteger('admin_user_id');
            $table->timestamp('created_at')->nullable();
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

        Schema::create('dokumen_siswa', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('siswa_id');
            $table->string('nama_file');
        });

        Schema::create('jurnal', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('guru_id');
            $table->string('judul');
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
            'password' => 'Pass1234',
            'kelas_id' => $classId,
            'status' => 'Aktif',
        ])->assertCreated()
            ->assertJsonPath('data.nama', 'Siswa Baru')
            ->assertJsonPath('data.kelas', 'XII RPL 1')
            ->assertJsonMissingPath('data.password_hash');

        $studentId = $created->json('data.id');
        $studentUser = User::query()->findOrFail($studentId);
        $this->assertSame('siswa', $studentUser->role->nama);
        $this->assertTrue(Hash::check('Pass1234', $studentUser->password_hash));

        $this->getJson('/api/v1/admin/students?search=Siswa')
            ->assertOk()
            ->assertJsonPath('data.users.0.id', $studentId)
            ->assertJsonPath('data.users.0.username', '1234567890')
            ->assertJsonMissingPath('data.users.0.no_hp');

        $this->patchJson('/api/v1/admin/students/'.$studentId, [
            'nama' => 'Nama Siswa Diperbarui',
            'nisn' => '1234567891',
            'nis' => 'S-1001',
            'kelas_id' => $classId,
            'jenis_kelamin' => 'P',
            'no_hp' => '08123456789',
            'email_sekolah' => 'siswa@sekolah.test',
            'alamat' => 'Alamat siswa',
            'nama_orang_tua' => 'Nama Wali',
            'no_hp_orang_tua' => '08129876543',
            'password' => 'Pass1234',
        ])->assertOk()
            ->assertJsonPath('data.nama', 'Nama Siswa Diperbarui')
            ->assertJsonPath('data.nisn', '1234567891')
            ->assertJsonPath('data.nis', 'S-1001')
            ->assertJsonPath('data.jenis_kelamin', 'P')
            ->assertJsonPath('data.no_hp', '08123456789')
            ->assertJsonPath('data.email_sekolah', 'siswa@sekolah.test')
            ->assertJsonPath('data.alamat', 'Alamat siswa')
            ->assertJsonPath('data.nama_orang_tua', 'Nama Wali')
            ->assertJsonPath('data.no_hp_orang_tua', '08129876543');

        $this->assertDatabaseHas('siswa', [
            'user_id' => $studentId,
            'nisn' => '1234567891',
            'nis' => 'S-1001',
            'kelas_id' => $classId,
            'jenis_kelamin' => 'P',
            'no_hp' => '08123456789',
            'email_sekolah' => 'siswa@sekolah.test',
            'alamat' => 'Alamat siswa',
            'nama_orang_tua' => 'Nama Wali',
            'no_hp_orang_tua' => '08129876543',
        ]);
        $this->assertDatabaseHas('users', ['id' => $studentId, 'status' => 'aktif']);
        $this->assertTrue(Hash::check('Pass1234', $studentUser->fresh()->password_hash));
        $studentUser->createToken('student-delete-test');
        $studentProfileId = \DB::table('siswa')->where('user_id', $studentId)->value('id');
        $placementId = \DB::table('penempatan_pkl')->insertGetId([
            'siswa_id' => $studentProfileId,
        ]);
        $documentId = \DB::table('dokumen_siswa')->insertGetId([
            'siswa_id' => $studentProfileId,
            'nama_file' => 'dokumen-siswa.pdf',
        ]);

        $this->deleteJson('/api/v1/admin/students/'.$studentId, ['alasan_kode' => 'siswa_mengundurkan_diri'])
            ->assertOk()
            ->assertJsonPath('data', null);

        $this->assertDatabaseHas('users', ['id' => $studentId, 'status' => 'nonaktif']);
        $this->assertDatabaseHas('siswa', ['id' => $studentProfileId, 'user_id' => $studentId]);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_type' => User::class,
            'tokenable_id' => $studentId,
        ]);
        $this->assertDatabaseHas('penempatan_pkl', ['id' => $placementId, 'siswa_id' => $studentProfileId]);
        $this->assertDatabaseHas('dokumen_siswa', ['id' => $documentId, 'siswa_id' => $studentProfileId]);
        $this->assertDatabaseCount('riwayat_status_penempatan', 0);

        $this->deleteJson('/api/v1/admin/students/'.$studentId, ['alasan_kode' => 'siswa_mengundurkan_diri'])
            ->assertOk()
            ->assertJsonPath('data', null);
        $this->assertDatabaseHas('users', ['id' => $studentId, 'status' => 'nonaktif']);
    }

    public function test_new_inactive_student_account_has_a_nonactive_profile(): void
    {
        $admin = $this->createUser('admin', 'create-inactive-student-admin');
        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/v1/admin/students', [
            'nama' => 'Siswa Nonaktif',
            'username' => 'inactive-student',
            'password' => 'password-siswa-aman',
            'status' => 'nonaktif',
        ])->assertCreated();

        $userId = (int) $response->json('data.id');
        $this->assertDatabaseHas('users', ['id' => $userId, 'status' => 'nonaktif']);
        $this->assertDatabaseHas('siswa', ['user_id' => $userId, 'status_siswa' => 'nonaktif']);
    }

    public function test_student_update_sends_status_changes_through_account_status_service(): void
    {
        $admin = $this->createUser('admin', 'student-update-status-admin');
        $student = $this->createStudentAccount('student-update-status');
        $placementId = $this->createPlacement($student, 'aktif');
        $student->createToken('student-update-status-token');

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/admin/students/'.$student->id, [
                'nama' => 'Siswa Diperbarui',
            ])
            ->assertOk()
            ->assertJsonPath('data.nama', 'Siswa Diperbarui')
            ->assertJsonPath('data.status', 'aktif')
            ->assertJsonPath('data.status_siswa', 'aktif');

        $this->patchJson('/api/v1/admin/students/'.$student->id, [
            'status' => 'nonaktif',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->patchJson('/api/v1/admin/students/'.$student->id.'/status', [
            'status' => 'nonaktif',
            'alasan_kode' => 'perubahan_penempatan_dari_sekolah',
        ])->assertOk()
            ->assertJsonPath('data.status', 'nonaktif')
            ->assertJsonPath('data.status_siswa', 'nonaktif');

        $this->assertDatabaseHas('users', ['id' => $student->id, 'status' => 'nonaktif']);
        $this->assertDatabaseHas('siswa', [
            'user_id' => $student->id,
            'nama_lengkap' => 'Siswa Diperbarui',
            'status_siswa' => 'nonaktif',
        ]);
        $this->assertDatabaseHas('penempatan_pkl', [
            'id' => $placementId,
            'status' => 'dibatalkan',
        ]);
        $this->assertDatabaseHas('riwayat_status_penempatan', [
            'penempatan_id' => $placementId,
            'status_baru' => 'dibatalkan',
            'alasan_kode' => 'perubahan_penempatan_dari_sekolah',
        ]);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_type' => User::class,
            'tokenable_id' => $student->id,
        ]);
    }

    public function test_student_deactivation_requires_a_valid_admin_selected_reason(): void
    {
        $admin = $this->createUser('admin', 'student-reason-validation-admin');
        $student = $this->createStudentAccount('student-reason-validation');
        $this->actingAs($admin, 'sanctum');

        $this->patchJson('/api/v1/admin/students/'.$student->id.'/status', [
            'status' => 'nonaktif',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('alasan_kode');

        $this->patchJson('/api/v1/admin/students/'.$student->id.'/status', [
            'status' => 'nonaktif',
            'alasan_kode' => 'alasan_tidak_valid',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('alasan_kode');

        $this->patchJson('/api/v1/admin/students/'.$student->id.'/status', [
            'status' => 'nonaktif',
            'alasan_kode' => 'lainnya',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('alasan_detail');

        $this->patchJson('/api/v1/admin/students/'.$student->id.'/status', [
            'status' => 'nonaktif',
            'alasan_kode' => 'lainnya',
            'alasan_detail' => 'Keputusan khusus sekolah.',
        ])->assertOk()
            ->assertJsonPath('data.status', 'nonaktif');

        $this->assertDatabaseHas('users', ['id' => $student->id, 'status' => 'nonaktif']);
        $this->assertDatabaseHas('siswa', [
            'user_id' => $student->id,
            'status_siswa' => 'nonaktif',
        ]);
    }

    public function test_delete_student_endpoint_does_not_require_a_user_supplied_reason(): void
    {
        $admin = $this->createUser('admin', 'student-delete-reason-admin');
        $student = $this->createStudentAccount('student-delete-reason');
        $placementId = $this->createPlacement($student, 'aktif');
        $this->actingAs($admin, 'sanctum');

        $this->deleteJson('/api/v1/admin/students/'.$student->id)
            ->assertOk()
            ->assertJsonPath('data', null);

        $this->assertDatabaseHas('users', ['id' => $student->id, 'status' => 'nonaktif']);
        $this->assertDatabaseHas('siswa', ['user_id' => $student->id, 'status_siswa' => 'nonaktif']);
        $this->assertDatabaseHas('penempatan_pkl', [
            'id' => $placementId,
            'status' => 'dibatalkan',
        ]);
        $this->assertDatabaseHas('riwayat_status_penempatan', [
            'penempatan_id' => $placementId,
            'alasan_kode' => 'lainnya',
            'alasan_detail' => 'Akun dinonaktifkan melalui aksi Hapus pada Manajemen Siswa.',
        ]);
    }

    public function test_student_biodata_update_rejects_status_and_unsupported_ttl_fields(): void
    {
        $admin = $this->createUser('admin', 'student-biodata-validation-admin');
        $student = $this->createStudentAccount('student-biodata-validation');

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/admin/students/'.$student->id, [
                'status' => 'nonaktif',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->patchJson('/api/v1/admin/students/'.$student->id, [
            'ttl' => 'Bandung, 14 Mei 2007',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('ttl');

        $this->assertDatabaseHas('users', ['id' => $student->id, 'status' => 'aktif']);
        $this->assertDatabaseHas('siswa', [
            'user_id' => $student->id,
            'status_siswa' => 'aktif',
        ]);
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
        ])->assertOk()
            ->assertJsonPath('data.username', '19870002');

        $this->assertDatabaseHas('users', ['id' => $teacherId, 'status' => 'aktif']);
        $teacherUser->createToken('teacher-delete-test');
        $teacherProfileId = \DB::table('guru')->where('user_id', $teacherId)->value('id');
        $placementId = \DB::table('penempatan_pkl')->insertGetId([
            'guru_id' => $teacherProfileId,
        ]);
        $journalId = \DB::table('jurnal')->insertGetId([
            'guru_id' => $teacherProfileId,
            'judul' => 'Riwayat bimbingan',
        ]);

        $this->deleteJson('/api/v1/admin/teachers/'.$teacherId)
            ->assertOk()
            ->assertJsonPath('data', null);

        $this->assertDatabaseHas('users', ['id' => $teacherId, 'status' => 'nonaktif']);
        $this->assertDatabaseHas('guru', ['id' => $teacherProfileId, 'user_id' => $teacherId]);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_type' => User::class,
            'tokenable_id' => $teacherId,
        ]);
        $this->assertDatabaseHas('penempatan_pkl', ['id' => $placementId, 'guru_id' => $teacherProfileId]);
        $this->assertDatabaseHas('jurnal', ['id' => $journalId, 'guru_id' => $teacherProfileId]);

        $this->deleteJson('/api/v1/admin/teachers/'.$teacherId)
            ->assertOk()
            ->assertJsonPath('data', null);
        $this->assertDatabaseHas('users', ['id' => $teacherId, 'status' => 'nonaktif']);
    }

    public function test_new_inactive_teacher_account_has_nonactive_mentoring_status(): void
    {
        $admin = $this->createUser('admin', 'create-inactive-teacher-admin');
        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/v1/admin/teachers', [
            'nama' => 'Guru Nonaktif',
            'username' => 'inactive-teacher',
            'password' => 'password-guru-aman',
            'status' => 'nonaktif',
        ])->assertCreated();

        $userId = (int) $response->json('data.id');
        $this->assertDatabaseHas('users', ['id' => $userId, 'status' => 'nonaktif']);
        $this->assertDatabaseHas('guru', ['user_id' => $userId, 'status_bimbingan' => 'nonaktif']);
    }

    public function test_deleting_active_student_and_teacher_preserves_accounts_and_history(): void
    {
        $admin = $this->createUser('admin', 'root-admin');
        $student = $this->createStudentAccount('delete-student');
        $teacher = $this->createTeacherAccount('delete-teacher');
        $student->createToken('active-student-delete');
        $teacher->createToken('active-teacher-delete');

        $studentProfileId = \DB::table('siswa')->where('user_id', $student->id)->value('id');
        $teacherProfileId = \DB::table('guru')->where('user_id', $teacher->id)->value('id');
        $studentHistoryId = \DB::table('penempatan_pkl')->insertGetId([
            'siswa_id' => $studentProfileId,
        ]);
        $teacherHistoryId = \DB::table('penempatan_pkl')->insertGetId([
            'guru_id' => $teacherProfileId,
        ]);

        $this->actingAs($admin, 'sanctum');
        $this->deleteJson('/api/v1/admin/students/'.$student->id, [
            'alasan_kode' => 'siswa_mengundurkan_diri',
        ])->assertOk();
        $this->deleteJson('/api/v1/admin/teachers/'.$teacher->id)->assertOk();

        $this->assertDatabaseHas('users', ['id' => $student->id, 'status' => 'nonaktif']);
        $this->assertDatabaseHas('siswa', ['id' => $studentProfileId, 'user_id' => $student->id]);
        $this->assertDatabaseHas('users', ['id' => $teacher->id, 'status' => 'nonaktif']);
        $this->assertDatabaseHas('guru', ['id' => $teacherProfileId, 'user_id' => $teacher->id]);
        $this->assertDatabaseHas('penempatan_pkl', ['id' => $studentHistoryId, 'siswa_id' => $studentProfileId]);
        $this->assertDatabaseHas('penempatan_pkl', ['id' => $teacherHistoryId, 'guru_id' => $teacherProfileId]);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_type' => User::class,
            'tokenable_id' => $student->id,
        ]);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_type' => User::class,
            'tokenable_id' => $teacher->id,
        ]);
    }

    public function test_student_deactivation_cancels_only_open_placements_and_records_history(): void
    {
        $admin = $this->createUser('admin', 'student-deactivation-admin');
        $student = $this->createStudentAccount('deactivated-student');
        $placements = [
            $this->createPlacement($student, 'aktif'),
            $this->createPlacement($student, 'belum_mulai'),
            $this->createPlacement($student, 'selesai'),
            $this->createPlacement($student, 'dibatalkan'),
        ];
        $student->createToken('student-before-deactivation');

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/v1/admin/students/'.$student->id, [
                'alasan_kode' => 'kondisi_operasional_tertentu',
                'alasan_detail' => 'Perubahan operasional yang diputuskan Admin.',
            ])
            ->assertOk();

        $this->assertDatabaseHas('users', ['id' => $student->id, 'status' => 'nonaktif']);
        $this->assertDatabaseHas('siswa', [
            'user_id' => $student->id,
            'status_siswa' => 'nonaktif',
        ]);
        $this->assertDatabaseCount('penempatan_pkl', 4);
        $this->assertDatabaseHas('penempatan_pkl', ['id' => $placements[0], 'status' => 'dibatalkan']);
        $this->assertDatabaseHas('penempatan_pkl', ['id' => $placements[1], 'status' => 'dibatalkan']);
        $this->assertDatabaseHas('penempatan_pkl', ['id' => $placements[2], 'status' => 'selesai']);
        $this->assertDatabaseHas('penempatan_pkl', ['id' => $placements[3], 'status' => 'dibatalkan']);
        $this->assertDatabaseCount('riwayat_status_penempatan', 2);
        $this->assertDatabaseHas('riwayat_status_penempatan', [
            'penempatan_id' => $placements[0],
            'status_lama' => 'aktif',
            'status_baru' => 'dibatalkan',
            'alasan_kode' => 'kondisi_operasional_tertentu',
            'alasan_detail' => 'Perubahan operasional yang diputuskan Admin.',
            'admin_user_id' => $admin->id,
        ]);
        $this->assertDatabaseHas('riwayat_status_penempatan', [
            'penempatan_id' => $placements[1],
            'status_lama' => 'belum_mulai',
            'status_baru' => 'dibatalkan',
            'alasan_kode' => 'kondisi_operasional_tertentu',
            'alasan_detail' => 'Perubahan operasional yang diputuskan Admin.',
            'admin_user_id' => $admin->id,
        ]);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_type' => User::class,
            'tokenable_id' => $student->id,
        ]);
    }

    public function test_student_status_and_placements_roll_back_if_cancellation_history_fails(): void
    {
        $admin = $this->createUser('admin', 'student-rollback-admin');
        $student = $this->createStudentAccount('rollback-student');
        $placementId = $this->createPlacement($student, 'aktif');
        Schema::drop('riwayat_status_penempatan');

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/v1/admin/students/'.$student->id, [
                'alasan_kode' => 'siswa_mengundurkan_diri',
            ])
            ->assertInternalServerError();

        $this->assertDatabaseHas('users', ['id' => $student->id, 'status' => 'aktif']);
        $this->assertDatabaseHas('siswa', ['user_id' => $student->id, 'status_siswa' => 'aktif']);
        $this->assertDatabaseHas('penempatan_pkl', ['id' => $placementId, 'status' => 'aktif']);
    }

    public function test_reactivating_student_does_not_reactivate_old_placements(): void
    {
        $admin = $this->createUser('admin', 'student-reactivation-admin');
        $student = $this->createStudentAccount('reactivated-student');
        $placementId = $this->createPlacement($student, 'aktif');
        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/v1/admin/students/'.$student->id, [
                'alasan_kode' => 'siswa_mengundurkan_diri',
            ])
            ->assertOk();

        $this->patchJson('/api/v1/admin/students/'.$student->id.'/status', ['status' => 'aktif'])
            ->assertOk()
            ->assertJsonPath('data.status', 'aktif')
            ->assertJsonPath('data.status_siswa', 'aktif');

        $this->assertDatabaseHas('penempatan_pkl', ['id' => $placementId, 'status' => 'dibatalkan']);
        $this->assertDatabaseHas('riwayat_status_penempatan', [
            'penempatan_id' => $placementId,
            'status_baru' => 'dibatalkan',
        ]);
    }

    public function test_teacher_cannot_be_deactivated_until_every_open_placement_is_reassigned(): void
    {
        $admin = $this->createUser('admin', 'teacher-transfer-admin');
        $teacher = $this->createTeacherAccount('teacher-to-transfer');
        $replacement = $this->createTeacherAccount('replacement-teacher');
        $studentA = $this->createStudentAccount('transfer-student-a');
        $studentB = $this->createStudentAccount('transfer-student-b');
        $placementA = $this->createPlacement($studentA, 'aktif', $teacher);
        $placementB = $this->createPlacement($studentB, 'belum_mulai', $teacher);
        $this->actingAs($admin, 'sanctum');

        $this->getJson('/api/v1/admin/teachers/'.$teacher->id.'/active-placements')
            ->assertOk()
            ->assertJsonCount(2, 'data.penempatan')
            ->assertJsonPath('data.penempatan.0.siswa.nama', 'transfer-student-a')
            ->assertJsonPath('data.penempatan.0.instansi.nama', 'Instansi Uji')
            ->assertJsonPath('data.penempatan.0.periode.nama', 'Periode Aktif');

        $this->patchJson('/api/v1/admin/teachers/'.$teacher->id, ['status' => 'nonaktif'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reassignments');
        $this->assertDatabaseHas('users', ['id' => $teacher->id, 'status' => 'aktif']);
        $this->assertDatabaseHas('penempatan_pkl', ['id' => $placementA, 'guru_id' => $teacher->guru->id]);

        $this->patchJson('/api/v1/admin/teachers/'.$teacher->id, [
            'status' => 'nonaktif',
            'reassignments' => [
                ['penempatan_id' => $placementA, 'guru_baru_id' => $replacement->guru->id, 'alasan' => 'Alih tugas'],
                ['penempatan_id' => $placementB, 'guru_baru_id' => $replacement->guru->id, 'alasan' => 'Alih tugas'],
            ],
        ])->assertOk()
            ->assertJsonPath('data.status', 'nonaktif')
            ->assertJsonPath('data.status_bimbingan', 'nonaktif');

        $this->assertDatabaseHas('penempatan_pkl', [
            'id' => $placementA,
            'guru_id' => $replacement->guru->id,
            'status' => 'aktif',
            'tanggal_mulai' => '2026-11-01',
            'tanggal_selesai' => '2026-12-01',
        ]);
        $this->assertDatabaseHas('penempatan_pkl', [
            'id' => $placementB,
            'guru_id' => $replacement->guru->id,
            'status' => 'belum_mulai',
        ]);
        $this->assertDatabaseCount('riwayat_pergantian_guru', 2);
        $this->assertDatabaseHas('riwayat_pergantian_guru', [
            'penempatan_id' => $placementA,
            'guru_lama_id' => $teacher->guru->id,
            'guru_baru_id' => $replacement->guru->id,
            'admin_user_id' => $admin->id,
            'alasan' => 'Alih tugas',
        ]);

        $this->patchJson('/api/v1/admin/teachers/'.$teacher->id, ['status' => 'aktif'])
            ->assertOk();
        $this->assertDatabaseHas('penempatan_pkl', [
            'id' => $placementA,
            'guru_id' => $replacement->guru->id,
            'status' => 'aktif',
        ]);
    }

    public function test_inactive_teacher_cannot_be_used_as_replacement(): void
    {
        $admin = $this->createUser('admin', 'inactive-replacement-admin');
        $teacher = $this->createTeacherAccount('inactive-replacement-old');
        $replacement = $this->createTeacherAccount('inactive-replacement-target');
        $student = $this->createStudentAccount('inactive-replacement-student');
        $placementId = $this->createPlacement($student, 'aktif', $teacher);
        $replacementProfile = $replacement->guru;
        $replacementProfile->status_bimbingan = 'nonaktif';
        $replacementProfile->save();
        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/admin/teachers/'.$teacher->id, [
                'status' => 'nonaktif',
                'reassignments' => [[
                    'penempatan_id' => $placementId,
                    'guru_baru_id' => $replacementProfile->id,
                    'alasan' => 'Alih tugas',
                ]],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('guru_baru_id');
    }

    public function test_teacher_with_inactive_account_cannot_be_used_as_replacement(): void
    {
        $admin = $this->createUser('admin', 'inactive-account-replacement-admin');
        $teacher = $this->createTeacherAccount('inactive-account-replacement-old');
        $replacement = $this->createTeacherAccount('inactive-account-replacement-target');
        $student = $this->createStudentAccount('inactive-account-replacement-student');
        $placementId = $this->createPlacement($student, 'aktif', $teacher);
        $replacement->status = 'nonaktif';
        $replacement->save();

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/admin/teachers/'.$teacher->id, [
                'status' => 'nonaktif',
                'reassignments' => [[
                    'penempatan_id' => $placementId,
                    'guru_baru_id' => $replacement->guru->id,
                    'alasan' => 'Alih tugas',
                ]],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('guru_baru_id');

        $this->assertDatabaseHas('users', ['id' => $teacher->id, 'status' => 'aktif']);
        $this->assertDatabaseHas('penempatan_pkl', [
            'id' => $placementId,
            'guru_id' => $teacher->guru->id,
            'status' => 'aktif',
        ]);
    }

    public function test_teacher_reassignment_is_rolled_back_when_history_cannot_be_saved(): void
    {
        $admin = $this->createUser('admin', 'teacher-rollback-admin');
        $teacher = $this->createTeacherAccount('teacher-rollback');
        $replacement = $this->createTeacherAccount('replacement-rollback');
        $student = $this->createStudentAccount('teacher-rollback-student');
        $placementId = $this->createPlacement($student, 'aktif', $teacher);
        Schema::drop('riwayat_pergantian_guru');

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/admin/teachers/'.$teacher->id, [
                'status' => 'nonaktif',
                'reassignments' => [[
                    'penempatan_id' => $placementId,
                    'guru_baru_id' => $replacement->guru->id,
                    'alasan' => 'Alih tugas',
                ]],
            ])
            ->assertInternalServerError();

        $this->assertDatabaseHas('users', ['id' => $teacher->id, 'status' => 'aktif']);
        $this->assertDatabaseHas('guru', ['id' => $teacher->guru->id, 'status_bimbingan' => 'aktif']);
        $this->assertDatabaseHas('penempatan_pkl', [
            'id' => $placementId,
            'guru_id' => $teacher->guru->id,
            'status' => 'aktif',
        ]);
    }

    public function test_teacher_deactivation_rolls_back_all_reassignments_when_second_history_insert_fails(): void
    {
        $admin = $this->createUser('admin', 'teacher-second-history-failure-admin');
        $teacher = $this->createTeacherAccount('teacher-second-history-failure');
        $replacement = $this->createTeacherAccount('replacement-second-history-failure');
        $studentA = $this->createStudentAccount('second-history-failure-student-a');
        $studentB = $this->createStudentAccount('second-history-failure-student-b');
        $placementA = $this->createPlacement($studentA, 'aktif', $teacher);
        $placementB = $this->createPlacement($studentB, 'belum_mulai', $teacher);
        $teacherToken = $teacher->createToken('teacher-before-failed-deactivation')->accessToken;

        \DB::unprepared(
            'CREATE TRIGGER fail_second_teacher_history_insert '
                .'BEFORE INSERT ON riwayat_pergantian_guru '
                .'FOR EACH ROW WHEN NEW.penempatan_id = '.$placementB.' '
                ."BEGIN SELECT RAISE(ABORT, 'forced second history insert failure'); END"
        );

        try {
            $this->actingAs($admin, 'sanctum')
                ->patchJson('/api/v1/admin/teachers/'.$teacher->id, [
                    'status' => 'nonaktif',
                    'reassignments' => [
                        [
                            'penempatan_id' => $placementA,
                            'guru_baru_id' => $replacement->guru->id,
                            'alasan' => 'Alih tugas pertama',
                        ],
                        [
                            'penempatan_id' => $placementB,
                            'guru_baru_id' => $replacement->guru->id,
                            'alasan' => 'Alih tugas kedua',
                        ],
                    ],
                ])
                ->assertInternalServerError();
        } finally {
            \DB::unprepared('DROP TRIGGER IF EXISTS fail_second_teacher_history_insert');
        }

        $this->assertDatabaseHas('users', ['id' => $teacher->id, 'status' => 'aktif']);
        $this->assertDatabaseHas('guru', ['id' => $teacher->guru->id, 'status_bimbingan' => 'aktif']);
        $this->assertDatabaseHas('penempatan_pkl', [
            'id' => $placementA,
            'guru_id' => $teacher->guru->id,
            'status' => 'aktif',
        ]);
        $this->assertDatabaseHas('penempatan_pkl', [
            'id' => $placementB,
            'guru_id' => $teacher->guru->id,
            'status' => 'belum_mulai',
        ]);
        $this->assertDatabaseCount('riwayat_pergantian_guru', 0);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $teacherToken->id]);
    }

    public function test_single_teacher_reassignment_preserves_placement_and_records_history(): void
    {
        $admin = $this->createUser('admin', 'single-transfer-admin');
        $teacher = $this->createTeacherAccount('single-transfer-old');
        $replacement = $this->createTeacherAccount('single-transfer-new');
        $student = $this->createStudentAccount('single-transfer-student');
        $placementId = $this->createPlacement($student, 'aktif', $teacher);
        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/teachers/'.$teacher->id.'/placements/'.$placementId.'/reassign', [
                'guru_baru_id' => $replacement->guru->id,
                'alasan' => 'Penyesuaian pembimbing',
            ])
            ->assertOk()
            ->assertJsonPath('data.guru_id', $replacement->guru->id);

        $this->assertDatabaseHas('penempatan_pkl', [
            'id' => $placementId,
            'guru_id' => $replacement->guru->id,
            'status' => 'aktif',
            'tanggal_mulai' => '2026-11-01',
            'tanggal_selesai' => '2026-12-01',
        ]);
        $this->assertDatabaseHas('riwayat_pergantian_guru', [
            'penempatan_id' => $placementId,
            'guru_lama_id' => $teacher->guru->id,
            'guru_baru_id' => $replacement->guru->id,
            'admin_user_id' => $admin->id,
            'alasan' => 'Penyesuaian pembimbing',
        ]);
    }

    public function test_inactive_student_and_teacher_profiles_are_denied_by_role_middleware(): void
    {
        $student = $this->createStudentAccount('middleware-student');
        $student->siswa->status_siswa = 'nonaktif';
        $student->siswa->save();
        $this->actingAs($student, 'sanctum')
            ->getJson('/api/v1/siswa/auth/me')
            ->assertForbidden();

        $teacher = $this->createTeacherAccount('middleware-teacher');
        $teacher->guru->status_bimbingan = 'nonaktif';
        $teacher->guru->save();
        $this->actingAs($teacher, 'sanctum')
            ->getJson('/api/v1/guru/auth/me')
            ->assertForbidden();
    }

    public function test_nonactive_accounts_are_denied_by_role_middleware(): void
    {
        $student = $this->createStudentAccount('inactive-middleware-student');
        $student->status = 'nonaktif';
        $student->save();
        $this->actingAs($student, 'sanctum')
            ->getJson('/api/v1/siswa/auth/me')
            ->assertForbidden();

        $teacher = $this->createTeacherAccount('inactive-middleware-teacher');
        $teacher->status = 'nonaktif';
        $teacher->save();
        $this->actingAs($teacher, 'sanctum')
            ->getJson('/api/v1/guru/auth/me')
            ->assertForbidden();
    }

    public function test_deleting_missing_student_or_teacher_returns_not_found(): void
    {
        $admin = $this->createUser('admin', 'root-admin');
        $this->actingAs($admin, 'sanctum');

        $this->deleteJson('/api/v1/admin/students/999999', [
            'alasan_kode' => 'siswa_mengundurkan_diri',
        ])
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Akun siswa tidak ditemukan.');
        $this->deleteJson('/api/v1/admin/teachers/999999')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Akun guru tidak ditemukan.');
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

    public function test_successful_student_and_teacher_logins_record_last_login(): void
    {
        $student = $this->createUser('siswa', 'student-user');
        \DB::table('siswa')->insert([
            'user_id' => $student->id,
            'nisn' => '1234567890',
            'nis' => '12345',
            'nama_lengkap' => 'Siswa Uji',
            'kelas_id' => null,
            'status_siswa' => 'aktif',
        ]);

        $teacher = $this->createUser('guru', 'teacher-user');
        \DB::table('guru')->insert([
            'user_id' => $teacher->id,
            'nip' => '19870001',
            'nama_lengkap' => 'Guru Uji',
            'status_bimbingan' => 'aktif',
        ]);

        $this->assertNotNull(app(SiswaAuthService::class)->login('student-user', 'password-aman'));
        $this->assertNotNull(app(GuruAuthService::class)->login('teacher-user', 'password-aman'));

        $this->assertNotNull($student->fresh()->terakhir_login_at);
        $this->assertNotNull($teacher->fresh()->terakhir_login_at);
    }

    public function test_student_and_teacher_management_requires_admin_authentication(): void
    {
        $this->getJson('/api/v1/admin/students')->assertUnauthorized();
        $this->getJson('/api/v1/admin/teachers')->assertUnauthorized();
    }

    public function test_manual_admin_password_reset_endpoint_is_unavailable(): void
    {
        $target = $this->createUser('guru', 'reset-target');
        $this->postJson('/api/v1/admin/users/' . $target->id . '/reset-password')
            ->assertNotFound();

        $admin = $this->createUser('admin', 'reset-admin');
        $this->actingAs($admin, 'sanctum');
        $this->postJson('/api/v1/admin/users/' . $admin->id . '/reset-password')
            ->assertNotFound();
    }

    public function test_admin_required_to_change_password_cannot_manage_accounts(): void
    {
        $admin = $this->createUser('admin', 'forced-admin');
        $admin->wajib_ganti_password = true;
        $admin->save();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users')
            ->assertForbidden()
            ->assertJsonPath('code', 'PASSWORD_CHANGE_REQUIRED');

        $this->getJson('/api/v1/admin/students')
            ->assertForbidden()
            ->assertJsonPath('code', 'PASSWORD_CHANGE_REQUIRED');
        $this->getJson('/api/v1/admin/teachers')
            ->assertForbidden()
            ->assertJsonPath('code', 'PASSWORD_CHANGE_REQUIRED');
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

    private function createStudentAccount(string $username): User
    {
        $user = $this->createUser('siswa', $username);
        $student = new Siswa;
        $student->user_id = $user->id;
        $student->nisn = $username;
        $student->nama_lengkap = $username;
        $student->save();

        return $user;
    }

    private function createTeacherAccount(string $username): User
    {
        $user = $this->createUser('guru', $username);
        $teacher = new Guru;
        $teacher->user_id = $user->id;
        $teacher->nip = $username;
        $teacher->nama_lengkap = $username;
        $teacher->save();

        return $user;
    }

    private function createPlacement(User $student, string $status, ?User $teacher = null): int
    {
        $teacher ??= $this->createTeacherAccount('placement-teacher-'.uniqid());
        $studentProfileId = (int) \DB::table('siswa')->where('user_id', $student->id)->value('id');
        $teacherProfileId = (int) \DB::table('guru')->where('user_id', $teacher->id)->value('id');
        $instanceId = \DB::table('instansi')->insertGetId(['nama' => 'Instansi Uji', 'status' => 'aktif']);
        $periodId = \DB::table('periode_pkl')->insertGetId([
            'nama' => 'Periode Aktif',
            'tanggal_mulai' => '2026-10-01',
            'tanggal_selesai' => '2026-12-31',
            'status' => 'berjalan',
        ]);

        return \DB::table('penempatan_pkl')->insertGetId([
            'siswa_id' => $studentProfileId,
            'guru_id' => $teacherProfileId,
            'instansi_id' => $instanceId,
            'periode_id' => $periodId,
            'tanggal_mulai' => '2026-11-01',
            'tanggal_selesai' => '2026-12-01',
            'status' => $status,
        ]);
    }
}
