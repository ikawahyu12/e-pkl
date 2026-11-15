<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Rekapitulasi presensi dan jurnal PKL untuk halaman Laporan Admin.
 *
 * Seluruh angka dihitung di database (agregasi SQL satu kali jalan per laporan),
 * bukan di PHP per siswa, sehingga tidak ada N+1 query. Penempatan berstatus
 * "dibatalkan" tidak ikut dihitung.
 */
class LaporanService
{
    /** Batas pengaman jumlah baris satu laporan. */
    public const BATAS_BARIS = 5000;

    /**
     * Pilihan filter: seluruh periode PKL dan jurusan.
     *
     * @return array{periode: array<int, array<string, mixed>>, jurusan: array<int, array<string, mixed>>, periode_default_id: int|null}
     */
    public function filter(): array
    {
        $periode = array_map(
            static fn (object $row): array => [
                'id' => (int) $row->id,
                'nama' => $row->nama,
                'tanggal_mulai' => $row->tanggal_mulai,
                'tanggal_selesai' => $row->tanggal_selesai,
                'status' => $row->status,
            ],
            DB::select(
                'SELECT id, nama, tanggal_mulai, tanggal_selesai, status
                 FROM periode_pkl
                 ORDER BY tanggal_mulai DESC, id DESC'
            )
        );

        $jurusan = array_map(
            static fn (object $row): array => [
                'kode' => strtoupper((string) $row->kode),
                'nama' => $row->nama,
            ],
            DB::select(
                'SELECT kode, nama
                 FROM jurusan
                 WHERE kode IS NOT NULL AND kode <> \'\'
                 ORDER BY kode ASC'
            )
        );

        return [
            'periode' => $periode,
            'jurusan' => $jurusan,
            'periode_default_id' => $this->periodeDefaultId($periode),
        ];
    }

    /**
     * Periode yang dipakai bila filter tidak diisi: periode yang sedang berjalan,
     * kalau tidak ada maka periode terbaru.
     *
     * @param  array<int, array<string, mixed>>|null  $periode
     */
    public function periodeDefaultId(?array $periode = null): ?int
    {
        $periode ??= $this->filter()['periode'];

        foreach ($periode as $item) {
            if ($item['status'] === 'berjalan') {
                return $item['id'];
            }
        }

        return $periode[0]['id'] ?? null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function periode(?int $periodeId): ?array
    {
        $periodeId ??= $this->periodeDefaultId();

        if ($periodeId === null) {
            return null;
        }

        $row = DB::selectOne(
            'SELECT id, nama, tanggal_mulai, tanggal_selesai, status
             FROM periode_pkl
             WHERE id = ?',
            [$periodeId]
        );

        return $row ? [
            'id' => (int) $row->id,
            'nama' => $row->nama,
            'tanggal_mulai' => $row->tanggal_mulai,
            'tanggal_selesai' => $row->tanggal_selesai,
            'status' => $row->status,
        ] : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function jurusan(?string $kode): ?array
    {
        if ($kode === null || $kode === '') {
            return null;
        }

        $row = DB::selectOne(
            'SELECT kode, nama FROM jurusan WHERE kode = ?',
            [strtoupper($kode)]
        );

        return $row ? ['kode' => strtoupper((string) $row->kode), 'nama' => $row->nama] : null;
    }

    /**
     * Rekap presensi per siswa.
     *
     * Persentase kehadiran = hari hadir / seluruh hari presensi yang tercatat
     * (hadir + izin + sakit + alpha + belum lengkap).
     *
     * @return array<string, mixed>
     */
    public function presensi(?int $periodeId, ?string $jurusan): array
    {
        $periode = $this->periode($periodeId);
        $jurusanData = $this->jurusan($jurusan);

        if ($periode === null) {
            return $this->kosong('presensi', null, $jurusanData);
        }

        [$sql, $bindings] = $this->kerangka(
            "COALESCE(SUM(CASE WHEN p.status_harian = 'hadir' THEN 1 ELSE 0 END), 0) AS hadir,
             COALESCE(SUM(CASE WHEN p.status_harian = 'izin' THEN 1 ELSE 0 END), 0) AS izin,
             COALESCE(SUM(CASE WHEN p.status_harian = 'sakit' THEN 1 ELSE 0 END), 0) AS sakit,
             COALESCE(SUM(CASE WHEN p.status_harian = 'alpha' THEN 1 ELSE 0 END), 0) AS alpha,
             COALESCE(SUM(CASE WHEN p.status_harian = 'belum_lengkap' THEN 1 ELSE 0 END), 0) AS belum_lengkap,
             MAX(p.tanggal) AS presensi_terakhir",
            'LEFT JOIN presensi p ON p.penempatan_id = pp.id',
            $periode['id'],
            $jurusanData['kode'] ?? null,
        );

        $hasil = DB::select($sql, $bindings);
        $terpotong = count($hasil) > self::BATAS_BARIS;
        $hasil = array_slice($hasil, 0, self::BATAS_BARIS);

        $rows = [];
        $total = ['hadir' => 0, 'izin' => 0, 'sakit' => 0, 'alpha' => 0, 'belum_lengkap' => 0];
        $terakhir = null;

        foreach ($hasil as $row) {
            $hadir = (int) $row->hadir;
            $izin = (int) $row->izin;
            $sakit = (int) $row->sakit;
            $alpha = (int) $row->alpha;
            $belum = (int) $row->belum_lengkap;
            $hari = $hadir + $izin + $sakit + $alpha + $belum;

            $total['hadir'] += $hadir;
            $total['izin'] += $izin;
            $total['sakit'] += $sakit;
            $total['alpha'] += $alpha;
            $total['belum_lengkap'] += $belum;

            if ($row->presensi_terakhir !== null && ($terakhir === null || $row->presensi_terakhir > $terakhir)) {
                $terakhir = $row->presensi_terakhir;
            }

            $rows[] = [
                'nis' => $row->nis,
                'nama' => $row->nama,
                'kelas' => $row->kelas,
                'instansi' => $row->instansi,
                'hadir' => $hadir,
                'izin' => $izin,
                'sakit' => $sakit,
                'alpha' => $alpha,
                'belum_lengkap' => $belum,
                'persentase' => $hari > 0 ? round($hadir / $hari * 100, 1) : null,
            ];
        }

        $hariTotal = array_sum($total);

        return [
            'periode' => $periode,
            'jurusan' => $jurusanData,
            'ringkasan' => [
                'total_siswa' => count($rows),
                'total_hari_tercatat' => $hariTotal,
                'hadir' => $total['hadir'],
                'izin' => $total['izin'],
                'sakit' => $total['sakit'],
                'alpha' => $total['alpha'],
                'belum_lengkap' => $total['belum_lengkap'],
                'persentase_kehadiran' => $hariTotal > 0 ? round($total['hadir'] / $hariTotal * 100, 1) : null,
                'presensi_terakhir' => $terakhir,
            ],
            'rows' => $rows,
            'terpotong' => $terpotong,
        ];
    }

    /**
     * Rekap jurnal kegiatan per siswa.
     *
     * "Perlu review" = jurnal berstatus menunggu; jurnal revisi dihitung terpisah.
     *
     * @return array<string, mixed>
     */
    public function jurnal(?int $periodeId, ?string $jurusan): array
    {
        $periode = $this->periode($periodeId);
        $jurusanData = $this->jurusan($jurusan);

        if ($periode === null) {
            return $this->kosong('jurnal', null, $jurusanData);
        }

        [$sql, $bindings] = $this->kerangka(
            "COUNT(jr.id) AS masuk,
             COALESCE(SUM(CASE WHEN jr.status = 'disetujui' THEN 1 ELSE 0 END), 0) AS terverifikasi,
             COALESCE(SUM(CASE WHEN jr.status = 'menunggu' THEN 1 ELSE 0 END), 0) AS menunggu,
             COALESCE(SUM(CASE WHEN jr.status = 'revisi' THEN 1 ELSE 0 END), 0) AS revisi",
            'LEFT JOIN jurnal jr ON jr.penempatan_id = pp.id',
            $periode['id'],
            $jurusanData['kode'] ?? null,
        );

        $hasil = DB::select($sql, $bindings);
        $terpotong = count($hasil) > self::BATAS_BARIS;
        $hasil = array_slice($hasil, 0, self::BATAS_BARIS);

        $rows = [];
        $total = ['masuk' => 0, 'terverifikasi' => 0, 'menunggu' => 0, 'revisi' => 0];

        foreach ($hasil as $row) {
            $item = [
                'nis' => $row->nis,
                'nama' => $row->nama,
                'kelas' => $row->kelas,
                'instansi' => $row->instansi,
                'masuk' => (int) $row->masuk,
                'terverifikasi' => (int) $row->terverifikasi,
                'menunggu' => (int) $row->menunggu,
                'revisi' => (int) $row->revisi,
            ];

            foreach ($total as $kunci => $_) {
                $total[$kunci] += $item[$kunci];
            }

            $rows[] = $item;
        }

        return [
            'periode' => $periode,
            'jurusan' => $jurusanData,
            'ringkasan' => [
                'total_siswa' => count($rows),
                'jurnal_masuk' => $total['masuk'],
                'terverifikasi' => $total['terverifikasi'],
                'perlu_review' => $total['menunggu'],
                'revisi' => $total['revisi'],
                'persentase_terverifikasi' => $total['masuk'] > 0
                    ? round($total['terverifikasi'] / $total['masuk'] * 100, 1)
                    : null,
            ],
            'rows' => $rows,
            'terpotong' => $terpotong,
        ];
    }

    /**
     * Ringkasan dua jenis laporan untuk kartu di halaman Laporan.
     *
     * @return array{periode: array<string, mixed>|null, jurusan: array<string, mixed>|null, presensi: array<string, mixed>, jurnal: array<string, mixed>}
     */
    public function ringkasan(?int $periodeId, ?string $jurusan): array
    {
        $presensi = $this->presensi($periodeId, $jurusan);
        $jurnal = $this->jurnal($periodeId, $jurusan);

        return [
            'periode' => $presensi['periode'],
            'jurusan' => $presensi['jurusan'],
            'presensi' => $presensi['ringkasan'],
            'jurnal' => $jurnal['ringkasan'],
        ];
    }

    /**
     * Kerangka query: satu baris per penempatan PKL siswa pada periode terpilih.
     *
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function kerangka(string $agregat, string $joinDetail, int $periodeId, ?string $jurusanKode): array
    {
        $bindings = [$periodeId];
        $filterJurusan = '';

        if ($jurusanKode !== null) {
            $filterJurusan = ' AND j.kode = ?';
            $bindings[] = $jurusanKode;
        }

        $sql = "SELECT s.nis, s.nama_lengkap AS nama, k.nama AS kelas, i.nama AS instansi,
                       {$agregat}
                FROM penempatan_pkl pp
                JOIN siswa s ON s.id = pp.siswa_id
                JOIN instansi i ON i.id = pp.instansi_id
                LEFT JOIN kelas k ON k.id = s.kelas_id
                LEFT JOIN jurusan j ON j.id = k.jurusan_id
                {$joinDetail}
                WHERE pp.periode_id = ?
                  AND pp.status <> 'dibatalkan'{$filterJurusan}
                GROUP BY pp.id, s.id, s.nis, s.nama_lengkap, k.nama, i.nama
                ORDER BY k.nama ASC, s.nama_lengkap ASC
                LIMIT " . (self::BATAS_BARIS + 1);

        return [$sql, $bindings];
    }

    /**
     * @param  array<string, mixed>|null  $periode
     * @param  array<string, mixed>|null  $jurusan
     * @return array<string, mixed>
     */
    private function kosong(string $jenis, ?array $periode, ?array $jurusan): array
    {
        $ringkasan = $jenis === 'presensi'
            ? [
                'total_siswa' => 0, 'total_hari_tercatat' => 0, 'hadir' => 0, 'izin' => 0,
                'sakit' => 0, 'alpha' => 0, 'belum_lengkap' => 0,
                'persentase_kehadiran' => null, 'presensi_terakhir' => null,
            ]
            : [
                'total_siswa' => 0, 'jurnal_masuk' => 0, 'terverifikasi' => 0,
                'perlu_review' => 0, 'revisi' => 0, 'persentase_terverifikasi' => null,
            ];

        return [
            'periode' => $periode,
            'jurusan' => $jurusan,
            'ringkasan' => $ringkasan,
            'rows' => [],
            'terpotong' => false,
        ];
    }
}