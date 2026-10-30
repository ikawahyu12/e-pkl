<?php
// ===== 1. BLOK PHP ATAS =====
require_once __DIR__ . '/../../../../config/koneksi.php';

$activePage   = 'instansi-mitra';
$pageTitle    = 'Edit Instansi Mitra';
$pageSubtitle = 'Perbarui informasi instansi atau penyesuaian kuota penempatan PKL.';

$pageHeaderActions = '
    <a href="?page=data-mitra" class="btn btn-sm sl-btn-back">
        <i class="fas fa-arrow-left mr-2"></i>Kembali ke Daftar
    </a>';

$scriptDir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$assetBaseUrl = $scriptDir . '/assets';
$e            = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

// ==========================================
// 1. AMBIL ID DARI URL
// ==========================================
$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: ?page=data-mitra&pesan=id_tidak_valid');
    exit;
}

// ==========================================
// 2. PROSES SIMPAN (POST) — UPDATE KE DATABASE
// ==========================================
$pesanSukses = '';
$pesanError  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $namaInstansi   = trim($_POST['nama_instansi'] ?? '');
    $namaKontak     = trim($_POST['nama_kontak'] ?? '');
    $noTelepon      = trim($_POST['no_telepon'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $kuota          = (int) ($_POST['kuota'] ?? 0);
    $alamat         = trim($_POST['alamat'] ?? '');
    $jenisInstansi  = $_POST['jenis_instansi'] ?? 'eksternal';
    $status         = $_POST['status'] ?? 'aktif';

    // Validasi
    if (empty($namaInstansi) || empty($namaKontak) 
        || empty($noTelepon) || empty($alamat) || $kuota <= 0) {
        $pesanError = 'Harap isi seluruh kolom formulir dengan benar.';
    } elseif (!in_array($jenisInstansi, ['eksternal', 'internal'], true)) {
        $pesanError = 'Jenis instansi tidak valid.';
    } elseif (!in_array($status, ['aktif', 'nonaktif'], true)) {
        $pesanError = 'Status instansi tidak valid.';
    } else {
        // Update ke database (prepared statement) — tanpa kolom sektor
        $sql = "UPDATE instansi SET
                    nama_instansi  = ?,
                    nama_kontak    = ?,
                    no_telepon     = ?,
                    email          = ?,
                    kuota          = ?,
                    alamat         = ?,
                    jenis_instansi = ?,
                    status         = ?
                WHERE id_instansi = ?";

        $stmt = mysqli_prepare($koneksi, $sql);

        if ($stmt) {
            $emailParam = ($email === '') ? null : $email;

            // Tipe: s s s s i s s s i  → 9 parameter
            mysqli_stmt_bind_param(
                $stmt,
                "ssssisssi",
                $namaInstansi,
                $namaKontak,
                $noTelepon,
                $emailParam,
                $kuota,
                $alamat,
                $jenisInstansi,
                $status,
                $id
            );

            if (mysqli_stmt_execute($stmt)) {
                $pesanSukses = 'Perubahan data instansi <strong>' . $e($namaInstansi) . '</strong> berhasil diperbarui!';
            } else {
                $pesanError = 'Gagal menyimpan: ' . mysqli_error($koneksi);
            }
            mysqli_stmt_close($stmt);
        } else {
            $pesanError = 'Gagal menyiapkan query: ' . mysqli_error($koneksi);
        }
    }
}

// ==========================================
// 3. AMBIL DATA INSTANSI DARI DATABASE (tanpa sektor)
// ==========================================
$stmtGet = mysqli_prepare($koneksi, "
    SELECT id_instansi, nama_instansi, nama_kontak, no_telepon, email,
           kuota, alamat, jenis_instansi, status
    FROM instansi
    WHERE id_instansi = ?
");
mysqli_stmt_bind_param($stmtGet, "i", $id);
mysqli_stmt_execute($stmtGet);
$resultGet = mysqli_stmt_get_result($stmtGet);
$mitra     = mysqli_fetch_assoc($resultGet);
mysqli_stmt_close($stmtGet);

if (!$mitra) {
    header('Location: ?page=data-mitra&pesan=tidak_ditemukan');
    exit;
}

ob_start();
?>
<style>
    .sl-btn-back { background: #dbe7f6; color: #1e293b; border: 0; border-radius: 8px; padding: .5rem .9rem; font-size: .78rem; font-weight: 600; }
    .sl-btn-back:hover { background: #cddff2; color: #1e293b; }
    .sl-card-form { background: #fff; border-radius: 16px; box-shadow: 0 1px 3px rgba(16, 24, 40, .07); padding: 1.8rem; }

    .sl-form-label { font-size: .68rem; font-weight: 700; color: #1e293b; letter-spacing: .03em; text-transform: uppercase; margin-bottom: .45rem; display: block; }
    .sl-input-group { position: relative; display: flex; align-items: center; }
    .sl-input-group i { position: absolute; left: 14px; color: #94a3b8; font-size: .85rem; pointer-events: none; z-index: 2; }
    .sl-form-control { width: 100%; height: 42px; border: 1px solid #e2e8f0; border-radius: 10px; background: #fff; font-size: .8rem; color: #1e293b; padding: 0 .9rem 0 38px; outline: 0; transition: all .2s ease; }
    textarea.sl-form-control { height: auto; padding-top: .65rem; padding-bottom: .65rem; }
    .sl-form-control:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, .12); }
    select.sl-form-control { cursor: pointer; appearance: none; -webkit-appearance: none; }

    .sl-select-wrapper { position: relative; }
    .sl-select-wrapper::after { content: "\f0d7"; font-family: "Font Awesome 5 Free"; font-weight: 900; position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: .8rem; pointer-events: none; }

    .sl-btn-submit { background: #2563eb; color: #fff; border: 0; border-radius: 10px; padding: .65rem 1.6rem; font-size: .8rem; font-weight: 600; cursor: pointer; }
    .sl-btn-submit:hover { background: #1d4ed8; color: #fff; }
    .sl-btn-cancel { background: #f1f5f9; color: #475569; border: 0; border-radius: 10px; padding: .65rem 1.4rem; font-size: .8rem; font-weight: 600; text-decoration: none; display: inline-block; }
    .sl-btn-cancel:hover { background: #e2e8f0; color: #475569; }
</style>

<div class="instansi-edit">
    <!-- Breadcrumb -->
    <div class="mb-2" style="font-size: .78rem; color: #64748b;">
        <a href="?page=data-mitra" style="color: #64748b; text-decoration: none;">Instansi Mitra</a>
        <span class="mx-1">&gt;</span>
        <span style="color: #2563eb; font-weight: 600;">Edit Data Instansi</span>
    </div>

    <!-- Container Form -->
    <div class="sl-card-form mt-3">

        <?php if ($pesanError): ?>
            <div class="alert alert-danger border-0 rounded-lg p-3 mb-4 small">
                <i class="fas fa-exclamation-circle mr-2"></i><?= $pesanError ?>
            </div>
        <?php endif; ?>

        <?php if ($pesanSukses): ?>
            <div class="alert alert-success border-0 rounded-lg p-3 mb-4 small">
                <i class="fas fa-check-circle mr-2"></i><?= $pesanSukses ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <!-- Nama Instansi -->
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="sl-form-label">Nama Lengkap Instansi / Perusahaan <span class="text-danger">*</span></label>
                    <div class="sl-input-group">
                        <i class="fas fa-building"></i>
                        <input type="text" name="nama_instansi" class="sl-form-control"
                               value="<?= $e($mitra['nama_instansi']) ?>" required>
                    </div>
                </div>
            </div>

            <!-- Penanggung Jawab & Kontak -->
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="sl-form-label">Penanggung Jawab / Pembimbing <span class="text-danger">*</span></label>
                    <div class="sl-input-group">
                        <i class="fas fa-user-tie"></i>
                        <input type="text" name="nama_kontak" class="sl-form-control"
                               value="<?= $e($mitra['nama_kontak'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="sl-form-label">No. Kontak / WhatsApp Instansi <span class="text-danger">*</span></label>
                    <div class="sl-input-group">
                        <i class="fas fa-phone-alt"></i>
                        <input type="text" name="no_telepon" class="sl-form-control"
                               value="<?= $e($mitra['no_telepon'] ?? '') ?>" required>
                    </div>
                </div>
            </div>

            <!-- Email & Kuota -->
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="sl-form-label">Email Instansi (Opsional)</label>
                    <div class="sl-input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" class="sl-form-control"
                               value="<?= $e($mitra['email'] ?? '') ?>">
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="sl-form-label">Kuota Tempat (Siswa) <span class="text-danger">*</span></label>
                    <div class="sl-input-group">
                        <i class="fas fa-users"></i>
                        <input type="number" name="kuota" class="sl-form-control" min="1"
                               value="<?= $e($mitra['kuota'] ?? 1) ?>" required>
                    </div>
                </div>
            </div>

            <!-- Jenis & Status -->
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="sl-form-label">Jenis Instansi <span class="text-danger">*</span></label>
                    <div class="sl-input-group sl-select-wrapper">
                        <i class="fas fa-tag"></i>
                        <select name="jenis_instansi" class="sl-form-control" required>
                            <option value="eksternal" <?= $mitra['jenis_instansi'] === 'eksternal' ? 'selected' : '' ?>>Eksternal</option>
                            <option value="internal"  <?= $mitra['jenis_instansi'] === 'internal'  ? 'selected' : '' ?>>Internal (Sekolah)</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="sl-form-label">Status <span class="text-danger">*</span></label>
                    <div class="sl-input-group sl-select-wrapper">
                        <i class="fas fa-toggle-on"></i>
                        <select name="status" class="sl-form-control" required>
                            <option value="aktif"    <?= $mitra['status'] === 'aktif'    ? 'selected' : '' ?>>Aktif</option>
                            <option value="nonaktif" <?= $mitra['status'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Alamat -->
            <div class="mb-4">
                <label class="sl-form-label">Alamat Lengkap Instansi <span class="text-danger">*</span></label>
                <div class="sl-input-group">
                    <i class="fas fa-map-marker-alt" style="top: 14px;"></i>
                    <textarea name="alamat" class="sl-form-control" rows="3" required><?= $e($mitra['alamat']) ?></textarea>
                </div>
            </div>

            <!-- Tombol Aksi -->
            <div class="d-flex justify-content-end" style="gap: .6rem;">
                <a href="?page=data-mitra" class="sl-btn-cancel">Batal</a>
                <button type="submit" class="sl-btn-submit">
                    <i class="fas fa-sync-alt mr-2"></i>Perbarui Data Instansi
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require dirname(__DIR__, 2) . '/layouts/admin.php';