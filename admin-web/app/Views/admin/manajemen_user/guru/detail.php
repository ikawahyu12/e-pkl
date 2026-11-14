<?php
$pageTitle = 'Detail Akun Guru';
$activePage = 'user-guru';

$guru = is_array($guru ?? null) ? $guru : [];
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$status = strtolower((string) ($guru['status'] ?? ''));

ob_start();
?>

<div class="container-fluid p-0">
    <div class="mb-3 small text-muted">
        <a href="?page=manajemen-guru" class="text-secondary">Manajemen Guru</a> &gt; <span class="text-primary font-weight-bold">Detail Akun</span>
    </div>

    <div class="card border-0 shadow-sm rounded-lg mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-user-tie mr-2"></i>Detail Akun Guru</h6>
            <a href="?page=manajemen-guru" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Kembali</a>
        </div>
        <?php if (is_string($adminPageError ?? null)): ?>
            <div class="alert alert-danger m-3"><?= $escape($adminPageError) ?></div>
        <?php endif; ?>
        <div class="card-body">
            <table class="table table-borderless table-sm mb-0">
                <tr>
                    <th width="200" class="text-muted">Nama Guru</th>
                    <td class="font-weight-bold">: <?= $escape($guru['nama'] ?? '-') ?></td>
                </tr>
                <tr>
                    <th class="text-muted">NIP / Username</th>
                    <td>: <?= $escape($guru['username'] ?? '-') ?></td>
                </tr>
                <tr>
                    <th class="text-muted">Password</th>
                    <td>: <span class="text-muted" title="Password disimpan sebagai hash dan tidak dapat ditampilkan.">••••••••</span></td>
                </tr>
                <tr>
                    <th class="text-muted">Status Akun</th>
                    <td>: <span class="badge <?= $status === 'aktif' ? 'badge-success' : 'badge-secondary' ?> px-2 py-1"><?= $escape($guru['status'] ?? '-') ?></span></td>
                </tr>
                <tr>
                    <th class="text-muted">Terakhir Login</th>
                    <td>: <?= $escape(!empty($guru['terakhir_login_at']) ? date('d M Y, H:i', strtotime((string) $guru['terakhir_login_at'])) : '-') ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require dirname(__DIR__, 3) . '/layouts/admin.php';
?>