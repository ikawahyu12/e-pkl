<?php
/**
 * Admin > Data Guru > Edit  (UI saja, belum menyimpan ke database)
 *
 * File ini berdiri sendiri. Satu-satunya yang dibutuhkan: app/Views/layouts/admin.php
 * Letak file: app/Views/admin/guru/edit.php
 * Data guru diambil dari array dummy berdasarkan ?id=  (contoh: ?page=edit&id=1)
 */

// 1. Alamat asset (dipakai hanya jika halaman dibuka langsung dari folder app/Views)
if (!isset($assetBaseUrl)) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    if (preg_match('#^(.*?)/app/Views(?:/.*)?$#', $scriptDir, $m)) {
        $assetBaseUrl = $m[1] . '/public/assets';
    }
}

// 2. Alamat tombol Kembali / Batal (daftar guru).
$urlDaftar = '?page=data-guru';

// 3. Data dummy guru
$guruList = [
    1 => ['nip' => '198804122015031001', 'nama' => 'Alfredo H., S.Kom', 'jk' => 'Laki-laki', 'wa' => '0812-9876-5432', 'email' => 'alfredo@smksalfattah.sch.id', 'jabatan' => 'Guru Pembimbing', 'jurusan' => 'Rekayasa Perangkat Lunak (RPL)', 'status' => 'Aktif'],
    2 => ['nip' => '197903152008012003', 'nama' => 'Dewi Sartika, M.Sn.', 'jk' => 'Perempuan', 'wa' => '0813-8765-4321', 'email' => 'dewi.sartika@smksalfattah.sch.id', 'jabatan' => 'Kajur / Guru Pembimbing', 'jurusan' => 'Desain Komunikasi Visual (DKV)', 'status' => 'Aktif'],
    3 => ['nip' => '198501202010011005', 'nama' => 'Eko Prasetyo, S.T.', 'jk' => 'Laki-laki', 'wa' => '0857-7654-3210', 'email' => 'eko.prasetyo@smksalfattah.sch.id', 'jabatan' => 'Guru Pembimbing', 'jurusan' => 'Teknik Komputer & Jaringan (TKJ)', 'status' => 'Aktif'],
    4 => ['nip' => '199008052019031009', 'nama' => 'Budi Santoso, S.Kom.', 'jk' => 'Laki-laki', 'wa' => '0821-6543-2109', 'email' => 'budi.santoso@smksalfattah.sch.id', 'jabatan' => 'Guru Pembimbing', 'jurusan' => 'Rekayasa Perangkat Lunak (RPL)', 'status' => 'Cuti'],
];
$id = (int) ($_GET['id'] ?? 1);
$g  = $guruList[$id] ?? $guruList[1];

$opsiJabatan = [
    'Guru Pembimbing',
    'Kajur / Guru Pembimbing',
    'Koordinator PKL',
];

$opsiJurusan = [
    'Rekayasa Perangkat Lunak (RPL)',
    'Teknik Komputer & Jaringan (TKJ)',
    'Desain Komunikasi Visual (DKV)',
    'Akuntansi dan Keuangan Lembaga (AKL)',
];

$opsiStatus = ['Aktif', 'Cuti', 'Nonaktif'];

$e   = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$pil = static fn ($a, $b): string => (string) $a === (string) $b ? ' selected' : '';

// 4. Variabel untuk layouts/admin.php
$pageTitle  = 'Edit Data Guru';
$activePage = 'guru'; // samakan dengan kunci menu "Data Guru" di sidebar.php

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
    .ed-pill.nonaktif { background: #fee2e2; color: #b91c1c; border-color: #fca5a5; }
    .ed-pill.cuti { background: #fef3c7; color: #b45309; border-color: #fde68a; }

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
        .ed-foot .ed-btn { flex: 1; }
    }
</style>

<div class="ed">
    <div class="ed-top">
        <div>
            <div class="ed-crumb">
                <a href="<?= $e($urlDaftar) ?>">Data Guru</a><span class="sep">/</span>Edit<span class="sep">/</span><span class="now"><?= $e($g['nama']) ?></span>
            </div>
            <h1 class="ed-title">Edit Data Guru</h1>
            <p class="ed-sub">Perbarui informasi data diri, jabatan, tugas pembimbingan, dan status keaktifan guru</p>
        </div>
        <a href="<?= $e($urlDaftar) ?>" class="ed-back"><i class="fas fa-arrow-left mr-2"></i>Kembali</a>
    </div>

    <div class="ed-banner">
        <span><i class="fas fa-user-edit"></i>Anda sedang mengubah data <strong><?= $e($g['nama']) ?></strong> <small>(NIP: <?= $e($g['nip']) ?>)</small></span>
        <?php 
            $statusClass = '';
            if ($g['status'] === 'Nonaktif') $statusClass = ' nonaktif';
            elseif ($g['status'] === 'Cuti') $statusClass = ' cuti';
        ?>
        <span class="ed-pill<?= $statusClass ?>"><?= $e($g['status']) ?></span>
    </div>

    <form id="edForm" action="#" method="post" novalidate>
        <div class="ed-card">

            <div class="ed-sec">1. Informasi Biodata &amp; Kontak Guru</div>
            <div class="row">
                <div class="col-12 form-group">
                    <label class="ed-label" for="nip">NIP / Nomor Induk Pegawai <span class="req">*</span></label>
                    <input class="ed-input" type="text" id="nip" name="nip" value="<?= $e($g['nip']) ?>" readonly>
                </div>
                <div class="col-12 form-group">
                    <label class="ed-label" for="nama">Nama Lengkap &amp; Gelar <span class="req">*</span></label>
                    <input class="ed-input" type="text" id="nama" name="nama" value="<?= $e($g['nama']) ?>" required>
                </div>
                <div class="col-md-4 form-group">
                    <label class="ed-label" for="jk">Jenis Kelamin <span class="req">*</span></label>
                    <select class="ed-input" id="jk" name="jk" required>
                        <option<?= $pil('Laki-laki', $g['jk']) ?>>Laki-laki</option>
                        <option<?= $pil('Perempuan', $g['jk']) ?>>Perempuan</option>
                    </select>
                </div>
                <div class="col-md-4 form-group">
                    <label class="ed-label" for="wa">Nomor WhatsApp Guru <span class="req">*</span></label>
                    <input class="ed-input" type="tel" id="wa" name="wa" value="<?= $e($g['wa']) ?>" required>
                </div>
                <div class="col-md-4 form-group">
                    <label class="ed-label" for="email">Email Guru <span class="req">*</span></label>
                    <input class="ed-input" type="email" id="email" name="email" value="<?= $e($g['email']) ?>" required>
                </div>
            </div>

            <hr class="ed-divider">

            <div class="ed-sec">2. Penugasan &amp; Status Bimbingan</div>
            <div class="row">
                <div class="col-md-4 form-group">
                    <label class="ed-label" for="jabatan">Jabatan Pembimbing <span class="req">*</span></label>
                    <select class="ed-input" id="jabatan" name="jabatan" required>
                        <?php foreach ($opsiJabatan as $o): ?><option<?= $pil($o, $g['jabatan']) ?>><?= $e($o) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 form-group">
                    <label class="ed-label" for="jurusan">Kompetensi Keahlian / Jurusan <span class="req">*</span></label>
                    <select class="ed-input" id="jurusan" name="jurusan" required>
                        <?php foreach ($opsiJurusan as $o): ?><option<?= $pil($o, $g['jurusan']) ?>><?= $e($o) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 form-group">
                    <label class="ed-label" for="status">Status Guru <span class="req">*</span></label>
                    <select class="ed-input" id="status" name="status" required>
                        <?php foreach ($opsiStatus as $o): ?><option<?= $pil($o, $g['status']) ?>><?= $e($o) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <div class="ed-note"><i class="fas fa-info-circle mt-1"></i><span>Perubahan status atau keahlian akan memengaruhi alokasi siswa bimbingan PKL yang ditugaskan ke guru ini.</span></div>
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
            if (!form.checkValidity()) { form.reportValidity(); return; }
            document.getElementById('edToast').style.display = 'block';
        });
    });
</script>
<?php
$content = ob_get_clean();

require dirname(__DIR__, 2) . '/layouts/admin.php';