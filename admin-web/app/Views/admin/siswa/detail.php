<?php
/**
 * Admin > Data Siswa > Detail  (UI saja, data dummy)
 *
 * File ini berdiri sendiri. Satu-satunya yang dibutuhkan: app/Views/layouts/admin.php
 * Letak file: app/Views/admin/siswa/detail.php
 * Data siswa diambil dari array dummy berdasarkan ?id=  (contoh: ?page=detail&id=3)
 * Catatan: belum ada desain Figma untuk halaman ini, jadi tampilannya mengikuti gaya halaman Edit.
 */

// 1. Alamat asset (dipakai hanya jika halaman dibuka langsung dari folder app/Views)
if (!isset($assetBaseUrl)) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    if (preg_match('#^(.*?)/app/Views(?:/.*)?$#', $scriptDir, $m)) {
        $assetBaseUrl = $m[1] . '/public/assets';
    }
}

// 2. Alamat tombol. Ubah jika nama page di pengatur (public/index.php) berbeda.
$urlDaftar = '?page=data-siswa';
$urlEdit   = '?page=edit&id=';

// 3. Data dummy (ganti dengan data dari database nanti)
$siswaList = [
    1 => ['nisn' => '0061829102', 'nama' => 'Ahmad Rizky Pratama', 'kelas' => 'XII RPL 1 (Rekayasa Perangkat Lunak)',   'jk' => 'Laki-laki', 'ttl' => 'Bandung, 14 Mei 2007',    'wa' => '0812-3456-7890', 'email' => 'ahmad.rizky@smksalfattah.sch.id', 'alamat' => 'Jl. Merdeka No. 12, RT 02/RW 03, Kertosono, Nganjuk', 'ortu' => 'Slamet Pratama',     'tlp_ortu' => '0813-1111-2222', 'instansi' => 'PT. Informatika Solusi Nusantara', 'pic' => 'Agus Kurniawan, S.Kom', 'guru' => 'Alfredo H., S.Kom (NIP: 1988...)', 'mulai' => '2024-07-01', 'selesai' => '2024-12-31', 'status' => 'Aktif'],
    2 => ['nisn' => '0064920193', 'nama' => 'Siti Nurhaliza',       'kelas' => 'XII DKV 2 (Desain Komunikasi Visual)',     'jk' => 'Perempuan', 'ttl' => 'Nganjuk, 3 Maret 2007',   'wa' => '0813-2222-3333', 'email' => '', 'alamat' => 'Jl. Pahlawan No. 5, Kertosono, Nganjuk',           'ortu' => 'Hasan Basri',        'tlp_ortu' => '0813-2222-4444', 'instansi' => 'Studio Animasi Semesta',  'pic' => 'Rina Marlina',  'guru' => 'Dewi Sartika, M.Sn.',  'mulai' => '2024-07-01', 'selesai' => '2024-12-31', 'status' => 'Aktif'],
    3 => ['nisn' => '0062839104', 'nama' => 'Muhammad Faisal',      'kelas' => 'XII TKJ 1 (Teknik Komputer & Jaringan)',   'jk' => 'Laki-laki', 'ttl' => 'Jombang, 21 Juli 2007',   'wa' => '0857-3333-4444', 'email' => '', 'alamat' => 'Jl. Raya Kertosono No. 77, Nganjuk',              'ortu' => 'Ahmad Fauzi',        'tlp_ortu' => '0857-3333-5555', 'instansi' => 'Lintasarta Datacenter',   'pic' => 'Dedi Hermawan', 'guru' => 'Eko Prasetyo, S.T.',   'mulai' => '2024-07-01', 'selesai' => '2024-12-31', 'status' => 'Aktif'],
    4 => ['nisn' => '0069382012', 'nama' => 'Nabila Putri',         'kelas' => 'XII RPL 2 (Rekayasa Perangkat Lunak)',     'jk' => 'Perempuan', 'ttl' => 'Kediri, 9 Agustus 2007',  'wa' => '0821-4444-5555', 'email' => '', 'alamat' => 'Jl. Melati No. 8, Kertosono, Nganjuk',             'ortu' => 'Sugeng Riyadi',      'tlp_ortu' => '0821-4444-6666', 'instansi' => 'CV Digital Optima',       'pic' => 'Wulan Sari',    'guru' => 'Budi Santoso, S.Kom.', 'mulai' => '2024-01-01', 'selesai' => '2024-06-30', 'status' => 'Selesai'],
    5 => ['nisn' => '0061928371', 'nama' => 'Dimas Wicaksono',      'kelas' => 'XII TKJ 2 (Teknik Komputer & Jaringan)',   'jk' => 'Laki-laki', 'ttl' => 'Surabaya, 17 Januari 2007','wa' => '0815-5555-6666', 'email' => '', 'alamat' => 'Jl. Kenanga No. 21, Kertosono, Nganjuk',           'ortu' => 'Bambang Wicaksono',  'tlp_ortu' => '0815-5555-7777', 'instansi' => 'Biznet Networks',         'pic' => 'Hendra Gunawan','guru' => 'Eko Prasetyo, S.T.',   'mulai' => '2024-07-01', 'selesai' => '2024-12-31', 'status' => 'Aktif'],
    6 => ['nisn' => '0068472910', 'nama' => 'Sarah Safitri',        'kelas' => 'XII DKV 1 (Desain Komunikasi Visual)',     'jk' => 'Perempuan', 'ttl' => 'Nganjuk, 30 Oktober 2007','wa' => '0878-6666-7777', 'email' => '', 'alamat' => 'Jl. Anggrek No. 3, Kertosono, Nganjuk',            'ortu' => 'Joko Santoso',       'tlp_ortu' => '0878-6666-8888', 'instansi' => 'PT Kreatif Media Kreasi', 'pic' => 'Maya Anggraini','guru' => 'Dewi Sartika, M.Sn.',  'mulai' => '2024-07-01', 'selesai' => '2024-12-31', 'status' => 'Aktif'],
    7 => ['nisn' => '0063920194', 'nama' => 'Yoga Pratama',         'kelas' => 'XII RPL 1 (Rekayasa Perangkat Lunak)',     'jk' => 'Laki-laki', 'ttl' => 'Madiun, 5 Februari 2007', 'wa' => '0896-7777-8888', 'email' => '', 'alamat' => 'Jl. Dahlia No. 14, Kertosono, Nganjuk',            'ortu' => 'Eko Pratama',        'tlp_ortu' => '0896-7777-9999', 'instansi' => 'PT Finnet Indonesia',     'pic' => 'Fajar Nugroho', 'guru' => 'Budi Santoso, S.Kom.', 'mulai' => '2024-01-01', 'selesai' => '2024-06-30', 'status' => 'Selesai'],
];
$id = (int) ($_GET['id'] ?? 1);
$s  = $siswaList[$id] ?? $siswaList[1];
$id = isset($siswaList[$id]) ? $id : 1;

$e   = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$val = static fn ($v): string => trim((string) $v) === '' ? '-' : htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$tgl = static function ($d): string {
    $bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $t = strtotime($d);
    return $t ? date('j', $t) . ' ' . $bulan[(int) date('n', $t) - 1] . ' ' . date('Y', $t) : '-';
};
$inisial = '';
foreach (array_slice(preg_split('/\s+/', trim($s['nama'])), 0, 2) as $kata) { $inisial .= mb_strtoupper(mb_substr($kata, 0, 1)); }
$selesai = $s['status'] === 'Selesai';

// 4. Variabel untuk layouts/admin.php
$pageTitle  = 'Detail Data Siswa';
$activePage = 'siswa'; // samakan dengan kunci menu "Data Siswa" di sidebar.php

ob_start();
?>
<style>
    /* Judul + breadcrumb dibuat sendiri di halaman ini, jadi header bawaan layout disembunyikan */
    .page-heading { display: none !important; }

    .dt-top { display: flex; justify-content: space-between; align-items: flex-start; gap: .75rem; flex-wrap: wrap; margin-bottom: 1rem; }
    .dt-crumb { font-size: .72rem; color: #64748b; margin-bottom: .45rem; }
    .dt-crumb a { color: #64748b; text-decoration: none; }
    .dt-crumb .sep { margin: 0 .3rem; }
    .dt-crumb .now { color: #1e293b; }
    .dt-title { font-size: 1.45rem; font-weight: 800; color: #0f1d3a; margin: 0; }
    .dt-sub { font-size: .8rem; color: #64748b; margin: .25rem 0 0; }
    .dt-actions { display: flex; gap: .5rem; flex-wrap: wrap; }
    .dt-btn { display: inline-flex; align-items: center; height: 36px; padding: 0 1rem; border-radius: 9px; font-size: .78rem; font-weight: 600; text-decoration: none; cursor: pointer; border: 1px solid transparent; background: none; }
    .dt-btn-back { background: #fff; border-color: #e2e8f0; color: #334155; font-weight: 500; }
    .dt-btn-back:hover { background: #f8fafc; color: #334155; text-decoration: none; }
    .dt-btn-edit { background: #1e56a0; color: #fff; }
    .dt-btn-edit:hover { background: #184685; color: #fff; text-decoration: none; }
    .dt-btn-del { background: #fff; border-color: #fecdd3; color: #e11d48; }
    .dt-btn-del:hover { background: #fff1f2; }

    .dt-card { background: #fff; border: 1px solid #eef2f7; border-radius: 16px; box-shadow: 0 1px 3px rgba(16,24,40,.06); padding: 1.4rem 1.6rem; height: 100%; }
    .dt-profile { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }
    .dt-avatar { width: 60px; height: 60px; border-radius: 50%; background: #e6effc; color: #1d56c9; display: flex; align-items: center; justify-content: center; font-size: 1.15rem; font-weight: 800; flex-shrink: 0; }
    .dt-name { font-size: 1.1rem; font-weight: 800; color: #0f1d3a; margin: 0; }
    .dt-meta { font-size: .78rem; color: #64748b; margin-top: .15rem; }
    .dt-pill { margin-left: auto; background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; border-radius: 999px; font-size: .68rem; font-weight: 700; padding: .25rem .9rem; }
    .dt-pill.selesai { background: #dbeafe; color: #1d4ed8; border-color: #bfdbfe; }

    .dt-sec { font-size: .68rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #94a3b8; margin: 0 0 .5rem; }
    .dt-row { display: flex; gap: 1rem; padding: .65rem 0; border-bottom: 1px solid #f1f5f9; font-size: .8rem; }
    .dt-row:last-child { border-bottom: 0; }
    .dt-row .k { width: 170px; flex-shrink: 0; color: #64748b; }
    .dt-row .v { color: #1e293b; font-weight: 600; word-break: break-word; }

    /* Dialog hapus */
    .dt-modal .modal-content { border: 0; border-radius: 16px; }
    .dt-modal .dt-trash { width: 44px; height: 44px; border-radius: 10px; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .dt-modal .dt-ringkas { background: #f8fafc; border: 1px solid #eef2f7; border-radius: 10px; padding: .7rem 1rem; font-size: .78rem; }
    .dt-modal .dt-ringkas div { display: flex; justify-content: space-between; gap: 1rem; padding: .2rem 0; }
    .dt-modal .dt-ringkas div span:first-child { color: #64748b; }
    .dt-modal .dt-ringkas div span:last-child { font-weight: 600; text-align: right; }
    .dt-modal .dt-warn { background: #fffbeb; border: 1px solid #fde68a; color: #b45309; border-radius: 10px; padding: .6rem .85rem; font-size: .76rem; }

    @media (max-width: 575.98px) {
        .dt-card { padding: 1.1rem; }
        .dt-row { flex-direction: column; gap: .15rem; }
        .dt-row .k { width: auto; font-size: .72rem; }
        .dt-pill { margin-left: 0; }
        .dt-actions { width: 100%; }
        .dt-actions .dt-btn { flex: 1; justify-content: center; }
    }
</style>

<div class="dt">
    <div class="dt-top">
        <div>
            <div class="dt-crumb">
                <a href="<?= $e($urlDaftar) ?>">Data Siswa</a><span class="sep">/</span>Detail<span class="sep">/</span><span class="now"><?= $e($s['nama']) ?></span>
            </div>
            <h1 class="dt-title">Detail Data Siswa</h1>
            <p class="dt-sub">Informasi lengkap biodata siswa, penempatan PKL, dan status bimbingan magang</p>
        </div>
        <div class="dt-actions">
            <a href="<?= $e($urlDaftar) ?>" class="dt-btn dt-btn-back"><i class="fas fa-arrow-left mr-2"></i>Kembali</a>
            <a href="<?= $e($urlEdit . $id) ?>" class="dt-btn dt-btn-edit"><i class="fas fa-pen mr-2"></i>Edit</a>
            <button type="button" class="dt-btn dt-btn-del" id="dtHapus"><i class="far fa-trash-alt mr-2"></i>Hapus</button>
        </div>
    </div>

    <div class="dt-card mb-4" style="height:auto;">
        <div class="dt-profile">
            <div class="dt-avatar"><?= $e($inisial) ?></div>
            <div>
                <h2 class="dt-name"><?= $e($s['nama']) ?></h2>
                <div class="dt-meta">NISN: <?= $e($s['nisn']) ?> &middot; <?= $e($s['kelas']) ?></div>
            </div>
            <span class="dt-pill<?= $selesai ? ' selesai' : '' ?>"><?= $selesai ? 'Selesai PKL' : 'Aktif PKL' ?></span>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="dt-card">
                <div class="dt-sec">1. Informasi Pribadi &amp; Akademik Siswa</div>
                <div class="dt-row"><div class="k">Nama Lengkap</div><div class="v"><?= $val($s['nama']) ?></div></div>
                <div class="dt-row"><div class="k">NISN</div><div class="v"><?= $val($s['nisn']) ?></div></div>
                <div class="dt-row"><div class="k">Kelas &amp; Keahlian</div><div class="v"><?= $val($s['kelas']) ?></div></div>
                <div class="dt-row"><div class="k">Jenis Kelamin</div><div class="v"><?= $val($s['jk']) ?></div></div>
                <div class="dt-row"><div class="k">Tempat, Tanggal Lahir</div><div class="v"><?= $val($s['ttl']) ?></div></div>
                <div class="dt-row"><div class="k">WhatsApp Siswa</div><div class="v"><?= $val($s['wa']) ?></div></div>
                <div class="dt-row"><div class="k">Email Siswa</div><div class="v"><?= $val($s['email']) ?></div></div>
                <div class="dt-row"><div class="k">Alamat</div><div class="v"><?= $val($s['alamat']) ?></div></div>
                <div class="dt-row"><div class="k">Orang Tua / Wali</div><div class="v"><?= $val($s['ortu']) ?></div></div>
                <div class="dt-row"><div class="k">Telepon Orang Tua</div><div class="v"><?= $val($s['tlp_ortu']) ?></div></div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="dt-card">
                <div class="dt-sec">2. Penempatan Instansi &amp; Pembimbing</div>
                <div class="dt-row"><div class="k">Instansi Mitra DU-DI</div><div class="v"><?= $val($s['instansi']) ?></div></div>
                <div class="dt-row"><div class="k">Pembimbing Lapangan</div><div class="v"><?= $val($s['pic']) ?></div></div>
                <div class="dt-row"><div class="k">Guru Pembimbing</div><div class="v"><?= $val($s['guru']) ?></div></div>

                <div class="dt-sec" style="margin-top:1.25rem;">3. Periode Magang</div>
                <div class="dt-row"><div class="k">Mulai</div><div class="v"><?= $e($tgl($s['mulai'])) ?></div></div>
                <div class="dt-row"><div class="k">Selesai</div><div class="v"><?= $e($tgl($s['selesai'])) ?></div></div>
                <div class="dt-row"><div class="k">Status Bimbingan</div><div class="v"><?= $selesai ? 'Selesai' : 'Aktif Magang (Sedang Berjalan)' ?></div></div>
            </div>
        </div>
    </div>
</div>

<!-- Dialog konfirmasi hapus (UI saja, belum menghapus data) -->
<div class="modal fade dt-modal" id="dtModalHapus" tabindex="-1" role="dialog" aria-labelledby="dtModalJudul" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content p-2">
            <div class="modal-body">
                <div class="d-flex mb-3" style="gap:1rem;">
                    <div class="dt-trash"><i class="fas fa-trash-alt"></i></div>
                    <div>
                        <h5 class="font-weight-bold mb-1" id="dtModalJudul">Hapus Data Siswa?</h5>
                        <p class="small text-muted mb-0">
                            Apakah Anda yakin ingin menghapus data siswa <strong><?= $e($s['nama']) ?></strong> (NISN: <?= $e($s['nisn']) ?>)?
                            Tindakan ini akan membatalkan status penempatan PKL dan seluruh riwayat jurnal serta presensi siswa terkait.
                        </p>
                    </div>
                </div>
                <div class="dt-ringkas mb-3">
                    <div><span>Nama Siswa:</span><span><?= $e($s['nama']) ?></span></div>
                    <div><span>Kelas &amp; Jurusan:</span><span><?= $e($s['kelas']) ?></span></div>
                    <div><span>Instansi Penempatan:</span><span class="text-primary"><?= $e($s['instansi']) ?></span></div>
                    <div><span>Guru Pembimbing:</span><span><?= $e($s['guru']) ?></span></div>
                </div>
                <div class="dt-warn d-flex mb-3" style="gap:.5rem;">
                    <i class="fas fa-exclamation-triangle mt-1"></i>
                    <span>Pastikan koordinasi dengan guru pembimbing dan pihak instansi sebelum menghapus siswa aktif.</span>
                </div>
                <div class="d-flex justify-content-end" style="gap:.5rem;">
                    <button type="button" class="btn btn-light border btn-sm px-4" data-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger btn-sm px-3" id="dtKonfirmasi"><i class="fas fa-trash-alt mr-1"></i> Ya, Hapus Data</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // jQuery dimuat layout di akhir halaman, jadi skrip dijalankan setelah semuanya siap.
    window.addEventListener('load', function () {
        var $ = window.jQuery;
        $('#dtHapus').on('click', function () { $('#dtModalHapus').modal('show'); });
        $('#dtKonfirmasi').on('click', function () {
            // TODO (backend): kirim permintaan hapus ke controller. Sementara hanya menutup dialog.
            $('#dtModalHapus').modal('hide');
        });
    });
</script>
<?php
$content = ob_get_clean();

require dirname(__DIR__, 2) . '/layouts/admin.php';