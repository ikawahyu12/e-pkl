<?php
// ==========================================================================
// Halaman: Laporan & Rekapitulasi  (route: ?page=laporan)
// TAHAP FRONTEND: data sementara dari app/Models/LaporanData.php
// Gaya  : public/assets/css/laporan.css
// Skrip : public/assets/js/laporan.js
// ==========================================================================
require_once dirname(__DIR__, 3) . '/Models/LaporanData.php';

$activePage        = 'laporan';
$pageTitle         = 'Laporan & Rekapitulasi';
$hidePageHeading   = true;
$searchPlaceholder = 'Cari siswa, guru, institusi mitra, atau dokumen...';

$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

// ---------------------------------------------------------------------------
// URL aset (sama dengan perhitungan di layouts/admin.php) + cache-busting
// ---------------------------------------------------------------------------
$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$scriptDir = $scriptDir === '.' ? '' : $scriptDir;
$assetUrl  = $scriptDir . '/assets';
$assetDir  = dirname(__DIR__, 4) . '/public/assets';
$cssVer    = (int) @filemtime($assetDir . '/css/laporan.css');
$jsVer     = (int) @filemtime($assetDir . '/js/laporan.js');

$extraHead = '<link rel="preconnect" href="https://fonts.googleapis.com">'
    . '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
    . '<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">'
    . '<link rel="stylesheet" href="' . $e($assetUrl) . '/css/laporan.css?v=' . $cssVer . '">';

// ---------------------------------------------------------------------------
// Filter terpilih
// ---------------------------------------------------------------------------
$periodeOptions = LaporanData::periodeOptions();
$jurusanOptions = LaporanData::jurusanOptions();
$selPeriode     = LaporanData::normalizePeriode($_GET['periode'] ?? '');
$selJurusan     = LaporanData::normalizeJurusan($_GET['jurusan'] ?? '');

// ---------------------------------------------------------------------------
// Kartu laporan. 'num' = angka yang dihitung naik oleh JS.
// ---------------------------------------------------------------------------
$reportCards = [
    [
        'type'  => 'presensi',
        'icon'  => 'fas fa-user-check',
        'tag'   => 'Harian & Kumulatif',
        'title' => 'Rekap Presensi Siswa',
        'desc'  => 'Data rekapitulasi kehadiran harian siswa PKL, persentase kehadiran per jurusan dan instansi, serta rekapan izin atau sakit lengkap dengan time-stamp presensi geolokasi.',
        'chips' => [
            ['icon' => 'fas fa-user-friends',   'pre' => 'Total ', 'num' => 348,  'dec' => 0, 'sep' => false, 'post' => ' Siswa',         'variant' => ''],
            ['icon' => 'fas fa-chart-line',     'pre' => '',       'num' => 96.4, 'dec' => 1, 'sep' => false, 'post' => '% Kehadiran',    'variant' => 'lp-chip--accent'],
            ['icon' => 'far fa-calendar-check', 'text' => 'Update 10m lalu',                                                               'variant' => 'lp-chip--soft'],
        ],
    ],
    [
        'type'  => 'jurnal',
        'icon'  => 'fas fa-book-open',
        'tag'   => 'Aktivitas DU/DI',
        'title' => 'Rekap Jurnal Kegiatan',
        'desc'  => 'Kumpulan logbook kegiatan dan pekerjaan harian siswa di industri, status verifikasi pembimbing lapangan, catatan evaluasi, serta dokumentasi visual pekerjaan praktik.',
        'chips' => [
            ['icon' => 'fas fa-clipboard-list', 'pre' => '', 'num' => 1420, 'dec' => 0, 'sep' => true,  'post' => ' Jurnal Masuk',    'variant' => ''],
            ['icon' => 'far fa-check-circle',   'pre' => '', 'num' => 98,   'dec' => 0, 'sep' => false, 'post' => '% Terverifikasi', 'variant' => 'lp-chip--accent'],
            ['icon' => 'far fa-calendar-alt',   'pre' => '', 'num' => 28,   'dec' => 0, 'sep' => false, 'post' => ' Perlu Review',   'variant' => 'lp-chip--soft'],
        ],
    ],
];

// ---------------------------------------------------------------------------
// Riwayat = arsip nyata (hasil klik Unduh) + data contoh tiap periode & jurusan
// ---------------------------------------------------------------------------
$maxRows = 12; // harus sama dengan MAX_ROWS di laporan.js
$riwayat = [];

foreach (LaporanData::archiveList() as $a) {
    $riwayat[] = [
        'id'      => $a['id'],
        'file'    => $a['name'],
        'meta'    => $a['meta'],
        'ts'      => (int) $a['ts'],
        'format'  => $a['format'],
        'size'    => (int) $a['size'],
        'jurusan' => $a['jurusan'],
        'periode' => $a['periode'],
        'type'    => $a['type'] ?? 'presensi',
    ];
}
foreach (LaporanData::sampleArchive() as $s) {
    $riwayat[] = $s;
}

// Tentukan baris yang tampil untuk filter awal (JS menghitung ulang saat filter diganti)
$shown = 0;
foreach ($riwayat as $i => $r) {
    $okPeriode = $r['periode'] === $selPeriode;
    $okJurusan = $selJurusan === '' || $r['jurusan'] === 'all' || $r['jurusan'] === $selJurusan;
    $visible   = $okPeriode && $okJurusan && $shown < $maxRows;
    $riwayat[$i]['visible'] = $visible;
    if ($visible) {
        $shown++;
    }
}

ob_start();
?>
<div class="lp-page">

    <!-- ============================ HEADER ============================ -->
    <header class="lp-head lp-rise">
        <div>
            <span class="lp-eyebrow"><i class="far fa-folder-open" aria-hidden="true"></i> Dokumentasi Administrasi</span>
            <h1 class="lp-title">Laporan &amp; Rekapitulasi</h1>
            <p class="lp-lead">Unduh dan cetak rekapitulasi data PKL siswa SMK secara efisien, terpusat, dan terverifikasi berkala.</p>
        </div>
        <div class="lp-verified">
            <span class="lp-verified__icon"><i class="fas fa-shield-alt" aria-hidden="true"></i></span>
            <div>
                <strong>Data Terverifikasi Server</strong>
                <span>Sinkronisasi Otomatis • Aman</span>
            </div>
        </div>
    </header>

    <!-- ========================= KARTU LAPORAN ======================== -->
    <section class="lp-grid" aria-label="Jenis laporan">
        <?php foreach ($reportCards as $idx => $card): ?>
            <?php $exportBase = '?page=laporan-export&amp;type=' . $e($card['type']) . '&amp;periode=' . rawurlencode($selPeriode) . '&amp;jurusan=' . rawurlencode($selJurusan); ?>
            <article class="lp-card lp-rise" style="--d: <?= 0.1 + $idx * 0.12 ?>s">
                <div class="lp-card__top">
                    <span class="lp-card__icon"><i class="<?= $e($card['icon']) ?>" aria-hidden="true"></i></span>
                    <span class="lp-tag"><?= $e($card['tag']) ?></span>
                </div>

                <h2 class="lp-card__title"><?= $e($card['title']) ?></h2>
                <p class="lp-card__desc"><?= $e($card['desc']) ?></p>

                <div class="lp-chips">
                    <?php foreach ($card['chips'] as $chip): ?>
                        <span class="lp-chip <?= $e($chip['variant']) ?>">
                            <i class="<?= $e($chip['icon']) ?>" aria-hidden="true"></i>
                            <?php if (isset($chip['num'])): ?>
                                <?= $e($chip['pre']) ?><span class="js-count" data-count="<?= $e($chip['num']) ?>" data-dec="<?= (int) $chip['dec'] ?>" data-sep="<?= $chip['sep'] ? 1 : 0 ?>"><?= $e(number_format($chip['num'], (int) $chip['dec'], '.', $chip['sep'] ? ',' : '')) ?></span><?= $e($chip['post']) ?>
                            <?php else: ?>
                                <?= $e($chip['text']) ?>
                            <?php endif; ?>
                        </span>
                    <?php endforeach; ?>
                </div>

                <div class="lp-actions">
                    <a class="lp-btn lp-btn--light js-export" data-type="<?= $e($card['type']) ?>" data-format="excel"
                       data-title="<?= $e($card['title']) ?> (Excel)"
                       href="<?= $exportBase ?>&amp;format=excel">
                        <i class="fas fa-table" aria-hidden="true"></i> <span class="lp-btn__label">Unduh Excel</span>
                    </a>
                    <a class="lp-btn lp-btn--dark js-export" data-type="<?= $e($card['type']) ?>" data-format="pdf"
                       data-title="<?= $e($card['title']) ?> (PDF)"
                       target="_blank" rel="noopener"
                       href="<?= $exportBase ?>&amp;format=pdf">
                        <i class="fas fa-print" aria-hidden="true"></i> <span class="lp-btn__label">Cetak PDF</span>
                    </a>
                </div>

                <p class="lp-scope">
                    Laporan untuk: <strong class="js-scope-periode"><?= $e($periodeOptions[$selPeriode]) ?></strong>
                    &bull; <strong class="js-scope-jurusan"><?= $e($jurusanOptions[$selJurusan]) ?></strong>
                </p>
            </article>
        <?php endforeach; ?>
    </section>

    <!-- ======================= RIWAYAT UNDUHAN ======================== -->
    <section class="lp-history lp-lazy" id="lpHistory" aria-labelledby="lpHistoryTitle">
        <div class="lp-history__head">
            <div class="lp-history__titlewrap">
                <span class="lp-history__icon"><i class="fas fa-history" aria-hidden="true"></i></span>
                <div>
                    <h2 class="lp-history__title" id="lpHistoryTitle">Riwayat Unduhan Terbaru</h2>
                    <p class="lp-history__sub">Arsip berkas unduhan hasil cetak dan ekspor sistem</p>
                </div>
            </div>

            <div class="lp-filters">
                <div class="lp-pill lp-pill--periode">
                    <label for="lpPeriode">Periode:</label>
                    <select id="lpPeriode">
                        <?php foreach ($periodeOptions as $val => $label): ?>
                            <option value="<?= $e($val) ?>" <?= $val === $selPeriode ? 'selected' : '' ?>><?= $e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="button" class="lp-refresh" id="lpRefresh" aria-label="Muat ulang riwayat">
                    <i class="fas fa-sync-alt" aria-hidden="true"></i>
                </button>

                <div class="lp-pill">
                    <label for="lpJurusan">Jurusan:</label>
                    <select id="lpJurusan">
                        <?php foreach ($jurusanOptions as $val => $label): ?>
                            <option value="<?= $e($val) ?>" <?= $val === $selJurusan ? 'selected' : '' ?>><?= $e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="lp-tablewrap">
            <table class="lp-table">
                <thead>
                    <tr>
                        <th>Nama Laporan</th>
                        <th>Periode</th>
                        <th>Jurusan</th>
                        <th>Tanggal Generate</th>
                        <th class="lp-c">Format</th>
                        <th class="lp-c">Ukuran</th>
                        <th class="lp-c">Aksi</th>
                    </tr>
                </thead>
                <tbody id="lpRows">
                    <?php foreach ($riwayat as $row): ?>
                        <?php
                        $isPdf  = $row['format'] === 'pdf';
                        $jKey   = $row['jurusan'] === 'all' ? '' : $row['jurusan'];
                        $pFull  = $periodeOptions[$row['periode']] ?? $row['periode'];
                        $pShort = trim(str_replace('Semester', '', $pFull));
                        $jLabel = $jKey === '' ? 'Semua Jurusan' : strtoupper($jKey);

                        $hrefP = '?page=laporan&periode=' . rawurlencode($row['periode']) . '&jurusan=' . rawurlencode($selJurusan) . '#lpHistory';
                        $hrefJ = '?page=laporan&periode=' . rawurlencode($selPeriode) . '&jurusan=' . rawurlencode($jKey) . '#lpHistory';

                        if ($row['id']) {
                            // Arsip nyata: unduh berkas yang tersimpan
                            $href = '?page=laporan-export&id=' . rawurlencode((string) $row['id']);
                        } else {
                            // Baris contoh: buat laporan sesuai periode & jurusan baris ini, tanpa menambah arsip
                            $href = '?page=laporan-export&type=' . rawurlencode($row['type'])
                                . '&format=' . ($isPdf ? 'pdf' : 'excel')
                                . '&periode=' . rawurlencode($row['periode'])
                                . '&jurusan=' . rawurlencode($jKey)
                                . '&nosave=1';
                        }
                        ?>
                        <tr data-jurusan="<?= $e($row['jurusan']) ?>" data-periode="<?= $e($row['periode']) ?>" <?= $row['visible'] ? '' : 'hidden' ?>>
                            <td>
                                <div class="lp-file">
                                    <span class="lp-file__icon <?= $isPdf ? 'lp-file__icon--pdf' : '' ?>">
                                        <i class="<?= $isPdf ? 'fas fa-file-pdf' : 'fas fa-table' ?>" aria-hidden="true"></i>
                                    </span>
                                    <div>
                                        <span class="lp-file__name"><?= $e($row['file']) ?></span>
                                        <span class="lp-file__meta"><?= $e($row['meta']) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <a class="lp-chiplink lp-chiplink--periode js-filter-chip <?= $row['periode'] === $selPeriode ? 'is-active' : '' ?>"
                                   href="<?= $e($hrefP) ?>" data-filter="periode" data-value="<?= $e($row['periode']) ?>"
                                   title="Tampilkan riwayat <?= $e($pFull) ?>">
                                    <i class="far fa-calendar-alt" aria-hidden="true"></i><?= $e($pShort) ?>
                                </a>
                            </td>
                            <td>
                                <a class="lp-chiplink lp-chiplink--<?= $jKey === '' ? 'all' : $e($jKey) ?> js-filter-chip <?= $jKey === $selJurusan ? 'is-active' : '' ?>"
                                   href="<?= $e($hrefJ) ?>" data-filter="jurusan" data-value="<?= $e($jKey) ?>"
                                   title="Tampilkan riwayat <?= $e($jLabel) ?>">
                                    <i class="fas <?= $jKey === '' ? 'fa-th-large' : 'fa-graduation-cap' ?>" aria-hidden="true"></i><?= $e($jLabel) ?>
                                </a>
                            </td>
                            <td><?= $e(LaporanData::formatTanggal((int) $row['ts'])) ?></td>
                            <td class="lp-c">
                                <span class="lp-badge <?= $isPdf ? 'lp-badge--pdf' : 'lp-badge--xls' ?>"><?= $isPdf ? 'PDF' : 'XLS' ?></span>
                            </td>
                            <td class="lp-c lp-size"><?= $e(LaporanData::formatUkuran((int) $row['size'])) ?></td>
                            <td class="lp-c">
                                <a class="lp-dl" href="<?= $e($href) ?>" <?= $isPdf ? 'target="_blank" rel="noopener"' : '' ?>>
                                    <i class="fas fa-download" aria-hidden="true"></i> Unduh Berkas
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="lp-empty <?= $shown === 0 ? 'is-visible' : '' ?>" id="lpEmpty">
                        <td colspan="7">
                            <i class="far fa-folder-open" aria-hidden="true"></i>
                            Belum ada arsip untuk periode &amp; jurusan ini.<br>
                            Klik <strong>Unduh Excel</strong> atau <strong>Cetak PDF</strong> di atas untuk membuat laporan.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="lp-history__foot">
            <span><i class="fas fa-info-circle" aria-hidden="true"></i><span id="lpCount"><?= $shown === 0 ? 'Tidak ada berkas' : 'Menampilkan ' . $shown . ' berkas terbaru' ?></span></span>
            <span><i class="fas fa-archive" aria-hidden="true"></i>Otomatis diarsipkan setiap akhir semester</span>
        </div>
    </section>

    <div class="lp-toasts" id="lpToasts" aria-live="polite"></div>
</div>

<script src="<?= $e($assetUrl) ?>/js/laporan.js?v=<?= $jsVer ?>" defer></script>
<?php
$content = ob_get_clean();
require dirname(__DIR__, 2) . '/layouts/admin.php';