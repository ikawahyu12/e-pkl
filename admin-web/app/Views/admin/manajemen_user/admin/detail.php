<?php
$pageTitle = 'Detail Admin';
$activePage = 'user-admin';
$admin = is_array($admin ?? null) ? $admin : [];
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

ob_start();
?>

<div class="container-fluid p-0">
    <div class="mb-3 small text-muted">
        <a href="?page=manajemen-admin" class="text-secondary">Manajemen Admin</a> &gt; <span class="text-primary font-weight-bold">Detail Admin</span>
    </div>

    <?php if (is_string($adminPageError ?? null)): ?>
        <div class="alert alert-danger"><?= $escape($adminPageError) ?></div>
    <?php endif; ?>

    <?php if ($admin !== []): ?>
    <div class="card border-0 shadow-sm rounded-lg mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-user-shield mr-2"></i>Detail Akun Admin</h6>
            <a href="?page=manajemen-admin" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Kembali</a>
        </div>
        <div class="card-body">
            <table class="table table-borderless table-sm mb-0">
                <tr>
                    <th width="200" class="text-muted">Nama Admin</th>
                    <td class="font-weight-bold">: <?= $escape($admin['nama'] ?? $admin['nama_tampilan'] ?? '') ?></td>
                </tr>
                <tr>
                    <th class="text-muted">Username</th>
                    <td>: <?= $escape($admin['username'] ?? '') ?></td>
                </tr>
                <tr>
                    <th class="text-muted">Email</th>
                    <td>: <?= $escape($admin['email'] ?? '-') ?></td>
                </tr>
                <tr>
                    <th class="text-muted">Status Akun</th>
                    <td>: <span class="badge badge-success px-2 py-1"><?= $escape(ucfirst((string) ($admin['status'] ?? '-'))) ?></span></td>
                </tr>
                <tr>
                    <th class="text-muted">Terakhir Login</th>
                    <td>: <?= $escape(!empty($admin['terakhir_login_at']) ? date('d M Y, H:i', strtotime((string) $admin['terakhir_login_at'])) : '-') ?></td>
                </tr>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require dirname(__DIR__, 3) . '/layouts/admin.php';
?>