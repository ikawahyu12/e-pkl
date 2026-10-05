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
    <div class="col-md-6 form-group">
        <label class="sf-label" for="nama">Nama Admin <span class="req">*</span></label>
        <div class="sf-ic"><i class="far fa-user"></i>
            <input class="sf-input" type="text" id="nama" name="nama" value="<?= $e($adm['nama']) ?>" placeholder="Masukkan nama admin" required>
        </div>
    </div>

    <div class="col-md-6 form-group">
        <label class="sf-label" for="username">Username <span class="req">*</span></label>
        <div class="sf-ic"><i class="fas fa-user-shield"></i>
            <input class="sf-input" type="text" id="username" name="username" value="<?= $e($adm['username']) ?>" placeholder="Masukkan username admin" required>
        </div>
    </div>

    <div class="col-md-6 form-group">
        <label class="sf-label" for="password">Password <span class="req">*</span></label>
        <div class="sf-ic"><i class="fas fa-key"></i>
            <input class="sf-input" type="password" id="password" name="password" value="<?= $e($adm['password']) ?>" placeholder="Masukkan password" required>
        </div>
    </div>

    <div class="col-md-6 form-group">
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