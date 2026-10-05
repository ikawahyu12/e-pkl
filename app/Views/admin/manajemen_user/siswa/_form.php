<?php
$s = $siswa ?? [
    'nama'     => '',
    'username' => '',
    'password' => '',
    'kelas'    => '',
    'status'   => 'Aktif',
];

$opsiKelas = ['XII RPL 1', 'XII RPL 2', 'XI RPL 1', 'XI RPL 2', 'X RPL 1', 'X RPL 2'];
$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>

<div class="row">
    <div class="col-md-6 form-group">
        <label class="sf-label" for="nama">Nama Siswa <span class="req">*</span></label>
        <div class="sf-ic"><i class="far fa-user"></i>
            <input class="sf-input" type="text" id="nama" name="nama" value="<?= $e($s['nama']) ?>" placeholder="Masukkan nama siswa" required>
        </div>
    </div>

    <div class="col-md-6 form-group">
        <label class="sf-label" for="username">Username / NISN <span class="req">*</span></label>
        <div class="sf-ic"><i class="fas fa-id-card"></i>
            <input class="sf-input" type="text" id="username" name="username" value="<?= $e($s['username']) ?>" placeholder="Masukkan NISN siswa" required>
        </div>
    </div>

    <div class="col-md-6 form-group">
        <label class="sf-label" for="password">Password <span class="req">*</span></label>
        <div class="sf-ic"><i class="fas fa-key"></i>
            <input class="sf-input" type="password" id="password" name="password" value="<?= $e($s['password']) ?>" placeholder="Masukkan password" required>
        </div>
    </div>

    <div class="col-md-6 form-group">
        <label class="sf-label" for="kelas">Kelas <span class="req">*</span></label>
        <div class="sf-ic"><i class="fas fa-graduation-cap"></i>
            <select class="sf-input" id="kelas" name="kelas" required>
                <option value="" disabled hidden <?= empty($s['kelas']) ? 'selected' : '' ?>>Pilih Kelas</option>
                <?php foreach ($opsiKelas as $k): ?>
                    <option value="<?= $e($k) ?>" <?= ($s['kelas'] === $k) ? 'selected' : '' ?>><?= $e($k) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="col-md-6 form-group">
        <span class="sf-label">Status Akun Siswa <span class="req">*</span></span>
        <div class="sf-radios">
            <label class="sf-radio <?= ($s['status'] === 'Aktif') ? 'on' : '' ?>">
                <span class="txt"><input type="radio" name="status" value="Aktif" <?= ($s['status'] === 'Aktif') ? 'checked' : '' ?>> Aktif</span>
                <span class="sf-tag sf-tag-aktif">Aktif</span>
            </label>
            <label class="sf-radio <?= ($s['status'] !== 'Aktif') ? 'on' : '' ?>">
                <span class="txt"><input type="radio" name="status" value="Nonaktif" <?= ($s['status'] !== 'Aktif') ? 'checked' : '' ?>> Nonaktif</span>
                <span class="sf-tag sf-tag-pending">Nonaktif</span>
            </label>
        </div>
    </div>
</div>