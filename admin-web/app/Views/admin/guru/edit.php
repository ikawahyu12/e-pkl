<?php
require_once dirname(__DIR__, 3) . '/Support/AdminHttp.php';

if (!isset($assetBaseUrl)) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    if (preg_match('#^(.*?)/app/Views(?:/.*)?$#', $scriptDir, $m)) {
        $assetBaseUrl = $m[1] . '/public/assets';
    }
}

$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$pageTitle  = 'Edit Data Guru';
$activePage = 'data-guru';
$urlDaftar  = '?page=data-guru';

$id  = (int) ($_GET['id'] ?? 0);
$res = admin_api('GET', '/admin/guru/' . $id);

if ($res['status'] !== 200) {
    admin_flash_set('error', admin_errors($res));
    header('Location: ' . $urlDaftar);
    exit;
}

$g           = $res['body']['data'] ?? [];
$jurusanList = admin_api('GET', '/admin/jurusan')['body']['data'] ?? [];
$error       = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!guru_csrf_ok()) {
        $error = 'Sesi formulir tidak valid. Muat ulang halaman.';
    } else {
        $put = admin_api('PUT', '/admin/guru/' . $id, [
            'nama_lengkap'     => trim($_POST['nama_lengkap'] ?? ''),
            'nip'              => trim($_POST['nip'] ?? ''),
            'username'         => trim($_POST['username'] ?? ''),
            'email'            => trim($_POST['email'] ?? ''),
            'password'         => $_POST['password'] ?? '',
            'jenis_kelamin'    => $_POST['jenis_kelamin'] ?? null,
            'no_hp'            => trim($_POST['no_hp'] ?? ''),
            'alamat'           => trim($_POST['alamat'] ?? ''),
            'mata_pelajaran'   => trim($_POST['mata_pelajaran'] ?? ''),
            'jurusan_id'       => ($_POST['jurusan_id'] ?? '') !== '' ? (int) $_POST['jurusan_id'] : null,
            'status_bimbingan' => $_POST['status_bimbingan'] ?? 'aktif',
            'kuota_bimbingan'  => ($_POST['kuota_bimbingan'] ?? '') !== '' ? (int) $_POST['kuota_bimbingan'] : 0,
        ]);

        if ($put['status'] === 200) {
            admin_flash_set('success', 'Data guru berhasil diperbarui.');
            header('Location: ' . $urlDaftar);
            exit;
        }
        $error = admin_errors($put);
        $g = array_merge($g, $_POST); // pertahankan isian
    }
}

$v = static fn (string $k, string $d = ''): string => htmlspecialchars((string) ($g[$k] ?? $d), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$aktif = ($g['status_bimbingan'] ?? 'aktif') === 'aktif';

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
                <a href="<?= $e($urlDaftar) ?>">Data Guru</a><span class="sep">/</span>Edit<span class="sep">/</span><span class="now"><?= $v('nama_lengkap') ?></span>
            </div>
            <h1 class="ed-title">Edit Data Guru</h1>
            <p class="ed-sub">Perbarui data diri, akun login, dan status bimbingan guru</p>
        </div>
        <a href="<?= $e($urlDaftar) ?>" class="ed-back"><i class="fas fa-arrow-left mr-2"></i>Kembali</a>
    </div>

    <div class="ed-banner">
        <span><i class="fas fa-user-edit"></i>Anda sedang mengubah data <strong><?= $v('nama_lengkap') ?></strong> <small>(NIP: <?= $v('nip', '-') ?>)</small></span>
        <span class="ed-pill<?= $aktif ? '' : ' nonaktif' ?>"><?= $aktif ? 'Aktif' : 'Non-Aktif' ?></span>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="?page=edit-guru&id=<?= $id ?>">
        <input type="hidden" name="csrf" value="<?= $e(guru_csrf()) ?>">
        <div class="ed-card">

            <div class="ed-sec">1. Biodata &amp; Kontak</div>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label class="ed-label" for="nama_lengkap">Nama Lengkap &amp; Gelar <span class="req">*</span></label>
                    <input class="ed-input" type="text" id="nama_lengkap" name="nama_lengkap" value="<?= $v('nama_lengkap') ?>" required>
                </div>
                <div class="col-md-6 form-group">
                    <label class="ed-label" for="nip">NIP / NUPTK</label>
                    <input class="ed-input" type="text" id="nip" name="nip" maxlength="30" value="<?= $v('nip') ?>">
                </div>
                <div class="col-md-4 form-group">
                    <label class="ed-label" for="jenis_kelamin">Jenis Kelamin</label>
                    <select class="ed-input" id="jenis_kelamin" name="jenis_kelamin">
                        <option value="">Pilih</option>
                        <option value="L" <?= ($g['jenis_kelamin'] ?? '') === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                        <option value="P" <?= ($g['jenis_kelamin'] ?? '') === 'P' ? 'selected' : '' ?>>Perempuan</option>
                    </select>
                </div>
                <div class="col-md-4 form-group">
                    <label class="ed-label" for="no_hp">No. HP / WhatsApp</label>
                    <input class="ed-input" type="tel" id="no_hp" name="no_hp" value="<?= $v('no_hp') ?>">
                </div>
                <div class="col-md-4 form-group">
                    <label class="ed-label" for="alamat">Alamat</label>
                    <input class="ed-input" type="text" id="alamat" name="alamat" value="<?= $v('alamat') ?>">
                </div>
            </div>

            <hr class="ed-divider">

            <div class="ed-sec">2. Akun Login</div>
            <div class="row">
                <div class="col-md-4 form-group">
                    <label class="ed-label" for="username">Username <span class="req">*</span></label>
                    <input class="ed-input" type="text" id="username" name="username" value="<?= $v('username') ?>" required>
                </div>
                <div class="col-md-4 form-group">
                    <label class="ed-label" for="email">Email <span class="req">*</span></label>
                    <input class="ed-input" type="email" id="email" name="email" value="<?= $v('email') ?>" required>
                </div>
                <div class="col-md-4 form-group">
                    <label class="ed-label" for="password">Password Baru <span class="opt">(kosongkan jika tidak diubah)</span></label>
                    <input class="ed-input" type="password" id="password" name="password" minlength="8" autocomplete="new-password">
                </div>
            </div>

            <hr class="ed-divider">

            <div class="ed-sec">3. Penugasan &amp; Status Bimbingan</div>
            <div class="row">
                <div class="col-md-3 form-group">
                    <label class="ed-label" for="mata_pelajaran">Mata Pelajaran</label>
                    <input class="ed-input" type="text" id="mata_pelajaran" name="mata_pelajaran" value="<?= $v('mata_pelajaran') ?>">
                </div>
                <div class="col-md-3 form-group">
                    <label class="ed-label" for="jurusan_id">Jurusan</label>
                    <select class="ed-input" id="jurusan_id" name="jurusan_id">
                        <option value="">Pilih Jurusan</option>
                        <?php foreach ($jurusanList as $j): ?>
                            <option value="<?= (int) $j['id'] ?>" <?= (string) ($g['jurusan_id'] ?? '') === (string) $j['id'] ? 'selected' : '' ?>>
                                <?= $e($j['kode'] . ' - ' . $j['nama']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 form-group">
                    <label class="ed-label" for="kuota_bimbingan">Kuota Bimbingan</label>
                    <input class="ed-input" type="number" min="0" max="100" id="kuota_bimbingan" name="kuota_bimbingan" value="<?= $v('kuota_bimbingan', '0') ?>">
                </div>
                <div class="col-md-3 form-group">
                    <label class="ed-label" for="status_bimbingan">Status</label>
                    <select class="ed-input" id="status_bimbingan" name="status_bimbingan">
                        <option value="aktif" <?= $aktif ? 'selected' : '' ?>>Aktif</option>
                        <option value="nonaktif" <?= !$aktif ? 'selected' : '' ?>>Non-Aktif</option>
                    </select>
                </div>
            </div>

            <div class="ed-foot">
                <a href="<?= $e($urlDaftar) ?>" class="ed-btn ed-btn-cancel">Batal</a>
                <button type="submit" class="ed-btn ed-btn-save">Simpan Perubahan</button>
            </div>
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