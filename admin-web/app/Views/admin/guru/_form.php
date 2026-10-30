<?php
// Pastikan variabel $guru terdefinisi (dikirim dari Controller)
$g = $guru ?? [
    'nip'     => '',
    'nama'    => '',
    'jk'      => 'Laki-laki',
    'wa'      => '',
    'email'   => '',
    'jabatan' => 'Guru Pembimbing',
    'jurusan' => 'Rekayasa Perangkat Lunak (RPL)',
    'status'  => 'Aktif',
];
?>

<div class="row">
    <div class="col-12 form-group">
        <label class="ed-label" for="nip">NIP <span class="req">*</span></label>
        <input class="ed-input" type="text" id="nip" name="nip" value="<?= htmlspecialchars($g['nip'] ?? '', ENT_QUOTES) ?>" readonly>
    </div>
    <div class="col-12 form-group">
        <label class="ed-label" for="nama">Nama Lengkap <span class="req">*</span></label>
        <input class="ed-input" type="text" id="nama" name="nama" value="<?= htmlspecialchars($g['nama'] ?? '', ENT_QUOTES) ?>" required>
    </div>
</div>