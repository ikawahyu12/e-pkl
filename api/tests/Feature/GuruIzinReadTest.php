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

class GuruIzinReadTest extends TestCase
{
    private User $guruUser;

    private int $guruId;

    private int $izinId;

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
            $table->boolean('wajib_ganti_password')->default(false);
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
            $table->string('nisn');
            $table->string('nama_lengkap');
            $table->timestamps();
        });
        Schema::create('penempatan_pkl', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('siswa_id');
            $table->unsignedBigInteger('guru_id');
            $table->string('status')->default('aktif');
            $table->timestamps();
        });
        Schema::create('pengajuan_izin', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('penempatan_id');
            $table->string('jenis');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->text('alasan');
            $table->string('status')->default('menunggu');
            $table->timestamps();
        });
        Schema::create('pengajuan_izin_lampiran', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pengajuan_id');
            $table->string('nama_file');
            $table->string('path_file', 500);
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('ukuran_byte')->nullable();
            $table->timestamps();
        });

        $roleGuru = Role::query()->create(['nama' => 'guru']);
        Role::query()->create(['nama' => 'siswa']);
        $this->guruUser = User::query()->forceCreate([
            'role_id' => $roleGuru->id,
            'username' => 'guru-utama',
            'email' => 'guru@example.test',
            'nama_tampilan' => 'Guru Utama',
            'status' => 'aktif',
            'wajib_ganti_password' => false,
        ]);
        $this->guruId = DB::table('guru')->insertGetId([
            'user_id' => $this->guruUser->id,
            'nama_lengkap' => 'Guru Utama',
            'status_bimbingan' => 'aktif',
        ]);

        $siswaId = DB::table('siswa')->insertGetId([
            'nisn' => '10001',
            'nama_lengkap' => 'Siswa Bimbingan',
        ]);
        $penempatanId = DB::table('penempatan_pkl')->insertGetId([
            'siswa_id' => $siswaId,
            'guru_id' => $this->guruId,
        ]);
        $this->izinId = $this->insertIzin($penempatanId);

        $guruLainUser = User::query()->forceCreate([
            'role_id' => $roleGuru->id,
            'username' => 'guru-lain',
            'email' => 'guru-lain@example.test',
            'nama_tampilan' => 'Guru Lain',
            'status' => 'aktif',
            'wajib_ganti_password' => false,
        ]);
        $guruLainId = DB::table('guru')->insertGetId([
            'user_id' => $guruLainUser->id,
            'nama_lengkap' => 'Guru Lain',
            'status_bimbingan' => 'aktif',
        ]);
        $siswaLainId = DB::table('siswa')->insertGetId([
            'nisn' => '10002',
            'nama_lengkap' => 'Siswa Guru Lain',
        ]);
        $penempatanLainId = DB::table('penempatan_pkl')->insertGetId([
            'siswa_id' => $siswaLainId,
            'guru_id' => $guruLainId,
        ]);
        $izinLainId = $this->insertIzin($penempatanLainId);
        Storage::fake('local');
        Storage::disk('local')->put('izin/surat-lain.jpg', 'private-other-file');
        DB::table('pengajuan_izin_lampiran')->insert([
            'pengajuan_id' => $izinLainId,
            'nama_file' => 'surat-lain.jpg',
            'path_file' => 'izin/surat-lain.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        $this->actingAs($this->guruUser, 'sanctum');
    }

    public function test_teacher_only_receives_izin_for_assigned_students(): void
    {
        Storage::disk('local')->put('izin/surat.jpg', 'private-image');
        $lampiranId = DB::table('pengajuan_izin_lampiran')->insertGetId([
            'pengajuan_id' => $this->izinId,
            'nama_file' => 'surat.jpg',
            'path_file' => 'izin/surat.jpg',
            'mime_type' => 'image/jpeg',
            'ukuran_byte' => 13,
        ]);

        $this->getJson('/api/v1/guru/izin')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->izinId)
            ->assertJsonPath('data.0.jenis', 'izin')
            ->assertJsonPath('data.0.status', 'menunggu')
            ->assertJsonPath('data.0.siswa.nama_lengkap', 'Siswa Bimbingan')
            ->assertJsonPath('data.0.lampiran.0.id', $lampiranId)
            ->assertJsonPath('data.0.lampiran.0.nama_file', 'surat.jpg')
            ->assertJsonPath(
                'data.0.lampiran.0.url',
                url("/api/v1/guru/izin/{$this->izinId}/lampiran/{$lampiranId}"),
            );
    }

    public function test_teacher_can_read_own_private_izin_attachment(): void
    {
        Storage::disk('local')->put('izin/surat.jpg', 'private-image');
        $lampiranId = DB::table('pengajuan_izin_lampiran')->insertGetId([
            'pengajuan_id' => $this->izinId,
            'nama_file' => 'surat.jpg',
            'path_file' => 'izin/surat.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        $this->get('/api/v1/guru/izin/'.$this->izinId.'/lampiran/'.$lampiranId)
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg')
            ->assertContent('private-image');
    }

    public function test_teacher_cannot_read_another_teachers_attachment(): void
    {
        $izinLainId = DB::table('pengajuan_izin')
            ->where('penempatan_id', '!=', DB::table('pengajuan_izin')
                ->where('id', $this->izinId)
                ->value('penempatan_id'))
            ->value('id');
        Storage::disk('local')->put('izin/surat-lain.jpg', 'private-other-file');
        $lampiranId = DB::table('pengajuan_izin_lampiran')
            ->where('pengajuan_id', $izinLainId)
            ->value('id');

        $this->getJson('/api/v1/guru/izin/'.$izinLainId.'/lampiran/'.$lampiranId)
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_izin_list_requires_a_teacher_account(): void
    {
        Auth::forgetGuards();

        $this->getJson('/api/v1/guru/izin')
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    private function insertIzin(int $penempatanId): int
    {
        return DB::table('pengajuan_izin')->insertGetId([
            'penempatan_id' => $penempatanId,
            'jenis' => 'izin',
            'tanggal_mulai' => '2026-11-27',
            'tanggal_selesai' => '2026-11-28',
            'alasan' => 'Keperluan keluarga',
            'status' => 'menunggu',
        ]);
    }
}
