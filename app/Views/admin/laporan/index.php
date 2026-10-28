<?php
// ==========================================================================
// Halaman: Laporan & Rekapitulasi  (route: ?page=laporan)
// Desain mengacu pada Figma "Laporan".
// ==========================================================================
$activePage        = 'laporan';
$pageTitle         = 'Laporan & Rekapitulasi';
$hidePageHeading   = true;   // header halaman digambar sendiri (sesuai Figma)
$searchPlaceholder = 'Cari siswa, guru, institusi mitra, atau dokumen...';

$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

// Font Figma (Plus Jakarta Sans), hanya dimuat di halaman ini.
$extraHead = '<link rel="preconnect" href="https://fonts.googleapis.com">'
    . '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
    . '<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">';

// ---------------------------------------------------------------------------
// Data dummy (ganti dengan data dari database nanti)
// ---------------------------------------------------------------------------
$reportCards = [
    [
        'type'  => 'presensi',
        'icon'  => 'fas fa-user-check',
        'tag'   => 'Harian & Kumulatif',
        'title' => 'Rekap Presensi Siswa',
        'desc'  => 'Data rekapitulasi kehadiran harian siswa PKL, persentase kehadiran per jurusan dan instansi, serta rekapan izin atau sakit lengkap dengan time-stamp presensi geolokasi.',
        'chips' => [
            ['icon' => 'fas fa-user-friends',   'text' => 'Total 348 Siswa',  'variant' => ''],
            ['icon' => 'fas fa-chart-line',     'text' => '96.4% Kehadiran',  'variant' => 'lp-chip--accent'],
            ['icon' => 'far fa-calendar-check', 'text' => 'Update 10m lalu',  'variant' => 'lp-chip--soft'],
        ],
    ],
    [
        'type'  => 'jurnal',
        'icon'  => 'fas fa-book-open',
        'tag'   => 'Aktivitas DU/DI',
        'title' => 'Rekap Jurnal Kegiatan',
        'desc'  => 'Kumpulan logbook kegiatan dan pekerjaan harian siswa di industri, status verifikasi pembimbing lapangan, catatan evaluasi, serta dokumentasi visual pekerjaan praktik.',
        'chips' => [
            ['icon' => 'fas fa-clipboard-list', 'text' => '1,420 Jurnal Masuk', 'variant' => ''],
            ['icon' => 'far fa-check-circle',   'text' => '98% Terverifikasi',  'variant' => 'lp-chip--accent'],
            ['icon' => 'far fa-calendar-alt',   'text' => '28 Perlu Review',    'variant' => 'lp-chip--soft'],
        ],
    ],
];

$periodeOptions = [
    'ganjil-2024-2025' => 'Semester Ganjil 2024/2025',
    'genap-2024-2025'  => 'Semester Genap 2024/2025',
    'ganjil-2025-2026' => 'Semester Ganjil 2025/2026',
    'genap-2025-2026'  => 'Semester Genap 2025/2026',
];
$jurusanOptions = [
    ''    => 'Semua Jurusan',
    'rpl' => 'RPL',
    'tkj' => 'TKJ',
    'dkv' => 'DKV',
];

// format: xls | pdf   |   jurusan: rpl|tkj|dkv|all (all = muncul di semua filter jurusan)
$riwayat = [
    ['file' => 'Rekap_Presensi_RPL_Ganjil_2425.xlsx',   'meta' => 'Presensi Siswa • Kelas XII RPL 1 & 2', 'tanggal' => '18 Okt 2026, 09:12', 'format' => 'xls', 'ukuran' => '1.4 MB', 'jurusan' => 'rpl', 'periode' => 'ganjil-2024-2025'],
    ['file' => 'Rekap_Jurnal_SemuaJurusan_Oktober.pdf', 'meta' => 'Logbook Harian • Validasi Pembimbing',   'tanggal' => '17 Okt 2026, 15:40', 'format' => 'pdf', 'ukuran' => '3.8 MB', 'jurusan' => 'all', 'periode' => 'ganjil-2024-2025'],
    ['file' => 'Nilai_Akhir_Sertifikasi_DUDI_TKJ.xlsx', 'meta' => 'Nilai Industri & Sekolah • XII TKJ',    'tanggal' => '16 Okt 2026, 11:25', 'format' => 'xls', 'ukuran' => '890 KB', 'jurusan' => 'tkj', 'periode' => 'ganjil-2024-2025'],
    ['file' => 'Laporan_Kunjungan_Guru_DKV_Bulan9.pdf', 'meta' => 'Catatan Monitoring • Studio & Agensi',  'tanggal' => '15 Okt 2026, 14:02', 'format' => 'pdf', 'ukuran' => '2.1 MB', 'jurusan' => 'dkv', 'periode' => 'ganjil-2024-2025'],
    ['file' => 'Rekap_Presensi_TKJ_Sep2024.xlsx',       'meta' => 'Presensi Siswa • PT Telkom & Mitra',   'tanggal' => '14 Okt 2026, 08:30', 'format' => 'xls', 'ukuran' => '1.1 MB', 'jurusan' => 'tkj', 'periode' => 'ganjil-2024-2025'],
];

ob_start();
?>
<style>
    /* ------------------------------------------------------------------
       Token desain (diambil dari Figma)
       ------------------------------------------------------------------ */
    .lp-page {
        --lp-primary: #006194;
        --lp-primary-soft: #dce9ff;
        --lp-surface: #eff4ff;
        --lp-bg: #f8f9ff;
        --lp-ink: #0b1c30;
        --lp-ink-2: #3f4850;
        --lp-muted: #5f6b7a;
        --lp-danger: #ba1a1a;
        --lp-danger-soft: #ffdad6;
        font-family: 'Plus Jakarta Sans', 'Nunito', sans-serif;
        color: var(--lp-ink);
        padding-bottom: 2rem;
    }
    .lp-page *, .lp-page button, .lp-page select, .lp-page input {
        font-family: 'Plus Jakarta Sans', 'Nunito', sans-serif;
    }
    /* Samakan font & latar shell dengan Figma khusus halaman ini */
    body, #content-wrapper { background-color: #f8f9ff !important; }
    .sidebar-epkl, .sidebar-epkl *, .topbar, .topbar * { font-family: 'Plus Jakarta Sans', 'Nunito', sans-serif; }

    /* ----------------------------- Header ----------------------------- */
    .lp-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 1.5rem; margin-bottom: 1.75rem; }
    .lp-eyebrow {
        display: inline-flex; align-items: center; gap: .4rem;
        background: var(--lp-primary-soft); color: var(--lp-primary);
        font-size: .66rem; font-weight: 700; letter-spacing: .02em; text-transform: uppercase;
        padding: .28rem .7rem; border-radius: 999px; margin-bottom: 1rem;
    }
    .lp-title { font-size: 1.9rem; font-weight: 700; letter-spacing: -.02em; color: var(--lp-ink); margin: 0 0 .5rem; line-height: 1.2; }
    .lp-lead  { font-size: .9rem; line-height: 1.6; color: var(--lp-ink-2); max-width: 36rem; margin: 0; }

    .lp-verified {
        display: flex; align-items: center; gap: .75rem; flex-shrink: 0;
        background: #fff; border-radius: 14px; padding: .7rem 1.1rem .7rem .8rem;
        box-shadow: 0 6px 24px rgba(11, 28, 48, .06);
    }
    .lp-verified__icon {
        width: 36px; height: 36px; border-radius: 50%;
        background: var(--lp-primary-soft); color: var(--lp-primary);
        display: inline-flex; align-items: center; justify-content: center; font-size: .85rem;
    }
    .lp-verified strong { display: block; font-size: .82rem; font-weight: 700; color: var(--lp-ink); line-height: 1.25; }
    .lp-verified span   { display: block; font-size: .66rem; color: var(--lp-muted); }

    /* --------------------------- Kartu laporan ------------------------- */
    .lp-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.5rem; margin-bottom: 1.75rem; }
    .lp-card {
        position: relative; overflow: hidden; background: #fff; border-radius: 18px;
        padding: 1.9rem 2rem 2rem; box-shadow: 0 6px 28px rgba(11, 28, 48, .05);
    }
    .lp-card::after { /* gelembung dekoratif kanan atas */
        content: ''; position: absolute; top: -70px; right: -70px; width: 230px; height: 230px; border-radius: 50%;
        background: radial-gradient(circle at center, #e3ecfb 0%, rgba(227, 236, 251, 0) 68%);
        pointer-events: none;
    }
    .lp-card > * { position: relative; z-index: 1; }
    .lp-card__top { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1.6rem; }
    .lp-card__icon {
        width: 58px; height: 58px; border-radius: 16px; background: var(--lp-primary-soft); color: var(--lp-primary);
        display: inline-flex; align-items: center; justify-content: center; font-size: 1.15rem;
    }
    .lp-tag {
        display: inline-flex; align-items: center; gap: .4rem; background: var(--lp-surface);
        color: var(--lp-ink-2); font-size: .66rem; font-weight: 500; padding: .28rem .7rem; border-radius: 999px;
    }
    .lp-tag::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: var(--lp-primary); }

    .lp-card__title { font-size: 1.15rem; font-weight: 700; color: var(--lp-ink); margin: 0 0 .65rem; letter-spacing: -.01em; }
    .lp-card__desc  { font-size: .78rem; line-height: 1.75; color: var(--lp-ink-2); max-width: 25rem; margin: 0 0 1.2rem; }

    .lp-chips { display: flex; flex-wrap: wrap; gap: .5rem; max-width: 21rem; }
    .lp-chip {
        display: inline-flex; align-items: center; gap: .45rem; background: var(--lp-surface);
        color: var(--lp-ink); font-size: .7rem; font-weight: 600; padding: .4rem .75rem; border-radius: 8px; white-space: nowrap;
    }
    .lp-chip i { color: var(--lp-primary); font-size: .72rem; }
    .lp-chip--accent { background: var(--lp-primary-soft); color: var(--lp-primary); }
    .lp-chip--soft   { background: #f1f5fd; color: var(--lp-muted); font-weight: 500; }
    .lp-chip--soft i { color: var(--lp-muted); }

    .lp-actions { display: flex; gap: .75rem; margin-top: 2.4rem; }
    .lp-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: .55rem;
        min-width: 10.4rem; height: 2.75rem; padding: 0 1.25rem; border-radius: 10px; border: 0;
        font-size: .82rem; font-weight: 600; text-decoration: none !important; cursor: pointer;
        transition: background-color .15s ease, box-shadow .15s ease;
    }
    .lp-btn--light { background: var(--lp-surface); color: var(--lp-ink); }
    .lp-btn--light i { color: var(--lp-primary); }
    .lp-btn--light:hover { background: var(--lp-primary-soft); color: var(--lp-ink); }
    .lp-btn--dark  { background: var(--lp-primary); color: #fff; }
    .lp-btn--dark:hover { background: #004f78; color: #fff; box-shadow: 0 6px 16px rgba(0, 97, 148, .25); }

    /* --------------------------- Riwayat unduhan ----------------------- */
    .lp-history { background: #fff; border-radius: 18px; overflow: hidden; box-shadow: 0 6px 28px rgba(11, 28, 48, .05); }
    .lp-history__head {
        display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;
        padding: 1.75rem 2rem 1.5rem;
    }
    .lp-history__titlewrap { display: flex; align-items: flex-start; gap: .75rem; }
    .lp-history__icon {
        width: 2.1rem; height: 2.1rem; border-radius: 10px; background: var(--lp-primary-soft); color: var(--lp-primary);
        display: inline-flex; align-items: center; justify-content: center; font-size: .85rem; flex-shrink: 0;
    }
    .lp-history__title { font-size: 1.05rem; font-weight: 700; margin: 0; line-height: 1.3; color: var(--lp-ink); }
    .lp-history__sub   { font-size: .72rem; color: var(--lp-muted); margin: .25rem 0 0; }

    .lp-filters { display: flex; align-items: center; gap: .75rem; }
    .lp-pill {
        position: relative; display: inline-flex; align-items: center; gap: .6rem; background: var(--lp-surface);
        border-radius: 10px; height: 2.4rem; padding: 0 2rem 0 .9rem;
    }
    .lp-pill label { margin: 0; font-size: .64rem; color: var(--lp-muted); font-weight: 500; }
    .lp-pill select {
        appearance: none; -webkit-appearance: none; background: transparent; border: 0; outline: 0; cursor: pointer;
        font-size: .82rem; font-weight: 600; color: var(--lp-ink); padding: 0; min-width: 8rem;
    }
    .lp-pill--periode select { min-width: 12.5rem; }
    .lp-pill::after {
        content: '\f078'; font-family: 'Font Awesome 5 Free'; font-weight: 900; font-size: .55rem;
        color: var(--lp-muted); position: absolute; right: .85rem; pointer-events: none;
    }
    .lp-pill:focus-within { box-shadow: 0 0 0 2px rgba(0, 97, 148, .25); }
    .lp-refresh {
        width: 2.4rem; height: 2.4rem; border: 0; border-radius: 10px; background: var(--lp-surface); color: var(--lp-ink-2);
        display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: background-color .15s;
    }
    .lp-refresh:hover { background: var(--lp-primary-soft); color: var(--lp-primary); }
    .lp-refresh.is-spinning i { animation: lp-spin .6s linear; }
    @keyframes lp-spin { to { transform: rotate(360deg); } }

    .lp-tablewrap { overflow-x: auto; }
    .lp-table { width: 100%; min-width: 760px; border-collapse: collapse; margin: 0; }
    .lp-table thead th {
        background: var(--lp-surface); color: var(--lp-ink-2); font-size: .66rem; font-weight: 500; letter-spacing: .05em;
        text-transform: uppercase; padding: .95rem 1rem; border: 0; white-space: nowrap;
    }
    .lp-table thead th:first-child { padding-left: 2rem; }
    .lp-table tbody td { padding: .95rem 1rem; vertical-align: middle; border: 0; font-size: .75rem; color: var(--lp-ink-2); }
    .lp-table tbody td:first-child { padding-left: 2rem; }
    .lp-table tbody tr { transition: background-color .12s ease; }
    .lp-table tbody tr:hover { background: var(--lp-bg); }
    .lp-table tbody tr[hidden] { display: none; }
    .lp-c { text-align: center; }

    .lp-file { display: flex; align-items: center; gap: .85rem; }
    .lp-file__icon {
        width: 2.4rem; height: 2.4rem; border-radius: 10px; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center; font-size: .9rem;
        background: var(--lp-primary-soft); color: var(--lp-primary);
    }
    .lp-file__icon--pdf { background: var(--lp-danger-soft); color: #c33232; }
    .lp-file__name { display: block; font-size: .82rem; font-weight: 600; color: var(--lp-ink); line-height: 1.3; word-break: break-all; }
    .lp-file__meta { display: block; font-size: .65rem; color: var(--lp-muted); margin-top: .1rem; }

    .lp-badge { display: inline-block; font-size: .62rem; font-weight: 700; padding: .22rem .7rem; border-radius: 999px; letter-spacing: .02em; }
    .lp-badge--xls { background: var(--lp-primary-soft); color: var(--lp-primary); }
    .lp-badge--pdf { background: var(--lp-danger-soft); color: var(--lp-danger); }
    .lp-size { font-weight: 500; color: var(--lp-ink); white-space: nowrap; }

    .lp-dl { display: inline-flex; align-items: center; gap: .45rem; font-size: .82rem; font-weight: 600; color: var(--lp-primary); text-decoration: none !important; white-space: nowrap; }
    .lp-dl:hover { color: #004f78; }
    .lp-dl:hover i { transform: translateY(1px); }
    .lp-dl i { font-size: .72rem; transition: transform .15s ease; }

    .lp-empty td { text-align: center; padding: 2.5rem 1rem !important; color: var(--lp-muted); }
    .lp-empty i { display: block; font-size: 1.4rem; margin-bottom: .5rem; color: #b4c3d6; }
    .lp-empty { display: none; }

    .lp-history__foot {
        display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .5rem;
        background: #f9fbff; padding: .9rem 2rem; font-size: .66rem; color: var(--lp-muted);
    }
    .lp-history__foot i { margin-right: .4rem; }

    /* ------------------------------ Responsif -------------------------- */
    @media (max-width: 1199.98px) { .lp-grid { grid-template-columns: 1fr; } }
    @media (max-width: 767.98px) {
        .lp-head { flex-direction: column; }
        .lp-title { font-size: 1.5rem; }
        .lp-card { padding: 1.4rem; }
        .lp-actions { flex-wrap: wrap; }
        .lp-btn { flex: 1 1 8rem; }
        .lp-history__head { padding: 1.4rem; }
        .lp-filters { flex-wrap: wrap; }
        .lp-history__foot { padding: .9rem 1.4rem; }
    }
</style>

<div class="lp-page">

    <!-- ============================ HEADER ============================ -->
    <header class="lp-head">
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
        <?php foreach ($reportCards as $card): ?>
            <article class="lp-card">
                <div class="lp-card__top">
                    <span class="lp-card__icon"><i class="<?= $e($card['icon']) ?>" aria-hidden="true"></i></span>
                    <span class="lp-tag"><?= $e($card['tag']) ?></span>
                </div>

                <h2 class="lp-card__title"><?= $e($card['title']) ?></h2>
                <p class="lp-card__desc"><?= $e($card['desc']) ?></p>

                <div class="lp-chips">
                    <?php foreach ($card['chips'] as $chip): ?>
                        <span class="lp-chip <?= $e($chip['variant']) ?>">
                            <i class="<?= $e($chip['icon']) ?>" aria-hidden="true"></i><?= $e($chip['text']) ?>
                        </span>
                    <?php endforeach; ?>
                </div>

                <div class="lp-actions">
                    <a class="lp-btn lp-btn--light"
                       href="?page=laporan-export&amp;type=<?= $e($card['type']) ?>&amp;format=excel">
                        <i class="fas fa-table" aria-hidden="true"></i> Unduh Excel
                    </a>
                    <a class="lp-btn lp-btn--dark" target="_blank" rel="noopener"
                       href="?page=laporan-export&amp;type=<?= $e($card['type']) ?>&amp;format=pdf">
                        <i class="fas fa-print" aria-hidden="true"></i> Cetak PDF
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    </section>

    <!-- ======================= RIWAYAT UNDUHAN ======================== -->
    <section class="lp-history" aria-labelledby="lpHistoryTitle">
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
                            <option value="<?= $e($val) ?>"><?= $e($label) ?></option>
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
                            <option value="<?= $e($val) ?>"><?= $e($label) ?></option>
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
                        <th>Tanggal Generate</th>
                        <th class="lp-c">Format</th>
                        <th class="lp-c">Ukuran</th>
                        <th class="lp-c">Aksi</th>
                    </tr>
                </thead>
                <tbody id="lpRows">
                    <?php foreach ($riwayat as $row): $isPdf = $row['format'] === 'pdf'; ?>
                        <tr data-jurusan="<?= $e($row['jurusan']) ?>" data-periode="<?= $e($row['periode']) ?>">
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
                            <td><?= $e($row['tanggal']) ?></td>
                            <td class="lp-c">
                                <span class="lp-badge <?= $isPdf ? 'lp-badge--pdf' : 'lp-badge--xls' ?>"><?= $isPdf ? 'PDF' : 'XLS' ?></span>
                            </td>
                            <td class="lp-c lp-size"><?= $e($row['ukuran']) ?></td>
                            <td class="lp-c">
                                <a class="lp-dl" href="?page=laporan-export&amp;file=<?= rawurlencode($row['file']) ?>">
                                    <i class="fas fa-download" aria-hidden="true"></i> Unduh Berkas
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="lp-empty" id="lpEmpty">
                        <td colspan="5">
                            <i class="far fa-folder-open" aria-hidden="true"></i>
                            Belum ada berkas untuk filter yang dipilih.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="lp-history__foot">
            <span><i class="far fa-info-circle" aria-hidden="true"></i><span id="lpCount">Menampilkan <?= count($riwayat) ?> berkas terbaru</span></span>
            <span><i class="fas fa-archive" aria-hidden="true"></i>Otomatis diarsipkan setiap akhir semester</span>
        </div>
    </section>
</div>

<script>
    (function () {
        var periode = document.getElementById('lpPeriode');
        var jurusan = document.getElementById('lpJurusan');
        var refresh = document.getElementById('lpRefresh');
        var rows    = Array.prototype.slice.call(document.querySelectorAll('#lpRows tr[data-jurusan]'));
        var empty   = document.getElementById('lpEmpty');
        var count   = document.getElementById('lpCount');
        var defaultPeriode = periode.value;

        function apply() {
            var visible = 0;
            rows.forEach(function (row) {
                var okPeriode = row.dataset.periode === periode.value;
                var okJurusan = jurusan.value === '' || row.dataset.jurusan === 'all' || row.dataset.jurusan === jurusan.value;
                var show = okPeriode && okJurusan;
                row.hidden = !show;
                if (show) { visible++; }
            });
            empty.style.display = visible === 0 ? 'table-row' : 'none';
            count.textContent = visible === 0 ? 'Tidak ada berkas' : 'Menampilkan ' + visible + ' berkas terbaru';
        }

        periode.addEventListener('change', apply);
        jurusan.addEventListener('change', apply);

        refresh.addEventListener('click', function () {
            periode.value = defaultPeriode;
            jurusan.value = '';
            refresh.classList.remove('is-spinning');
            void refresh.offsetWidth;              // restart animasi
            refresh.classList.add('is-spinning');
            apply();
        });
    </script>
<?php
$content = ob_get_clean();
require dirname(__DIR__, 2) . '/layouts/admin.php';