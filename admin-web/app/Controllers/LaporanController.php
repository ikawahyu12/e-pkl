<?php
// LaporanController.php - handler unduh, cetak, dan arsip laporan (TERSAMBUNG KE API)
// Dipanggil lewat: ?page=laporan-export
//   &type=presensi|jurnal &format=excel|pdf &periode=pN &jurusan=kode  -> buat laporan di API + arsipkan
//   &id=123                                                            -> unduh ulang berkas dari arsip
//
// Berkasnya dibuat dan diarsipkan oleh API (tabel laporan_arsip); controller ini
// hanya meneruskan permintaan lalu mengirim berkas hasilnya ke browser.
require_once dirname(__DIR__) . '/Models/LaporanData.php';

$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

/** Tampilkan halaman pesan singkat lalu berhenti. */
$laporanGagal = static function (int $status, string $pesan) use ($e): void {
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><title>Laporan | E-PKL</title></head>'
        . '<body style="font-family:Arial,sans-serif;margin:48px;color:#0b1c30">'
        . '<h3>Laporan tidak dapat diproses</h3><p>' . $e($pesan) . '</p>'
        . '<p><a href="?page=laporan">&larr; Kembali ke Laporan</a></p></body></html>';
    exit;
};

$api   = LaporanData::api();
$token = LaporanData::token();
$id    = (string) ($_GET['id'] ?? '');

try {
    if ($id !== '') {
        // A. Unduh ulang berkas dari ARSIP
        if (!ctype_digit($id)) {
            $laporanGagal(404, 'Berkas arsip tidak ditemukan.');
        }

        $res = $api->laporanUnduh($token, (int) $id);
    } else {
        // B. Buat laporan baru (periode + jurusan terpilih) -> API menyimpannya ke arsip
        $jenis      = ($_GET['type'] ?? 'presensi') === 'jurnal' ? 'jurnal' : 'presensi';
        $format     = ($_GET['format'] ?? 'excel') === 'pdf' ? 'pdf' : 'xls';
        $periodeKey = LaporanData::normalizePeriode($_GET['periode'] ?? '');
        $jurusanKey = LaporanData::normalizeJurusan($_GET['jurusan'] ?? '');

        $payload   = ['jenis' => $jenis, 'format' => $format];
        $periodeId = LaporanData::periodeId($periodeKey);
        $kode      = LaporanData::jurusanKode($jurusanKey);

        if ($periodeId !== null) {
            $payload['periode_id'] = $periodeId;
        }
        if ($kode !== null) {
            $payload['jurusan'] = $kode;
        }

        $res = $api->laporanEkspor($token, $payload);
    }
} catch (Throwable $ex) {
    $laporanGagal(502, 'Tidak dapat terhubung ke server API. Pastikan API sudah berjalan.');
}

$status = (int) $res['status'];

if ($status === 401 || $status === 403) {
    $laporanGagal($status, 'Sesi login berakhir atau Anda tidak memiliki akses. Silakan login ulang.');
}

if ($status === 404) {
    $laporanGagal(404, 'Berkas arsip tidak ditemukan.');
}

if ($status === 422) {
    $json = json_decode($res['body'], true);
    $laporanGagal(422, is_array($json) && isset($json['message']) ? (string) $json['message'] : 'Filter laporan tidak valid.');
}

if ($status < 200 || $status >= 300) {
    $laporanGagal(502, 'Server API mengembalikan kesalahan (status ' . $status . ').');
}

// Teruskan berkas dari API ke browser
$tipe = preg_replace('/[\r\n]+/', ' ', $res['headers']['content-type'] ?? 'application/octet-stream');
header('Content-Type: ' . $tipe);

// Excel diunduh sebagai berkas; "PDF" adalah halaman cetak (HTML) yang dibuka di tab baru
if (stripos($tipe, 'text/html') === false && isset($res['headers']['content-disposition'])) {
    header('Content-Disposition: ' . preg_replace('/[\r\n]+/', ' ', $res['headers']['content-disposition']));
}

header('Cache-Control: max-age=0');
echo $res['body'];
exit;