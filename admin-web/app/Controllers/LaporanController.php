<?php
// LaporanController.php - handler unduh & cetak laporan
// Dipanggil lewat: ?page=laporan-export
//   &type=presensi|jurnal  &format=excel|pdf     -> Unduh Excel / Cetak PDF
//   &file=NamaBerkas.ext                         -> Unduh Berkas (riwayat)

$type     = ($_GET['type'] ?? 'presensi') === 'jurnal' ? 'jurnal' : 'presensi';
$format   = ($_GET['format'] ?? 'excel') === 'pdf' ? 'pdf' : 'excel';
$filename = $_GET['file'] ?? '';

$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

// ---------------------------------------------------------------------------
// Data dummy (ganti dengan query database nanti)
// ---------------------------------------------------------------------------
$datasets = [
    'presensi' => [
        'title'   => 'Rekap Presensi Siswa PKL',
        'columns' => ['NIS', 'Nama Siswa', 'Kelas', 'Instansi Mitra', 'Hadir', 'Izin', 'Sakit', 'Alpa', 'Persentase'],
        'rows'    => [
            ['22231001', 'Ahmad Rizky',  'XII RPL 1', 'PT Telkom Indonesia', 42, 1, 0, 0, '97.6%'],
            ['22231002', 'Budi Santoso', 'XII RPL 1', 'PT Telkom Indonesia', 43, 0, 0, 0, '100%'],
            ['22231005', 'Citra Dewi',   'XII RPL 2', 'CV Digital Creative', 40, 2, 1, 0, '93.0%'],
            ['22231012', 'Dwi Prasetyo', 'XII TKJ 1', 'PT Network Solution', 38, 1, 2, 2, '88.3%'],
            ['22231018', 'Eka Putri',    'XII DKV 1', 'Studio Animasi 3D',   43, 0, 0, 0, '100%'],
        ],
    ],
    'jurnal' => [
        'title'   => 'Rekap Jurnal Kegiatan Siswa PKL',
        'columns' => ['NIS', 'Nama Siswa', 'Kelas', 'Instansi Mitra', 'Jurnal Masuk', 'Terverifikasi', 'Perlu Review'],
        'rows'    => [
            ['22231001', 'Ahmad Rizky',  'XII RPL 1', 'PT Telkom Indonesia', 43, 43, 0],
            ['22231002', 'Budi Santoso', 'XII RPL 1', 'PT Telkom Indonesia', 43, 41, 2],
            ['22231005', 'Citra Dewi',   'XII RPL 2', 'CV Digital Creative', 42, 40, 2],
            ['22231012', 'Dwi Prasetyo', 'XII TKJ 1', 'PT Network Solution', 39, 35, 4],
            ['22231018', 'Eka Putri',    'XII DKV 1', 'Studio Animasi 3D',   43, 43, 0],
        ],
    ],
];

// Tabel HTML sederhana (dipakai untuk Excel & halaman cetak)
$renderTable = static function (array $data) use ($e): string {
    $html = '<table border="1" cellspacing="0" cellpadding="6"><thead><tr>';
    foreach ($data['columns'] as $col) {
        $html .= '<th style="background:#eff4ff;text-align:left">' . $e($col) . '</th>';
    }
    $html .= '</tr></thead><tbody>';
    foreach ($data['rows'] as $row) {
        $html .= '<tr>';
        foreach ($row as $cell) {
            // Sel sebagai teks agar angka nol di depan (NIS) tidak hilang di Excel
            $html .= '<td style="mso-number-format:\'\\@\'">' . $e($cell) . '</td>';
        }
        $html .= '</tr>';
    }
    return $html . '</tbody></table>';
};

// 1. Unduh Berkas dari Riwayat Unduhan --------------------------------------
if ($filename !== '') {
    $clean = basename($filename);
    $clean = preg_replace('/[^A-Za-z0-9._-]/', '_', $clean);

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $clean . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');

    echo 'Isi dokumen rekapitulasi: ' . $clean; // TODO: ganti dengan berkas asli dari storage/
    exit;
}

$data = $datasets[$type];

// 2. Unduh Excel -------------------------------------------------------------
if ($format === 'excel') {
    $name = ($type === 'jurnal' ? 'Rekap_Jurnal_Kegiatan_Siswa' : 'Rekap_Presensi_Siswa_PKL') . '.xls';

    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    header('Cache-Control: max-age=0');

    // Tabel HTML dibuka Excel sebagai spreadsheet. Untuk .xlsx asli gunakan PhpSpreadsheet.
    echo "\xEF\xBB\xBF<html><head><meta charset=\"utf-8\"></head><body>"
        . '<h3>' . $e($data['title']) . '</h3>' . $renderTable($data) . '</body></html>';
    exit;
}

// 3. Cetak PDF (halaman cetak -> "Simpan sebagai PDF" di dialog print) --------
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title><?= $e($data['title']) ?> | E-PKL</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; color: #0b1c30; margin: 32px; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        p  { font-size: 12px; color: #5f6b7a; margin: 0 0 18px; }
        table { border-collapse: collapse; width: 100%; font-size: 12px; }
        th, td { border: 1px solid #cfd8e3; padding: 6px 8px; text-align: left; }
        th { background: #eff4ff; }
        @media print { body { margin: 0; } }
    </style>
</head>
<body onload="window.print()">
    <h1><?= $e($data['title']) ?></h1>
    <p>SMK Al Fattah Ngawak Kertosono &bull; Dicetak <?= date('d/m/Y H:i') ?></p>
    <?= $renderTable($data) ?>
</body>
</html>