<?php
/**
 * Edit Profil (fragment, dipasang oleh layouts)
 * Variabel: $siswa (array), $flash (array|null)
 */
include __DIR__ . '/_styles.php';

// Sesuaikan nama variabel dengan yang dikirim controllermu (cukup ubah di sini)
$siswa = $siswa ?? $profil ?? $user ?? [];
$flash = $flash ?? null;
if (empty($siswa)) $siswa = require __DIR__ . '/_dummy.php'; // DUMMY: hapus kalau controller sudah kirim data
// Avatar: pakai foto kalau ada, kalau tidak pakai inisial nama (tanpa butuh file gambar)
$ini = '';
foreach (array_slice(preg_split('/\s+/', trim($siswa['nama_lengkap'] ?? '')), 0, 2) as $w) {
    $ini .= mb_strtoupper(mb_substr($w, 0, 1));
}
$fallback = 'data:image/svg+xml;base64,' . base64_encode(
    '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200"><rect width="200" height="200" fill="#2b8cff"/>' .
    '<text x="50%" y="54%" font-family="Arial" font-size="80" font-weight="700" fill="#fff" text-anchor="middle" dominant-baseline="middle">' .
    htmlspecialchars($ini ?: '?') . '</text></svg>'
);
$foto = !empty($siswa['foto']) ? url('uploads/siswa/' . $siswa['foto']) : $fallback;
?>
<form class="pf-stack reveal" method="POST" action="<?= url('profil/update') ?>" enctype="multipart/form-data">
    <?php if (!empty($flash)): ?>
        <div class="pf-alert pf-alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <?php
    $fotoHtml =
        '<div class="pf-photo">' .
        '<img id="pfPreview" src="' . e($foto) . '" alt="Foto profil" onerror="this.onerror=null;this.src=\'' . $fallback . '\'">' .
        '<input type="file" name="foto" id="pfFoto" accept="image/png,image/jpeg,image/webp" hidden>' .
        '<button type="button" class="pf-btn-sm" onclick="document.getElementById(\'pfFoto\').click()">Ganti Foto</button>' .
        '</div>';
    echo ui_card($fotoHtml);

    $form =
        '<div class="pf-group"><label>NISN</label><input class="pf-input" value="' . e($siswa['nisn'] ?? '') . '" readonly></div>' .
        '<div class="pf-group"><label>Kelas / Jurusan</label><input class="pf-input" value="' . e($siswa['kelas'] ?? '') . '" readonly></div>' .
        '<div class="pf-group"><label for="nama_lengkap">Nama Lengkap</label><input class="pf-input" id="nama_lengkap" name="nama_lengkap" value="' . e($siswa['nama_lengkap'] ?? '') . '" required></div>' .
        '<div class="pf-group"><label for="no_hp">No. Handphone</label><input class="pf-input" id="no_hp" name="no_hp" type="tel" pattern="[0-9+]{8,15}" value="' . e($siswa['no_hp'] ?? '') . '" required></div>' .
        '<div class="pf-group" style="margin-bottom:0"><label for="alamat">Alamat</label><textarea class="pf-input" id="alamat" name="alamat" required>' . e($siswa['alamat'] ?? '') . '</textarea></div>';
    echo ui_card($form);
    ?>

    <div class="pf-actions">
        <button type="submit" class="pf-btn pf-btn-primary">Simpan Perubahan</button>
        <a href="<?= url('profil') ?>" class="pf-btn pf-btn-outline">Batal</a>
    </div>
</form>

<script>
    // Preview foto sebelum diunggah
    document.getElementById('pfFoto').addEventListener('change', function () {
        if (this.files[0]) document.getElementById('pfPreview').src = URL.createObjectURL(this.files[0]);
    });
</script>