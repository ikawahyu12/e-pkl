<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\Guru;
use App\Models\Instansi;
use App\Models\PenempatanPkl;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GuruAgendaTest extends TestCase
{
    private User $guruUser;

    private Guru $guru;

    private Instansi $instansi;

    private Siswa $siswa;

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
        Schema::create('guru', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('nip');
            $table->string('nama_lengkap');
            $table->string('status_bimbingan')->default('aktif');
            $table->unsignedSmallInteger('kuota_bimbingan')->default(1);
            $table->timestamps();
        });
        Schema::create('kelas', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
        });
        Schema::create('siswa', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('nisn');
            $table->string('nama_lengkap');
            $table->unsignedBigInteger('kelas_id')->nullable();
            $table->string('status_siswa')->default('aktif');
            $table->timestamps();
        });
        Schema::create('instansi', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->text('alamat');
            $table->string('status')->default('aktif');
            $table->timestamps();
        });
        Schema::create('penempatan_pkl', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('guru_id')->nullable();
            $table->unsignedBigInteger('instansi_id');
            $table->unsignedBigInteger('siswa_id');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('agenda_kunjungan', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('guru_id');
            $table->unsignedBigInteger('instansi_id');
            $table->date('tanggal');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->string('jenis_pencatatan', 20)->nullable();
            $table->string('status')->default('terjadwal');
            $table->text('catatan')->nullable();
            $table->text('alasan_pembatalan')->nullable();
            $table->timestamp('dibatalkan_at')->nullable();
            $table->unsignedBigInteger('dibatalkan_oleh_user_id')->nullable();
            $table->timestamps();
        });
        Schema::create('agenda_kunjungan_siswa', function (Blueprint $table): void {
            $table->unsignedBigInteger('agenda_kunjungan_id');
            $table->unsignedBigInteger('siswa_id');
            $table->primary(['agenda_kunjungan_id', 'siswa_id']);
        });

        $role = Role::query()->create(['nama' => 'guru']);
        $this->guruUser = User::query()->forceCreate([
            'role_id' => $role->id,
            'email' => 'guru@example.test',
            'nama_tampilan' => 'Guru Uji',
            'status' => 'aktif',
        ]);
        $this->guru = Guru::query()->forceCreate([
            'user_id' => $this->guruUser->id,
            'nip' => 'nip-uji',
            'nama_lengkap' => 'Guru Uji',
            'status_bimbingan' => 'aktif',
            'kuota_bimbingan' => 1,
        ]);
        $kelasId = DB::table('kelas')->insertGetId(['nama' => 'XI RPL']);
        $this->siswa = Siswa::query()->forceCreate([
            'nisn' => 'nisn-uji',
            'nama_lengkap' => 'Siswa Uji',
            'kelas_id' => $kelasId,
            'status_siswa' => 'aktif',
        ]);
        $this->instansi = Instansi::query()->forceCreate([
            'nama' => 'Instansi Uji',
            'alamat' => 'Jalan Uji',
            'status' => 'aktif',
        ]);
        PenempatanPkl::query()->forceCreate([
            'guru_id' => $this->guru->id,
            'instansi_id' => $this->instansi->id,
            'siswa_id' => $this->siswa->id,
            'tanggal_mulai' => CarbonImmutable::now('Asia/Jakarta')->toDateString(),
            'tanggal_selesai' => CarbonImmutable::now('Asia/Jakarta')->addDays(30)->toDateString(),
            'status' => 'aktif',
        ]);

        $this->actingAs($this->guruUser, 'sanctum');
    }

    public function test_agenda_is_created_and_cancellation_is_recorded_without_deleting_it(): void
    {
        $date = now()->toDateString();
        $response = $this->postJson('/api/v1/guru/agenda', [
            'instansi_id' => $this->instansi->id,
            'siswa_ids' => [$this->siswa->id],
            'tanggal' => $date,
            'jam_mulai' => '09:00',
            'jam_selesai' => '10:00',
            'catatan' => 'Kunjungan uji',
        ])->assertCreated()
            ->assertJsonPath('data.status', 'terjadwal');
        $this->assertSame('terencana', $response->json('data.jenis_pencatatan'));

        $agendaId = $response->json('data.id');
        $this->patchJson("/api/v1/guru/agenda/$agendaId/status", [
            'status' => 'dibatalkan',
            'alasan_pembatalan' => 'Instansi tutup',
        ])->assertOk()
            ->assertJsonPath('data.status', 'dibatalkan')
            ->assertJsonPath('data.alasan_pembatalan', 'Instansi tutup');

        $this->assertDatabaseHas('agenda_kunjungan', [
            'id' => $agendaId,
            'status' => 'dibatalkan',
            'alasan_pembatalan' => 'Instansi tutup',
            'dibatalkan_oleh_user_id' => $this->guruUser->id,
        ]);
        $this->assertDatabaseHas('agenda_kunjungan_siswa', [
            'agenda_kunjungan_id' => $agendaId,
            'siswa_id' => $this->siswa->id,
        ]);
        $this->assertSame(1, Agenda::query()->count());
    }

    public function test_overlapping_agenda_for_the_same_student_is_rejected(): void
    {
        $date = now()->toDateString();
        $this->postJson('/api/v1/guru/agenda', [
            'instansi_id' => $this->instansi->id,
            'siswa_ids' => [$this->siswa->id],
            'tanggal' => $date,
            'jam_mulai' => '09:00',
            'jam_selesai' => '10:00',
        ])->assertCreated();

        $this->postJson('/api/v1/guru/agenda', [
            'instansi_id' => $this->instansi->id,
            'siswa_ids' => [$this->siswa->id],
            'tanggal' => $date,
            'jam_mulai' => '09:30',
            'jam_selesai' => '10:30',
        ])->assertUnprocessable();

        $this->assertSame(1, Agenda::query()->count());
    }

    public function test_agenda_cancellation_requires_a_reason(): void
    {
        $response = $this->postJson('/api/v1/guru/agenda', [
            'instansi_id' => $this->instansi->id,
            'siswa_ids' => [$this->siswa->id],
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '09:00',
            'jam_selesai' => '10:00',
        ])->assertCreated();

        $agendaId = $response->json('data.id');
        $this->patchJson("/api/v1/guru/agenda/$agendaId/status", [
            'status' => 'dibatalkan',
        ])->assertUnprocessable();

        $this->assertDatabaseHas('agenda_kunjungan', [
            'id' => $agendaId,
            'status' => 'terjadwal',
            'alasan_pembatalan' => null,
        ]);
    }

    public function test_inactive_guru_cannot_create_an_agenda(): void
    {
        $this->guru->update(['status_bimbingan' => 'nonaktif']);

        $this->postJson('/api/v1/guru/agenda', $this->agendaPayload())
            ->assertForbidden();

        $this->assertSame(0, Agenda::query()->count());
    }

    public function test_inactive_instance_is_rejected(): void
    {
        $this->instansi->forceFill(['status' => 'nonaktif'])->save();

        $this->postJson('/api/v1/guru/agenda', $this->agendaPayload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('instansi_id');

        $this->assertSame(0, Agenda::query()->count());
    }

    public function test_inactive_or_unplaced_student_is_rejected(): void
    {
        $this->siswa->update(['status_siswa' => 'nonaktif']);
        $this->postJson('/api/v1/guru/agenda', $this->agendaPayload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('siswa_ids');

        $this->siswa->update(['status_siswa' => 'aktif']);
        DB::table('penempatan_pkl')->delete();
        $this->postJson('/api/v1/guru/agenda', $this->agendaPayload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('siswa_ids');

        $this->assertSame(0, Agenda::query()->count());
    }

    public function test_student_placement_must_cover_the_agenda_date(): void
    {
        DB::table('penempatan_pkl')->update([
            'tanggal_mulai' => now()->addDay()->toDateString(),
        ]);

        $this->postJson('/api/v1/guru/agenda', $this->agendaPayload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('siswa_ids');

        $this->assertSame(0, Agenda::query()->count());
    }

    public function test_planned_agenda_accepts_today(): void
    {
        $this->postJson('/api/v1/guru/agenda', $this->agendaPayload())
            ->assertCreated()
            ->assertJsonPath('data.jenis_pencatatan', 'terencana')
            ->assertJsonPath('data.status', 'terjadwal');
    }

    public function test_planned_agenda_rejects_a_past_date(): void
    {
        $yesterday = CarbonImmutable::now('Asia/Jakarta')->subDay()->toDateString();
        DB::table('penempatan_pkl')->update([
            'tanggal_mulai' => CarbonImmutable::now('Asia/Jakarta')->subDays(7)->toDateString(),
        ]);

        $this->postJson('/api/v1/guru/agenda', array_merge($this->agendaPayload(), [
            'tanggal' => $yesterday,
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors('tanggal');

        $this->assertSame(0, Agenda::query()->count());
    }

    public function test_planned_agenda_accepts_a_future_date_in_the_placement_period(): void
    {
        $tomorrow = CarbonImmutable::now('Asia/Jakarta')->addDay()->toDateString();

        $this->postJson('/api/v1/guru/agenda', array_merge($this->agendaPayload(), [
            'tanggal' => $tomorrow,
        ]))->assertCreated()
            ->assertJsonPath('data.jenis_pencatatan', 'terencana')
            ->assertJsonPath('data.status', 'terjadwal');
    }

    public function test_retroactive_agenda_requires_confirmation_and_records_a_completed_visit(): void
    {
        $now = CarbonImmutable::now('Asia/Jakarta');
        $visitDate = $now->subDay()->toDateString();
        DB::table('penempatan_pkl')->update([
            'tanggal_mulai' => $now->subDays(7)->toDateString(),
            'status' => 'selesai',
        ]);

        $this->postJson('/api/v1/guru/agenda', array_merge($this->agendaPayload(), [
            'tanggal' => $visitDate,
            'jenis_pencatatan' => 'susulan',
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors('konfirmasi_sudah_dilaksanakan');

        $response = $this->postJson('/api/v1/guru/agenda', array_merge($this->agendaPayload(), [
            'tanggal' => $visitDate,
            'jenis_pencatatan' => 'susulan',
            'konfirmasi_sudah_dilaksanakan' => true,
        ]))->assertCreated()
            ->assertJsonPath('data.jenis_pencatatan', 'susulan')
            ->assertJsonPath('data.status', 'selesai');

        $this->assertDatabaseHas('agenda_kunjungan', [
            'id' => $response->json('data.id'),
            'jenis_pencatatan' => 'susulan',
            'status' => 'selesai',
        ]);
    }

    public function test_retroactive_agenda_rejects_a_visit_outside_the_placement_period(): void
    {
        $yesterday = CarbonImmutable::now('Asia/Jakarta')->subDay()->toDateString();

        $this->postJson('/api/v1/guru/agenda', array_merge($this->agendaPayload(), [
            'tanggal' => $yesterday,
            'jenis_pencatatan' => 'susulan',
            'konfirmasi_sudah_dilaksanakan' => true,
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors('siswa_ids');

        $this->assertSame(0, Agenda::query()->count());
    }

    public function test_guru_is_not_limited_by_kuota_bimbingan(): void
    {
        $otherSiswa = Siswa::query()->forceCreate([
            'nisn' => 'nisn-uji-2',
            'nama_lengkap' => 'Siswa Uji 2',
            'kelas_id' => $this->siswa->kelas_id,
            'status_siswa' => 'aktif',
        ]);
        PenempatanPkl::query()->forceCreate([
            'guru_id' => $this->guru->id,
            'instansi_id' => $this->instansi->id,
            'siswa_id' => $otherSiswa->id,
            'tanggal_mulai' => CarbonImmutable::now('Asia/Jakarta')->toDateString(),
            'tanggal_selesai' => CarbonImmutable::now('Asia/Jakarta')->addDays(30)->toDateString(),
            'status' => 'aktif',
        ]);

        $this->postJson('/api/v1/guru/agenda', array_merge($this->agendaPayload(), [
            'siswa_ids' => [$this->siswa->id, $otherSiswa->id],
        ]))->assertCreated()
            ->assertJsonCount(2, 'data.siswa');

        $this->assertSame(1, $this->guru->kuota_bimbingan);
    }

    public function test_guru_cannot_record_a_visit_for_a_student_assigned_to_another_guru(): void
    {
        $otherGuru = Guru::query()->forceCreate([
            'user_id' => null,
            'nip' => 'nip-guru-lain',
            'nama_lengkap' => 'Guru Lain',
            'status_bimbingan' => 'aktif',
        ]);
        DB::table('penempatan_pkl')->update(['guru_id' => $otherGuru->id]);

        $this->postJson('/api/v1/guru/agenda', $this->agendaPayload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('siswa_ids');

        $this->assertSame(0, Agenda::query()->count());
    }

    public function test_future_agenda_cannot_be_marked_completed_before_its_scheduled_time(): void
    {
        $tomorrow = CarbonImmutable::now('Asia/Jakarta')->addDay()->toDateString();
        $agendaId = $this->postJson('/api/v1/guru/agenda', array_merge($this->agendaPayload(), [
            'tanggal' => $tomorrow,
        ]))->assertCreated()
            ->json('data.id');

        $this->patchJson("/api/v1/guru/agenda/$agendaId/status", [
            'status' => 'selesai',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->assertDatabaseHas('agenda_kunjungan', [
            'id' => $agendaId,
            'status' => 'terjadwal',
        ]);
    }

    public function test_invalid_time_range_and_status_are_rejected(): void
    {
        $this->postJson('/api/v1/guru/agenda', array_merge($this->agendaPayload(), [
            'jam_mulai' => '10:00',
            'jam_selesai' => '09:00',
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors('jam_selesai');

        $agenda = $this->postJson('/api/v1/guru/agenda', $this->agendaPayload())
            ->assertCreated()
            ->json('data.id');

        $this->patchJson("/api/v1/guru/agenda/$agenda/status", [
            'status' => 'dibatalkan',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('alasan_pembatalan');

        $this->patchJson("/api/v1/guru/agenda/$agenda/status", [
            'status' => 'tidak-valid',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    private function agendaPayload(): array
    {
        return [
            'instansi_id' => $this->instansi->id,
            'siswa_ids' => [$this->siswa->id],
            'tanggal' => CarbonImmutable::now('Asia/Jakarta')->toDateString(),
            'jam_mulai' => '09:00',
            'jam_selesai' => '10:00',
        ];
    }
}
