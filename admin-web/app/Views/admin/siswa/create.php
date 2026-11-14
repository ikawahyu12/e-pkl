<?php
require_once __DIR__ . '/../../../../../config/koneksi.php';
require_once dirname(__DIR__, 3) . '/Support/Csrf.php';

if (!isset($assetBaseUrl)) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    if (preg_match('#^(.*?)/app/Views(?:/.*)?$#', $scriptDir, $m)) {
        $assetBaseUrl = $m[1] . '/public/assets';
    }
}

$urlDaftar  = '?page=data-siswa';
$pesanError = '';
$csrfToken  = AdminCsrf::token();
$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

// ---------- Proses simpan ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!AdminCsrf::verify($_POST['csrf_token'] ?? null)) {
        $pesanError = 'Formulir kedaluwarsa atau tidak valid. Muat ulang halaman dan coba lagi.';
    } else {
        $nisn         = trim((string) ($_POST['nisn'] ?? ''));
        $nis          = trim((string) ($_POST['nis'] ?? ''));
        $namaSiswa    = trim((string) ($_POST['nama'] ?? ''));
        $username     = trim((string) ($_POST['username'] ?? ''));
        $email        = trim((string) ($_POST['email'] ?? ''));
        $jenisKelamin = (string) ($_POST['jk'] ?? '');
        $noHp         = trim((string) ($_POST['no_hp'] ?? ''));
        $alamat       = trim((string) ($_POST['alamat'] ?? ''));
        $namaOrtu     = trim((string) ($_POST['ortu'] ?? ''));
        $hpOrtu       = trim((string) ($_POST['tlp_ortu'] ?? ''));
        $statusSiswa  = (string) ($_POST['status'] ?? 'aktif');


        if (
            $nisn === '' || $namaSiswa === '' || $username === ''
            || !in_array($jenisKelamin, ['L', 'P'], true)
            || !in_array($statusSiswa, ['aktif', 'lulus', 'keluar'], true)
        ) {
            $pesanError = 'Lengkapi NISN, nama, username, dan jenis kelamin dengan benar.';
        } elseif (
            mb_strlen($nisn) > 20 || mb_strlen($nis) > 30 || mb_strlen($namaSiswa) > 120
            || mb_strlen($username) > 60 || mb_strlen($email) > 150 || mb_strlen($noHp) > 25
            || mb_strlen($namaOrtu) > 120 || mb_strlen($hpOrtu) > 25
        ) {
            $pesanError = 'Panjang salah satu isian melebihi batas.';
        } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $pesanError = 'Masukkan email yang valid atau kosongkan kolom email.';
        } else {
            $transactionStarted = false;
            try {
                mysqli_begin_transaction($koneksi);
                $transactionStarted = true;

                $roleName = 'siswa';
                $rs = mysqli_prepare($koneksi, 'SELECT id FROM roles WHERE LOWER(nama) = ? LIMIT 1');
                mysqli_stmt_bind_param($rs, 's', $roleName);
                mysqli_stmt_execute($rs);
                mysqli_stmt_bind_result($rs, $roleId);
                $roleFound = mysqli_stmt_fetch($rs);
                mysqli_stmt_close($rs);

                if (!$roleFound) {
                    mysqli_rollback($koneksi);
                    $transactionStarted = false;
                    $pesanError = 'Role Siswa belum tersedia di database. Data tidak disimpan.';
                } else {
                    $emailValue   = $email === '' ? null : $email;
                    // Password acak yang tidak diketahui siapa pun; admin mengatur password lewat Manajemen User
                    $passwordHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
                    $statusAkun   = $statusSiswa === 'keluar' ? 'nonaktif' : 'aktif';
                    $wajibGanti   = 1;

                    $us = mysqli_prepare(
                        $koneksi,
                        'INSERT INTO users (role_id, username, email, password_hash, nama_tampilan, status, wajib_ganti_password)
                         VALUES (?, ?, ?, ?, ?, ?, ?)'
                    );
                    mysqli_stmt_bind_param($us, 'isssssi', $roleId, $username, $emailValue, $passwordHash, $namaSiswa, $statusAkun, $wajibGanti);
                    mysqli_stmt_execute($us);
                    $userId = mysqli_insert_id($koneksi);
                    mysqli_stmt_close($us);

                    $nisValue = $nis === '' ? null : $nis;
                    $ss = mysqli_prepare(
                        $koneksi,
                        'INSERT INTO siswa (user_id, nisn, nis, nama_lengkap, jenis_kelamin, no_hp,
                                            alamat, nama_orang_tua, no_hp_orang_tua, status_siswa)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                    );
                    mysqli_stmt_bind_param($ss, 'isssssssss', $userId, $nisn, $nisValue, $namaSiswa,
                        $jenisKelamin, $noHp, $alamat, $namaOrtu, $hpOrtu, $statusSiswa);
                    mysqli_stmt_execute($ss);
                    mysqli_stmt_close($ss);

                    mysqli_commit($koneksi);
                    $transactionStarted = false;
                    header('Location: ' . $urlDaftar);
                    exit;
                }
            } catch (mysqli_sql_exception $ex) {
                if ($transactionStarted) { mysqli_rollback($koneksi); }
                error_log('Admin Siswa create failed: ' . $ex->getMessage());
                $pesanError = $ex->getCode() === 1062
                    ? 'NISN, NIS, username, atau email sudah terdaftar.'
                    : 'Data siswa gagal disimpan. Periksa kembali isian.';
            }
        }
    }
}

$pageTitle  = 'Tambah Data Siswa';
$activePage = 'data-siswa';

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
    .sf-sec { font-size: .68rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #94a3b8; margin: 0 0 1rem; }
    .sf-label { display: block; font-size: .64rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: #475569; margin-bottom: .4rem; }
    .sf-label .req { color: #e11d48; }
    .sf-ic { position: relative; }
    .sf-ic > i { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: .8rem; pointer-events: none; }
    .sf-ic > .sf-caret { left: auto; right: 13px; font-size: .7rem; }
    .sf-input { width: 100%; height: 40px; border: 1px solid #e2e8f0; border-radius: 10px; background: #fff; font-size: .8rem; color: #1e293b; padding: 0 .85rem; outline: 0; }
    .sf-ic .sf-input { padding-left: 36px; }
    .sf-input::placeholder { color: #94a3b8; }
    .sf-input:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.12); }
    select.sf-input { -webkit-appearance: none; -moz-appearance: none; appearance: none; padding-right: 34px; cursor: pointer; }
    select.sf-input option { color: #1e293b; }
    .sf-radios { display: flex; gap: .6rem; flex-wrap: wrap; }
    .sf-radio { flex: 1; display: flex; align-items: center; justify-content: space-between; gap: .4rem; min-height: 40px; border: 1px solid #e2e8f0; border-radius: 10px; padding: .35rem .75rem; margin: 0; cursor: pointer; font-size: .76rem; color: #334155; background: #fff; }
    .sf-radio input { accent-color: #2563eb; margin: 0 .5rem 0 0; }
    .sf-radio .txt { display: flex; align-items: center; }
    .sf-radio.on { border-color: #2563eb; background: #fafcff; }
    .sf-foot { display: flex; justify-content: flex-end; gap: .6rem; margin-top: 1.5rem; }
    .sf-btn { height: 36px; border-radius: 9px; font-size: .76rem; font-weight: 600; padding: 0 1.15rem; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; cursor: pointer; }
    .sf-btn-cancel { background: #fff; color: #334155; border: 1px solid #e2e8f0; }
    .sf-btn-cancel:hover { background: #f8fafc; color: #334155; text-decoration: none; }
    .sf-btn-save { background: #1d56c9; color: #fff; border: 0; }
    .sf-btn-save:hover { background: #1948a8; }
    @media (max-width: 575.98px) { .sf-foot .sf-btn { flex: 1; } .sf-card { padding: 1.1rem; } }
</style>

<div class="sf">
    <div class="sf-crumb">
        <a href="<?= $e($urlDaftar) ?>">Data Siswa</a><span class="sep">&gt;</span><span class="now">Tambah Siswa Baru</span>
    </div>
    <h1 class="sf-title">Tambah Data Siswa</h1>
    <p class="sf-sub">Formulir pendaftaran siswa baru. Akun login dibuat otomatis, password diatur lewat Manajemen User.</p>

    <?php if ($pesanError): ?>
        <div class="alert alert-danger border-0 rounded-lg p-3 mb-3 small">
            <i class="fas fa-exclamation-circle mr-2"></i><?= $e($pesanError) ?>
        </div>
    <?php endif; ?>

    <form id="sfForm" action="" method="post" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
        <div class="sf-card">

            <div class="sf-sec">1. Akun Login</div>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label class="sf-label" for="username">Username <span class="req">*</span></label>
                    <input class="sf-input" type="text" id="username" name="username" maxlength="60" autocomplete="off" placeholder="Contoh: ahmad.rizky" value="<?= $e($_POST['username'] ?? '') ?>" required>
                </div>
                <div class="col-md-6 form-group">
                    <label class="sf-label" for="email">Email</label>
                    <input class="sf-input" type="email" id="email" name="email" maxlength="150" autocomplete="off" value="<?= $e($_POST['email'] ?? '') ?>">
                </div>
            </div>

            <hr>
            <div class="sf-sec">2. Data Siswa</div>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label class="sf-label" for="nisn">NISN <span class="req">*</span></label>
                    <input class="sf-input" type="text" id="nisn" name="nisn" maxlength="20" value="<?= $e($_POST['nisn'] ?? '') ?>" required>
                </div>
                <div class="col-md-6 form-group">
                    <label class="sf-label" for="nis">NIS</label>
                    <input class="sf-input" type="text" id="nis" name="nis" maxlength="30" inputmode="numeric" value="<?= $e($_POST['nis'] ?? '') ?>">
                </div>
                <div class="col-md-6 form-group">
                    <label class="sf-label" for="nama">Nama Lengkap Siswa <span class="req">*</span></label>
                    <div class="sf-ic"><i class="far fa-user"></i>
                        <input class="sf-input" type="text" id="nama" name="nama" maxlength="120" placeholder="Contoh: Ahmad Rizky Pratama" value="<?= $e($_POST['nama'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="col-md-6 form-group">
                    <label class="sf-label" for="jk">Jenis Kelamin <span class="req">*</span></label>
                    <div class="sf-ic"><i class="far fa-user"></i>
                        <select class="sf-input" id="jk" name="jk" required>
                            <option value="" disabled <?= ($_POST['jk'] ?? '') === '' ? 'selected' : '' ?>>Pilih Jenis Kelamin</option>
                            <option value="L" <?= ($_POST['jk'] ?? '') === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                            <option value="P" <?= ($_POST['jk'] ?? '') === 'P' ? 'selected' : '' ?>>Perempuan</option>
                        </select>
                        <i class="fas fa-chevron-down sf-caret"></i>
                    </div>
                </div>

                <div class="col-md-6 form-group">
                    <label class="sf-label" for="no_hp">No. Kontak / WhatsApp Siswa</label>
                    <div class="sf-ic"><i class="fas fa-phone-alt"></i>
                        <input class="sf-input" type="tel" id="no_hp" name="no_hp" maxlength="25" placeholder="Contoh: 0812-xxxx-xxxx" value="<?= $e($_POST['no_hp'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-6 form-group">
                    <label class="sf-label" for="alamat">Alamat Lengkap</label>
                    <div class="sf-ic"><i class="fas fa-map-marker-alt"></i>
                        <input class="sf-input" type="text" id="alamat" name="alamat" placeholder="Jalan, RT/RW, kelurahan, kecamatan, kota" value="<?= $e($_POST['alamat'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-6 form-group">
                    <label class="sf-label" for="ortu">Nama Orang Tua / Wali</label>
                    <div class="sf-ic"><i class="fas fa-user-friends"></i>
                        <input class="sf-input" type="text" id="ortu" name="ortu" maxlength="120" value="<?= $e($_POST['ortu'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-6 form-group">
                    <label class="sf-label" for="tlp_ortu">No. Telepon Orang Tua / Wali</label>
                    <div class="sf-ic"><i class="fas fa-phone-alt"></i>
                        <input class="sf-input" type="tel" id="tlp_ortu" name="tlp_ortu" maxlength="25" value="<?= $e($_POST['tlp_ortu'] ?? '') ?>">
                    </div>
                </div>

                <div class="col-12 form-group">
                    <span class="sf-label">Status Siswa <span class="req">*</span></span>
                    <div class="sf-radios">
                        <?php foreach (['aktif' => 'Aktif / Terdaftar', 'lulus' => 'Lulus', 'keluar' => 'Keluar'] as $val => $lab):
                            $cur = ($_POST['status'] ?? 'aktif') === $val; ?>
                            <label class="sf-radio <?= $cur ? 'on' : '' ?>">
                                <span class="txt"><input type="radio" name="status" value="<?= $val ?>" <?= $cur ? 'checked' : '' ?>> <?= $e($lab) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="sf-foot">
                <a href="<?= $e($urlDaftar) ?>" class="sf-btn sf-btn-cancel">Batal</a>
                <button type="submit" class="sf-btn sf-btn-save"><i class="fas fa-check mr-2"></i>Simpan Data Siswa</button>
            </div>
        </div>
    </form>
</div>

<script>
    window.addEventListener('load', function () {
        var $ = window.jQuery;

        $('.sf-radio input').on('change', function () {
            $('.sf-radio').removeClass('on');
            $(this).closest('.sf-radio').addClass('on');
        });
    });
</script>
<?php
$content = ob_get_clean();
require dirname(__DIR__, 2) . '/layouts/admin.php';