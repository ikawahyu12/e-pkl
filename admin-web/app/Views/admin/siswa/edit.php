<?php
require_once __DIR__ . '/../../../../../config/koneksi.php';
require_once dirname(__DIR__, 3) . '/Support/Csrf.php';

$urlDaftar = '?page=data-siswa';
$id        = (int) ($_GET['id'] ?? 0);
$csrfToken = AdminCsrf::token();
$e   = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

if ($id <= 0) {
    echo "<script>alert('ID Siswa tidak valid!'); window.location='$urlDaftar';</script>";
    exit;
}

// ---------- Data siswa ----------
$st = mysqli_prepare($koneksi, 'SELECT s.* FROM siswa s WHERE s.id = ? LIMIT 1');
mysqli_stmt_bind_param($st, 'i', $id);
mysqli_stmt_execute($st);
$res = mysqli_stmt_get_result($st);
$s   = $res ? mysqli_fetch_assoc($res) : null;
mysqli_stmt_close($st);

if (!$s) {
    echo "<script>alert('Data siswa tidak ditemukan!'); window.location='$urlDaftar';</script>";
    exit;
}

// ---------- Simpan ----------
$pesanError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!AdminCsrf::verify($_POST['csrf_token'] ?? null)) {
        $pesanError = 'Formulir kedaluwarsa atau tidak valid. Muat ulang halaman dan coba lagi.';
    } else {
        $nis          = trim((string) ($_POST['nis'] ?? ''));
        $namaSiswa    = trim((string) ($_POST['nama'] ?? ''));
        $jenisKelamin = (string) ($_POST['jk'] ?? '');
        $noHp         = trim((string) ($_POST['wa'] ?? ''));
        $alamat       = trim((string) ($_POST['alamat'] ?? ''));
        $statusSiswa  = (string) ($_POST['status'] ?? '');

        if (
            $namaSiswa === ''
            || !in_array($jenisKelamin, ['L', 'P'], true)
            || !in_array($statusSiswa, ['aktif', 'lulus', 'keluar'], true)
        ) {
            $pesanError = 'Lengkapi nama, jenis kelamin, dan status dengan benar.';
        } elseif (mb_strlen($nis) > 30 || mb_strlen($namaSiswa) > 120 || mb_strlen($noHp) > 25) {
            $pesanError = 'Panjang salah satu isian melebihi batas.';
        } else {
            $transactionStarted = false;
            try {
                mysqli_begin_transaction($koneksi);
                $transactionStarted = true;

                $nisValue = $nis === '' ? null : $nis;
                $up = mysqli_prepare(
                    $koneksi,
                    'UPDATE siswa SET nis = ?, nama_lengkap = ?, jenis_kelamin = ?, no_hp = ?,
                                      alamat = ?, status_siswa = ? WHERE id = ?'
                );
                mysqli_stmt_bind_param($up, 'ssssssi', $nisValue, $namaSiswa, $jenisKelamin, $noHp, $alamat, $statusSiswa, $id);
                mysqli_stmt_execute($up);
                mysqli_stmt_close($up);

                // Nama tampilan akun ikut berubah; status akun mengikuti status siswa
                $akunStatus = $statusSiswa === 'keluar' ? 'nonaktif' : 'aktif';
                $us = mysqli_prepare(
                    $koneksi,
                    'UPDATE users INNER JOIN siswa ON siswa.user_id = users.id
                     SET users.nama_tampilan = ?, users.status = ? WHERE siswa.id = ?'
                );
                mysqli_stmt_bind_param($us, 'ssi', $namaSiswa, $akunStatus, $id);
                mysqli_stmt_execute($us);
                mysqli_stmt_close($us);

                mysqli_commit($koneksi);
                $transactionStarted = false;
                header('Location: ' . $urlDaftar);
                exit;
            } catch (mysqli_sql_exception $ex) {
                if ($transactionStarted) { mysqli_rollback($koneksi); }
                error_log('Admin Siswa edit failed: ' . $ex->getMessage());
                $pesanError = $ex->getCode() === 1062
                    ? 'NIS sudah digunakan oleh siswa lain.'
                    : 'Data siswa gagal diperbarui. Periksa kembali isian.';
            }
        }
    }
    // Pertahankan isian saat ada error
    $s = array_merge($s, [
        'nis' => $_POST['nis'] ?? $s['nis'], 'nama_lengkap' => $_POST['nama'] ?? $s['nama_lengkap'],
        'jenis_kelamin' => $_POST['jk'] ?? $s['jenis_kelamin'],
        'no_hp' => $_POST['wa'] ?? $s['no_hp'], 'alamat' => $_POST['alamat'] ?? $s['alamat'],
        'status_siswa' => $_POST['status'] ?? $s['status_siswa'],
    ]);
}

$pil = static fn ($a, $b): string => (string) $a === (string) $b ? ' selected' : '';

$pageTitle  = 'Edit Data Siswa';
$activePage = 'data-siswa';

ob_start();
?>
<style>
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
    .ed-card { background: #fff; border: 1px solid #eef2f7; border-radius: 16px; box-shadow: 0 1px 3px rgba(16,24,40,.06); padding: 1.5rem 1.75rem; }
    .ed-sec { font-size: .68rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #94a3b8; margin: 0 0 1rem; }
    .ed-divider { border: 0; border-top: 1px solid #eef2f7; margin: 1.25rem 0; }
    .ed-label { display: block; font-size: .74rem; font-weight: 700; color: #1e293b; margin-bottom: .35rem; }
    .ed-label .req { color: #e11d48; }
    .ed-input { width: 100%; height: 38px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; font-size: .76rem; color: #1e293b; padding: 0 .85rem; outline: 0; }
    .ed-input:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.12); }
    .ed-input[readonly] { background: #f1f5f9; color: #475569; cursor: not-allowed; }
    select.ed-input { -webkit-appearance: none; -moz-appearance: none; appearance: none; padding-right: 32px; cursor: pointer;
        background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%231e293b' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'><polyline points='6 9 12 15 18 9'/></svg>");
        background-repeat: no-repeat; background-position: right 12px center; }
    .ed-foot { display: flex; justify-content: flex-end; gap: .6rem; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #eef2f7; }
    .ed-btn { height: 36px; border-radius: 8px; font-size: .76rem; font-weight: 600; padding: 0 1.2rem; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; cursor: pointer; }
    .ed-btn-cancel { background: #fff; color: #334155; border: 1px solid #e2e8f0; }
    .ed-btn-cancel:hover { background: #f8fafc; color: #334155; text-decoration: none; }
    .ed-btn-save { background: #1e56a0; color: #fff; border: 0; }
    .ed-btn-save:hover { background: #184685; }
</style>

<div class="ed">
    <div class="ed-top">
        <div>
            <div class="ed-crumb">
                <a href="<?= $e($urlDaftar) ?>">Data Siswa</a><span class="sep">/</span>Edit<span class="sep">/</span><span class="now"><?= $e($s['nama_lengkap']) ?></span>
            </div>
            <h1 class="ed-title">Edit Data Siswa</h1>
            <p class="ed-sub">Perbarui data akademik, biodata, dan status siswa.</p>
        </div>
        <a href="<?= $e($urlDaftar) ?>" class="ed-back"><i class="fas fa-arrow-left mr-2"></i>Kembali</a>
    </div>

    <div class="ed-banner">
        <span><i class="fas fa-user-edit"></i>Anda sedang mengubah data <strong><?= $e($s['nama_lengkap']) ?></strong> <small>(NISN: <?= $e($s['nisn'] ?? '-') ?>)</small></span>
    </div>

    <?php if ($pesanError): ?>
        <div class="alert alert-danger border-0 rounded-lg p-3 mb-3 small">
            <i class="fas fa-exclamation-circle mr-2"></i><?= $e($pesanError) ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST" novalidate>
        <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
        <div class="ed-card">
            <div class="ed-sec">1. Informasi Pribadi &amp; Akademik Siswa</div>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label class="ed-label" for="nisn">NISN</label>
                    <input class="ed-input" type="text" id="nisn" value="<?= $e($s['nisn'] ?? '') ?>" readonly>
                </div>
                <div class="col-md-6 form-group">
                    <label class="ed-label" for="nis">NIS</label>
                    <input class="ed-input" type="text" id="nis" name="nis" maxlength="30" value="<?= $e($s['nis'] ?? '') ?>">
                </div>
                <div class="col-12 form-group">
                    <label class="ed-label" for="nama">Nama Lengkap Siswa <span class="req">*</span></label>
                    <input class="ed-input" type="text" id="nama" name="nama" maxlength="120" value="<?= $e($s['nama_lengkap']) ?>" required>
                </div>
                <div class="col-md-6 form-group">
                    <label class="ed-label" for="jk">Jenis Kelamin <span class="req">*</span></label>
                    <select class="ed-input" id="jk" name="jk" required>
                        <option value=""<?= $pil('', $s['jenis_kelamin'] ?? '') ?>>-- Pilih --</option>
                        <option value="L"<?= $pil('L', $s['jenis_kelamin'] ?? '') ?>>Laki-laki</option>
                        <option value="P"<?= $pil('P', $s['jenis_kelamin'] ?? '') ?>>Perempuan</option>
                    </select>
                </div>
                <div class="col-md-6 form-group">
                    <label class="ed-label" for="wa">Nomor WhatsApp Siswa</label>
                    <input class="ed-input" type="tel" id="wa" name="wa" maxlength="25" value="<?= $e($s['no_hp'] ?? '') ?>">
                </div>
                <div class="col-12 form-group">
                    <label class="ed-label" for="alamat">Alamat Lengkap</label>
                    <input class="ed-input" type="text" id="alamat" name="alamat" value="<?= $e($s['alamat'] ?? '') ?>">
                </div>
            </div>

            <hr class="ed-divider">

            <div class="ed-sec">2. Status Siswa</div>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label class="ed-label" for="status">Status Siswa <span class="req">*</span></label>
                    <select class="ed-input" id="status" name="status" required>
                        <option value="aktif"<?= $pil('aktif', $s['status_siswa'] ?? '') ?>>Aktif</option>
                        <option value="lulus"<?= $pil('lulus', $s['status_siswa'] ?? '') ?>>Lulus</option>
                        <option value="keluar"<?= $pil('keluar', $s['status_siswa'] ?? '') ?>>Keluar (akun dinonaktifkan)</option>
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
<?php
$content = ob_get_clean();
require dirname(__DIR__, 2) . '/layouts/admin.php';