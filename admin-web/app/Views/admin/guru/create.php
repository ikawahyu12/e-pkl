<?php
require_once dirname(__DIR__, 3) . '/Support/AdminHttp.php';

if (!isset($assetBaseUrl)) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    if (preg_match('#^(.*?)/app/Views(?:/.*)?$#', $scriptDir, $m)) {
        $assetBaseUrl = $m[1] . '/public/assets';
    }
}

$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$pageTitle  = 'Tambah Data Guru';
$activePage = 'data-guru';
$urlDaftar  = '?page=data-guru';

$jurusanList = admin_api('GET', '/admin/jurusan')['body']['data'] ?? [];
$old   = [];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;

    if (!guru_csrf_ok()) {
        $error = 'Sesi formulir tidak valid. Muat ulang halaman.';
    } else {
        $res = admin_api('POST', '/admin/guru', [
            'nama_lengkap'     => trim($_POST['nama_lengkap'] ?? ''),
            'nip'              => trim($_POST['nip'] ?? ''),
            'username'         => trim($_POST['username'] ?? ''),
            'email'            => trim($_POST['email'] ?? ''),
            'password'         => $_POST['password'] ?? '',
            'jenis_kelamin'    => ($_POST['jenis_kelamin'] ?? '') !== '' ? $_POST['jenis_kelamin'] : null,
            'no_hp'            => trim($_POST['no_hp'] ?? ''),
            'alamat'           => trim($_POST['alamat'] ?? ''),
            'jurusan_id'       => ($_POST['jurusan_id'] ?? '') !== '' ? (int) $_POST['jurusan_id'] : null,
            'status_bimbingan' => $_POST['status_bimbingan'] ?? 'aktif',
            'kuota_bimbingan'  => ($_POST['kuota_bimbingan'] ?? '') !== '' ? (int) $_POST['kuota_bimbingan'] : 0,
        ]);

        if ($res['status'] === 201) {
            admin_flash_set('success', 'Guru berhasil ditambahkan.');
            header('Location: ' . $urlDaftar);
            exit;
        }
        $error = admin_errors($res);
    }
}

$o = static fn (string $k, string $d = ''): string => $e($old[$k] ?? $d);

ob_start();
?>
<style>
    .page-heading { display: none !important; }

    .sf-crumb { font-size: .72rem; color: #64748b; margin-bottom: .5rem; }
    .sf-crumb a { color: #64748b; text-decoration: none; }
    .sf-crumb .now { color: #2563eb; font-weight: 700; }
    .sf-crumb .sep { margin: 0 .35rem; }
    .sf-title { font-size: 1.45rem; font-weight: 800; color: #0f1d3a; margin: 0; }
    .sf-sub { font-size: .82rem; color: #64748b; margin: .25rem 0 1.25rem; }

    .sf-card { background: #fff; border-radius: 16px; box-shadow: 0 1px 3px rgba(16,24,40,.07); padding: 1.5rem; }
    .sf-label { display: block; font-size: .64rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: #475569; margin-bottom: .4rem; }
    .sf-label .req { color: #e11d48; }

    .sf-ic { position: relative; }
    .sf-ic > i { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: .8rem; pointer-events: none; }
    .sf-ic > .sf-caret { left: auto; right: 13px; font-size: .7rem; }
    .sf-input { width: 100%; height: 40px; border: 1px solid #e2e8f0; border-radius: 10px; background: #fff; font-size: .8rem; color: #1e293b; padding: 0 .85rem 0 36px; outline: 0; }
    .sf-input::placeholder { color: #94a3b8; }
    .sf-input:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.12); }
    select.sf-input { -webkit-appearance: none; -moz-appearance: none; appearance: none; padding-right: 34px; cursor: pointer; }
    select.sf-input option { color: #1e293b; }

    .sf-foot { display: flex; justify-content: flex-end; gap: .6rem; margin-top: 1.5rem; }
    .sf-btn { height: 36px; border-radius: 9px; font-size: .76rem; font-weight: 600; padding: 0 1.15rem; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; cursor: pointer; }
    .sf-btn-cancel { background: #fff; color: #334155; border: 1px solid #e2e8f0; }
    .sf-btn-cancel:hover { background: #f8fafc; color: #334155; text-decoration: none; }
    .sf-btn-save { background: #1d56c9; color: #fff; border: 0; }
    .sf-btn-save:hover { background: #1948a8; }

    @media (max-width: 767.98px) { .sf-card { padding: 1.1rem; } }
    @media (max-width: 575.98px) { .sf-foot .sf-btn { flex: 1; } }
</style>

<div class="sf">
    <div class="sf-crumb">
        <a href="<?= $e($urlDaftar) ?>">Data Guru</a><span class="sep">&gt;</span><span class="now">Tambah Guru Baru</span>
    </div>
    <h1 class="sf-title">Tambah Data Guru</h1>
    <p class="sf-sub">Formulir pendataan guru pembimbing. Akun login guru dibuat otomatis dari data ini.</p>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="?page=create-guru" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= $e(guru_csrf()) ?>">
        <div class="sf-card">
            <div class="row">
                <div class="col-md-6 form-group">
                    <label class="sf-label" for="nama_lengkap">Nama Lengkap &amp; Gelar <span class="req">*</span></label>
                    <div class="sf-ic"><i class="far fa-user"></i>
                        <input class="sf-input" type="text" id="nama_lengkap" name="nama_lengkap" value="<?= $o('nama_lengkap') ?>" required>
                    </div>
                </div>
                <div class="col-md-6 form-group">
                    <label class="sf-label" for="nip">NIP / NUPTK</label>
                    <div class="sf-ic"><i class="fas fa-id-card"></i>
                        <input class="sf-input" type="text" id="nip" name="nip" maxlength="30" value="<?= $o('nip') ?>">
                    </div>
                </div>

                <div class="col-md-6 form-group">
                    <label class="sf-label" for="username">Username Login <span class="req">*</span></label>
                    <div class="sf-ic"><i class="fas fa-user-tag"></i>
                        <input class="sf-input" type="text" id="username" name="username" value="<?= $o('username') ?>" autocomplete="off" placeholder="Contoh: guru.budi" required>
                    </div>
                </div>
                <div class="col-md-6 form-group">
                    <label class="sf-label" for="password">Password Awal <span class="req">*</span></label>
                    <div class="sf-ic"><i class="fas fa-lock"></i>
                        <input class="sf-input" type="password" id="password" name="password" minlength="8" autocomplete="new-password" placeholder="Minimal 8 karakter" required>
                    </div>
                </div>

                <div class="col-md-6 form-group">
                    <label class="sf-label" for="email">Email <span class="req">*</span></label>
                    <div class="sf-ic"><i class="far fa-envelope"></i>
                        <input class="sf-input" type="email" id="email" name="email" value="<?= $o('email') ?>" autocomplete="off" required>
                    </div>
                </div>
                <div class="col-md-6 form-group">
                    <label class="sf-label" for="no_hp">No. Kontak / WhatsApp</label>
                    <div class="sf-ic"><i class="fas fa-phone-alt"></i>
                        <input class="sf-input" type="tel" id="no_hp" name="no_hp" value="<?= $o('no_hp') ?>">
                    </div>
                </div>

                <div class="col-md-6 form-group">
                    <label class="sf-label" for="jenis_kelamin">Jenis Kelamin</label>
                    <div class="sf-ic"><i class="far fa-user"></i>
                        <select class="sf-input" id="jenis_kelamin" name="jenis_kelamin">
                            <option value="">Pilih</option>
                            <option value="L" <?= ($old['jenis_kelamin'] ?? '') === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                            <option value="P" <?= ($old['jenis_kelamin'] ?? '') === 'P' ? 'selected' : '' ?>>Perempuan</option>
                        </select>
                        <i class="fas fa-chevron-down sf-caret"></i>
                    </div>
                </div>
                <div class="col-md-6 form-group">
                    <label class="sf-label" for="jurusan_id">Jurusan</label>
                    <div class="sf-ic"><i class="fas fa-graduation-cap"></i>
                        <select class="sf-input" id="jurusan_id" name="jurusan_id">
                            <option value="">Pilih Jurusan</option>
                            <?php foreach ($jurusanList as $j): ?>
                                <option value="<?= (int) $j['id'] ?>" <?= (string) ($old['jurusan_id'] ?? '') === (string) $j['id'] ? 'selected' : '' ?>>
                                    <?= $e($j['kode'] . ' - ' . $j['nama']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-chevron-down sf-caret"></i>
                    </div>
                </div>

                <div class="col-md-6 form-group">
                    <label class="sf-label" for="kuota_bimbingan">Kuota Bimbingan (siswa)</label>
                    <div class="sf-ic"><i class="fas fa-users"></i>
                        <input class="sf-input" type="number" min="0" max="100" id="kuota_bimbingan" name="kuota_bimbingan" value="<?= $o('kuota_bimbingan', '0') ?>">
                    </div>
                </div>
                <div class="col-md-6 form-group">
                    <label class="sf-label" for="status_bimbingan">Status</label>
                    <div class="sf-ic"><i class="fas fa-toggle-on"></i>
                        <select class="sf-input" id="status_bimbingan" name="status_bimbingan">
                            <option value="aktif" <?= ($old['status_bimbingan'] ?? 'aktif') === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="nonaktif" <?= ($old['status_bimbingan'] ?? '') === 'nonaktif' ? 'selected' : '' ?>>Non-Aktif</option>
                        </select>
                        <i class="fas fa-chevron-down sf-caret"></i>
                    </div>
                </div>

                <div class="col-12 form-group">
                    <label class="sf-label" for="alamat">Alamat</label>
                    <div class="sf-ic"><i class="fas fa-map-marker-alt"></i>
                        <input class="sf-input" type="text" id="alamat" name="alamat" value="<?= $o('alamat') ?>">
                    </div>
                </div>
            </div>

            <div class="sf-foot">
                <a href="<?= $e($urlDaftar) ?>" class="sf-btn sf-btn-cancel">Batal</a>
                <button type="submit" class="sf-btn sf-btn-save"><i class="fas fa-check mr-2"></i>Simpan Data Guru</button>
            </div>
        </div>
    </form>
</div>
<?php
$content = ob_get_clean();

require dirname(__DIR__, 2) . '/layouts/admin.php';