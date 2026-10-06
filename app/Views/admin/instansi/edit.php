<?php
// ===== 1. BLOK PHP ATAS =====
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

// Dummy Data Terpilih (Nanti diganti query database berdasarkan $_GET['id'])
$id = (int)($_GET['id'] ?? 1);
$mitra = [
    'id'                  => $id,
    'nama_instansi'       => 'PT Telkom Indonesia (Persero) Tbk',
    'sektor'              => 'Teknologi Informasi',
    'pembimbing_lapangan' => 'Budi Santoso, S.Kom.',
    'telepon'             => '081234567890',
    'kuota'               => 10,
    'alamat'              => 'Jl. Pahlawan No. 12, Surabaya, Jawa Timur'
];

$pesanSukses = '';
$pesanError  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $namaInstansi = trim($_POST['nama_instansi'] ?? '');
    $sektor       = trim($_POST['sektor'] ?? '');
    $pembimbing   = trim($_POST['pembimbing_lapangan'] ?? '');
    $telepon      = trim($_POST['telepon'] ?? '');
    $kuota        = (int)($_POST['kuota'] ?? 0);
    $alamat       = trim($_POST['alamat'] ?? '');

    if (empty($namaInstansi) || empty($sektor) || empty($pembimbing) || empty($telepon) || empty($alamat) || $kuota <= 0) {
        $pesanError = 'Harap isi seluruh kolom formulir dengan benar.';
    } else {
        // TODO: Simpan perubahan ke database
        $pesanSukses = 'Perubahan data instansi <strong>' . $e($namaInstansi) . '</strong> berhasil diperbarui!';
        // Update data array lokal untuk simulasi
        $mitra = compact('id', 'namaInstansi', 'sektor', 'pembimbing', 'telepon', 'kuota', 'alamat');
    }
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
            <div class="row">
                <!-- Nama Instansi -->
                <div class="col-md-6 mb-3">
                    <label class="sl-form-label">Nama Lengkap Instansi / Perusahaan <span class="text-danger">*</span></label>
                    <div class="sl-input-group">
                        <i class="fas fa-building"></i>
                        <input type="text" name="nama_instansi" class="sl-form-control" value="<?= $e($mitra['nama_instansi']) ?>" required>
                    </div>
                </div>

                <!-- Sektor -->
                <div class="col-md-6 mb-3">
                    <label class="sl-form-label">Sektor / Bidang Usaha <span class="text-danger">*</span></label>
                    <div class="sl-input-group sl-select-wrapper">
                        <i class="fas fa-briefcase"></i>
                        <select name="sektor" class="sl-form-control" required>
                            <option value="Teknologi Informasi" <?= $mitra['sektor'] === 'Teknologi Informasi' ? 'selected' : '' ?>>Teknologi Informasi</option>
                            <option value="Industri Kreatif" <?= $mitra['sektor'] === 'Industri Kreatif' ? 'selected' : '' ?>>Industri Kreatif</option>
                            <option value="Jaringan & Telekomunikasi" <?= $mitra['sektor'] === 'Jaringan & Telekomunikasi' ? 'selected' : '' ?>>Jaringan & Telekomunikasi</option>
                            <option value="Pemerintahan" <?= $mitra['sektor'] === 'Pemerintahan' ? 'selected' : '' ?>>Pemerintahan</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Penanggung Jawab -->
                <div class="col-md-6 mb-3">
                    <label class="sl-form-label">Penanggung Jawab / Pembimbing <span class="text-danger">*</span></label>
                    <div class="sl-input-group">
                        <i class="fas fa-user-tie"></i>
                        <input type="text" name="pembimbing_lapangan" class="sl-form-control" value="<?= $e($mitra['pembimbing_lapangan']) ?>" required>
                    </div>
                </div>

                <!-- Kontak -->
                <div class="col-md-6 mb-3">
                    <label class="sl-form-label">No. Kontak / WhatsApp Instansi <span class="text-danger">*</span></label>
                    <div class="sl-input-group">
                        <i class="fas fa-phone-alt"></i>
                        <input type="text" name="telepon" class="sl-form-control" value="<?= $e($mitra['telepon']) ?>" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Kuota PKL -->
                <div class="col-md-6 mb-3">
                    <label class="sl-form-label">Kuota Kuota Tempat (Siswa) <span class="text-danger">*</span></label>
                    <div class="sl-input-group">
                        <i class="fas fa-users"></i>
                        <input type="number" name="kuota" class="sl-form-control" min="1" value="<?= $e($mitra['kuota']) ?>" required>
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