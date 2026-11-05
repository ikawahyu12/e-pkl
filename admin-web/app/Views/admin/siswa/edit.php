<?php
/**
 * Admin > Data Siswa > Edit (Terhubung Database)
 * Letak file: app/Views/admin/siswa/edit.php
 */

// 1. Panggil koneksi
require_once __DIR__ . '/../../../../../config/koneksi.php';

$urlDaftar = '?page=data-siswa';
$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    echo "<script>alert('ID Siswa tidak valid!'); window.location='$urlDaftar';</script>";
    exit;
}

// ==========================================
// 1. AMBIL DATA SISWA DARI DATABASE
// ==========================================
$sql = "SELECT 
            s.*, 
            i.nama_instansi,
            k.nama_kelas AS kelas
        FROM siswa s
        LEFT JOIN instansi i ON s.id_instansi = i.id_instansi
        LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
        WHERE s.id_siswa = $id
        LIMIT 1";

$result = mysqli_query($koneksi, $sql);
if (!$result || mysqli_num_rows($result) === 0) {
    echo "<script>alert('Data siswa tidak ditemukan!'); window.location='$urlDaftar';</script>";
    exit;
}
$s = mysqli_fetch_assoc($result);

// ==========================================
// 2. PROSES SIMPAN PERUBAHAN (UPDATE)
// ==========================================
$pesanSukses = '';
$pesanError  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nis            = mysqli_real_escape_string($koneksi, $_POST['nis'] ?? '');
    $nama_siswa     = mysqli_real_escape_string($koneksi, $_POST['nama'] ?? '');
    $id_kelas       = (int) ($_POST['kelas'] ?? 0);
    $jenis_kelamin  = mysqli_real_escape_string($koneksi, $_POST['jk'] ?? '');
    $no_hp          = mysqli_real_escape_string($koneksi, $_POST['wa'] ?? '');
    $alamat         = mysqli_real_escape_string($koneksi, $_POST['alamat'] ?? '');
    $id_instansi    = (int) ($_POST['instansi'] ?? 0);
    $status         = mysqli_real_escape_string($koneksi, $_POST['status'] ?? 'Aktif');

    if (empty($nis) || empty($nama_siswa) || empty($id_kelas) || empty($jenis_kelamin)) {
        $pesanError = 'Harap isi kolom NIS, Nama, Kelas, dan Jenis Kelamin.';
    } else {
        $queryUpdate = "UPDATE siswa SET 
                            nis = '$nis',
                            nama_siswa = '$nama_siswa',
                            id_kelas = $id_kelas,
                            no_hp = '$no_hp',
                            alamat = '$alamat',
                            id_instansi = " . ($id_instansi > 0 ? $id_instansi : "NULL") . ",
                            status = '$status'
                        WHERE id_siswa = $id";

        if (mysqli_query($koneksi, $queryUpdate)) {
            echo "<script>alert('Data siswa berhasil diperbarui!'); window.location='$urlDaftar';</script>";
            exit;
        } else {
            $pesanError = 'Gagal memperbarui: ' . mysqli_error($koneksi);
        }
    }
}

// ==========================================
// 3. AMBIL DATA UNTUK DROPDOWN
// ==========================================
$opsiKelas = [];
$qKelas = mysqli_query($koneksi, "SELECT id_kelas, nama_kelas FROM kelas ORDER BY nama_kelas ASC");
if ($qKelas) {
    while ($k = mysqli_fetch_assoc($qKelas)) {
        $opsiKelas[] = $k;
    }
}

$opsiInstansi = [];
$qInst = mysqli_query($koneksi, "SELECT id_instansi, nama_instansi FROM instansi WHERE status = 'aktif' ORDER BY nama_instansi ASC");
if ($qInst) {
    while ($i = mysqli_fetch_assoc($qInst)) {
        $opsiInstansi[] = $i;
    }
}

$e   = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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
                <a href="<?= $e($urlDaftar) ?>">Data Siswa</a><span class="sep">/</span>Edit<span class="sep">/</span><span class="now"><?= $e($s['nama_siswa']) ?></span>
            </div>
            <h1 class="ed-title">Edit Data Siswa</h1>
            <p class="ed-sub">Perbarui data biodata siswa, penempatan PKL, dan status bimbingan magang</p>
        </div>
        <a href="<?= $e($urlDaftar) ?>" class="ed-back"><i class="fas fa-arrow-left mr-2"></i>Kembali</a>
    </div>

    <div class="ed-banner">
        <span><i class="fas fa-user-edit"></i>Anda sedang mengubah data <strong><?= $e($s['nama_siswa']) ?></strong> <small>(NIS: <?= $e($s['nis'] ?? '-') ?>)</small></span>
    </div>

    <?php if ($pesanError): ?>
        <div class="alert alert-danger border-0 rounded-lg p-3 mb-3 small">
            <i class="fas fa-exclamation-circle mr-2"></i><?= $pesanError ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST" novalidate>
        <div class="ed-card">
            <div class="ed-sec">1. Informasi Pribadi &amp; Akademik Siswa</div>
            <div class="row">
                <div class="col-12 form-group">
                    <label class="ed-label" for="nis">NIS <span class="req">*</span></label>
                    <input class="ed-input" type="text" id="nis" name="nis" value="<?= $e($s['nis'] ?? '') ?>" required>
                </div>
                <div class="col-12 form-group">
                    <label class="ed-label" for="nama">Nama Lengkap Siswa <span class="req">*</span></label>
                    <input class="ed-input" type="text" id="nama" name="nama" value="<?= $e($s['nama_siswa']) ?>" required>
                </div>
                <div class="col-md-6 form-group">
                    <label class="ed-label" for="kelas">Kelas &amp; Konsentrasi Keahlian <span class="req">*</span></label>
                    <select class="ed-input" id="kelas" name="kelas" required>
                        <option value="">-- Pilih Kelas --</option>
                        <?php foreach ($opsiKelas as $k): ?>
                            <option value="<?= $k['id_kelas'] ?>" <?= $pil($k['id_kelas'], $s['id_kelas']) ?>>
                                <?= $e($k['nama_kelas']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 form-group">
                    <label class="ed-label" for="jk">Jenis Kelamin <span class="req">*</span></label>
                    <select class="ed-input" id="jk" name="jk" required>
                        <option value="Laki-laki" <?= $pil('Laki-laki', $s['jenis_kelamin'] ?? '') ?>>Laki-laki</option>
                        <option value="Perempuan" <?= $pil('Perempuan', $s['jenis_kelamin'] ?? '') ?>>Perempuan</option>
                    </select>
                </div>
                <div class="col-md-6 form-group">
                    <label class="ed-label" for="wa">Nomor WhatsApp Siswa</label>
                    <input class="ed-input" type="tel" id="wa" name="wa" value="<?= $e($s['no_hp'] ?? '') ?>">
                </div>
                <div class="col-md-6 form-group">
                    <label class="ed-label" for="alamat">Alamat Lengkap</label>
                    <input class="ed-input" type="text" id="alamat" name="alamat" value="<?= $e($s['alamat'] ?? '') ?>">
                </div>
            </div>

            <hr class="ed-divider">

            <div class="ed-sec">2. Penempatan Instansi &amp; Status</div>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label class="ed-label" for="instansi">Instansi Mitra DU-DI</label>
                    <select class="ed-input" id="instansi" name="instansi">
                        <option value="">-- Pilih Instansi --</option>
                        <?php foreach ($opsiInstansi as $inst): ?>
                            <option value="<?= $inst['id_instansi'] ?>" <?= $pil($inst['id_instansi'], $s['id_instansi']) ?>>
                                <?= $e($inst['nama_instansi']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 form-group">
                    <label class="ed-label" for="status">Status Bimbingan PKL <span class="req">*</span></label>
                    <select class="ed-input" id="status" name="status" required>
                        <option value="Aktif" <?= $pil('Aktif', $s['status'] ?? '') ?>>Aktif</option>
                        <option value="Selesai" <?= $pil('Selesai', $s['status'] ?? '') ?>>Selesai</option>
                        <option value="Pending" <?= $pil('Pending', $s['status'] ?? '') ?>>Pending</option>
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