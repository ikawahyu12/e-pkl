<?php
$pageTitle = 'Ganti Kata Sandi';
$pageSubtitle = 'Gunakan kata sandi baru untuk melindungi akun Admin.';
$activePage = '';
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$flash = is_array($adminFlash ?? null) ? $adminFlash : null;

ob_start();
?>
<div class="card shadow-sm">
    <div class="card-body">
        <div class="alert alert-info">
            <?= !empty($adminUser['wajib_ganti_password'])
                ? 'Kata sandi sementara harus diganti sebelum menggunakan fitur Admin.'
                : 'Masukkan kata sandi lama dan kata sandi baru.' ?>
        </div>
        <?php if (($flash['type'] ?? '') === 'danger'): ?>
            <div class="alert alert-danger"><?= $escape($flash['message'] ?? 'Kata sandi gagal diubah.') ?></div>
        <?php endif; ?>
        <form method="post" action="<?= $escape($routerUrl) ?>?page=ganti-password">
            <input type="hidden" name="action" value="change-admin-password">
            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
            <div class="form-group">
                <label for="password_lama">Kata sandi lama</label>
                <input class="form-control" id="password_lama" name="password_lama" type="password" autocomplete="current-password" required>
            </div>
            <div class="form-group">
                <label for="password_baru">Kata sandi baru</label>
                <input class="form-control" id="password_baru" name="password_baru" type="password" minlength="8" autocomplete="new-password" required>
                <small class="form-text text-muted">Minimal 8 karakter dan mengandung huruf serta angka.</small>
            </div>
            <div class="form-group">
                <label for="password_baru_confirmation">Konfirmasi kata sandi baru</label>
                <input class="form-control" id="password_baru_confirmation" name="password_baru_confirmation" type="password" minlength="8" autocomplete="new-password" required>
            </div>
            <button type="submit" class="btn btn-primary">Ubah Kata Sandi</button>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
require $adminRoot . '/app/Views/layouts/admin.php';
