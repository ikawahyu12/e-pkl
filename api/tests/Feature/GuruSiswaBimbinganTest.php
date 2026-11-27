<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GuruSiswaBimbinganTest extends TestCase
{
    private User $guruUser;
    private int $guruId;
    private int $instansiId;
    private int $pengaturanId;
    private int $roleGuruId;
    private int $picId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-11-20 10:00:00', 'Asia/Jakarta'));
        $this->buatSkema();

        $this->roleGuruId = Role::query()->create(['nama' => 'guru'])->id;
        $this->guruUser = $this->buatGuru('guru-utama');
        $this->guruId = (int) DB::table('guru')->where('user_id', $this->guruUser->id)->value('id');

        DB::table('kelas')->insert(['id' => 1, 'nama' => 'XI RPL']);
        $this->instansiId = DB::table('instansi')->insertGetId([
            'nama' => 'PT. Informatika Solusi',
            'alamat' => 'Jl. Merdeka 1',
        ]);
        $picId = DB::table('pic_instansi')->insertGetId([
            'instansi_id' => $this->instansiId,
            'nama' => 'Bpk. Hendra',
        ]);
        $this->pengaturanId = DB::table('pengaturan_presensi')->insertGetId([
            'instansi_id' => $this->instansiId,
            'aktif' => true,
        ]);

        $this->actingAs($this->guruUser, 'sanctum');
        $this->picId = $picId;
    }

    public function test_list_contains_only_students_of_the_authenticated_teacher(): void
    {
        $this->buatBimbingan('Ahmad Rizky', '1001');
        $guruLain = $this->buatGuru('guru-lain');
        $this->buatBimbingan(
            'Siswa Guru Lain',
            '2001',
            guruId: (int) DB::table('guru')->where('user_id', $guruLain->id)->value('id'),
        );

        $this->getJson('/api/v1/guru/siswa-bimbingan')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nama', 'Ahmad Rizky')
            ->assertJsonPath('data.0.kelas', 'XI RPL')
            ->assertJsonPath('data.0.instansi', 'PT. Informatika Solusi')
            ->assertJsonPath('data.0.pembimbing_industri', 'Bpk. Hendra')
            ->assertJsonPath('data.0.periode_pkl', '3 Agu 2026 – 30 Nov 2026')
            ->assertJsonPath('meta.total', 1);
    }

    public function test_cancelled_placements_are_not_listed(): void
    {
        $this->buatBimbingan('Siswa Batal', '1002', status: 'dibatalkan');

        $this->getJson('/api/v1/guru/siswa-bimbingan')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_status_today_follows_attendance_leave_and_closed_session(): void
    {
        $hadir = $this->buatBimbingan('Siswa Hadir', '1003');
        $izin = $this->buatBimbingan('Siswa Izin', '1004');
        $alpha = $this->buatBimbingan('Siswa Alpha', '1005');
        $kosong = $this->buatBimbingan('Siswa Belum Ada', '1006');

        $sesiHariIni = $this->buatSesi('2026-11-20', 'dibuka');
        DB::table('presensi')->insert([
            'sesi_id' => $sesiHariIni, 'penempatan_id' => $hadir['penempatan'],
            'tanggal' => '2026-11-20', 'status_harian' => 'hadir',
        ]);
        DB::table('pengajuan_izin')->insert([
            'penempatan_id' => $izin['penempatan'], 'status' => 'disetujui',
            'tanggal_mulai' => '2026-11-19', 'tanggal_selesai' => '2026-11-21',
        ]);
        DB::table('presensi')->insert([
            'sesi_id' => $sesiHariIni, 'penempatan_id' => $alpha['penempatan'],
            'tanggal' => '2026-11-20', 'status_harian' => 'alpha',
        ]);

        $response = $this->getJson('/api/v1/guru/siswa-bimbingan')->assertOk();
        $status = collect($response->json('data'))->pluck('status', 'nama');

        $this->assertSame('hadir', $status['Siswa Hadir']);
        $this->assertSame('izin', $status['Siswa Izin']);
        $this->assertSame('alpha', $status['Siswa Alpha']);
        $this->assertSame('belum_ada', $status['Siswa Belum Ada']);
        $response->assertJsonPath('meta.ringkasan_status_hari_ini.hadir', 1)
            ->assertJsonPath('meta.ringkasan_status_hari_ini.belum_ada', 1);
    }

    public function test_attendance_percentage_is_present_days_over_sessions_so_far(): void
    {
        $siswa = $this->buatBimbingan('Siswa Rajin', '1007');

        foreach (['2026-11-17', '2026-11-18', '2026-11-19', '2026-11-20'] as $i => $tanggal) {
            $sesi = $this->buatSesi($tanggal, 'ditutup');
            if ($i < 3) { // hadir 3 dari 4 sesi
                DB::table('presensi')->insert([
                    'sesi_id' => $sesi, 'penempatan_id' => $siswa['penempatan'],
                    'tanggal' => $tanggal, 'status_harian' => 'hadir',
                ]);
            }
        }

        $this->getJson('/api/v1/guru/siswa-bimbingan')
            ->assertOk()
            ->assertJsonPath('data.0.persentase_kehadiran', 75)
            ->assertJsonPath('data.0.jumlah_hadir', 3)
            ->assertJsonPath('data.0.status', 'alpha'); // sesi hari ini ditutup tanpa presensi
    }

    public function test_search_filter_and_sort(): void
    {
        $this->buatBimbingan('Budi', '1008');
        $this->buatBimbingan('Andi', '1009');
        $this->buatBimbingan('Citra', '1010');

        $this->getJson('/api/v1/guru/siswa-bimbingan?q=and')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.nama', 'Andi');

        $this->getJson('/api/v1/guru/siswa-bimbingan?urutan=nama')
            ->assertOk()->assertJsonPath('data.0.nama', 'Andi')->assertJsonPath('data.2.nama', 'Citra');

        $this->getJson('/api/v1/guru/siswa-bimbingan?status=hadir')
            ->assertOk()->assertJsonCount(0, 'data')->assertJsonPath('meta.total_bimbingan', 3);

        $this->getJson('/api/v1/guru/siswa-bimbingan?status=salah')
            ->assertUnprocessable();
    }

    public function test_detail_returns_student_profile_and_summaries(): void
    {
        $siswa = $this->buatBimbingan('Ahmad Rizky', '1011');
        $sesi = $this->buatSesi('2026-11-19', 'ditutup');
        DB::table('presensi')->insert([
            'sesi_id' => $sesi, 'penempatan_id' => $siswa['penempatan'],
            'tanggal' => '2026-11-19', 'status_harian' => 'hadir',
        ]);
        foreach (['menunggu', 'disetujui', 'disetujui'] as $status) {
            DB::table('jurnal')->insert([
                'penempatan_id' => $siswa['penempatan'], 'tanggal' => '2026-11-19',
                'judul' => 'Jurnal', 'kegiatan' => 'Kegiatan', 'status' => $status,
            ]);
        }

        $this->getJson('/api/v1/guru/siswa-bimbingan/'.$siswa['siswa'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $siswa['siswa'])
            ->assertJsonPath('data.nama', 'Ahmad Rizky')
            ->assertJsonPath('data.nis', 'NIS-1011')
            ->assertJsonPath('data.no_hp', '0812')
            ->assertJsonPath('data.pic.nama', 'Bpk. Hendra')
            ->assertJsonPath('data.ringkasan_presensi.hadir', 1)
            ->assertJsonPath('data.ringkasan_jurnal.total', 3)
            ->assertJsonPath('data.ringkasan_jurnal.disetujui', 2)
            ->assertJsonPath('data.ringkasan_jurnal.menunggu', 1);
    }

    public function test_detail_of_another_teachers_student_is_not_found(): void
    {
        $guruLain = $this->buatGuru('guru-lain');
        $milikLain = $this->buatBimbingan(
            'Siswa Guru Lain',
            '2002',
            guruId: (int) DB::table('guru')->where('user_id', $guruLain->id)->value('id'),
        );

        $this->getJson('/api/v1/guru/siswa-bimbingan/'.$milikLain['siswa'])
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_student_with_two_placements_is_listed_once_with_the_latest(): void
    {
        $siswa = $this->buatBimbingan('Siswa Dua Periode', '1012', mulai: '2026-02-02', selesai: '2026-05-01', status: 'selesai');
        DB::table('penempatan_pkl')->insert([
            'siswa_id' => $siswa['siswa'], 'instansi_id' => $this->instansiId, 'guru_id' => $this->guruId,
            'status' => 'aktif', 'tanggal_mulai' => '2026-08-03', 'tanggal_selesai' => '2026-11-30',
        ]);

        $this->getJson('/api/v1/guru/siswa-bimbingan')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status_penempatan', 'aktif');
    }

    /* ---------- helper ---------- */

    private function buatGuru(string $username): User
    {
        $user = User::query()->forceCreate([
            'role_id' => $this->roleGuruId,
            'username' => $username,
            'email' => $username.'@example.test',
            'nama_tampilan' => $username,
            'status' => 'aktif',
        ]);
        DB::table('guru')->insert([
            'user_id' => $user->id, 'nama_lengkap' => $username, 'status_bimbingan' => 'aktif',
        ]);

        return $user;
    }

    /** @return array{siswa: int, penempatan: int} */
    private function buatBimbingan(
        string $nama,
        string $nisn,
        ?int $guruId = null,
        string $status = 'aktif',
        string $mulai = '2026-08-03',
        string $selesai = '2026-11-30',
    ): array {
        $siswaId = DB::table('siswa')->insertGetId([
            'nisn' => $nisn, 'nis' => 'NIS-'.$nisn, 'nama_lengkap' => $nama,
            'kelas_id' => 1, 'no_hp' => '0812',
        ]);
        $penempatanId = DB::table('penempatan_pkl')->insertGetId([
            'siswa_id' => $siswaId,
            'instansi_id' => $this->instansiId,
            'guru_id' => $guruId ?? $this->guruId,
            'pic_instansi_id' => $this->picId,
            'status' => $status,
            'tanggal_mulai' => $mulai,
            'tanggal_selesai' => $selesai,
        ]);

        return ['siswa' => $siswaId, 'penempatan' => $penempatanId];
    }

    private function buatSesi(string $tanggal, string $status): int
    {
        return DB::table('sesi_presensi')->insertGetId([
            'pengaturan_id' => $this->pengaturanId,
            'tanggal' => $tanggal,
            'status' => $status,
            'dibuat_oleh' => $this->guruUser->id,
        ]);
    }

    private function buatSkema(): void
    {
        Schema::create('roles', function (Blueprint $t): void {
            $t->id();
            $t->string('nama')->unique();
            $t->timestamp('created_at')->nullable();
        });
        Schema::create('users', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('role_id');
            $t->string('username')->nullable();
            $t->string('email')->nullable();
            $t->string('password_hash')->nullable();
            $t->string('nama_tampilan');
            $t->string('status')->default('aktif');
            $t->boolean('wajib_ganti_password')->default(false);
            $t->timestamps();
        });
        Schema::create('guru', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('nama_lengkap');
            $t->string('status_bimbingan')->default('aktif');
            $t->timestamps();
        });
        Schema::create('kelas', function (Blueprint $t): void {
            $t->id();
            $t->string('nama');
        });
        Schema::create('siswa', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('nisn')->unique();
            $t->string('nis')->nullable();
            $t->string('nama_lengkap');
            $t->unsignedBigInteger('kelas_id')->nullable();
            $t->string('jenis_kelamin')->nullable();
            $t->string('no_hp')->nullable();
            $t->string('email_sekolah')->nullable();
            $t->text('alamat')->nullable();
            $t->string('nama_orang_tua')->nullable();
            $t->string('no_hp_orang_tua')->nullable();
            $t->timestamps();
        });
        Schema::create('instansi', function (Blueprint $t): void {
            $t->id();
            $t->string('nama');
            $t->text('alamat');
            $t->string('telepon')->nullable();
        });
        Schema::create('pic_instansi', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('instansi_id');
            $t->string('nama');
            $t->string('jabatan')->nullable();
            $t->string('no_hp')->nullable();
        });
        Schema::create('penempatan_pkl', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('siswa_id');
            $t->unsignedBigInteger('instansi_id');
            $t->unsignedBigInteger('guru_id');
            $t->unsignedBigInteger('pic_instansi_id')->nullable();
            $t->date('tanggal_mulai');
            $t->date('tanggal_selesai');
            $t->string('status')->default('aktif');
            $t->text('catatan')->nullable();
            $t->timestamps();
        });
        Schema::create('pengaturan_presensi', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('instansi_id');
            $t->boolean('aktif')->default(true);
        });
        Schema::create('sesi_presensi', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('pengaturan_id');
            $t->date('tanggal');
            $t->string('status')->default('dibuka');
            $t->unsignedBigInteger('dibuat_oleh')->nullable();
        });
        Schema::create('presensi', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('sesi_id');
            $t->unsignedBigInteger('penempatan_id');
            $t->date('tanggal');
            $t->string('status_harian')->default('belum_lengkap');
        });
        Schema::create('pengajuan_izin', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('penempatan_id');
            $t->string('status')->default('menunggu');
            $t->date('tanggal_mulai');
            $t->date('tanggal_selesai');
        });
        Schema::create('jurnal', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('penempatan_id');
            $t->date('tanggal');
            $t->string('judul');
            $t->text('kegiatan');
            $t->string('status')->default('menunggu');
        });
    }
}