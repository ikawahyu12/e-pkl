<?php
$s = $siswa ?? [
    'nama'     => '',
    'username' => '',
    'kelas_id' => '',
    'status'   => 'aktif',
];

$kelasOptions = is_array($studentClasses ?? null) ? $studentClasses : [];
$hasClasses = $kelasOptions !== [];
$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$isEditing = $isEditing ?? false;
$status = strtolower((string) ($s['status'] ?? 'aktif'));
$formInput = is_array($adminFlash['input'] ?? null) ? $adminFlash['input'] : [];
$reasonCode = (string) ($formInput['alasan_kode'] ?? '');
$reasonDetail = (string) ($formInput['alasan_detail'] ?? '');
?>

<div class="row">
    <!-- Nama Siswa -->
    <div class="col-md-6 form-group mb-3">
        <label class="sf-label" for="nama">Nama Siswa <span class="req">*</span></label>
        <div class="sf-ic">
            <i class="far fa-user"></i>
            <input class="sf-input" type="text" id="nama" name="nama" value="<?= $e($s['nama']) ?>" placeholder="Masukkan nama siswa" required>
        </div>
    </div>

    <!-- Username / NISN -->
    <div class="col-md-6 form-group mb-3">
        <label class="sf-label" for="username">Username / NISN <span class="req">*</span></label>
        <div class="sf-ic">
            <i class="fas fa-id-card"></i>
            <input class="sf-input" type="text" id="username" name="username" value="<?= $e($s['username']) ?>" placeholder="Masukkan NISN siswa" maxlength="20" required>
        </div>
    </div>

    <!-- Password + Show/Hide + Generate Auto -->
    <div class="col-md-6 form-group mb-3">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <label class="sf-label mb-0" for="password">Password <span class="req">*</span></label>
            <!-- Tombol Generate Password -->
            <button type="button" class="btn btn-link p-0 text-decoration-none small font-weight-bold" id="btnGeneratePass" style="font-size: .72rem; color: #2563eb;">
                <i class="fas fa-random mr-1"></i> Generate Password
            </button>
        </div>
        <div class="sf-ic" style="position: relative;">
            <i class="fas fa-key"></i>
            <input class="sf-input" type="password" id="password" name="password" value="" placeholder="<?= $isEditing ? 'Kosongkan jika tidak diganti' : 'Minimal 8 karakter' ?>" <?= $isEditing ? '' : 'required' ?> minlength="8" maxlength="255" style="padding-right: 45px;">
            <!-- Tombol Lihat Password -->
            <button type="button" id="togglePassword" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); border: none; background: none; cursor: pointer; color: #64748b; padding: 0; outline: none;" title="Tampilkan/Sembunyikan Password">
                <i class="fas fa-eye" id="eyeIcon"></i>
            </button>
        </div>
    </div>

    <!-- Kelas -->
    <div class="col-md-6 form-group mb-3">
        <label class="sf-label" for="kelas">Kelas <span class="req">*</span></label>
        <div class="sf-ic">
            <i class="fas fa-graduation-cap"></i>
            <select class="sf-input" id="kelas_id" name="kelas_id" <?= $hasClasses ? 'required' : '' ?>>
                <option value="" <?= $hasClasses ? 'disabled hidden' : '' ?> <?= empty($s['kelas_id']) ? 'selected' : '' ?>><?= $hasClasses ? 'Pilih Kelas' : 'Belum ada data kelas (opsional)' ?></option>
                <?php foreach ($kelasOptions as $kelas): ?>
                    <option value="<?= (int) $kelas['id'] ?>" <?= (string) ($s['kelas_id'] ?? '') === (string) $kelas['id'] ? 'selected' : '' ?>><?= $e($kelas['nama']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if (!$hasClasses): ?>
            <small class="form-text text-muted">Akun tetap dapat dibuat tanpa kelas. Tambahkan data kelas untuk mengisi kelas siswa.</small>
        <?php endif; ?>
    </div>

    <!-- Status Akun Siswa -->
    <div class="col-md-6 form-group mb-3">
        <span class="sf-label">Status Akun Siswa <span class="req">*</span></span>
        <div class="sf-radios">
            <label class="sf-radio <?= $status === 'aktif' ? 'on' : '' ?>">
                <span class="txt"><input type="radio" name="status" value="aktif" <?= $status === 'aktif' ? 'checked' : '' ?>> Aktif</span>
                <span class="sf-tag sf-tag-aktif">Aktif</span>
            </label>
            <label class="sf-radio <?= $status !== 'aktif' ? 'on' : '' ?>">
                <span class="txt"><input type="radio" name="status" value="nonaktif" <?= $status !== 'aktif' ? 'checked' : '' ?>> Nonaktif</span>
                <span class="sf-tag sf-tag-pending">Nonaktif</span>
            </label>
        </div>
    </div>
    <?php if ($isEditing): ?>
        <div class="col-12 form-group mb-3" id="student-deactivation-reason" <?= $status === 'nonaktif' ? '' : 'hidden' ?>>
            <label class="sf-label" for="alasan_kode">Alasan penonaktifan <span class="req">*</span></label>
            <select class="sf-input" id="alasan_kode" name="alasan_kode" <?= $status === 'nonaktif' ? 'required' : 'disabled' ?>>
                <option value="">Pilih alasan penonaktifan</option>
                <option value="siswa_mengundurkan_diri" <?= $reasonCode === 'siswa_mengundurkan_diri' ? 'selected' : '' ?>>Siswa mengundurkan diri</option>
                <option value="perubahan_penempatan_dari_sekolah" <?= $reasonCode === 'perubahan_penempatan_dari_sekolah' ? 'selected' : '' ?>>Perubahan penempatan dari sekolah</option>
                <option value="instansi_tidak_dapat_menerima_siswa" <?= $reasonCode === 'instansi_tidak_dapat_menerima_siswa' ? 'selected' : '' ?>>Instansi tidak dapat menerima siswa</option>
                <option value="ketidaksesuaian_data_penempatan" <?= $reasonCode === 'ketidaksesuaian_data_penempatan' ? 'selected' : '' ?>>Ketidaksesuaian data penempatan</option>
                <option value="pelanggaran_atau_ketidakdisiplinan" <?= $reasonCode === 'pelanggaran_atau_ketidakdisiplinan' ? 'selected' : '' ?>>Pelanggaran atau ketidakdisiplinan</option>
                <option value="kondisi_operasional_tertentu" <?= $reasonCode === 'kondisi_operasional_tertentu' ? 'selected' : '' ?>>Kondisi operasional tertentu</option>
                <option value="lainnya" <?= $reasonCode === 'lainnya' ? 'selected' : '' ?>>Lainnya</option>
            </select>
            <div class="mt-2" id="student-deactivation-detail" <?= $reasonCode === 'lainnya' ? '' : 'hidden' ?>>
                <label class="sf-label" for="alasan_detail">Detail alasan <span class="req">*</span></label>
                <textarea class="sf-input" id="alasan_detail" name="alasan_detail" maxlength="2000" <?= $reasonCode === 'lainnya' ? 'required' : 'disabled' ?>><?= $e($reasonDetail) ?></textarea>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const passwordInput = document.getElementById('password');
    const togglePasswordBtn = document.getElementById('togglePassword');
    const eyeIcon = document.getElementById('eyeIcon');
    const btnGeneratePass = document.getElementById('btnGeneratePass');
    const reasonSection = document.getElementById('student-deactivation-reason');
    const reasonSelect = document.getElementById('alasan_kode');
    const reasonDetailSection = document.getElementById('student-deactivation-detail');
    const reasonDetailInput = document.getElementById('alasan_detail');

    function updateStudentDeactivationReason() {
        if (!reasonSection || !reasonSelect) {
            return;
        }

        const deactivating = document.querySelector('input[name="status"]:checked')?.value === 'nonaktif';
        reasonSection.hidden = !deactivating;
        reasonSelect.disabled = !deactivating;
        reasonSelect.required = deactivating;

        const needsDetail = deactivating && reasonSelect.value === 'lainnya';
        if (reasonDetailSection && reasonDetailInput) {
            reasonDetailSection.hidden = !needsDetail;
            reasonDetailInput.disabled = !needsDetail;
            reasonDetailInput.required = needsDetail;
        }
    }

    document.querySelectorAll('input[name="status"]').forEach(function (statusInput) {
        statusInput.addEventListener('change', updateStudentDeactivationReason);
    });
    if (reasonSelect) {
        reasonSelect.addEventListener('change', updateStudentDeactivationReason);
    }
    updateStudentDeactivationReason();

    // 1. Fitur Lihat / Sembunyikan Password
    if (togglePasswordBtn) {
        togglePasswordBtn.addEventListener('click', function () {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            eyeIcon.classList.toggle('fa-eye', !isPassword);
            eyeIcon.classList.toggle('fa-eye-slash', isPassword);
        });
    }

    // 2. Fitur Generate Password Acak
    if (btnGeneratePass) {
        btnGeneratePass.addEventListener('click', function () {
            const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#$!";
            const passwordLength = 12;
            const maxByte = Math.floor(256 / chars.length) * chars.length;
            let newPass = "";
            while (newPass.length < passwordLength) {
                const randomValues = new Uint8Array(passwordLength - newPass.length + 8);
                window.crypto.getRandomValues(randomValues);
                for (const value of randomValues) {
                    if (value < maxByte) {
                        newPass += chars.charAt(value % chars.length);
                        if (newPass.length === passwordLength) {
                            break;
                        }
                    }
                }
            }
            passwordInput.value = newPass;
            
            // Otomatis ubah tipe ke text agar password baru langsung terlihat
            passwordInput.type = 'text';
            eyeIcon.classList.remove('fa-eye');
            eyeIcon.classList.add('fa-eye-slash');
        });
    }
});
</script>