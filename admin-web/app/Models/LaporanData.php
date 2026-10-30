<?php
class LaporanData
{
    // ------------------------------------------------------------------
    // Pilihan filter
    // ------------------------------------------------------------------
    public static function periodeOptions(): array
    {
        return [
            'ganjil-2024-2025' => 'Semester Ganjil 2024/2025',
            'genap-2024-2025'  => 'Semester Genap 2024/2025',
            'ganjil-2025-2026' => 'Semester Ganjil 2025/2026',
            'genap-2025-2026'  => 'Semester Genap 2025/2026',
        ];
    }

    public static function jurusanOptions(): array
    {
        return [
            ''    => 'Semua Jurusan',
            'rpl' => 'RPL',
            'tkj' => 'TKJ',
            'dkv' => 'DKV',
        ];
    }

    public static function normalizePeriode($value): string
    {
        $options = self::periodeOptions();
        $value   = (string) $value;
        if (isset($options[$value])) {
            return $value;
        }
        $keys = array_keys($options);
        return $keys[0];
    }

    public static function normalizeJurusan($value): string
    {
        $value = (string) $value;
        return array_key_exists($value, self::jurusanOptions()) ? $value : '';
    }

    public static function periodeLabel(string $periode): string
    {
        $options = self::periodeOptions();
        return $options[$periode] ?? $periode;
    }

    public static function jurusanLabel(string $jurusan): string
    {
        $options = self::jurusanOptions();
        return $options[$jurusan] ?? 'Semua Jurusan';
    }

    // ------------------------------------------------------------------
    // Data siswa dasar (CONTOH)
    // ------------------------------------------------------------------
    private static function siswa(): array
    {
        return [
            ['nis' => '22231001', 'nama' => 'Ahmad Rizky',     'kelas' => 'XII RPL 1', 'jurusan' => 'rpl', 'mitra' => 'PT Telkom Indonesia'],
            ['nis' => '22231002', 'nama' => 'Budi Santoso',    'kelas' => 'XII RPL 1', 'jurusan' => 'rpl', 'mitra' => 'PT Telkom Indonesia'],
            ['nis' => '22231005', 'nama' => 'Citra Dewi',      'kelas' => 'XII RPL 2', 'jurusan' => 'rpl', 'mitra' => 'CV Digital Creative'],
            ['nis' => '22231007', 'nama' => 'Fajar Nugroho',   'kelas' => 'XII RPL 2', 'jurusan' => 'rpl', 'mitra' => 'PT Informatika Solusi Nusantara'],

            ['nis' => '22231012', 'nama' => 'Dwi Prasetyo',    'kelas' => 'XII TKJ 1', 'jurusan' => 'tkj', 'mitra' => 'PT Network Solution'],
            ['nis' => '22231013', 'nama' => 'Muhammad Faisal', 'kelas' => 'XII TKJ 1', 'jurusan' => 'tkj', 'mitra' => 'Lintasarta Datacenter'],
            ['nis' => '22231015', 'nama' => 'Dimas Wicaksono', 'kelas' => 'XII TKJ 2', 'jurusan' => 'tkj', 'mitra' => 'Biznet Networks'],
            ['nis' => '22231016', 'nama' => 'Rani Oktaviani',  'kelas' => 'XII TKJ 2', 'jurusan' => 'tkj', 'mitra' => 'PT Network Solution'],

            ['nis' => '22231018', 'nama' => 'Eka Putri',       'kelas' => 'XII DKV 1', 'jurusan' => 'dkv', 'mitra' => 'Studio Animasi 3D'],
            ['nis' => '22231019', 'nama' => 'Siti Nurhaliza',  'kelas' => 'XII DKV 2', 'jurusan' => 'dkv', 'mitra' => 'Studio Animasi Semesta'],
            ['nis' => '22231021', 'nama' => 'Sarah Safitri',   'kelas' => 'XII DKV 1', 'jurusan' => 'dkv', 'mitra' => 'PT Kreatif Media Kreasi'],
            ['nis' => '22231023', 'nama' => 'Alya Maharani',   'kelas' => 'XII DKV 2', 'jurusan' => 'dkv', 'mitra' => 'Studio Animasi 3D'],
        ];
    }

    // ------------------------------------------------------------------
    // Laporan: hasil berbeda untuk tiap periode & jurusan (CONTOH)
    // ------------------------------------------------------------------
    public static function report(string $type, string $periode, string $jurusan): array
    {
        $type      = $type === 'jurnal' ? 'jurnal' : 'presensi';
        $rows      = [];
        $sumPct    = 0.0;
        $sumMasuk  = 0;
        $sumVerif  = 0;
        $sumReview = 0;

        foreach (self::siswa() as $s) {
            if ($jurusan !== '' && $s['jurusan'] !== $jurusan) {
                continue;
            }

            // Angka contoh yang konsisten untuk kombinasi siswa + periode
            $seed  = crc32($s['nis'] . '|' . $periode) & 0x7fffffff;
            $total = 40 + ($seed % 6);
            $izin  = ($seed >> 3) % 3;
            $sakit = ($seed >> 6) % 3;
            $alpa  = (($seed >> 9) % 5) === 0 ? 1 + (($seed >> 12) % 2) : 0;
            $hadir = $total - $izin - $sakit - $alpa;
            $pct   = round($hadir / $total * 100, 1);

            if ($type === 'presensi') {
                $sumPct += $pct;
                $rows[] = [
                    $s['nis'], $s['nama'], $s['kelas'], $s['mitra'],
                    $hadir, $izin, $sakit, $alpa,
                    number_format($pct, 1, '.', '') . '%',
                ];
            } else {
                $masuk  = $hadir;
                $review = ($seed >> 15) % 4;
                $verif  = $masuk - $review;
                $sumMasuk  += $masuk;
                $sumVerif  += $verif;
                $sumReview += $review;
                $rows[] = [
                    $s['nis'], $s['nama'], $s['kelas'], $s['mitra'],
                    $masuk, $verif, $review,
                ];
            }
        }

        $count = count($rows);

        if ($type === 'presensi') {
            $avg = $count > 0 ? $sumPct / $count : 0;
            return [
                'type'          => 'presensi',
                'title'         => 'Rekap Presensi Siswa PKL',
                'periode_label' => self::periodeLabel($periode),
                'jurusan_label' => self::jurusanLabel($jurusan),
                'columns'       => ['NIS', 'Nama Siswa', 'Kelas', 'Instansi Mitra', 'Hadir', 'Izin', 'Sakit', 'Alpa', 'Persentase'],
                'rows'          => $rows,
                'summary'       => [
                    ['Total Siswa', $count],
                    ['Rata-rata Kehadiran', number_format($avg, 1, '.', '') . '%'],
                ],
            ];
        }

        $pctVerif = $sumMasuk > 0 ? $sumVerif / $sumMasuk * 100 : 0;
        return [
            'type'          => 'jurnal',
            'title'         => 'Rekap Jurnal Kegiatan Siswa PKL',
            'periode_label' => self::periodeLabel($periode),
            'jurusan_label' => self::jurusanLabel($jurusan),
            'columns'       => ['NIS', 'Nama Siswa', 'Kelas', 'Instansi Mitra', 'Jurnal Masuk', 'Terverifikasi', 'Perlu Review'],
            'rows'          => $rows,
            'summary'       => [
                ['Total Siswa', $count],
                ['Total Jurnal Masuk', $sumMasuk],
                ['Terverifikasi', number_format($pctVerif, 1, '.', '') . '%'],
                ['Perlu Review', $sumReview],
            ],
        ];
    }

    // ------------------------------------------------------------------
    // Nama berkas & keterangan
    // ------------------------------------------------------------------
    public static function fileName(string $type, string $periode, string $jurusan, string $ext): string
    {
        $parts = explode('-', $periode); // [ganjil, 2024, 2025]
        $smt   = ucfirst($parts[0] ?? 'Semester') . '_' . ($parts[1] ?? '') . '-' . ($parts[2] ?? '');
        $jur   = $jurusan === '' ? 'SemuaJurusan' : strtoupper($jurusan);
        $kind  = $type === 'jurnal' ? 'Jurnal' : 'Presensi';
        return 'Rekap_' . $kind . '_' . $jur . '_' . $smt . '.' . $ext;
    }

    public static function metaText(string $type, string $jurusan): string
    {
        $kind = $type === 'jurnal' ? 'Logbook Harian' : 'Presensi Siswa';
        $jur  = $jurusan === '' ? 'Semua Jurusan' : 'Jurusan ' . strtoupper($jurusan);
        return $kind . ' • ' . $jur;
    }

    public static function formatTanggal(int $ts): string
    {
        $bulan = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $d = new DateTime('@' . $ts);
        $d->setTimezone(new DateTimeZone('Asia/Jakarta'));
        return $d->format('j') . ' ' . $bulan[(int) $d->format('n')] . ' ' . $d->format('Y, H:i');
    }

    public static function formatUkuran(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024) . ' KB';
        }
        return number_format($bytes / 1048576, 1, '.', '') . ' MB';
    }

    // ------------------------------------------------------------------
    // Arsip berkas (disimpan di storage/laporan + index.json)
    // ------------------------------------------------------------------
        // ------------------------------------------------------------------
    // Riwayat CONTOH untuk setiap periode & jurusan (TAHAP FRONTEND).
    // Hapus method ini saat data resmi SMK sudah masuk database.
    // ------------------------------------------------------------------
    public static function sampleArchive(): array
    {
        // Tanggal generate terakhir tiap periode (contoh)
        $akhir = [
            'ganjil-2024-2025' => '2024-12-20 14:30:00',
            'genap-2024-2025'  => '2025-06-20 10:15:00',
            'ganjil-2025-2026' => '2025-12-19 13:40:00',
            'genap-2025-2026'  => '2026-06-19 09:50:00',
        ];

        $zone = new DateTimeZone('Asia/Jakarta');
        $out  = [];

        foreach (self::periodeOptions() as $periode => $label) {
            $base = (new DateTime($akhir[$periode] ?? '2025-01-01 08:00:00', $zone))->getTimestamp();

            // Per periode: Semua Jurusan + masing-masing jurusan, presensi (xls) & jurnal (pdf)
            $set = [['all', 'presensi', 'xls'], ['all', 'jurnal', 'pdf']];
            foreach (['rpl', 'tkj', 'dkv'] as $j) {
                $set[] = [$j, 'presensi', 'xls'];
                $set[] = [$j, 'jurnal', 'pdf'];
            }

            foreach ($set as $n => $item) {
                list($jur, $type, $fmt) = $item;

                $seed   = crc32($periode . '|' . $jur . '|' . $type) & 0x7fffffff;
                $jurKey = $jur === 'all' ? '' : $jur;

                $out[] = [
                    'id'      => null,
                    'file'    => self::fileName($type, $periode, $jurKey, $fmt),
                    'meta'    => self::metaText($type, $jurKey),
                    'ts'      => $base - ($n * 86400) - ($seed % 7200),
                    'format'  => $fmt,
                    'size'    => ($fmt === 'pdf' ? 1200000 : 400000) + ($seed % 1400000),
                    'jurusan' => $jur,
                    'periode' => $periode,
                    'type'    => $type,
                ];
            }
        }

        usort($out, static fn ($a, $b) => $b['ts'] <=> $a['ts']);
        return $out;
    }
    public static function archiveDir(): string
    {
        $dir = dirname(__DIR__, 2) . '/storage/laporan';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        // Cegah akses langsung lewat URL (berkas hanya lewat handler PHP)
        $guard = $dir . '/.htaccess';
        if (is_dir($dir) && !is_file($guard)) {
            @file_put_contents($guard, "Require all denied\nDeny from all\n");
        }
        return $dir;
    }

    private static function indexPath(): string
    {
        return self::archiveDir() . '/index.json';
    }

    /** Daftar arsip, terbaru dulu. */
    public static function archiveList(): array
    {
        $path = self::indexPath();
        if (!is_file($path)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($path), true);
        if (!is_array($data)) {
            return [];
        }
        usort($data, static fn ($a, $b) => ($b['ts'] ?? 0) <=> ($a['ts'] ?? 0));
        return $data;
    }

    public static function archiveFind(string $id): ?array
    {
        if (!preg_match('/^[a-f0-9]{12}$/', $id)) {
            return null;
        }
        foreach (self::archiveList() as $entry) {
            if (($entry['id'] ?? '') === $id) {
                return $entry;
            }
        }
        return null;
    }

    public static function archivePath(array $entry): string
    {
        return self::archiveDir() . '/' . basename((string) $entry['stored']);
    }

    /**
     * Simpan berkas ke arsip dan catat di index.
     * @return array entri arsip yang baru dibuat
     */
    public static function archiveAdd(string $content, string $displayName, string $storedExt, array $meta): array
    {
        $id     = bin2hex(random_bytes(6));
        $stored = $id . '_' . preg_replace('/\.[^.]+$/', '', $displayName) . '.' . $storedExt;

        file_put_contents(self::archiveDir() . '/' . $stored, $content, LOCK_EX);

        $entry = [
            'id'      => $id,
            'name'    => $displayName,
            'stored'  => $stored,
            'type'    => $meta['type'],
            'format'  => $meta['format'],   // xls | pdf
            'periode' => $meta['periode'],
            'jurusan' => $meta['jurusan'] === '' ? 'all' : $meta['jurusan'],
            'meta'    => $meta['meta'],
            'ts'      => time(),
            'size'    => strlen($content),
        ];

        $list   = self::archiveList();
        $list[] = $entry;
        file_put_contents(self::indexPath(), json_encode($list, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

        return $entry;
    }
}
