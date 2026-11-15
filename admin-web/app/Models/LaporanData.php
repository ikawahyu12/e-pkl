<?php
// ==========================================================================
// LaporanData.php - penghubung halaman Laporan dengan API Laravel
// Menggantikan data sementara (dummy) pada tahap frontend.
//
// Semua data (periode, jurusan, ringkasan, riwayat arsip) diambil dari
// endpoint /api/v1/admin/laporan/* memakai token login admin di sesi.
//
// Catatan kunci filter di URL (supaya <select> dan JS tetap jalan):
//   periode : "p" + id periode   (contoh: p3)
//   jurusan : kode jurusan huruf kecil (contoh: rpl), kosong = semua jurusan
// ==========================================================================

require_once dirname(__DIR__) . '/Support/AdminApiClient.php';

final class LaporanData
{
    private const BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    private static ?AdminApiClient $client = null;

    /** @var array<string, mixed>|null */
    private static ?array $filter = null;

    private static string $error = '';

    // ----------------------------------------------------------------------
    // Akses API
    // ----------------------------------------------------------------------

    public static function api(): AdminApiClient
    {
        if (self::$client === null) {
            self::$client = new AdminApiClient();
        }

        return self::$client;
    }

    public static function token(): string
    {
        return (string) ($_SESSION['admin_token'] ?? '');
    }

    /** Pesan kesalahan terakhir dari API (kosong bila semua lancar). */
    public static function lastError(): string
    {
        return self::$error;
    }

    /**
     * Jalankan satu panggilan API lalu ambil isi "data"-nya.
     * Mengembalikan null (dan mengisi lastError) bila gagal.
     *
     * @return array<string, mixed>|null
     */
    private static function ambilData(callable $panggil): ?array
    {
        try {
            $res = $panggil();
        } catch (Throwable $ex) {
            self::$error = 'Tidak dapat terhubung ke server API.';

            return null;
        }

        $status = (int) ($res['status'] ?? 0);
        $body = is_array($res['body'] ?? null) ? $res['body'] : [];

        if ($status === 200 && is_array($body['data'] ?? null)) {
            return $body['data'];
        }

        if ($status === 401 || $status === 403) {
            self::$error = 'Sesi login berakhir atau tidak memiliki akses. Silakan login ulang.';
        } else {
            self::$error = (string) ($body['message'] ?? ('Server API mengembalikan status ' . $status . '.'));
        }

        return null;
    }

    // ----------------------------------------------------------------------
    // Filter (periode & jurusan)
    // ----------------------------------------------------------------------

    /**
     * @return array{periode: array<int, array<string, mixed>>, jurusan: array<int, array<string, mixed>>, periode_default_id: int|null}
     */
    public static function filter(): array
    {
        if (self::$filter !== null) {
            return self::$filter;
        }

        self::$filter = ['periode' => [], 'jurusan' => [], 'periode_default_id' => null];

        $data = self::ambilData(fn () => self::api()->laporanFilter(self::token()));

        if ($data !== null) {
            self::$filter = [
                'periode' => is_array($data['periode'] ?? null) ? $data['periode'] : [],
                'jurusan' => is_array($data['jurusan'] ?? null) ? $data['jurusan'] : [],
                'periode_default_id' => isset($data['periode_default_id']) ? (int) $data['periode_default_id'] : null,
            ];
        }

        return self::$filter;
    }

    /** @return array<string, string> kunci "pN" => nama periode */
    public static function periodeOptions(): array
    {
        $opsi = [];

        foreach (self::filter()['periode'] as $periode) {
            $opsi['p' . (int) $periode['id']] = (string) $periode['nama'];
        }

        return $opsi !== [] ? $opsi : ['' => 'Belum ada periode PKL'];
    }

    /** @return array<string, string> kunci jurusan (huruf kecil) => label; '' = semua */
    public static function jurusanOptions(): array
    {
        $opsi = ['' => 'Semua Jurusan'];

        foreach (self::filter()['jurusan'] as $jurusan) {
            $opsi[self::jurusanKey((string) $jurusan['kode'])] = $jurusan['nama'] . ' (' . $jurusan['kode'] . ')';
        }

        return $opsi;
    }

    public static function normalizePeriode($mentah): string
    {
        $mentah = is_string($mentah) ? trim($mentah) : '';

        $opsi = self::periodeOptions();

        if ($mentah !== '' && isset($opsi[$mentah])) {
            return $mentah;
        }

        $bawaan = self::filter()['periode_default_id'];

        return $bawaan !== null ? 'p' . $bawaan : '';
    }

    public static function normalizeJurusan($mentah): string
    {
        $mentah = is_string($mentah) ? strtolower(trim($mentah)) : '';

        $opsi = self::jurusanOptions();

        return ($mentah !== '' && isset($opsi[$mentah])) ? $mentah : '';
    }

    /** "p3" -> 3 (null bila tidak valid) */
    public static function periodeId(string $kunci): ?int
    {
        return preg_match('/^p(\d+)$/', $kunci, $cocok) === 1 ? (int) $cocok[1] : null;
    }

    /** "rpl" -> "RPL" (kode asli untuk API); null bila semua jurusan */
    public static function jurusanKode(string $kunci): ?string
    {
        if ($kunci === '') {
            return null;
        }

        foreach (self::filter()['jurusan'] as $jurusan) {
            if (self::jurusanKey((string) $jurusan['kode']) === $kunci) {
                return (string) $jurusan['kode'];
            }
        }

        return null;
    }

    private static function jurusanKey(string $kode): string
    {
        $kunci = preg_replace('/[^a-z0-9_-]/', '-', strtolower($kode)) ?? '';

        // Kunci angka murni akan diubah PHP menjadi integer; beri awalan agar tetap string
        return ctype_digit($kunci) ? 'j' . $kunci : $kunci;
    }

    // ----------------------------------------------------------------------
    // Ringkasan kartu & riwayat arsip
    // ----------------------------------------------------------------------

    /**
     * Angka ringkas untuk dua kartu laporan.
     *
     * @return array{presensi: array<string, mixed>, jurnal: array<string, mixed>}
     */
    public static function ringkasan(string $periodeKey, string $jurusanKey): array
    {
        $hasil = [
            'presensi' => ['total_siswa' => 0, 'persentase_kehadiran' => null, 'presensi_terakhir' => null],
            'jurnal' => ['jurnal_masuk' => 0, 'persentase_terverifikasi' => null, 'perlu_review' => 0],
        ];

        $periodeId = self::periodeId($periodeKey);

        if ($periodeId === null) {
            return $hasil;
        }

        $query = ['periode_id' => $periodeId, 'jurusan' => self::jurusanKode($jurusanKey)];
        $data = self::ambilData(fn () => self::api()->laporanRingkasan(self::token(), $query));

        if ($data === null) {
            return $hasil;
        }

        foreach (['presensi', 'jurnal'] as $jenis) {
            if (is_array($data[$jenis] ?? null)) {
                $hasil[$jenis] = array_merge($hasil[$jenis], $data[$jenis]);
            }
        }

        return $hasil;
    }

    /**
     * Riwayat unduhan untuk periode terpilih, dalam bentuk yang dipakai view.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function archiveList(string $periodeKey, string $jurusanKey = ''): array
    {
        $periodeId = self::periodeId($periodeKey);

        if ($periodeId === null) {
            return [];
        }

        $data = self::ambilData(fn () => self::api()->laporanArsip(self::token(), ['periode_id' => $periodeId]));
        $baris = [];

        foreach ($data['arsip'] ?? [] as $arsip) {
            $baris[] = [
                'id' => (string) $arsip['id'],
                'name' => (string) $arsip['nama_berkas'],
                'meta' => (string) ($arsip['keterangan'] ?? ''),
                'ts' => (int) strtotime((string) $arsip['dibuat_pada']),
                'format' => (string) $arsip['format'],
                'size' => (int) $arsip['ukuran'],
                'jurusan' => $arsip['jurusan'] === null ? 'all' : self::jurusanKey((string) $arsip['jurusan']),
                'periode' => 'p' . (int) $arsip['periode_id'],
                'type' => (string) $arsip['jenis'],
            ];
        }

        return $baris;
    }

    // ----------------------------------------------------------------------
    // Format tampilan
    // ----------------------------------------------------------------------

    /** 1793842530 -> "5 Nov 2026, 09:15" (WIB) */
    public static function formatTanggal(int $ts): string
    {
        $waktu = (new DateTimeImmutable('@' . $ts))->setTimezone(new DateTimeZone('Asia/Jakarta'));

        return $waktu->format('j') . ' ' . self::BULAN[(int) $waktu->format('n') - 1] . ' ' . $waktu->format('Y, H:i');
    }

    /** "2026-11-05" -> "5 Nov 2026" */
    public static function formatTanggalSingkat(string $tanggal): string
    {
        $ts = strtotime($tanggal);

        if ($ts === false) {
            return $tanggal;
        }

        return date('j', $ts) . ' ' . self::BULAN[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts);
    }

    public static function formatUkuran(int $byte): string
    {
        if ($byte < 1024) {
            return $byte . ' B';
        }

        if ($byte < 1048576) {
            return number_format($byte / 1024, 1, ',', '.') . ' KB';
        }

        return number_format($byte / 1048576, 1, ',', '.') . ' MB';
    }
}