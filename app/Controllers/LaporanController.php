<?php
// LaporanController.php - handler unduh, cetak, dan arsip laporan (TAHAP FRONTEND)
// Dipanggil lewat: ?page=laporan-export
//   &type=presensi|jurnal &format=excel|pdf &periode=... &jurusan=...  -> buat laporan + simpan ke arsip
//   &id=xxxxxxxxxxxx                                                    -> unduh berkas dari arsip
//   &file=NamaBerkas.ext                                                -> unduh berkas contoh (demo)

require_once dirname(__DIR__) . '/Models/LaporanData.php';

$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$type    = ($_GET['type'] ?? 'presensi') === 'jurnal' ? 'jurnal' : 'presensi';
$format  = ($_GET['format'] ?? 'excel') === 'pdf' ? 'pdf' : 'excel';
$periode = LaporanData::normalizePeriode($_GET['periode'] ?? '');
$jurusan = LaporanData::normalizeJurusan($_GET['jurusan'] ?? '');
$id      = (string) ($_GET['id'] ?? '');
$demo    = (string) ($_GET['file'] ?? '');
$save    = ($_GET['nosave'] ?? '') !== '1'; // nosave=1 -> unduh tanpa menambah arsip

// ---------------------------------------------------------------------------
// Helper render
// ---------------------------------------------------------------------------
$renderTable = static function (array $report) use ($e): string {
    $html = '<table border="1" cellspacing="0" cellpadding="6"><thead><tr>';
    foreach ($report['columns'] as $col) {
        $html .= '<th style="background:#eff4ff;text-align:left">' . $e($col) . '</th>';
    }
    $html .= '</tr></thead><tbody>';
    foreach ($report['rows'] as $row) {
        $html .= '<tr>';
        foreach ($row as $cell) {
            // Sel sebagai teks agar angka nol di depan (NIS) tidak hilang di Excel
            $html .= '<td style="mso-number-format:\'\\@\'">' . $e($cell) . '</td>';
        }
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';

    if (!empty($report['summary'])) {
        $html .= '<br><table border="1" cellspacing="0" cellpadding="6"><tbody>';
        foreach ($report['summary'] as $item) {
            $html .= '<tr><td><strong>' . $e($item[0]) . '</strong></td><td>' . $e($item[1]) . '</td></tr>';
        }
        $html .= '</tbody></table>';
    }
    return $html;
};

$renderHeading = static function (array $report) use ($e): string {
    return '<h3 style="margin:0 0 4px">' . $e($report['title']) . '</h3>'
        . '<div>Periode: ' . $e($report['periode_label']) . ' &bull; Jurusan: ' . $e($report['jurusan_label']) . '</div>'
        . '<div>SMK Al Fattah Ngawak Kertosono &bull; Dibuat ' . $e(LaporanData::formatTanggal(time())) . '</div><br>';
};

// ---------------------------------------------------------------------------
// A. Unduh berkas dari ARSIP (?id=...)
// ---------------------------------------------------------------------------
if ($id !== '') {
    $entry = LaporanData::archiveFind($id);
    $path  = $entry ? LaporanData::archivePath($entry) : '';

    if (!$entry || !is_file($path)) {
        http_response_code(404);
        echo 'Berkas arsip tidak ditemukan.';
        exit;
    }

    if ($entry['format'] === 'xls') {
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . basename($entry['name']) . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    // Arsip PDF = halaman cetak; buka lalu "Simpan sebagai PDF" di dialog print
    header('Content-Type: text/html; charset=utf-8');
    readfile($path);
    exit;
}

// ---------------------------------------------------------------------------
// B. Unduh berkas contoh (baris demo saat arsip masih kosong)
// ---------------------------------------------------------------------------
if ($demo !== '') {
    $clean = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($demo));

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $clean . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');

    echo 'Berkas contoh: ' . $clean;
    exit;
}

// ---------------------------------------------------------------------------
// C. Buat laporan baru (periode + jurusan terpilih) -> simpan ke arsip
// ---------------------------------------------------------------------------
$report = LaporanData::report($type, $periode, $jurusan);
$meta   = [
    'type'    => $type,
    'periode' => $periode,
    'jurusan' => $jurusan,
    'meta'    => LaporanData::metaText($type, $jurusan),
];

if ($format === 'excel') {
    $name = LaporanData::fileName($type, $periode, $jurusan, 'xls');

    // Tabel HTML dibuka Excel sebagai spreadsheet (sementara; .xlsx asli menyusul di tahap berikutnya).
    $content = "\xEF\xBB\xBF<html><head><meta charset=\"utf-8\"></head><body>"
        . $renderHeading($report) . $renderTable($report) . '</body></html>';

    LaporanData::archiveAdd($content, $name, 'xls', $meta + ['format' => 'xls']);

    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    header('Cache-Control: max-age=0');
    echo $content;
    exit;
}

// PDF: halaman cetak (dialog print -> "Simpan sebagai PDF"), snapshot-nya diarsipkan
$name = LaporanData::fileName($type, $periode, $jurusan, 'pdf');

ob_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title><?= $e($report['title']) ?> | E-PKL</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; color: #0b1c30; margin: 32px; font-size: 12px; }
        table { border-collapse: collapse; width: 100%; font-size: 12px; }
        th, td { border: 1px solid #cfd8e3; padding: 6px 8px; text-align: left; }
        th { background: #eff4ff; }
        @media print { body { margin: 0; } }
    </style>
</head>
<body onload="window.print()">
    <?= $renderHeading($report) ?>
    <?= $renderTable($report) ?>
</body>
</html>
<?php
$html = ob_get_clean();

LaporanData::archiveAdd($html, $name, 'html', $meta + ['format' => 'pdf']);

header('Content-Type: text/html; charset=utf-8');
echo $html;