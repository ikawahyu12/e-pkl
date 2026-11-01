<?php
/**
 * Ganti Password (fragment, dipasang oleh layouts)
 * Variabel: $flash (array|null)
 */
include __DIR__ . '/_styles.php';

$flash = $flash ?? null;
?>
<form class="pf-stack reveal" method="POST" action="<?= url('profil/updatePassword') ?>">
    <?php if (!empty($flash)): ?>
        <div class="pf-alert pf-alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <?php
    $field = fn($id, $label, $ph, $last = false) =>
        '<div class="pf-group"' . ($last ? ' style="margin-bottom:0"' : '') . '>' .
        '<label for="' . $id . '">' . e($label) . '</label>' .
        '<div class="pf-pw"><input class="pf-input" type="password" id="' . $id . '" name="' . $id . '" placeholder="' . e($ph) . '" minlength="8" required>' .
        '<button type="button" data-toggle="' . $id . '">Lihat</button></div></div>';

    echo ui_card(
        $field('password', 'Password', 'Password baru') .
        $field('confirm_password', 'Confirm Password', 'Ulangi password baru', true)
    );
    ?>

    <div class="pf-actions">
        <button type="submit" class="pf-btn pf-btn-primary">Simpan Perubahan</button>
        <a href="<?= url('profil') ?>" class="pf-btn pf-btn-outline">Batal</a>
    </div>
</form>

<script>
    // Tampilkan / sembunyikan password
    document.querySelectorAll('[data-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.dataset.toggle);
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.textContent = show ? 'Sembunyi' : 'Lihat';
        });
    });

    // Cek kecocokan password sebelum submit
    document.querySelector('form').addEventListener('submit', function (e) {
        if (document.getElementById('password').value !== document.getElementById('confirm_password').value) {
            e.preventDefault();
            alert('Password dan konfirmasi password tidak sama.');
        }
    });
</script>