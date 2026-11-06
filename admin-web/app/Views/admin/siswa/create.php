<?php
/**
 * Admin > Data Siswa > Tambah Siswa Baru (Terhubung Database)
 * Letak file: app/Views/admin/siswa/create.php
 */

// 1. Panggil koneksi database
require_once __DIR__ . '/../../../../../config/koneksi.php';

// 2. Alamat asset
if (!isset($assetBaseUrl)) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    if (preg_match('#^(.*?)/app/Views(?:/.*)?$#', $scriptDir, $m)) {
        $assetBaseUrl = $m[1] . '/public/assets';
    }
}

$urlDaftar = '?page=data-siswa';
$pesanError = '';
$csrfHelper = dirname(__DIR__, 3) . '/Support/Csrf.php';
require_once $csrfHelper;
$csrfToken = AdminCsrf::token();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!AdminCsrf::verify($_POST['csrf_token'] ?? null)) {
        $pesanError = 'Formulir kedaluwarsa atau tidak valid. Muat ulang halaman dan coba lagi.';
    } else {
        $nisn = trim((string) ($_POST['nisn'] ?? ''));
        $nis = trim((string) ($_POST['nis'] ?? ''));
        $namaSiswa = trim((string) ($_POST['nama'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $kelasId = filter_var($_POST['kelas'] ?? null, FILTER_VALIDATE_INT);
        $jenisKelamin = (string) ($_POST['jk'] ?? '');
        $noHp = trim((string) ($_POST['no_hp'] ?? ''));
        $alamat = trim((string) ($_POST['alamat'] ?? ''));
        $namaOrangTua = trim((string) ($_POST['ortu'] ?? ''));
        $noHpOrangTua = trim((string) ($_POST['tlp_ortu'] ?? ''));
        $statusSiswa = (string) ($_POST['status'] ?? 'aktif');

        if (
            $nisn === ''
            || $namaSiswa === ''
            || $username === ''
            || $password === ''
            || $kelasId === false
            || $kelasId < 1
            || !in_array($jenisKelamin, ['L', 'P'], true)
            || !in_array($statusSiswa, ['aktif', 'lulus', 'keluar'], true)
        ) {
            $pesanError = 'Lengkapi NISN, nama, username, password, kelas, jenis kelamin, dan status dengan nilai yang valid.';
        } elseif (strlen($password) < 12) {
            $pesanError = 'Password awal harus terdiri dari minimal 12 karakter.';
        } elseif (
            mb_strlen($nisn, 'UTF-8') > 20
            || mb_strlen($nis, 'UTF-8') > 30
            || mb_strlen($namaSiswa, 'UTF-8') > 120
            || mb_strlen($username, 'UTF-8') > 60
            || mb_strlen($email, 'UTF-8') > 150
            || mb_strlen($noHp, 'UTF-8') > 25
            || mb_strlen($namaOrangTua, 'UTF-8') > 120
            || mb_strlen($noHpOrangTua, 'UTF-8') > 25
            || strlen($password) > 255
        ) {
            $pesanError = 'Panjang salah satu isian melebihi batas kolom database.';
        } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $pesanError = 'Masukkan alamat email yang valid atau kosongkan kolom email.';
        } else {
            $transactionStarted = false;

            try {
                mysqli_begin_transaction($koneksi);
                $transactionStarted = true;

                $roleName = 'siswa';
                $roleStatement = mysqli_prepare($koneksi, 'SELECT id FROM roles WHERE LOWER(nama) = ? LIMIT 1');
                mysqli_stmt_bind_param($roleStatement, 's', $roleName);
                mysqli_stmt_execute($roleStatement);
                mysqli_stmt_bind_result($roleStatement, $roleId);
                $roleFound = mysqli_stmt_fetch($roleStatement);
                mysqli_stmt_close($roleStatement);

                if (!$roleFound) {
                    mysqli_rollback($koneksi);
                    $transactionStarted = false;
                    $pesanError = 'Role Siswa belum tersedia di database. Data tidak disimpan.';
                } else {
                    $emailValue = $email === '' ? null : $email;
                    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
                    $namaTampilan = $namaSiswa;
                    $statusAkun = $statusSiswa === 'keluar' ? 'nonaktif' : 'aktif';
                    $wajibGantiPassword = 1;
                    $userStatement = mysqli_prepare(
                        $koneksi,
                        'INSERT INTO users (role_id, username, email, password_hash, nama_tampilan, status, wajib_ganti_password)
                         VALUES (?, ?, ?, ?, ?, ?, ?)'
                    );
                    mysqli_stmt_bind_param(
                        $userStatement,
                        'isssssi',
                        $roleId,
                        $username,
                        $emailValue,
                        $passwordHash,
                        $namaTampilan,
                        $statusAkun,
                        $wajibGantiPassword
                    );
                    mysqli_stmt_execute($userStatement);
                    $userId = mysqli_insert_id($koneksi);
                    mysqli_stmt_close($userStatement);

                    $nisValue = $nis === '' ? null : $nis;
                    $studentStatement = mysqli_prepare(
                        $koneksi,
                        'INSERT INTO siswa (
                            user_id, nisn, nis, nama_lengkap, kelas_id, jenis_kelamin, no_hp,
                            alamat, nama_orang_tua, no_hp_orang_tua, status_siswa
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                    );
                    mysqli_stmt_bind_param(
                        $studentStatement,
                        'isssissssss',
                        $userId,
                        $nisn,
                        $nisValue,
                        $namaSiswa,
                        $kelasId,
                        $jenisKelamin,
                        $noHp,
                        $alamat,
                        $namaOrangTua,
                        $noHpOrangTua,
                        $statusSiswa
                    );
                    mysqli_stmt_execute($studentStatement);
                    mysqli_stmt_close($studentStatement);

                    mysqli_commit($koneksi);
                    $transactionStarted = false;
                    header('Location: ' . $urlDaftar);
                    exit;
                }
            } catch (mysqli_sql_exception $exception) {
                if ($transactionStarted) {
                    mysqli_rollback($koneksi);
                }

                error_log('Admin Siswa create failed: ' . $exception->getMessage());
                $pesanError = $exception->getCode() === 1062
                    ? 'NISN, NIS, username, atau email sudah terdaftar.'
                    : 'Data Siswa gagal disimpan. Periksa kembali isian dan konfigurasi database.';
            }
        }
    }
}

// ==========================================
// AMBIL DATA DROPDOWN DARI DATABASE
// ==========================================
// Dropdown Kelas (dari tabel kelas)
$opsiKelas = [];
$qKelas = mysqli_query($koneksi, "SELECT id, nama FROM kelas ORDER BY nama ASC");
if ($qKelas) {
    while ($k = mysqli_fetch_assoc($qKelas)) {
        $opsiKelas[] = $k;
    }
}

$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

// 3. Variabel untuk layouts/admin.php
$pageTitle  = 'Tambah Data Siswa';
$activePage = 'siswa';

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
    select.sf-input:invalid { color: #94a3b8; }
    select.sf-input option { color: #1e293b; }

    .sf-radios { display: flex; gap: .6rem; }
    .sf-radio { flex: 1; display: flex; align-items: center; justify-content: space-between; gap: .4rem; min-height: 40px; border: 1px solid #e2e8f0; border-radius: 10px; padding: .35rem .75rem; margin: 0; cursor: pointer; font-size: .76rem; color: #334155; background: #fff; }
    .sf-radio input { accent-color: #2563eb; margin: 0 .5rem 0 0; }
    .sf-radio .txt { display: flex; align-items: center; }
    .sf-radio.on { border-color: #2563eb; background: #fafcff; }
    .sf-tag { font-size: .6rem; font-weight: 600; padding: .1rem .5rem; border-radius: 5px; border: 1px solid; white-space: nowrap; }
    .sf-tag-aktif { background: #ecfdf5; color: #059669; border-color: #a7f3d0; }
    .sf-tag-pending { background: #fffbeb; color: #d97706; border-color: #fde68a; }

    .sf-drop { border: 1.5px dashed #cbd5e1; border-radius: 14px; background: #fafcff; padding: 2rem 1rem; text-align: center; cursor: pointer; }
    .sf-drop.over { border-color: #2563eb; background: #eff6ff; }
    .sf-drop-ic { width: 40px; height: 40px; border-radius: 10px; background: #e6effc; color: #2563eb; display: inline-flex; align-items: center; justify-content: center; margin-bottom: .75rem; }
    .sf-drop-main { font-size: .82rem; color: #475569; }
    .sf-drop-main b { color: #2563eb; }
    .sf-drop-hint { font-size: .66rem; color: #94a3b8; margin-top: .3rem; }
    .sf-drop-file { font-size: .76rem; color: #059669; margin-top: .6rem; font-weight: 600; }

    .sf-foot { display: flex; justify-content: flex-end; gap: .6rem; margin-top: 1.5rem; }
    .sf-btn { height: 36px; border-radius: 9px; font-size: .76rem; font-weight: 600; padding: 0 1.15rem; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; cursor: pointer; }
    .sf-btn-cancel { background: #fff; color: #334155; border: 1px solid #e2e8f0; }
    .sf-btn-cancel:hover { background: #f8fafc; color: #334155; text-decoration: none; }
    .sf-btn-save { background: #1d56c9; color: #fff; border: 0; }
    .sf-btn-save:hover { background: #1948a8; }

    .sf-toast { display: none; margin-top: 1rem; padding: .65rem .9rem; border-radius: 10px; background: #ecfdf5; color: #047857; font-size: .78rem; }

    @media (max-width: 767.98px) {
        .sf-card { padding: 1.1rem; }
    }
    @media (max-width: 575.98px) {
        .sf-radios { flex-direction: column; }
        .sf-foot .sf-btn { flex: 1; }
    }
</style>

<div class="sf">
    <div class="sf-crumb">
        <a href="<?= $urlDaftar ?>">Data Siswa</a><span class="sep">&gt;</span><span class="now">Tambah Siswa Baru</span>
    </div>
    <h1 class="sf-title">Tambah Data Siswa</h1>
    <p class="sf-sub">Formulir pendaftaran data siswa baru untuk penempatan Praktik Kerja Lapangan (PKL)</p>

    <?php if ($pesanError): ?>
        <div class="alert alert-danger border-0 rounded-lg p-3 mb-3 small">
            <i class="fas fa-exclamation-circle mr-2"></i><?= $e($pesanError) ?>
        </div>
    <?php endif; ?>

    <form id="sfForm" action="" method="post">
        <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
        <div class="sf-card">
            <div class="row">
                <div class="col-md-6 form-group">
                    <label class="sf-label" for="nisn">NISN <span class="req">*</span></label>
                    <input class="sf-input" type="text" id="nisn" name="nisn" maxlength="20" value="<?= $e($_POST['nisn'] ?? '') ?>" required>
                </div>
                <div class="col-md-6 form-group">
                    <label class="sf-label" for="username">Username akun <span class="req">*</span></label>
                    <input class="sf-input" type="text" id="username" name="username" maxlength="60" value="<?= $e($_POST['username'] ?? '') ?>" required>
                </div>
                <div class="col-md-6 form-group">
                    <label class="sf-label" for="email">Email akun</label>
                    <input class="sf-input" type="email" id="email" name="email" maxlength="150" value="<?= $e($_POST['email'] ?? '') ?>">
                </div>
                <div class="col-md-6 form-group">
                    <label class="sf-label" for="password">Password awal <span class="req">*</span></label>
                    <input class="sf-input" type="password" id="password" name="password" minlength="12" maxlength="255" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label class="sf-label" for="nama">Nama Lengkap Siswa <span class="req">*</span></label>
                    <div class="sf-ic"><i class="far fa-user"></i>
                        <input class="sf-input" type="text" id="nama" name="nama" maxlength="120" placeholder="Contoh: Ahmad Rizky Pratama" value="<?= $e($_POST['nama'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="col-md-6 form-group">
                    <label class="sf-label" for="nis">NIS (Nomor Induk Siswa)</label>
                    <div class="sf-ic"><i class="fas fa-hashtag"></i>
                        <input class="sf-input" type="text" id="nis" name="nis" placeholder="Contoh: 0061829102" inputmode="numeric" maxlength="30" value="<?= $e($_POST['nis'] ?? '') ?>">
                    </div>
                </div>

                <div class="col-md-6 form-group">
                    <label class="sf-label" for="kelas">Kelas &amp; Jurusan <span class="req">*</span></label>
                    <div class="sf-ic"><i class="fas fa-layer-group"></i>
                        <select class="sf-input" id="kelas" name="kelas" required>
                            <option value="" selected disabled hidden>Pilih Jurusan &amp; Kelas</option>
                            <?php foreach ($opsiKelas as $k): ?>
                                <option value="<?= $k['id'] ?>" <?= (($_POST['kelas'] ?? '') == $k['id']) ? 'selected' : '' ?>>
                                    <?= $e($k['nama']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-chevron-down sf-caret"></i>
                    </div>
                </div>
                <div class="col-md-6 form-group">
                    <label class="sf-label" for="jk">Jenis Kelamin <span class="req">*</span></label>
                    <div class="sf-ic"><i class="far fa-user"></i>
                        <select class="sf-input" id="jk" name="jk" required>
                            <option value="" selected disabled hidden>Pilih Jenis Kelamin</option>
                            <option value="L" <?= (($_POST['jk'] ?? '') === 'L') ? 'selected' : '' ?>>Laki-laki</option>
                            <option value="P" <?= (($_POST['jk'] ?? '') === 'P') ? 'selected' : '' ?>>Perempuan</option>
                        </select>
                        <i class="fas fa-chevron-down sf-caret"></i>
                    </div>
                </div>

                <div class="col-md-6 form-group">
                    <label class="sf-label" for="no_hp">No. Kontak / WhatsApp Siswa <span class="req">*</span></label>
                    <div class="sf-ic"><i class="fas fa-phone-alt"></i>
                        <input class="sf-input" type="tel" id="no_hp" name="no_hp" maxlength="25" placeholder="Contoh: 0812-xxxx-xxxx" value="<?= $e($_POST['no_hp'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="col-12 form-group">
                    <label class="sf-label" for="alamat">Alamat Lengkap Siswa</label>
                    <div class="sf-ic"><i class="fas fa-map-marker-alt"></i>
                        <input class="sf-input" type="text" id="alamat" name="alamat" placeholder="Jalan, RT/RW, kelurahan, kecamatan, kota" value="<?= $e($_POST['alamat'] ?? '') ?>">
                    </div>
                </div>

                <div class="col-md-6 form-group">
                    <label class="sf-label" for="ortu">Nama Orang Tua / Wali</label>
                    <div class="sf-ic"><i class="fas fa-user-friends"></i>
                        <input class="sf-input" type="text" id="ortu" name="ortu" maxlength="120" placeholder="Nama lengkap ayah / ibu / wali" value="<?= $e($_POST['ortu'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-6 form-group">
                    <label class="sf-label" for="tlp_ortu">No. Telepon Orang Tua / Wali</label>
                    <div class="sf-ic"><i class="fas fa-phone-alt"></i>
                        <input class="sf-input" type="tel" id="tlp_ortu" name="tlp_ortu" maxlength="25" placeholder="Contoh: 0813-xxxx-xxxx" value="<?= $e($_POST['tlp_ortu'] ?? '') ?>">
                    </div>
                </div>

                <div class="col-md-6 form-group">
                    <span class="sf-label">Status Siswa <span class="req">*</span></span>
                    <div class="sf-radios">
                        <label class="sf-radio <?= (($_POST['status'] ?? 'aktif') === 'aktif') ? 'on' : '' ?>">
                            <span class="txt"><input type="radio" name="status" value="aktif" <?= (($_POST['status'] ?? 'aktif') === 'aktif') ? 'checked' : '' ?>> Aktif / Terdaftar</span>
                            <span class="sf-tag sf-tag-aktif">Aktif</span>
                        </label>
                        <label class="sf-radio <?= (($_POST['status'] ?? '') === 'lulus') ? 'on' : '' ?>">
                            <span class="txt"><input type="radio" name="status" value="lulus" <?= (($_POST['status'] ?? '') === 'lulus') ? 'checked' : '' ?>> Lulus</span>
                            <span class="sf-tag sf-tag-pending">Lulus</span>
                        </label>
                        <label class="sf-radio <?= (($_POST['status'] ?? '') === 'keluar') ? 'on' : '' ?>">
                            <span class="txt"><input type="radio" name="status" value="keluar" <?= (($_POST['status'] ?? '') === 'keluar') ? 'checked' : '' ?>> Keluar</span>
                            <span class="sf-tag sf-tag-pending">Keluar</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="sf-foot">
                <a href="<?= $urlDaftar ?>" class="sf-btn sf-btn-cancel">Batal</a>
                <button type="submit" class="sf-btn sf-btn-save"><i class="fas fa-check mr-2"></i>Simpan Data Siswa</button>
            </div>
        </div>
    </form>
</div>

<script>
    window.addEventListener('load', function () {
        var $ = window.jQuery;

        // Kartu status terpilih
        $('.sf-radio input').on('change', function () {
            $('.sf-radio').removeClass('on');
            $(this).closest('.sf-radio').addClass('on');
        });

    });
</script>
<?php
$content = ob_get_clean();

require dirname(__DIR__, 2) . '/layouts/admin.php';