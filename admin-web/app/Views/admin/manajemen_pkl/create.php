<?php
$pageTitle = 'Tambah Penempatan PKL';
$pageSubtitle = 'Tetapkan Siswa pada Instansi dan periode PKL yang sesuai.';
$activePage = 'manajemen-pkl';
$e = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$placementFormMode = 'create';
$placementInput = is_array($placementInput ?? null) ? $placementInput : [];
ob_start();
?>
<div class="mb-3">
    <a class="btn btn-sm btn-outline-secondary" href="?page=manajemen-pkl"><i class="fas fa-arrow-left mr-1"></i>Kembali ke daftar</a>
</div>
<?php if (is_string($adminPageError ?? null)): ?>
    <div class="alert alert-danger"><?= $e($adminPageError) ?></div>
<?php endif; ?>
<?php if (is_array($adminFlash ?? null) && ($adminFlash['type'] ?? '') === 'danger'): ?>
    <div class="alert alert-danger"><?= $e($adminFlash['message'] ?? 'Permintaan gagal.') ?></div>
<?php endif; ?>
<div class="card shadow-sm">
    <div class="card-body">
        <form id="placement-form" method="post" action="?page=create-penempatan-pkl">
            <input type="hidden" name="action" value="placement-create">
            <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
            <?php require __DIR__ . '/_fields.php'; ?>
            <div class="d-flex justify-content-end">
                <a class="btn btn-light mr-2" href="?page=manajemen-pkl">Batal</a>
                <button class="btn btn-primary" type="submit" <?= ($placementReferencesLoaded ?? false) ? '' : 'disabled' ?>>
                    <i class="fas fa-save mr-1"></i>Simpan Penempatan
                </button>
            </div>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
require dirname(__DIR__, 2) . '/layouts/admin.php';
?>
