<?php
$adm = $admin ?? [
    'nama'     => '',
    'username' => '',
    'password' => '',
    'status'   => 'Aktif',
];

$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>

<div class="row">
    <!-- Nama Admin -->
    <div class="col-md-6 form-group mb-3">
        <label class="sf-label" for="nama">Nama Admin <span class="req">*</span></label>
        <div class="sf-ic">
            <i class="far fa-user"></i>
            <input class="sf-input" type="text" id="nama" name="nama" value="<?= $e($adm['nama']) ?>" placeholder="Masukkan nama lengkap admin" required>
        </div>
    </div>

    <!-- Username -->
    <div class="col-md-6 form-group mb-3">
        <label class="sf-label" for="username">Username <span class="req">*</span></label>
        <div class="sf-ic">
            <i class="fas fa-user-circle"></i>
            <input class="sf-input" type="text" id="username" name="username" value="<?= $e($adm['username']) ?>" placeholder="Masukkan username" required>
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
            <input class="sf-input" type="password" id="password" name="password" value="<?= $e($adm['password']) ?>" placeholder="Masukkan password" required style="padding-right: 45px;">
            <!-- Tombol Lihat Password -->
            <button type="button" id="togglePassword" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); border: none; background: none; cursor: pointer; color: #64748b; padding: 0; outline: none;" title="Tampilkan/Sembunyikan Password">
                <i class="fas fa-eye" id="eyeIcon"></i>
            </button>
        </div>
    </div>

    <!-- Status Akun Admin -->
    <div class="col-md-6 form-group mb-3">
        <span class="sf-label">Status Akun Admin <span class="req">*</span></span>
        <div class="sf-radios">
            <label class="sf-radio <?= ($adm['status'] === 'Aktif') ? 'on' : '' ?>">
                <span class="txt"><input type="radio" name="status" value="Aktif" <?= ($adm['status'] === 'Aktif') ? 'checked' : '' ?>> Aktif</span>
                <span class="sf-tag sf-tag-aktif">Aktif</span>
            </label>
            <label class="sf-radio <?= ($adm['status'] !== 'Aktif') ? 'on' : '' ?>">
                <span class="txt"><input type="radio" name="status" value="Nonaktif" <?= ($adm['status'] !== 'Aktif') ? 'checked' : '' ?>> Nonaktif</span>
                <span class="sf-tag sf-tag-pending">Nonaktif</span>
            </label>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const passwordInput = document.getElementById('password');
    const togglePasswordBtn = document.getElementById('togglePassword');
    const eyeIcon = document.getElementById('eyeIcon');
    const btnGeneratePass = document.getElementById('btnGeneratePass');

    // 1. Fitur Lihat / Sembunyikan Password
    if (togglePasswordBtn) {
        togglePasswordBtn.addEventListener('click', function () {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            eyeIcon.classList.toggle('fa-eye', !isPassword);
            eyeIcon.classList.toggle('fa-eye-slash', isPassword);
        });
    }

    // 2. Fitur Generate Password Acak (Kombinasi Huruf Besar, Kecil, Angka, Simbol)
    if (btnGeneratePass) {
        btnGeneratePass.addEventListener('click', function () {
            const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#$!";
            let newPass = "";
            for (let i = 0; i < 10; i++) {
                newPass += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            passwordInput.value = newPass;
            
            // Ubah tipe ke 'text' agar password yang baru digenerate langsung kelihatan
            passwordInput.type = 'text';
            eyeIcon.classList.remove('fa-eye');
            eyeIcon.classList.add('fa-eye-slash');
        });
    }
});
</script>