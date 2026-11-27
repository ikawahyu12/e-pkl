<?php
require_once dirname(__DIR__, 3) . '/Support/Csrf.php';
$csrfToken = AdminCsrf::token();
$activePage   = 'instansi-mitra';
$pageTitle    = 'Tambah Instansi Mitra';
$pageSubtitle = 'Formulir pendaftaran data instansi/perusahaan mitra baru untuk tempat PKL.';
$pageHeaderActions = '
    <a href="?page=data-mitra" class="btn btn-sm sl-btn-back">
        <i class="fas fa-arrow-left mr-2"></i>Kembali ke Daftar
    </a>';

$scriptDir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$assetBaseUrl = $scriptDir . '/assets';
$e            = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$createForm = is_array($instanceInput ?? null) ? $instanceInput : [];
$periods = is_array($periods ?? null) ? $periods : [];
$pesanError = is_string($adminPageError ?? null) ? $adminPageError : null;
$pesanSukses = is_string($adminPageSuccess ?? null) ? $adminPageSuccess : null;

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
        <?php if ($periods === []): ?>
            <div class="alert alert-warning border-0 rounded-lg p-3 mb-4 small">
                Periode PKL belum tersedia. Buat Periode PKL terlebih dahulu sebelum menambahkan Instansi dan kuotanya.
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="action" value="master-instance-create">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <!-- Baris 1: Nama Instansi & Sektor -->
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="sl-form-label">Nama Lengkap Instansi / Perusahaan <span class="text-danger">*</span></label>
                    <div class="sl-input-group">
                        <i class="fas fa-building"></i>
                        <input type="text" name="nama_instansi" class="sl-form-control" maxlength="160" placeholder="Contoh: PT Telkom Indonesia (Persero) Tbk" value="<?= $e($createForm['nama_instansi'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="sl-form-label">Jenis Instansi <span class="text-danger">*</span></label>
                    <div class="sl-input-group sl-select-wrapper">
                        <i class="fas fa-briefcase"></i>
                        <select name="jenis" class="sl-form-control" required>
                            <option value="">Pilih jenis instansi</option>
                            <option value="internal" <?= (($createForm['jenis'] ?? '') === 'internal') ? 'selected' : '' ?>>Internal</option>
                            <option value="eksternal" <?= (($createForm['jenis'] ?? '') === 'eksternal') ? 'selected' : '' ?>>Eksternal</option>
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
                        <input type="text" name="pembimbing_lapangan" class="sl-form-control" maxlength="120" placeholder="Contoh: Budi Santoso, S.Kom." value="<?= $e($createForm['pembimbing_lapangan'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="sl-form-label">No. Kontak / WhatsApp Instansi <span class="text-danger">*</span></label>
                    <div class="sl-input-group">
                        <i class="fas fa-phone-alt"></i>
                        <input type="text" name="telepon" class="sl-form-control" maxlength="25" placeholder="Contoh: 0812-xxxx-xxxx" value="<?= $e($createForm['telepon'] ?? '') ?>" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12 mb-3">
                    <label class="sl-form-label">Alamat Instansi <span class="text-danger">*</span></label>
                    <div class="sl-input-group">
                        <i class="fas fa-map-marker-alt"></i>
                        <textarea name="alamat" class="sl-form-control" rows="3" placeholder="Masukkan alamat lengkap instansi" required><?= $e($createForm['alamat'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="sl-form-label">Latitude <span class="text-danger">*</span></label>
                    <div class="sl-input-group">
                        <i class="fas fa-map-marker-alt"></i>
                        <!-- Ditambahkan id="input-latitude" -->
                        <input type="number" name="latitude" id="input-latitude" class="sl-form-control" step="0.0000001"
                               min="-90" max="90" placeholder="Contoh: -7.7543000"
                               value="<?= $e($createForm['latitude'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="sl-form-label">Longitude <span class="text-danger">*</span></label>
                    <div class="sl-input-group">
                        <i class="fas fa-map-marker-alt"></i>
                        <!-- Ditambahkan id="input-longitude" -->
                        <input type="number" name="longitude" id="input-longitude" class="sl-form-control" step="0.0000001"
                               min="-180" max="180" placeholder="Contoh: 111.9025000"
                               value="<?= $e($createForm['longitude'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="sl-form-label">Radius Geofence (Meter) <span class="text-danger">*</span></label>
                    <div class="sl-input-group">
                        <i class="fas fa-location-crosshairs"></i>
                        <input type="number" name="radius_geofence_meter" class="sl-form-control"
                               min="1" step="1" placeholder="Contoh: 100"
                               value="<?= $e($createForm['radius_geofence_meter'] ?? '') ?>" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="sl-form-label">Periode PKL <span class="text-danger">*</span></label>
                    <div class="sl-input-group sl-select-wrapper">
                        <i class="fas fa-calendar-alt"></i>
                        <select name="periode_id" class="sl-form-control" required>
                            <option value="">Pilih periode PKL</option>
                            <?php foreach ($periods as $period): ?>
                                <?php if (is_array($period)): ?>
                                    <option value="<?= (int) ($period['id'] ?? 0) ?>" <?= (string) ($createForm['periode_id'] ?? '') === (string) ($period['id'] ?? '') ? 'selected' : '' ?>>
                                        <?= $e($period['nama'] ?? 'Periode PKL') ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="sl-form-label">Kuota Tempat (Siswa) <span class="text-danger">*</span></label>
                    <div class="sl-input-group">
                        <i class="fas fa-users"></i>
                        <input type="number" name="kuota_siswa" class="sl-form-control"
                               min="0" step="1" placeholder="Contoh: 5"
                               value="<?= $e($createForm['kuota_siswa'] ?? '') ?>" required>
                    </div>
                </div>
            </div>
            <!-- Tombol Aksi -->
            <div class="d-flex justify-content-end" style="gap: .6rem;">
                <a href="?page=data-mitra" class="sl-btn-cancel">Batal</a>
                <button type="submit" class="sl-btn-submit" <?= $periods === [] ? 'disabled' : '' ?>>
                    <i class="fas fa-save mr-2"></i>Simpan Data Instansi
                </button>
            </div>
        </form>
    </div>
</div>

<!-- SCRIPT UNTUK MEMOTONG ANGKA DESIMAL OTOMATIS -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fungsi untuk membatasi jumlah angka di belakang koma
    function limitDecimals(inputElement, maxDecimals) {
        if (!inputElement) return;

        // Event saat user mengetik atau paste
        inputElement.addEventListener('input', function(e) {
            let value = e.target.value;
            
            // Cek jika ada titik desimal
            if (value.includes('.')) {
                let parts = value.split('.');
                // Jika angka di belakang koma melebihi batas, potong
                if (parts[1].length > maxDecimals) {
                    parts[1] = parts[1].substring(0, maxDecimals);
                    e.target.value = parts.join('.');
                }
            }
        });

        // Event saat input kehilangan fokus (opsional, untuk memastikan)
        inputElement.addEventListener('blur', function(e) {
            let value = e.target.value;
            if (value.includes('.')) {
                let parts = value.split('.');
                if (parts[1].length > maxDecimals) {
                    parts[1] = parts[1].substring(0, maxDecimals);
                    e.target.value = parts.join('.');
                }
            }
        });
    }

    // Terapkan pada Latitude (maksimal 7 desimal)
    limitDecimals(document.getElementById('input-latitude'), 7);
    
    // Terapkan pada Longitude (maksimal 7 desimal)
    limitDecimals(document.getElementById('input-longitude'), 7);
});
</script>

<?php
// panggil layout utama
$content = ob_get_clean();
require dirname(__DIR__, 2) . '/layouts/admin.php';