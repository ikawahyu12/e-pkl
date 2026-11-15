<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GuruJurnalReadTest extends TestCase
{
    private User $guruUser;
    private int $guruId;
    private int $jurnalId;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('nama')->unique();
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

        Schema::create('guru', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('nama_lengkap');
            $table->string('status_bimbingan')->default('aktif');
            $table->timestamps();
        });

        Schema::create('siswa', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('nisn')->unique();
            $table->string('nama_lengkap');
            $table->timestamps();
        });

        Schema::create('penempatan_pkl', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('siswa_id');
            $table->unsignedBigInteger('guru_id');
            $table->string('status')->default('aktif');
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->timestamps();
        });

        Schema::create('jurnal', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('penempatan_id');
            $table->date('tanggal');
            $table->string('judul', 160);
            $table->text('kegiatan');
            $table->text('hasil')->nullable();
            $table->text('kendala')->nullable();
            $table->string('status')->default('menunggu');
            $table->text('catatan_guru')->nullable();
            $table->unsignedBigInteger('direview_oleh')->nullable();
            $table->dateTime('direview_at')->nullable();
            $table->timestamps();
        });

        Schema::create('jurnal_lampiran', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('jurnal_id');
            $table->string('nama_file');
            $table->string('path_file');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('ukuran_byte')->nullable();
            $table->timestamps();
        });

        $guruRole = Role::query()->create(['nama' => 'guru']);
        Role::query()->create(['nama' => 'siswa']);
        $this->guruUser = User::query()->forceCreate([
            'role_id' => $guruRole->id,
            'username' => 'guru-utama',
            'email' => 'guru@example.test',
            'nama_tampilan' => 'Guru Utama',
            'status' => 'aktif',
        ]);
        $this->guruId = DB::table('guru')->insertGetId([
            'user_id' => $this->guruUser->id,
            'nama_lengkap' => 'Guru Utama',
        ]);

        $studentId = DB::table('siswa')->insertGetId([
            'nisn' => '10001',
            'nama_lengkap' => 'Siswa Bimbingan',
        ]);
        $placementId = DB::table('penempatan_pkl')->insertGetId([
            'siswa_id' => $studentId,
            'guru_id' => $this->guruId,
            'status' => 'aktif',
        ]);
        $this->jurnalId = $this->insertJournal($placementId, 'Jurnal siswa bimbingan');

        $otherGuruUser = User::query()->forceCreate([
            'role_id' => $guruRole->id,
            'username' => 'guru-lain',
            'email' => 'guru-lain@example.test',
            'nama_tampilan' => 'Guru Lain',
            'status' => 'aktif',
        ]);
        $otherGuruId = DB::table('guru')->insertGetId([
            'user_id' => $otherGuruUser->id,
            'nama_lengkap' => 'Guru Lain',
        ]);
        $otherStudentId = DB::table('siswa')->insertGetId([
            'nisn' => '10002',
            'nama_lengkap' => 'Siswa Guru Lain',
        ]);
        $otherPlacementId = DB::table('penempatan_pkl')->insertGetId([
            'siswa_id' => $otherStudentId,
            'guru_id' => $otherGuruId,
            'status' => 'aktif',
        ]);
        $this->insertJournal($otherPlacementId, 'Jurnal milik Guru lain');

        $this->actingAs($this->guruUser, 'sanctum');
    }

    public function test_teacher_list_only_contains_journals_of_assigned_students(): void
    {
        $this->getJson('/api/v1/guru/jurnal')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->jurnalId)
            ->assertJsonPath('data.0.siswa.nama_lengkap', 'Siswa Bimbingan')
            ->assertJsonPath('data.0.siswa.nisn', '10001')
            ->assertJsonPath('data.0.judul', 'Jurnal siswa bimbingan')
            ->assertJsonPath('data.0.kendala', 'Kendala siswa');
    }

    public function test_teacher_can_read_full_journal_detail_and_documentation_url(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('jurnal/kegiatan.jpg', 'image');
        $lampiranId = DB::table('jurnal_lampiran')->insertGetId([
            'jurnal_id' => $this->jurnalId,
            'nama_file' => 'kegiatan.jpg',
            'path_file' => 'jurnal/kegiatan.jpg',
            'mime_type' => 'image/jpeg',
            'ukuran_byte' => 5,
        ]);

        $response = $this->getJson('/api/v1/guru/jurnal/'.$this->jurnalId)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $this->jurnalId)
            ->assertJsonPath('data.tanggal', '2026-11-15')
            ->assertJsonPath('data.judul', 'Jurnal siswa bimbingan')
            ->assertJsonPath('data.kegiatan', 'Kegiatan siswa')
            ->assertJsonPath('data.hasil', 'Hasil siswa')
            ->assertJsonPath('data.siswa.nama_lengkap', 'Siswa Bimbingan')
            ->assertJsonPath('data.dokumentasi.0.nama_file', 'kegiatan.jpg')
            ->assertJsonPath('data.dokumentasi.0.mime_type', 'image/jpeg');

        $this->assertSame(
            url('/api/v1/guru/jurnal/'.$this->jurnalId.'/lampiran/'.$lampiranId),
            $response->json('data.dokumentasi.0.url'),
        );
    }

    public function test_teacher_can_read_documentation_through_an_authorized_file_endpoint(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('jurnal/kegiatan.jpg', 'image-bytes');
        $lampiranId = DB::table('jurnal_lampiran')->insertGetId([
            'jurnal_id' => $this->jurnalId,
            'nama_file' => 'kegiatan.jpg',
            'path_file' => 'jurnal/kegiatan.jpg',
            'mime_type' => 'image/jpeg',
            'ukuran_byte' => 11,
        ]);

        $this->get('/api/v1/guru/jurnal/'.$this->jurnalId.'/lampiran/'.$lampiranId)
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg')
            ->assertStreamedContent('image-bytes');
    }

    public function test_teacher_cannot_read_documentation_for_another_teachers_journal(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('jurnal/other.jpg', 'image-bytes');
        $otherJournalId = DB::table('jurnal')
            ->where('judul', 'Jurnal milik Guru lain')
            ->value('id');
        $lampiranId = DB::table('jurnal_lampiran')->insertGetId([
            'jurnal_id' => $otherJournalId,
            'nama_file' => 'other.jpg',
            'path_file' => 'jurnal/other.jpg',
            'mime_type' => 'image/jpeg',
            'ukuran_byte' => 11,
        ]);

        $this->getJson('/api/v1/guru/jurnal/'.$otherJournalId.'/lampiran/'.$lampiranId)
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_teacher_cannot_read_journal_belonging_to_another_teacher(): void
    {
        $otherJournalId = DB::table('jurnal')
            ->where('judul', 'Jurnal milik Guru lain')
            ->value('id');

        $this->getJson('/api/v1/guru/jurnal/'.$otherJournalId)
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Jurnal tidak ditemukan.');
    }

    public function test_journal_list_returns_an_empty_array_without_student_journals(): void
    {
        DB::table('jurnal')->where('id', $this->jurnalId)->delete();

        $this->getJson('/api/v1/guru/jurnal')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data');
    }

    public function test_non_teacher_role_cannot_read_journals(): void
    {
        $studentRole = Role::query()->where('nama', 'siswa')->firstOrFail();
        $studentUser = User::query()->forceCreate([
            'role_id' => $studentRole->id,
            'username' => 'siswa-akun',
            'email' => 'siswa@example.test',
            'nama_tampilan' => 'Siswa',
            'status' => 'aktif',
        ]);
        $this->actingAs($studentUser, 'sanctum');

        $this->getJson('/api/v1/guru/jurnal')
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_teacher_account_without_guru_profile_cannot_read_journals(): void
    {
        $guruRole = Role::query()->where('nama', 'guru')->firstOrFail();
        $userWithoutProfile = User::query()->forceCreate([
            'role_id' => $guruRole->id,
            'username' => 'guru-tanpa-profil',
            'email' => 'guru-tanpa-profil@example.test',
            'nama_tampilan' => 'Guru Tanpa Profil',
            'status' => 'aktif',
        ]);
        $this->actingAs($userWithoutProfile, 'sanctum');

        $this->getJson('/api/v1/guru/jurnal')
            ->assertForbidden()
            ->assertJsonPath('message', 'Akses hanya tersedia untuk akun Guru aktif dengan status bimbingan aktif.');
    }

    public function test_journal_list_requires_a_teacher_account(): void
    {
        Auth::forgetGuards();

        $this->getJson('/api/v1/guru/jurnal')
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    private function insertJournal(int $placementId, string $title): int
    {
        return DB::table('jurnal')->insertGetId([
            'penempatan_id' => $placementId,
            'tanggal' => '2026-11-15',
            'judul' => $title,
            'kegiatan' => 'Kegiatan siswa',
            'hasil' => 'Hasil siswa',
            'kendala' => 'Kendala siswa',
            'status' => 'menunggu',
        ]);
    }
}
