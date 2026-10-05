<?php
$g = $guru ?? [
    'nama'     => '',
    'username' => '',
    'password' => '',
    'status'   => 'Aktif',
];

$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>

<div class="row">
    <!-- Nama Guru -->
    <div class="col-md-6 form-group">
        <label class="sf-label" for="nama">Nama Guru <span class="req">*</span></label>
        <div class="sf-ic"><i class="far fa-user"></i>
            <input class="sf-input" type="text" id="nama" name="nama" value="<?= $e($g['nama']) ?>" placeholder="Masukkan nama lengkap guru" required>
        </div>
    </div>

    <!-- Username / NIP -->
    <div class="col-md-6 form-group">
        <label class="sf-label" for="username">NIP / Username <span class="req">*</span></label>
        <div class="sf-ic"><i class="fas fa-id-card"></i>
            <input class="sf-input" type="text" id="username" name="username" value="<?= $e($g['username']) ?>" placeholder="Masukkan NIP (digunakan untuk login)" required>
        </div>
    </div>

    <!-- Password -->
    <div class="col-md-6 form-group">
        <label class="sf-label" for="password">Password <span class="req">*</span></label>
        <div class="sf-ic"><i class="fas fa-key"></i>
            <input class="sf-input" type="password" id="password" name="password" value="<?= $e($g['password']) ?>" placeholder="Masukkan password" required>
        </div>
    </div>

    <!-- Status Akun -->
    <div class="col-md-6 form-group">
        <span class="sf-label">Status Akun Guru <span class="req">*</span></span>
        <div class="sf-radios">
            <label class="sf-radio <?= ($g['status'] === 'Aktif') ? 'on' : '' ?>">
                <span class="txt"><input type="radio" name="status" value="Aktif" <?= ($g['status'] === 'Aktif') ? 'checked' : '' ?>> Aktif</span>
                <span class="sf-tag sf-tag-aktif">Aktif</span>
            </label>
            <label class="sf-radio <?= ($g['status'] !== 'Aktif') ? 'on' : '' ?>">
                <span class="txt"><input type="radio" name="status" value="Nonaktif" <?= ($g['status'] !== 'Aktif') ? 'checked' : '' ?>> Nonaktif</span>
                <span class="sf-tag sf-tag-pending">Nonaktif</span>
            </label>
        </div>
    </div>
</div>