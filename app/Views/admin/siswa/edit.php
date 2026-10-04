<?php
/**
 * Admin > Data Siswa > Edit  (UI saja, belum menyimpan ke database)
 *
 * File ini berdiri sendiri. Satu-satunya yang dibutuhkan: app/Views/layouts/admin.php
 * Letak file: app/Views/admin/siswa/edit.php
 * Data siswa diambil dari array dummy berdasarkan ?id=  (contoh: ?page=edit&id=3)
 */

// 1. Alamat asset (dipakai hanya jika halaman dibuka langsung dari folder app/Views)
if (!isset($assetBaseUrl)) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    if (preg_match('#^(.*?)/app/Views(?:/.*)?$#', $scriptDir, $m)) {
        $assetBaseUrl = $m[1] . '/public/assets';
    }
}

// 2. Alamat tombol Kembali / Batal (daftar siswa). Ubah jika nama page di pengatur berbeda.
$urlDaftar = '?page=data-siswa';

// 3. Data dummy (ganti dengan data dari database nanti)
$siswaList = [
    1 => ['nisn' => '0061829102', 'nis' => '1234567801', 'nama' => 'Ahmad Rizky Pratama', 'kelas' => 'XII RPL 1 (Rekayasa Perangkat Lunak)',   'jk' => 'Laki-laki', 'wa' => '0812-3456-7890', 'email' => 'ahmad.rizky@smksalfattah.sch.id', 'instansi' => 'PT. Informatika Solusi Nusantara', 'pic' => 'Agus Kurniawan, S.Kom', 'guru' => 'Alfredo H., S.Kom (NIP: 1988...)', 'mulai' => '2024-07-01', 'selesai' => '2024-12-31', 'status' => 'Aktif'],
    2 => ['nisn' => '0064920193', 'nis' => '1234567802', 'nama' => 'Siti Nurhaliza',       'kelas' => 'XII DKV 2 (Desain Komunikasi Visual)',     'jk' => 'Perempuan', 'wa' => '0813-2222-3333', 'email' => '', 'instansi' => 'Studio Animasi Semesta',  'pic' => 'Rina Marlina',  'guru' => 'Dewi Sartika, M.Sn.',  'mulai' => '2024-07-01', 'selesai' => '2024-12-31', 'status' => 'Aktif'],
    3 => ['nisn' => '0062839104', 'nis' => '1234567803', 'nama' => 'Muhammad Faisal',      'kelas' => 'XII TKJ 1 (Teknik Komputer & Jaringan)',   'jk' => 'Laki-laki', 'wa' => '0857-3333-4444', 'email' => '', 'instansi' => 'Lintasarta Datacenter',   'pic' => 'Dedi Hermawan', 'guru' => 'Eko Prasetyo, S.T.',   'mulai' => '2024-07-01', 'selesai' => '2024-12-31', 'status' => 'Aktif'],
    4 => ['nisn' => '0069382012', 'nis' => '1234567804', 'nama' => 'Nabila Putri',         'kelas' => 'XII RPL 2 (Rekayasa Perangkat Lunak)',     'jk' => 'Perempuan', 'wa' => '0821-4444-5555', 'email' => '', 'instansi' => 'CV Digital Optima',       'pic' => 'Wulan Sari',    'guru' => 'Budi Santoso, S.Kom.', 'mulai' => '2024-01-01', 'selesai' => '2024-06-30', 'status' => 'Selesai'],
    5 => ['nisn' => '0061928371', 'nis' => '1234567805', 'nama' => 'Dimas Wicaksono',      'kelas' => 'XII TKJ 2 (Teknik Komputer & Jaringan)',   'jk' => 'Laki-laki', 'wa' => '0815-5555-6666', 'email' => '', 'instansi' => 'Biznet Networks',         'pic' => 'Hendra Gunawan','guru' => 'Eko Prasetyo, S.T.',   'mulai' => '2024-07-01', 'selesai' => '2024-12-31', 'status' => 'Aktif'],
    6 => ['nisn' => '0068472910', 'nis' => '1234567806', 'nama' => 'Sarah Safitri',        'kelas' => 'XII DKV 1 (Desain Komunikasi Visual)',     'jk' => 'Perempuan', 'wa' => '0878-6666-7777', 'email' => '', 'instansi' => 'PT Kreatif Media Kreasi', 'pic' => 'Maya Anggraini','guru' => 'Dewi Sartika, M.Sn.',  'mulai' => '2024-07-01', 'selesai' => '2024-12-31', 'status' => 'Aktif'],
    7 => ['nisn' => '0063920194', 'nis' => '1234567807', 'nama' => 'Yoga Pratama',         'kelas' => 'XII RPL 1 (Rekayasa Perangkat Lunak)',     'jk' => 'Laki-laki', 'wa' => '0896-7777-8888', 'email' => '', 'instansi' => 'PT Finnet Indonesia',     'pic' => 'Fajar Nugroho', 'guru' => 'Budi Santoso, S.Kom.', 'mulai' => '2024-01-01', 'selesai' => '2024-06-30', 'status' => 'Selesai'],
];
$id = (int) ($_GET['id'] ?? 1);
$s  = $siswaList[$id] ?? $siswaList[1];

$opsiKelas = [
    'XII RPL 1 (Rekayasa Perangkat Lunak)', 'XII RPL 2 (Rekayasa Perangkat Lunak)',
    'XII TKJ 1 (Teknik Komputer & Jaringan)', 'XII TKJ 2 (Teknik Komputer & Jaringan)',
    'XII DKV 1 (Desain Komunikasi Visual)', 'XII DKV 2 (Desain Komunikasi Visual)',
];
$opsiInstansi = [
    'PT. Informatika Solusi Nusantara', 'PT Telkom Indonesia', 'Studio Animasi Semesta', 'Lintasarta Datacenter',
    'CV Digital Optima', 'Biznet Networks', 'PT Kreatif Media Kreasi', 'PT Finnet Indonesia',
];
$opsiGuru = [
    'Alfredo H., S.Kom (NIP: 1988...)', 'Budi Santoso, S.Kom.', 'Dewi Sartika, M.Sn.', 'Eko Prasetyo, S.T.',
];
$opsiStatus = ['Aktif Magang (Sedang Berjalan)', 'Selesai', 'Menunggu Penempatan'];
$statusDipilih = $s['status'] === 'Selesai' ? 'Selesai' : 'Aktif Magang (Sedang Berjalan)';

$e   = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$pil = static fn ($a, $b): string => (string) $a === (string) $b ? ' selected' : '';

// 4. Variabel untuk layouts/admin.php
$pageTitle  = 'Edit Data Siswa';
$activePage = 'siswa'; // samakan dengan kunci menu "Data Siswa" di sidebar.php

ob_start();
?>
<style>
    /* Judul + breadcrumb dibuat sendiri di halaman ini (sesuai Figma), jadi header bawaan layout disembunyikan */
    .page-heading { display: none !important; }

    .ed-top { display: flex; justify-content: space-between; align-items: flex-start; gap: .75rem; flex-wrap: wrap; margin-bottom: 1rem; }
    .ed-crumb { font-size: .72rem; color: #64748b; margin-bottom: .45rem; }
    .ed-crumb a { color: #64748b; text-decoration: none; }
    .ed-crumb .sep { margin: 0 .3rem; }
    .ed-crumb .now { color: #1e293b; }
    .ed-title { font-size: 1.45rem; font-weight: 800; color: #0f1d3a; margin: 0; }
    .ed-sub { font-size: .8rem; color: #64748b; margin: .25rem 0 0; }
    .ed-back { display: inline-flex; align-items: center; height: 36px; padding: 0 1rem; border: 1px solid #e2e8f0; border-radius: 9px; background: #fff; color: #334155; font-size: .78rem; font-weight: 500; text-decoration: none; }
    .ed-back:hover { background: #f8fafc; color: #334155; text-decoration: none; }

    .ed-banner { display: flex; justify-content: space-between; align-items: center; gap: .75rem; flex-wrap: wrap; background: #eaf2ff; border: 1px solid #dbe8fb; border-radius: 12px; padding: .65rem 1rem; font-size: .8rem; color: #334155; margin-bottom: 1rem; }
    .ed-banner i { color: #2563eb; margin-right: .6rem; }
    .ed-banner small { color: #64748b; font-size: .72rem; }
    .ed-pill { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; border-radius: 999px; font-size: .68rem; font-weight: 700; padding: .2rem .8rem; }
    .ed-pill.selesai { background: #dbeafe; color: #1d4ed8; border-color: #bfdbfe; }

    .ed-card { background: #fff; border: 1px solid #eef2f7; border-radius: 16px; box-shadow: 0 1px 3px rgba(16,24,40,.06); padding: 1.5rem 1.75rem; }
    .ed-sec { font-size: .68rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #94a3b8; margin: 0 0 1rem; }
    .ed-sec + .row, .ed-sec ~ .row { margin-top: 0; }
    .ed-divider { border: 0; border-top: 1px solid #eef2f7; margin: 1.25rem 0; }

    .ed-label { display: block; font-size: .74rem; font-weight: 700; color: #1e293b; margin-bottom: .35rem; }
    .ed-label .req { color: #e11d48; }
    .ed-label .opt { color: #94a3b8; font-weight: 400; }
    .ed-input { width: 100%; height: 38px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; font-size: .76rem; color: #1e293b; padding: 0 .85rem; outline: 0; }
    .ed-input:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.12); }
    .ed-input[readonly] { background: #f1f5f9; color: #475569; cursor: not-allowed; }
    select.ed-input { -webkit-appearance: none; -moz-appearance: none; appearance: none; padding-right: 32px; cursor: pointer;
        background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%231e293b' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'><polyline points='6 9 12 15 18 9'/></svg>");
        background-repeat: no-repeat; background-position: right 12px center; }
    .ed-period { display: flex; align-items: center; gap: .5rem; }
    .ed-period span { font-size: .72rem; color: #64748b; }
    .ed-note { display: flex; align-items: flex-start; gap: .45rem; font-size: .74rem; color: #b45309; margin-top: .25rem; }

    .ed-foot { display: flex; justify-content: flex-end; gap: .6rem; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #eef2f7; }
    .ed-btn { height: 36px; border-radius: 8px; font-size: .76rem; font-weight: 600; padding: 0 1.2rem; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; cursor: pointer; }
    .ed-btn-cancel { background: #fff; color: #334155; border: 1px solid #e2e8f0; }
    .ed-btn-cancel:hover { background: #f8fafc; color: #334155; text-decoration: none; }
    .ed-btn-save { background: #1e56a0; color: #fff; border: 0; }
    .ed-btn-save:hover { background: #184685; }
    .ed-toast { display: none; margin-top: 1rem; padding: .65rem .9rem; border-radius: 10px; background: #ecfdf5; color: #047857; font-size: .78rem; }

    @media (max-width: 575.98px) {
        .ed-card { padding: 1.1rem; }
        .ed-period { flex-wrap: wrap; }
        .ed-period .ed-input { flex: 1 1 100%; }
        .ed-foot .ed-btn { flex: 1; }
    }
</style>

<div class="ed">
    <div class="ed-top">
        <div>
            <div class="ed-crumb">
                <a href="<?= $e($urlDaftar) ?>">Data Siswa</a><span class="sep">/</span>Edit<span class="sep">/</span><span class="now"><?= $e($s['nama']) ?></span>
            </div>
            <h1 class="ed-title">Edit Data Siswa</h1>
            <p class="ed-sub">Perbarui data biodata siswa, penempatan PKL, dan status bimbingan magang</p>
        </div>
        <a href="<?= $e($urlDaftar) ?>" class="ed-back"><i class="fas fa-arrow-left mr-2"></i>Kembali</a>
    </div>

    <div class="ed-banner">
        <span><i class="fas fa-user-edit"></i>Anda sedang mengubah data <strong><?= $e($s['nama']) ?></strong> <small>(NISN: <?= $e($s['nisn']) ?>)</small></span>
        <span class="ed-pill<?= $s['status'] === 'Selesai' ? ' selesai' : '' ?>"><?= $s['status'] === 'Selesai' ? 'Selesai PKL' : 'Aktif PKL' ?></span>
    </div>

    <form id="edForm" action="#" method="post" novalidate>
        <div class="ed-card">

            <div class="ed-sec">1. Informasi Pribadi &amp; Akademik Siswa</div>
            <div class="row">
                <div class="col-12 form-group">
                    <label class="ed-label" for="nisn">NISN / NIS <span class="req">*</span></label>
                    <input class="ed-input" type="text" id="nisn" name="nisn" value="<?= $e($s['nisn'] . ' / ' . $s['nis']) ?>" readonly>
                </div>
                <div class="col-12 form-group">
                    <label class="ed-label" for="nama">Nama Lengkap Siswa <span class="req">*</span></label>
                    <input class="ed-input" type="text" id="nama" name="nama" value="<?= $e($s['nama']) ?>" required>
                </div>
                <div class="col-md-6 form-group">
                    <label class="ed-label" for="kelas">Kelas &amp; Konsentrasi Keahlian <span class="req">*</span></label>
                    <select class="ed-input" id="kelas" name="kelas" required>
                        <?php foreach ($opsiKelas as $o): ?><option<?= $pil($o, $s['kelas']) ?>><?= $e($o) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 form-group">
                    <label class="ed-label" for="jk">Jenis Kelamin <span class="req">*</span></label>
                    <select class="ed-input" id="jk" name="jk" required>
                        <option<?= $pil('Laki-laki', $s['jk']) ?>>Laki-laki</option>
                        <option<?= $pil('Perempuan', $s['jk']) ?>>Perempuan</option>
                    </select>
                </div>
                <div class="col-md-6 form-group">
                    <label class="ed-label" for="wa">Nomor WhatsApp Siswa <span class="req">*</span></label>
                    <input class="ed-input" type="tel" id="wa" name="wa" value="<?= $e($s['wa']) ?>" required>
                </div>
                <div class="col-md-6 form-group">
                    <label class="ed-label" for="email">Email Siswa <span class="opt">(Opsional)</span></label>
                    <input class="ed-input" type="email" id="email" name="email" value="<?= $e($s['email']) ?>">
                </div>
            </div>

            <hr class="ed-divider">

            <div class="ed-sec">2. Penempatan Instansi &amp; Pembimbing</div>
            <div class="row">
                <div class="col-md-4 form-group">
                    <label class="ed-label" for="instansi">Instansi Mitra DU-DI <span class="req">*</span></label>
                    <select class="ed-input" id="instansi" name="instansi" required>
                        <?php foreach ($opsiInstansi as $o): ?><option<?= $pil($o, $s['instansi']) ?>><?= $e($o) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 form-group">
                    <label class="ed-label" for="pic">Pembimbing Lapangan (PIC DU-DI) <span class="req">*</span></label>
                    <input class="ed-input" type="text" id="pic" name="pic" value="<?= $e($s['pic']) ?>" required>
                </div>
                <div class="col-md-4 form-group">
                    <label class="ed-label" for="guru">Guru Pembimbing Sekolah <span class="req">*</span></label>
                    <select class="ed-input" id="guru" name="guru" required>
                        <?php foreach ($opsiGuru as $o): ?><option<?= $pil($o, $s['guru']) ?>><?= $e($o) ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>

            <hr class="ed-divider">

            <div class="ed-sec">3. Periode Magang &amp; Kelengkapan Berkas</div>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label class="ed-label" for="mulai">Periode PKL (Mulai – Selesai) <span class="req">*</span></label>
                    <div class="ed-period">
                        <input class="ed-input" type="date" id="mulai" name="mulai" value="<?= $e($s['mulai']) ?>" required>
                        <span>s/d</span>
                        <input class="ed-input" type="date" id="selesai" name="selesai" value="<?= $e($s['selesai']) ?>" required aria-label="Tanggal selesai">
                    </div>
                </div>
                <div class="col-md-6 form-group">
                    <label class="ed-label" for="status">Status Bimbingan PKL <span class="req">*</span></label>
                    <select class="ed-input" id="status" name="status" required>
                        <?php foreach ($opsiStatus as $o): ?><option<?= $pil($o, $statusDipilih) ?>><?= $e($o) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <div class="ed-note"><i class="fas fa-info-circle mt-1"></i><span>Siswa telah terdaftar di sistem presensi geofencing instansi terkait. Kuota pembimbingan aktif.</span></div>
                </div>
            </div>

            <div class="ed-foot">
                <a href="<?= $e($urlDaftar) ?>" class="ed-btn ed-btn-cancel">Batal</a>
                <button type="submit" class="ed-btn ed-btn-save">Simpan Perubahan</button>
            </div>
            <div class="ed-toast" id="edToast" role="status">Tampilan saja: perubahan belum disimpan ke database.</div>
        </div>
    </form>
</div>

<script>
    window.addEventListener('load', function () {
        var form = document.getElementById('edForm');
        form.addEventListener('submit', function (ev) {
            ev.preventDefault();
            document.getElementById('edToast').style.display = 'none';
            var mulai = document.getElementById('mulai').value, selesai = document.getElementById('selesai').value;
            document.getElementById('selesai').setCustomValidity(mulai && selesai && selesai < mulai ? 'Tanggal selesai tidak boleh sebelum tanggal mulai.' : '');
            if (!form.checkValidity()) { form.reportValidity(); return; }
            document.getElementById('edToast').style.display = 'block';
        });
        document.getElementById('selesai').addEventListener('input', function () { this.setCustomValidity(''); });
    });
</script>
<?php
$content = ob_get_clean();

require dirname(__DIR__, 2) . '/layouts/admin.php';