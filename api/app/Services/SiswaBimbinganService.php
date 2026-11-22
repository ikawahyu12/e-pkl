<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Logika data "Siswa Bimbingan" untuk Guru pembimbing.
 *
 * Sumber data: penempatan_pkl milik Guru (guru_id) yang belum dibatalkan.
 * Satu siswa ditampilkan satu kali (penempatan terbaru).
 */
class SiswaBimbinganService
{
    public const STATUS_PENEMPATAN = ['belum_mulai', 'aktif', 'selesai'];

    /** Status kehadiran HARI INI yang dikenali aplikasi mobile. */
    public const STATUS_KEHADIRAN = ['hadir', 'izin', 'alpha', 'belum_ada'];

    public const URUTAN = ['terbaru', 'nama', 'kelas'];

    private const TZ = 'Asia/Jakarta';

    private const BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    /**
     * @param  object  $guru  baris/model guru (butuh id dan user_id)
     * @param  array{q?: ?string, status?: ?string, urutan?: ?string}  $filters
     * @return array{data: array<int, array<string, mixed>>, meta: array<string, mixed>}
     */
    public function daftar(object $guru, array $filters = []): array
    {
        $rows = $this->placementQuery((int) $guru->id)
            ->get()
            ->unique('siswa_id')
            ->values();

        $items = $this->buildItems($rows, (int) $guru->user_id);

        $ringkasan = array_fill_keys(self::STATUS_KEHADIRAN, 0);
        foreach ($items as $item) {
            $ringkasan[$item['status']]++;
        }

        $q = isset($filters['q']) ? trim((string) $filters['q']) : '';
        $status = $filters['status'] ?? null;
        $urutan = $filters['urutan'] ?? 'terbaru';

        $hasil = $items
            ->when($status, fn (Collection $c) => $c->where('status', $status))
            ->when($q !== '', fn (Collection $c) => $c->filter(function (array $item) use ($q): bool {
                foreach (['nama', 'kelas', 'instansi', 'nis', 'nisn'] as $kolom) {
                    if (mb_stripos((string) $item[$kolom], $q) !== false) {
                        return true;
                    }
                }

                return false;
            }));

        if ($urutan === 'nama') {
            $hasil = $hasil->sortBy(fn (array $i) => mb_strtolower($i['nama']));
        } elseif ($urutan === 'kelas') {
            $hasil = $hasil->sortBy([
                fn (array $a, array $b) => strcmp($a['kelas'], $b['kelas']),
                fn (array $a, array $b) => strcmp(mb_strtolower($a['nama']), mb_strtolower($b['nama'])),
            ]);
        }
        // 'terbaru' = urutan asli query (tanggal_mulai desc, id desc)

        $hasil = $hasil->values();

        return [
            'data' => $hasil->all(),
            'meta' => [
                'total' => $hasil->count(),
                'total_bimbingan' => $items->count(),
                'ringkasan_status_hari_ini' => $ringkasan,
                'tanggal' => CarbonImmutable::now(self::TZ)->toDateString(),
            ],
        ];
    }

    /**
     * Detail satu siswa. null bila siswa bukan bimbingan Guru ini.
     *
     * @return array<string, mixed>|null
     */
    public function detail(object $guru, int $siswaId): ?array
    {
        $row = $this->placementQuery((int) $guru->id)
            ->where('pp.siswa_id', $siswaId)
            ->first();

        if (! $row) {
            return null;
        }

        $item = $this->buildItems(collect([$row]), (int) $guru->user_id)->first();

        $presensi = DB::table('presensi')
            ->where('penempatan_id', $row->penempatan_id)
            ->selectRaw('status_harian, COUNT(*) as total')
            ->groupBy('status_harian')
            ->pluck('total', 'status_harian');

        $jurnal = DB::table('jurnal')
            ->where('penempatan_id', $row->penempatan_id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return $item + [
            'jenis_kelamin' => $row->jenis_kelamin,
            'no_hp' => $row->no_hp,
            'email_sekolah' => $row->email_sekolah,
            'alamat' => $row->alamat_siswa,
            'nama_orang_tua' => $row->nama_orang_tua,
            'no_hp_orang_tua' => $row->no_hp_orang_tua,
            'instansi_alamat' => $row->instansi_alamat,
            'instansi_telepon' => $row->instansi_telepon,
            'catatan_penempatan' => $row->catatan_penempatan,
            'pic' => $row->pic_nama === null ? null : [
                'nama' => $row->pic_nama,
                'jabatan' => $row->pic_jabatan,
                'no_hp' => $row->pic_no_hp,
            ],
            'ringkasan_presensi' => [
                'hadir' => (int) ($presensi['hadir'] ?? 0),
                'izin' => (int) ($presensi['izin'] ?? 0),
                'sakit' => (int) ($presensi['sakit'] ?? 0),
                'alpha' => (int) ($presensi['alpha'] ?? 0),
                'belum_lengkap' => (int) ($presensi['belum_lengkap'] ?? 0),
                'total_sesi' => $item['total_sesi'],
            ],
            'ringkasan_jurnal' => [
                'total' => (int) $jurnal->sum(),
                'menunggu' => (int) ($jurnal['menunggu'] ?? 0),
                'disetujui' => (int) ($jurnal['disetujui'] ?? 0),
                'revisi' => (int) ($jurnal['revisi'] ?? 0),
            ],
        ];
    }

    private function placementQuery(int $guruId): Builder
    {
        return DB::table('penempatan_pkl as pp')
            ->join('siswa as s', 's.id', '=', 'pp.siswa_id')
            ->join('instansi as i', 'i.id', '=', 'pp.instansi_id')
            ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
            ->leftJoin('pic_instansi as pic', 'pic.id', '=', 'pp.pic_instansi_id')
            ->where('pp.guru_id', $guruId)
            ->whereIn('pp.status', self::STATUS_PENEMPATAN)
            ->orderByDesc('pp.tanggal_mulai')
            ->orderByDesc('pp.id')
            ->select([
                'pp.id as penempatan_id',
                'pp.siswa_id',
                'pp.instansi_id',
                'pp.status as status_penempatan',
                'pp.tanggal_mulai',
                'pp.tanggal_selesai',
                'pp.catatan as catatan_penempatan',
                's.nisn',
                's.nis',
                's.nama_lengkap',
                's.jenis_kelamin',
                's.no_hp',
                's.email_sekolah',
                's.alamat as alamat_siswa',
                's.nama_orang_tua',
                's.no_hp_orang_tua',
                'k.nama as kelas_nama',
                'i.nama as instansi_nama',
                'i.alamat as instansi_alamat',
                'i.telepon as instansi_telepon',
                'pic.nama as pic_nama',
                'pic.jabatan as pic_jabatan',
                'pic.no_hp as pic_no_hp',
            ]);
    }

    /**
     * Hitung status kehadiran hari ini + persentase kehadiran untuk semua
     * penempatan sekaligus (tanpa N+1).
     *
     * @param  Collection<int, object>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    private function buildItems(Collection $rows, int $guruUserId): Collection
    {
        if ($rows->isEmpty()) {
            return collect();
        }

        $today = CarbonImmutable::now(self::TZ)->toDateString();
        $penempatanIds = $rows->pluck('penempatan_id')->all();
        $instansiIds = $rows->pluck('instansi_id')->unique()->values()->all();
        $palingAwal = $rows->map(fn ($r) => $this->tanggal($r->tanggal_mulai))->min();

        $presensiHariIni = DB::table('presensi')
            ->whereIn('penempatan_id', $penempatanIds)
            ->whereDate('tanggal', $today)
            ->get(['penempatan_id', 'status_harian'])
            ->groupBy('penempatan_id');

        $izinHariIni = DB::table('pengajuan_izin')
            ->whereIn('penempatan_id', $penempatanIds)
            ->where('status', 'disetujui')
            ->whereDate('tanggal_mulai', '<=', $today)
            ->whereDate('tanggal_selesai', '>=', $today)
            ->pluck('penempatan_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        // Sesi presensi yang dibuat Guru ini untuk instansi bimbingannya.
        $sesiPerInstansi = DB::table('sesi_presensi as sp')
            ->join('pengaturan_presensi as pg', 'pg.id', '=', 'sp.pengaturan_id')
            ->whereIn('pg.instansi_id', $instansiIds)
            ->where('pg.aktif', true)
            ->where('sp.dibuat_oleh', $guruUserId)
            ->whereDate('sp.tanggal', '>=', $palingAwal)
            ->whereDate('sp.tanggal', '<=', $today)
            ->get(['pg.instansi_id', 'sp.tanggal', 'sp.status'])
            ->groupBy('instansi_id');

        $jumlahHadir = DB::table('presensi')
            ->whereIn('penempatan_id', $penempatanIds)
            ->where('status_harian', 'hadir')
            ->selectRaw('penempatan_id, COUNT(*) as total')
            ->groupBy('penempatan_id')
            ->pluck('total', 'penempatan_id');

        return $rows->map(function (object $r) use (
            $today, $presensiHariIni, $izinHariIni, $sesiPerInstansi, $jumlahHadir
        ): array {
            $mulai = $this->tanggal($r->tanggal_mulai);
            $selesai = $this->tanggal($r->tanggal_selesai);
            $batasAkhir = min($today, $selesai);

            $sesi = ($sesiPerInstansi->get($r->instansi_id) ?? collect())
                ->filter(function ($s) use ($mulai, $batasAkhir): bool {
                    $t = $this->tanggal($s->tanggal);

                    return $t >= $mulai && $t <= $batasAkhir;
                });
            $totalSesi = $sesi->count();
            $sesiDitutupHariIni = $sesi->contains(
                fn ($s) => $this->tanggal($s->tanggal) === $today && $s->status === 'ditutup'
            );

            $status = $this->statusHariIni(
                aktifHariIni: $r->status_penempatan === 'aktif' && $today >= $mulai && $today <= $selesai,
                catatanHariIni: ($presensiHariIni->get($r->penempatan_id) ?? collect())->pluck('status_harian'),
                punyaIzinDisetujui: in_array((int) $r->penempatan_id, $izinHariIni, true),
                sesiDitutupHariIni: $sesiDitutupHariIni,
            );

            $hadir = (int) ($jumlahHadir[$r->penempatan_id] ?? 0);
            $persen = $totalSesi > 0 ? (int) min(100, round($hadir / $totalSesi * 100)) : 0;

            return [
                'id' => (int) $r->siswa_id,
                'siswa_id' => (int) $r->siswa_id,
                'penempatan_id' => (int) $r->penempatan_id,
                'nama' => (string) $r->nama_lengkap,
                'nis' => (string) ($r->nis ?: $r->nisn),
                'nisn' => (string) $r->nisn,
                'kelas' => (string) ($r->kelas_nama ?? '-'),
                'instansi' => (string) $r->instansi_nama,
                'status' => $status,
                'pembimbing_industri' => (string) ($r->pic_nama ?? '-'),
                'periode_pkl' => $this->formatTanggal($mulai).' – '.$this->formatTanggal($selesai),
                'tanggal_mulai' => $mulai,
                'tanggal_selesai' => $selesai,
                'status_penempatan' => (string) $r->status_penempatan,
                'persentase_kehadiran' => $persen,
                'total_sesi' => $totalSesi,
                'jumlah_hadir' => $hadir,
            ];
        })->values();
    }

    /**
     * Aturan: presensi hari ini (hadir > izin/sakit > alpha) -> izin disetujui
     * -> sesi hari ini sudah ditutup tanpa presensi = alpha -> belum_ada.
     */
    private function statusHariIni(
        bool $aktifHariIni,
        Collection $catatanHariIni,
        bool $punyaIzinDisetujui,
        bool $sesiDitutupHariIni,
    ): string {
        if (! $aktifHariIni) {
            return 'belum_ada';
        }
        if ($catatanHariIni->contains('hadir')) {
            return 'hadir';
        }
        if ($catatanHariIni->contains('izin') || $catatanHariIni->contains('sakit')) {
            return 'izin';
        }
        if ($catatanHariIni->contains('alpha')) {
            return 'alpha';
        }
        if ($punyaIzinDisetujui) {
            return 'izin';
        }
        if ($sesiDitutupHariIni) {
            return 'alpha';
        }

        return 'belum_ada';
    }

    private function tanggal(mixed $value): string
    {
        return substr((string) $value, 0, 10);
    }

    private function formatTanggal(string $ymd): string
    {
        $d = CarbonImmutable::createFromFormat('!Y-m-d', $ymd, self::TZ);

        return $d->day.' '.self::BULAN[$d->month - 1].' '.$d->year;
    }
}