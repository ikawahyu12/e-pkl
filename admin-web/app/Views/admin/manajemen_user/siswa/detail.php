<?php
$pageTitle = 'Detail Siswa';
$activePage = 'user-siswa';

$siswa = is_array($siswa ?? null) ? $siswa : [];
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$status = strtolower((string) ($siswa['status'] ?? ''));

ob_start();
?>

<div class="container-fluid p-0">
    <div class="mb-3 small text-muted">
        <a href="?page=manajemen-siswa" class="text-secondary">Manajemen Siswa</a> &gt; <span class="text-primary font-weight-bold">Detail Siswa</span>
    </div>

    <div class="card border-0 shadow-sm rounded-lg mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-user-graduate mr-2"></i>Detail Akun Siswa</h6>
            <a href="?page=manajemen-siswa" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Kembali</a>
        </div>
        <?php if (is_string($adminPageError ?? null)): ?>
            <div class="alert alert-danger m-3"><?= $escape($adminPageError) ?></div>
        <?php endif; ?>
        <div class="card-body">
            <table class="table table-borderless table-sm mb-0">
                <tr>
                    <th width="200" class="text-muted">Nama Siswa</th>
                    <td class="font-weight-bold">: <?= $escape($siswa['nama'] ?? '-') ?></td>
                </tr>
                <tr>
                    <th class="text-muted">NISN / Username</th>
                    <td>: <?= $escape($siswa['username'] ?? '-') ?></td>
                </tr>
                <tr>
                    <th class="text-muted">Kelas</th>
                    <td>: <?= $escape($siswa['kelas'] ?? '-') ?></td>
                </tr>
                <tr>
                    <th class="text-muted">Password</th>
                    <td>: <span class="text-muted" title="Password disimpan sebagai hash dan tidak dapat ditampilkan.">••••••••</span></td>
                </tr>
                <tr>
                    <th class="text-muted">Status Akun</th>
                    <td>: <span class="badge <?= $status === 'aktif' ? 'badge-success' : 'badge-secondary' ?> px-2 py-1"><?= $escape($siswa['status'] ?? '-') ?></span></td>
                </tr>
                <tr>
                    <th class="text-muted">Terakhir Login</th>
                    <td>: <?= $escape(!empty($siswa['terakhir_login_at']) ? date('d M Y, H:i', strtotime((string) $siswa['terakhir_login_at'])) : '-') ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require dirname(__DIR__, 3) . '/layouts/admin.php';
?>