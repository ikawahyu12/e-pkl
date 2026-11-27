<?php
require_once dirname(__DIR__, 3) . '/Support/Csrf.php';
$csrfToken = AdminCsrf::token();
$activePage   = 'instansi-mitra';
$pageTitle    = 'Edit Instansi Mitra'; // Bisa disesuaikan menjadi Tambah jika mode tambah
$pageSubtitle = 'Perbarui informasi instansi atau penyesuaian kuota penempatan PKL.';

$pageHeaderActions = '
    <a href="?page=data-mitra" class="btn btn-sm sl-btn-back">
        <i class="fas fa-arrow-left mr-2"></i>Kembali ke Daftar
    </a>';

$scriptDir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$assetBaseUrl = $scriptDir . '/assets';
$e            = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$instance = is_array($instance ?? null) ? $instance : [];
$instanceInput = is_array($instanceInput ?? null) ? $instanceInput : [];
$periods = is_array($periods ?? null) ? $periods : [];
$quotaRows = is_array($instance['kuota_periode'] ?? null) ? $instance['kuota_periode'] : [];
$form = [
    'nama_instansi' => $instance['nama'] ?? '',
    'jenis' => $instance['jenis'] ?? 'eksternal',
    'pembimbing_lapangan' => $instance['nama_penanggung_jawab'] ?? '',
    'telepon' => $instance['telepon'] ?? '',
    'alamat' => $instance['alamat'] ?? '',
    'latitude' => $instance['latitude'] ?? '',
    'longitude' => $instance['longitude'] ?? '',
    'radius_geofence_meter' => $instance['radius_geofence_meter'] ?? '',
    'status' => $instance['status'] ?? 'aktif',
    'email' => $instance['email'] ?? '',
];
$selectedPeriodId = '';
$quotaValue = '';
foreach ($quotaRows as $quotaRow) {
    if (is_array($quotaRow) && isset($quotaRow['periode_id'])) {
        $selectedPeriodId = (string) $quotaRow['periode_id'];
        $quotaValue = (string) ($quotaRow['kuota_siswa'] ?? '');
        break;
    }
}
foreach ($instanceInput as $field => $value) {
    if (array_key_exists($field, $form) && is_scalar($value)) {
        $form[$field] = (string) $value;
    }
}
if (isset($instanceInput['periode_id']) && is_scalar($instanceInput['periode_id'])) {
    $selectedPeriodId = (string) $instanceInput['periode_id'];
}
if (isset($instanceInput['kuota_siswa']) && is_scalar($instanceInput['kuota_siswa'])) {
    $quotaValue = (string) $instanceInput['kuota_siswa'];
}
$id = (int) ($instance['id'] ?? $_GET['id'] ?? 0);
$pesanError = is_string($adminPageError ?? null) ? $adminPageError : null;
$pesanSukses = is_string($adminPageSuccess ?? null) ? $adminPageSuccess : null;

ob_start();
?>
<style>
    .instansi-edit { --instance-ink: #172b4d; --instance-muted: #8290a3; --instance-blue: #2563eb; }
    .instansi-edit .sl-btn-back { display: inline-flex; align-items: center; background: #dbe7f6; color: var(--instance-ink); border: 0; border-radius: 11px; padding: .7rem 1rem; font-size: .88rem; font-weight: 600; }
    .instansi-edit .sl-btn-back:hover { background: #cddff2; color: var(--instance-ink); text-decoration: none; }
    .instansi-edit .edit-breadcrumb { margin-bottom: 1rem; color: var(--instance-muted); font-size: .9rem; }
    .instansi-edit .edit-breadcrumb a { color: var(--instance-muted); text-decoration: none; }
    .instansi-edit .edit-breadcrumb a:hover { color: var(--instance-blue); }
    .instansi-edit .edit-breadcrumb-current { color: var(--instance-blue); font-weight: 600; }
    .instansi-edit .sl-card-form { background: #fff; border: 1px solid #edf1f6; border-radius: 20px; box-shadow: 0 2px 8px rgba(16, 24, 40, .06); padding: 2rem; }
    
    /* Form Labels & Inputs */
    .instansi-edit .sl-form-label { display: block; margin-bottom: .55rem; color: var(--instance-ink); font-size: .78rem; font-weight: 700; letter-spacing: .025em; text-transform: uppercase; }
    .instansi-edit .sl-input-group { position: relative; display: flex; align-items: center; }
    .instansi-edit .sl-input-group i { position: absolute; z-index: 2; left: 16px; color: #94a3b8; font-size: .95rem; pointer-events: none; }
    .instansi-edit .sl-form-control { width: 100%; height: 50px; border: 1px solid #dfe7f1; border-radius: 12px; background: #fff; color: var(--instance-ink); font-size: .9rem; padding: 0 1rem 0 45px; outline: 0; transition: border-color .2s ease, box-shadow .2s ease; }
    .instansi-edit textarea.sl-form-control { min-height: 110px; height: auto; padding-top: .85rem; padding-bottom: .85rem; resize: vertical; }
    .instansi-edit .sl-form-control:focus { border-color: var(--instance-blue); box-shadow: 0 0 0 3px rgba(37, 99, 235, .12); }
    .instansi-edit .sl-form-control::placeholder { color: #9aa8ba; }
    .instansi-edit select.sl-form-control { cursor: pointer; appearance: none; -webkit-appearance: none; }
    .instansi-edit .sl-select-wrapper { position: relative; }
    .instansi-edit .sl-select-wrapper::after { position: absolute; top: 50%; right: 16px; color: #94a3b8; font-family: "Font Awesome 5 Free"; font-size: .8rem; font-weight: 900; content: "\f0d7"; pointer-events: none; transform: translateY(-50%); }
    .instansi-edit .alert { border-radius: 12px; font-size: .88rem; }
    .instansi-edit .edit-form-actions { display: flex; gap: .75rem; margin-top: 1rem; }
    .instansi-edit .sl-btn-submit, .instansi-edit .sl-btn-cancel { display: inline-flex; align-items: center; justify-content: center; min-height: 48px; border: 0; border-radius: 12px; padding: .75rem 1.35rem; font-size: .9rem; font-weight: 600; text-decoration: none; transition: background .2s ease, color .2s ease; }
    .instansi-edit .sl-btn-submit { background: var(--instance-blue); color: #fff; cursor: pointer; }
    .instansi-edit .sl-btn-submit:hover { background: #1d4ed8; color: #fff; }
    .instansi-edit .sl-btn-cancel { background: #f1f5f9; color: #475569; }
    .instansi-edit .sl-btn-cancel:hover { background: #e2e8f0; color: var(--instance-ink); }
    
    @media (max-width: 767.98px) {
        .instansi-edit .sl-card-form { padding: 1.2rem; border-radius: 16px; }
        .instansi-edit .edit-form-actions { flex-direction: column-reverse; }
        .instansi-edit .sl-btn-submit, .instansi-edit .sl-btn-cancel { width: 100%; }
    }
</style>

<div class="instansi-edit">
    <!-- Breadcrumb -->
    <div class="edit-breadcrumb">
        <a href="?page=data-mitra">Instansi Mitra</a>
        <span class="mx-1">&gt;</span>
        <span class="edit-breadcrumb-current">Edit Data Instansi</span>
    </div>

    <!-- Container Form -->
    <div class="sl-card-form mt-3">

        <?php if ($pesanError): ?>
            <div class="alert alert-danger border-0 rounded-lg p-3 mb-4 small">
                <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($pesanError, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($pesanSukses): ?>
            <div class="alert alert-success border-0 rounded-lg p-3 mb-4 small">
                <i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars(strip_tags($pesanSukses), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
            </div>
        <?php endif; ?>
        
        <?php if ($periods === []): ?>
            <div class="alert alert-warning border-0 rounded-lg p-3 mb-4 small">
                Periode PKL belum tersedia. Data Instansi tetap dapat diperbarui, tetapi kuota per Periode PKL belum dapat diatur.
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="action" value="master-instance-update">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="instansi_id" value="<?= $id ?>">
            
            <!-- BARIS 1: Nama Instansi & Jenis Instansi (Sesuai Desain) -->
            <div class="row">
                <div class="col-md-6 mb-4">
                    <label class="sl-form-label">Nama Lengkap Instansi / Perusahaan <span class="text-danger">*</span></label>
                    <div class="sl-input-group">
                        <i class="fas fa-building"></i>
                        <input type="text" name="nama_instansi" class="sl-form-control" maxlength="160"
                               placeholder="Contoh: PT Telkom Indonesia (Persero) Tbk"
                               value="<?= $e($form['nama_instansi']) ?>" required>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <label class="sl-form-label">Jenis Instansi <span class="text-danger">*</span></label>
                    <div class="sl-input-group sl-select-wrapper">
                        <i class="fas fa-briefcase"></i>
                        <select name="jenis" class="sl-form-control" required>
                            <option value="" disabled <?= $form['jenis'] === '' ? 'selected' : '' ?>>Pilih jenis instansi</option>
                            <option value="eksternal" <?= $form['jenis'] === 'eksternal' ? 'selected' : '' ?>>Eksternal</option>
                            <option value="internal"  <?= $form['jenis'] === 'internal' ? 'selected' : '' ?>>Internal (Sekolah)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- BARIS 2: Penanggung Jawab & No Kontak -->
            <div class="row">
                <div class="col-md-6 mb-4">
                    <label class="sl-form-label">Penanggung Jawab / Pembimbing <span class="text-danger">*</span></label>
                    <div class="sl-input-group">
                        <i class="fas fa-user-tie"></i>
                        <input type="text" name="pembimbing_lapangan" class="sl-form-control" maxlength="120"
                               placeholder="Contoh: Budi Santoso, S.Kom."
                               value="<?= $e($form['pembimbing_lapangan']) ?>" required>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <label class="sl-form-label">No. Kontak / WhatsApp Instansi <span class="text-danger">*</span></label>
                    <div class="sl-input-group">
                        <i class="fas fa-phone-alt"></i>
                        <input type="text" name="telepon" class="sl-form-control" maxlength="25"
                               placeholder="Contoh: 0812-xxxx-xxxx"
                               value="<?= $e($form['telepon']) ?>" required>
                    </div>
                </div>
            </div>

            <!-- BARIS 3: Alamat Instansi (Full Width) -->
            <div class="row">
                <div class="col-md-12 mb-4">
                    <label class="sl-form-label">Alamat Instansi <span class="text-danger">*</span></label>
                    <div class="sl-input-group">
                        <i class="fas fa-map-marker-alt" style="top: 14px;"></i>
                        <textarea name="alamat" class="sl-form-control" rows="3" placeholder="Masukkan alamat lengkap instansi" required><?= $e($form['alamat']) ?></textarea>
                    </div>
                </div>
            </div>

            <!-- BARIS 4: Latitude, Longitude, Radius, Kuota (Sesuai Desain 4 Kolom Sejajar) -->
            <div class="row">
                <div class="col-md-3 mb-4">
                    <label class="sl-form-label">Latitude <span class="text-danger">*</span></label>
                    <div class="sl-input-group">
                        <i class="fas fa-map-marker-alt"></i>
                        <!-- Ditambahkan id="edit-latitude" -->
                        <input type="number" name="latitude" id="edit-latitude" class="sl-form-control" step="0.0000001"
                               min="-90" max="90" placeholder="Contoh: -7.7543"
                               value="<?= $e($form['latitude']) ?>" required>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <label class="sl-form-label">Longitude <span class="text-danger">*</span></label>
                    <div class="sl-input-group">
                        <i class="fas fa-map-marker-alt"></i>
                        <!-- Ditambahkan id="edit-longitude" -->
                        <input type="number" name="longitude" id="edit-longitude" class="sl-form-control" step="0.0000001"
                               min="-180" max="180" placeholder="Contoh: 111.9025"
                               value="<?= $e($form['longitude']) ?>" required>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <label class="sl-form-label">Radius Geofence (meter) <span class="text-danger">*</span></label>
                    <div class="sl-input-group">
                        <i class="fas fa-location-crosshairs"></i>
                        <input type="number" name="radius_geofence_meter" class="sl-form-control"
                               min="50" step="1" placeholder="Contoh: 100"
                               value="<?= $e($form['radius_geofence_meter']) ?>" required>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <label class="sl-form-label">Kuota Tempat (Siswa) <span class="text-danger">*</span></label>
                    <div class="sl-input-group">
                        <i class="fas fa-users"></i>
                        <input type="number" name="kuota_siswa" id="editKuotaSiswa" class="sl-form-control" min="0"
                               placeholder="Contoh: 5"
                               value="<?= $e($quotaValue) ?>" required>
                    </div>
                </div>
            </div>

            <!-- Field Tambahan yang Tidak Ada di Gambar tapi Ada di Sistem (Disembunyikan / Ditata Rapi) -->
            <!-- Saya asumsikan Periode & Status tetap ada karena dibutuhkan oleh sistem backend -->
            <div class="row mt-2 pt-3 border-top">
                <div class="col-md-6 mb-4">
                    <label class="sl-form-label">Periode PKL (Opsional)</label>
                    <div class="sl-input-group sl-select-wrapper">
                        <i class="fas fa-calendar-alt"></i>
                        <select name="periode_id" id="editPeriodeId" class="sl-form-control">
                            <option value="">Pilih periode (opsional)</option>
                            <?php foreach ($periods as $period): ?>
                                <?php if (is_array($period)): ?>
                                    <?php
                                    $periodId = (string) ($period['id'] ?? '');
                                    $periodQuota = '';
                                    foreach ($quotaRows as $quotaRow) {
                                        if (is_array($quotaRow) && (string) ($quotaRow['periode_id'] ?? '') === $periodId) {
                                            $periodQuota = (string) ($quotaRow['kuota_siswa'] ?? '');
                                            break;
                                        }
                                    }
                                    ?>
                                    <option value="<?= $e($periodId) ?>" data-quota="<?= $e($periodQuota) ?>" <?= $selectedPeriodId === $periodId ? 'selected' : '' ?>>
                                        <?= $e($period['nama'] ?? 'Periode PKL') ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <label class="sl-form-label">Status <span class="text-danger">*</span></label>
                    <div class="sl-input-group sl-select-wrapper">
                        <i class="fas fa-toggle-on"></i>
                        <select name="status" class="sl-form-control" required>
                            <option value="aktif"    <?= $form['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="nonaktif" <?= $form['status'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Tombol Aksi -->
            <div class="edit-form-actions">
                <a href="?page=data-mitra" class="sl-btn-cancel">Batal</a>
                <button type="submit" class="sl-btn-submit">
                    <i class="fas fa-save mr-2"></i>Simpan Data Instansi
                </button>
            </div>
        </form>
    </div>
</div>

<!-- SCRIPT UNTUK MEMOTONG ANGKA DESIMAL OTOMATIS -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // --- Logika Periode & Kuota (Asli) ---
        var period = document.getElementById('editPeriodeId');
        var quota = document.getElementById('editKuotaSiswa');
        if (period && quota) {
            period.addEventListener('change', function () {
                var option = period.options[period.selectedIndex];
                quota.value = option ? option.getAttribute('data-quota') || '' : '';
            });
        }

        // --- Logika Memotong Desimal Latitude & Longitude ---
        function limitDecimals(inputElement, maxDecimals) {
            if (!inputElement) return;

            // Saat mengetik / paste
            inputElement.addEventListener('input', function(e) {
                let value = e.target.value;
                if (value.includes('.')) {
                    let parts = value.split('.');
                    if (parts[1].length > maxDecimals) {
                        parts[1] = parts[1].substring(0, maxDecimals);
                        e.target.value = parts.join('.');
                    }
                }
            });

            // Saat kehilangan fokus (opsional, memastikan data benar sebelum submit)
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

        // Terapkan pada input edit-latitude dan edit-longitude (maksimal 7 desimal)
        limitDecimals(document.getElementById('edit-latitude'), 7);
        limitDecimals(document.getElementById('edit-longitude'), 7);
    });
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__, 2) . '/layouts/admin.php';