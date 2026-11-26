<?php

namespace Tests\Feature;

use App\Models\PenempatanPkl;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiswaIzinJurnalTest extends TestCase
{
    private User $siswaUser;

    private Siswa $siswa;

    private PenempatanPkl $penempatan;

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
            $table->string('email')->nullable();
            $table->string('nama_tampilan');
            $table->string('password_hash')->nullable();
            $table->string('status')->default('aktif');
            $table->boolean('wajib_ganti_password')->default(false);
            $table->timestamps();
        });
        Schema::create('siswa', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('nisn');
            $table->string('nama_lengkap');
            $table->string('status_siswa')->default('aktif');
            $table->timestamps();
        });
        Schema::create('penempatan_pkl', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('siswa_id');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->string('status');
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
            $table->timestamp('created_at')->nullable();
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
            $table->timestamps();
        });
        Schema::create('jurnal_lampiran', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('jurnal_id');
            $table->string('nama_file');
            $table->string('path_file', 500);
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('ukuran_byte')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        $role = Role::query()->create(['nama' => 'siswa']);
        $this->siswaUser = User::query()->forceCreate([
            'role_id' => $role->id,
            'email' => 'siswa@example.test',
            'nama_tampilan' => 'Siswa Uji',
            'status' => 'aktif',
            'wajib_ganti_password' => false,
        ]);
        $this->siswa = Siswa::query()->forceCreate([
            'user_id' => $this->siswaUser->id,
            'nisn' => 'nisn-uji',
            'nama_lengkap' => 'Siswa Uji',
            'status_siswa' => 'aktif',
        ]);
        $this->penempatan = $this->createPlacement($this->siswa, 'aktif');

        Storage::fake('local');
        Storage::fake('public');
        $this->actingAs($this->siswaUser, 'sanctum');
    }

    public function test_izin_uses_the_authenticated_students_placement_only(): void
    {
        $otherSiswa = $this->createStudent(2);
        $otherPlacement = $this->createPlacement($otherSiswa, 'aktif');

        $this->postJson('/api/v1/siswa/izin', array_merge($this->izinPayload(), [
            'siswa_id' => $otherSiswa->id,
            'penempatan_id' => $otherPlacement->id,
        ]))->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('pengajuan_izin', [
            'penempatan_id' => $this->penempatan->id,
            'status' => 'disetujui',
        ]);
        $this->assertDatabaseMissing('pengajuan_izin', [
            'penempatan_id' => $otherPlacement->id,
        ]);
    }

    public function test_submitted_izin_is_returned_in_student_history(): void
    {
        $this->postJson('/api/v1/siswa/izin', $this->izinPayload([
            'tanggal_mulai' => now()->addDay()->toDateString(),
            'tanggal_selesai' => now()->addDays(2)->toDateString(),
        ]))->assertOk();

        $this->getJson('/api/v1/siswa/riwayat?tipe=izin')
            ->assertOk()
            ->assertJsonFragment([
                'tipe' => 'izin',
                'jenis' => 'sakit',
                'judul' => 'Pengajuan Sakit',
                'badge' => 'Disetujui',
                'keterangan' => 'Pemeriksaan uji',
            ]);
    }

    public function test_jurnal_uses_the_authenticated_students_placement_only(): void
    {
        $otherSiswa = $this->createStudent(2);
        $otherPlacement = $this->createPlacement($otherSiswa, 'aktif');

        $this->postJson('/api/v1/siswa/jurnal', array_merge($this->jurnalPayload(), [
            'siswa_id' => $otherSiswa->id,
            'penempatan_id' => $otherPlacement->id,
        ]))->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('jurnal', [
            'penempatan_id' => $this->penempatan->id,
            'status' => 'menunggu',
        ]);
        $this->assertDatabaseMissing('jurnal', [
            'penempatan_id' => $otherPlacement->id,
        ]);
    }

    public function test_other_roles_and_password_change_required_are_denied(): void
    {
        $this->siswaUser->role()->update(['nama' => 'guru']);
        $this->siswaUser->unsetRelation('role');
        $this->postJson('/api/v1/siswa/izin', $this->izinPayload())->assertForbidden();
        $this->siswaUser->role()->update(['nama' => 'siswa']);
        $this->siswaUser->unsetRelation('role');

        $this->siswaUser->forceFill(['wajib_ganti_password' => true])->save();
        $this->postJson('/api/v1/siswa/jurnal', $this->jurnalPayload())->assertForbidden();

        $this->assertSame(0, DB::table('pengajuan_izin')->count());
        $this->assertSame(0, DB::table('jurnal')->count());
    }

    public function test_completed_placement_is_rejected_for_izin_and_jurnal(): void
    {
        $this->penempatan->forceFill(['status' => 'selesai'])->save();

        $this->postJson('/api/v1/siswa/izin', $this->izinPayload())
            ->assertUnprocessable();
        $this->postJson('/api/v1/siswa/jurnal', $this->jurnalPayload())
            ->assertUnprocessable();

        $this->assertSame(0, DB::table('pengajuan_izin')->count());
        $this->assertSame(0, DB::table('jurnal')->count());
    }

    public function test_dates_outside_the_active_placement_are_rejected(): void
    {
        $this->postJson('/api/v1/siswa/izin', $this->izinPayload([
            'tanggal_mulai' => now()->addDays(31)->toDateString(),
            'tanggal_selesai' => now()->addDays(32)->toDateString(),
        ]))->assertUnprocessable();
        $this->postJson('/api/v1/siswa/jurnal', $this->jurnalPayload([
            'tanggal' => now()->addDays(31)->toDateString(),
        ]))->assertUnprocessable();

        $this->assertSame(0, DB::table('pengajuan_izin')->count());
        $this->assertSame(0, DB::table('jurnal')->count());
    }

    public function test_jurnal_accepts_today_in_the_application_timezone(): void
    {
        $today = CarbonImmutable::today((string) config('app.timezone'))->toDateString();

        $this->postJson('/api/v1/siswa/jurnal', $this->jurnalPayload([
            'tanggal' => $today,
        ]))->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('jurnal', [
            'penempatan_id' => $this->penempatan->id,
            'tanggal' => $today,
        ]);
    }

    public function test_future_jurnal_date_is_rejected_without_storing_jurnal_or_attachment(): void
    {
        $futureDate = CarbonImmutable::today((string) config('app.timezone'))
            ->addDay()
            ->toDateString();
        $foto = UploadedFile::fake()->create('future.png', 32, 'image/png');

        $this->post('/api/v1/siswa/jurnal', array_merge($this->jurnalPayload([
            'tanggal' => $futureDate,
        ]), [
            'foto' => $foto,
        ]), ['Accept' => 'application/json'])->assertUnprocessable()
            ->assertJsonValidationErrors('tanggal');

        $this->assertSame(0, DB::table('jurnal')->count());
        $this->assertSame(0, DB::table('jurnal_lampiran')->count());
        Storage::disk('local')->assertDirectoryEmpty('jurnal');
    }

    public function test_attachments_are_validated_and_stored_privately(): void
    {
        $izinFile = UploadedFile::fake()->create('surat.pdf', 32, 'application/pdf');
        $this->post('/api/v1/siswa/izin', array_merge($this->izinPayload(), [
            'lampiran' => $izinFile,
        ]), ['Accept' => 'application/json'])->assertOk();

        $izinLampiran = DB::table('pengajuan_izin_lampiran')->first();
        $this->assertNotNull($izinLampiran);
        Storage::disk('local')->assertExists($izinLampiran->path_file);
        Storage::disk('public')->assertMissing($izinLampiran->path_file);

        $foto = UploadedFile::fake()->create('foto.png', 32, 'image/png');
        $this->post('/api/v1/siswa/jurnal', array_merge($this->jurnalPayload(), [
            'foto' => $foto,
        ]), ['Accept' => 'application/json'])->assertOk();

        $jurnalLampiran = DB::table('jurnal_lampiran')->first();
        $this->assertNotNull($jurnalLampiran);
        Storage::disk('local')->assertExists($jurnalLampiran->path_file);
        Storage::disk('public')->assertMissing($jurnalLampiran->path_file);
    }

    public function test_invalid_or_oversized_attachments_are_rejected(): void
    {
        $invalid = UploadedFile::fake()->create('bukti.txt', 10, 'text/plain');
        $this->post('/api/v1/siswa/izin', array_merge($this->izinPayload(), [
            'lampiran' => $invalid,
        ]), ['Accept' => 'application/json'])->assertUnprocessable()
            ->assertJsonValidationErrors('lampiran');

        $oversized = UploadedFile::fake()->create('foto.png', 5121, 'image/png');
        $this->post('/api/v1/siswa/jurnal', array_merge($this->jurnalPayload(), [
            'foto' => $oversized,
        ]), ['Accept' => 'application/json'])->assertUnprocessable()
            ->assertJsonValidationErrors('foto');

        $this->assertSame(0, DB::table('pengajuan_izin')->count());
        $this->assertSame(0, DB::table('jurnal')->count());
    }

    public function test_failed_attachment_write_returns_an_error_without_creating_a_record(): void
    {
        $disk = \Mockery::mock(Filesystem::class);
        $disk->shouldReceive('putFileAs')->twice()->andReturn(false);
        Storage::shouldReceive('disk')->with('local')->andReturn($disk);

        $file = UploadedFile::fake()->create('surat.pdf', 32, 'application/pdf');
        $this->post('/api/v1/siswa/izin', array_merge($this->izinPayload(), [
            'lampiran' => $file,
        ]), ['Accept' => 'application/json'])->assertStatus(500);

        $foto = UploadedFile::fake()->create('foto.png', 32, 'image/png');
        $this->post('/api/v1/siswa/jurnal', array_merge($this->jurnalPayload(), [
            'foto' => $foto,
        ]), ['Accept' => 'application/json'])->assertStatus(500);

        $this->assertSame(0, DB::table('pengajuan_izin')->count());
        $this->assertSame(0, DB::table('jurnal')->count());
    }

    private function createStudent(int $suffix): Siswa
    {
        return Siswa::query()->forceCreate([
            'nisn' => "nisn-uji-$suffix",
            'nama_lengkap' => "Siswa $suffix",
            'status_siswa' => 'aktif',
        ]);
    }

    private function createPlacement(Siswa $siswa, string $status): PenempatanPkl
    {
        return PenempatanPkl::query()->forceCreate([
            'siswa_id' => $siswa->id,
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->addDays(30)->toDateString(),
            'status' => $status,
        ]);
    }

    private function izinPayload(array $overrides = []): array
    {
        return array_merge([
            'jenis' => 'sakit',
            'tanggal_mulai' => now()->addDay()->toDateString(),
            'tanggal_selesai' => now()->addDay()->toDateString(),
            'alasan' => 'Pemeriksaan uji',
        ], $overrides);
    }

    private function jurnalPayload(array $overrides = []): array
    {
        return array_merge([
            'tanggal' => CarbonImmutable::today((string) config('app.timezone'))->toDateString(),
            'judul' => 'Jurnal uji',
            'kegiatan' => 'Mengerjakan tugas uji.',
            'hasil' => 'Tugas selesai.',
        ], $overrides);
    }
}
