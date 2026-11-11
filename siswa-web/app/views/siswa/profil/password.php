<?php
/**
 * Ganti Password (fragment, dipasang oleh layouts)
 * Variabel: $flash (array|null), $csrfToken (string)
 */
include __DIR__ . '/_styles.php';
$flash = $flash ?? null;
?>
<form class="pf-stack reveal" method="POST" action="<?= url('profil/updatePassword') ?>">
    <input type="hidden" name="csrf_token" value="<?= e($csrfToken ?? '') ?>">

    <?php if (!empty($flash)): ?>
        <div class="pf-alert pf-alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <?php
    $form =
        '<div class="pf-group"><label for="password_lama">Kata Sandi Lama</label><input class="pf-input" id="password_lama" name="password_lama" type="password" autocomplete="current-password" required></div>' .
        '<div class="pf-group"><label for="password_baru">Kata Sandi Baru</label><input class="pf-input" id="password_baru" name="password_baru" type="password" minlength="8" autocomplete="new-password" required></div>' .
        '<div class="pf-group" style="margin-bottom:0"><label for="confirm_password">Konfirmasi Kata Sandi Baru</label><input class="pf-input" id="confirm_password" name="confirm_password" type="password" minlength="8" autocomplete="new-password" required></div>';
    echo ui_card($form);
    ?>

    <div class="pf-actions">
        <button type="submit" class="pf-btn pf-btn-primary">Simpan Kata Sandi</button>
        <a href="<?= url('profil') ?>" class="pf-btn pf-btn-outline">Batal</a>
    </div>
</form>