<?php
require_once dirname(__DIR__, 3) . '/Support/Csrf.php';
$csrfToken = AdminCsrf::token();
// Mengarah ke file koneksi.php (sesuai perbaikan path sebelumnya)
require_once __DIR__ . '/../../../../../config/koneksi.php';

    // ===== 1. BLOK PHP ATAS =====
$activePage   = 'instansi-mitra';
$pageTitle    = 'Tambah Instansi Mitra';
$pageSubtitle = 'Formulir pendaftaran data instansi/perusahaan mitra baru untuk tempat PKL.';

// Tombol Kembali di pojok kanan atas
$pageHeaderActions = '
    <a href="?page=data-mitra" class="btn btn-sm sl-btn-back">
        <i class="fas fa-arrow-left mr-2"></i>Kembali ke Daftar
    </a>';

$scriptDir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$assetBaseUrl = $scriptDir . '/assets';
$e            = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$pesanSukses = '';
$pesanError  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!AdminCsrf::verify($_POST['csrf_token'] ?? null)) {
        http_response_code(419);
        $pesanError = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba kembali.';
    } else {
    $namaInstansi = trim($_POST['nama_instansi'] ?? '');
    $jenis        = trim($_POST['jenis'] ?? '');
    $pembimbing   = trim($_POST['pembimbing_lapangan'] ?? '');
    $telepon      = trim($_POST['telepon'] ?? '');
    $kuota        = filter_var($_POST['kuota'] ?? null, FILTER_VALIDATE_INT);
    $alamat       = trim($_POST['alamat'] ?? '');

$latitude  = filter_var($_POST['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
$longitude = filter_var($_POST['longitude'] ?? null, FILTER_VALIDATE_FLOAT);
$radius    = filter_var($_POST['radius_geofence_meter'] ?? null, FILTER_VALIDATE_INT);

if (
    $namaInstansi === ''
    || !in_array($jenis, ['internal', 'eksternal'], true)
    || $pembimbing === ''
    || $telepon === ''
    || $alamat === ''
    || $kuota === false
    || $kuota === null
    || $kuota <= 0
    || $latitude === false
    || $latitude === null
    || $latitude < -90
    || $latitude > 90
    || $longitude === false
    || $longitude === null
    || $longitude < -180
    || $longitude > 180
    || $radius === false
    || $radius === null
    || $radius <= 0
) {
    $pesanError = 'Harap isi seluruh kolom formulir dengan benar.';
} elseif (
    mb_strlen($namaInstansi, 'UTF-8') > 160
    || mb_strlen($jenis, 'UTF-8') > 80
    || mb_strlen($pembimbing, 'UTF-8') > 120
    || mb_strlen($telepon, 'UTF-8') > 25
    || $kuota > 4294967295
    || $radius > 4294967295
) {
    $pesanError = 'Panjang atau nilai salah satu isian melebihi batas database.';
} else {
    $status = 'aktif';

    $stmt = mysqli_prepare(
        $koneksi,
        'INSERT INTO instansi (
            nama,
            jenis,
            nama_penanggung_jawab,
            telepon,
            kuota_siswa,
            alamat,
            status,
            latitude,
            longitude,
            radius_geofence_meter
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    if ($stmt === false) {
        error_log('Gagal menyiapkan penyimpanan instansi: ' . mysqli_error($koneksi));
        $pesanError = 'Data instansi belum dapat disimpan. Silakan coba kembali.';
    } else {
        mysqli_stmt_bind_param(
            $stmt,
            'ssssissddi',
            $namaInstansi,
            $jenis,
            $pembimbing,
            $telepon,
            $kuota,
            $alamat,
            $status,
            $latitude,
            $longitude,
            $radius
        );

        $saved = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if ($saved) {
            $pesanSukses = 'Data instansi <strong>' . $e($namaInstansi) . '</strong> berhasil ditambahkan ke database!';
            $_POST = [];
        } else {
            error_log('Gagal menyimpan instansi: ' . mysqli_error($koneksi));
            $pesanError = 'Data instansi belum dapat disimpan. Silakan coba kembali.';
        }
    }
}
    }
}

ob_start();
?>
<style>
    /* ---- Tombol Kembali ---- */
    .sl-btn-back {
        background: #dbe7f6;
        color: #1e293b;
        border: 0;
        border-radius: 8px;
        padding: .5rem .9rem;
        font-size: .78rem;
        font-weight: 600;
    }
    .sl-btn-back:hover {
        background: #cddff2;
        color: #1e293b;
    }

    /* ---- Kartu Form ---- */
    .sl-card-form {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(16, 24, 40, .07);
        padding: 1.8rem;
    }

    /* ---- Form Label & Input dengan Icon ---- */
    .sl-form-label {
        font-size: .68rem;
        font-weight: 700;
        color: #1e293b;
        letter-spacing: .03em;
        text-transform: uppercase;
        margin-bottom: .45rem;
        display: block;
    }

    .sl-input-group {
        position: relative;
        display: flex;
        align-items: center;
    }

    .sl-input-group i {
        position: absolute;
        left: 14px;
        color: #94a3b8;
        font-size: .85rem;
        pointer-events: none;
        z-index: 2;
    }

    .sl-form-control {
        width: 100%;
        height: 42px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #fff;
        font-size: .8rem;
        color: #1e293b;
        padding: 0 .9rem 0 38px;
        outline: 0;
        transition: all .2s ease;
    }

    textarea.sl-form-control {
        height: auto;
        padding-top: .65rem;
        padding-bottom: .65rem;
    }

    .sl-form-control:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
    }

    .sl-form-control::placeholder {
        color: #94a3b8;
    }

    select.sl-form-control {
        cursor: pointer;
        appearance: none;
        -webkit-appearance: none;
    }

    .sl-select-wrapper {
        position: relative;
    }

    .sl-select-wrapper::after {
        content: "\f0d7";
        font-family: "Font Awesome 5 Free";
        font-weight: 900;
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: .8rem;
        pointer-events: none;
    }

    /* ---- Tombol Simpan & Batal ---- */
    .sl-btn-submit {
        background: #2563eb;
        color: #fff;
        border: 0;
        border-radius: 10px;
        padding: .65rem 1.6rem;
        font-size: .8rem;
        font-weight: 600;
        cursor: pointer;
    }

    .sl-btn-submit:hover {
        background: #1d4ed8;
        color: #fff;
    }

    .sl-btn-cancel {
        background: #f1f5f9;
        color: #475569;
        border: 0;
        border-radius: 10px;
        padding: .65rem 1.4rem;
        font-size: .8rem;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
    }

    .sl-btn-cancel:hover {
        background: #e2e8f0;
        color: #1e293b;
        text-decoration: none;
    }
</style>

<div class="instansi-create">
    
    <!-- Breadcrumb (Petunjuk Halaman) -->
    <div class="mb-2" style="font-size: .78rem; color: #64748b;">
        <a href="?page=data-mitra" style="color: #64748b; text-decoration: none;">Instansi Mitra</a> 
        <span class="mx-1">&gt;</span> 
        <span style="color: #2563eb; font-weight: 600;">Tambah Instansi Baru</span>
    </div>

    <!-- Container Form -->
    <div class="sl-card-form mt-3">

        <?php if ($pesanError): ?>
            <div class="alert alert-danger border-0 rounded-lg p-3 mb-4 small">
                <i class="fas fa-exclamation-circle mr-2"></i><?= $e($pesanError) ?>
            </div>
        <?php endif; ?>

        <?php if ($pesanSukses): ?>
            <div class="alert alert-success border-0 rounded-lg p-3 mb-4 small">
                <i class="fas fa-check-circle mr-2"></i><?= $pesanSukses ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <!-- Baris 1: Nama Instansi & Sektor -->
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="sl-form-label">Nama Lengkap Instansi / Perusahaan <span class="text-danger">*</span></label>
                    <div class="sl-input-group">
                        <i class="fas fa-building"></i>
                        <input type="text" name="nama_instansi" class="sl-form-control" maxlength="160" placeholder="Contoh: PT Telkom Indonesia (Persero) Tbk" value="<?= $e($_POST['nama_instansi'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="sl-form-label">Jenis Instansi <span class="text-danger">*</span></label>
                    <div class="sl-input-group sl-select-wrapper">
                        <i class="fas fa-briefcase"></i>
                        <select name="jenis" class="sl-form-control" required>
                            <option value="">Pilih jenis instansi</option>
                            <option value="internal" <?= (($_POST['jenis'] ?? '') === 'internal') ? 'selected' : '' ?>>Internal</option>
                            <option value="eksternal" <?= (($_POST['jenis'] ?? '') === 'eksternal') ? 'selected' : '' ?>>Eksternal</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Baris 2: Penanggung Jawab & Kontak -->
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="sl-form-label">Penanggung Jawab / Pembimbing <span class="text-danger">*</span></label>
                    <div class="sl-input-group">
                        <i class="fas fa-user-tie"></i>
                        <input type="text" name="pembimbing_lapangan" class="sl-form-control" maxlength="120" placeholder="Contoh: Budi Santoso, S.Kom." value="<?= $e($_POST['pembimbing_lapangan'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="sl-form-label">No. Kontak / WhatsApp Instansi <span class="text-danger">*</span></label>
                    <div class="sl-input-group">
                        <i class="fas fa-phone-alt"></i>
                        <input type="text" name="telepon" class="sl-form-control" maxlength="25" placeholder="Contoh: 0812-xxxx-xxxx" value="<?= $e($_POST['telepon'] ?? '') ?>" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12 mb-3">
                    <label class="sl-form-label">Alamat Instansi <span class="text-danger">*</span></label>
                    <div class="sl-input-group">
                        <i class="fas fa-map-marker-alt"></i>
                        <textarea name="alamat" class="sl-form-control" rows="3" placeholder="Masukkan alamat lengkap instansi" required><?= $e($_POST['alamat'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Baris: Radius dan Kuota PKL -->
<div class="row">
    <!-- Radius -->
    <!-- Baris: Latitude dan Longitude -->
<div class="row">
    <div class="col-md-6 mb-3">
        <label class="sl-form-label">
            Latitude <span class="text-danger">*</span>
        </label>
        <div class="sl-input-group">
            <i class="fas fa-map-marker-alt"></i>
            <input type="number" name="latitude"
                class="sl-form-control"
                step="0.0000001"
                placeholder="Contoh: -7.7543000"
                value="<?= $e($_POST['latitude'] ?? '') ?>"
                required>
        </div>
    </div>

    <div class="col-md-6 mb-3">
        <label class="sl-form-label">
            Longitude <span class="text-danger">*</span>
        </label>
        <div class="sl-input-group">
            <i class="fas fa-map-marker-alt"></i>
            <input type="number" name="longitude"
                class="sl-form-control"
                step="0.0000001"
                placeholder="Contoh: 111.9025000"
                value="<?= $e($_POST['longitude'] ?? '') ?>"
                required>
        </div>
    </div>
</div>

<!-- Baris: Radius dan Kuota -->
<div class="row">
    <div class="col-md-6 mb-3">
        <label class="sl-form-label">
            Radius Geofence (Meter) <span class="text-danger">*</span>
        </label>
        <div class="sl-input-group">
            <i class="fas fa-location-crosshairs"></i>
            <input type="number" name="radius_geofence_meter"
                class="sl-form-control"
                min="1"
                step="1"
                placeholder="Contoh: 100"
                value="<?= $e($_POST['radius_geofence_meter'] ?? '') ?>"
                required>
        </div>
    </div>

    <div class="col-md-6 mb-3">
        <label class="sl-form-label">
            Kuota Tempat (Siswa) <span class="text-danger">*</span>
        </label>
        <div class="sl-input-group">
            <i class="fas fa-users"></i>
            <input type="number" name="kuota"
                class="sl-form-control"
                min="1"
                step="1"
                placeholder="Contoh: 5"
                value="<?= $e($_POST['kuota'] ?? '') ?>"
                required>
        </div>
    </div>
</div>
            <!-- Tombol Aksi -->
            <div class="d-flex justify-content-end" style="gap: .6rem;">
                <a href="?page=data-mitra" class="sl-btn-cancel">Batal</a>
                <button type="submit" class="sl-btn-submit">
                    <i class="fas fa-save mr-2"></i>Simpan Data Instansi
                </button>
            </div>
        </form>
    </div>
</div>

<?php
// panggil layout utama
$content = ob_get_clean();
require dirname(__DIR__, 2) . '/layouts/admin.php';