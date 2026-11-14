<?php
$pageTitle = 'Manajemen Admin';
$activePage = 'user-admin';
$users = is_array($adminUsers ?? null) ? $adminUsers : [];
$search = is_string($_GET['search'] ?? null) ? $_GET['search'] : '';
$statusFilter = is_string($_GET['status'] ?? null) ? $_GET['status'] : '';
$currentPage = (int) ($adminPagination['current_page'] ?? 1);
$lastPage = (int) ($adminPagination['last_page'] ?? 1);
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$pageUrl = static function (int $page) use ($search, $statusFilter): string {
    return '?' . http_build_query([
        'page' => 'manajemen-admin',
        'search' => $search,
        'status' => $statusFilter,
        'list_page' => $page,
    ]);
};

ob_start();
?>

<style>
    .card-user { border: 1px solid #e8edf2; border-radius: .5rem; box-shadow: 0 .15rem .5rem rgba(58, 59, 69, .05); }
    .table-user thead th { background: #f8f9fc; color: #5a6573; font-size: .78rem; font-weight: 700; border-top: 0; white-space: nowrap; }
    .table-user tbody td { vertical-align: middle; color: #344054; font-size: .85rem; }
    .username-text { color: #176b8b; font-weight: 600; }
    .status-badge { font-size: .72rem; padding: .35rem .6rem; border-radius: 20px; }
    .status-active { color: #198754; background: #e8f7ef; }
    .status-inactive { color: #dc3545; background: #fdebec; }
    .status-suspended { color: #8a5a00; background: #fff3cd; }
    .search-box { max-width: 320px; }

    /* Style Aksi Teks / Tombol */
    .sl-aksi { white-space: nowrap; }
    .sl-aksi a, .sl-aksi button { font-size: .82rem; font-weight: 600; margin-right: .5rem; border: 0; background: none; text-decoration: none; cursor: pointer; padding: 0; }
    .sl-aksi .a-detail { color: #1a2438; }
    .sl-aksi .a-detail:hover { text-decoration: underline; }
    .sl-aksi .a-edit { color: #2563eb; }
    .sl-aksi .a-edit:hover { text-decoration: underline; }
    .sl-aksi .a-hapus { color: #e0264a; }
    .sl-aksi .a-hapus:hover { text-decoration: underline; }
</style>

<div class="card card-user mb-4">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between flex-wrap mb-4">
            <div>
                <h5 class="font-weight-bold text-dark mb-1">Akun Admin</h5>
                <p class="text-muted small mb-0">Kelola username, password, dan status akun administrator.</p>
            </div>
            <!-- TAMBAH AKUN: Disesuaikan menggunakan ?page=create-admin -->
            <a href="?page=create-admin" class="btn btn-info mt-2 mt-md-0">
                <i class="fas fa-plus mr-1"></i> Tambah Akun Admin
            </a>
        </div>

        <div class="alert alert-light border small mb-4">
            <i class="fas fa-info-circle text-info mr-2"></i>
            Admin dapat membuat, melihat detail, dan mengedit akun administrator melalui halaman ini.
        </div>

        <?php if (is_string($adminPageSuccess ?? null)): ?>
            <div class="alert alert-success"><?= $escape($adminPageSuccess) ?></div>
        <?php endif; ?>
        <?php if (is_string($adminPageError ?? null)): ?>
            <div class="alert alert-danger"><?= $escape($adminPageError) ?></div>
        <?php endif; ?>
        <?php if (is_array($adminFlash ?? null) && ($adminFlash['type'] ?? '') === 'danger'): ?>
            <div class="alert alert-danger"><?= $escape($adminFlash['message'] ?? 'Permintaan gagal.') ?></div>
        <?php endif; ?>

        <form class="row mb-3" method="get" action="">
            <input type="hidden" name="page" value="manajemen-admin">
            <div class="col-md-6 mb-2 mb-md-0">
                <div class="input-group search-box">
                    <div class="input-group-prepend"><span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span></div>
                    <input type="text" name="search" class="form-control" value="<?= $escape($search) ?>" placeholder="Cari nama, username, atau email...">
                </div>
            </div>
            <div class="col-md-3 ml-auto">
                <select name="status" class="custom-select">
                    <option value="">Semua Status</option>
                    <option value="aktif" <?= $statusFilter === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                    <option value="nonaktif" <?= $statusFilter === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                    <option value="ditangguhkan" <?= $statusFilter === 'ditangguhkan' ? 'selected' : '' ?>>Ditangguhkan</option>
                </select>
            </div>
            <div class="col-md-auto mt-2 mt-md-0">
                <button class="btn btn-primary" type="submit">Cari</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover table-user mb-0" id="adminTable">
                <thead>
                    <tr>
                        <th width="50">No</th>
                        <th>Nama Admin</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Password</th>
                        <th>Status</th>
                        <th>Terakhir Login</th>
                        <th width="140">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $index => $user): ?>
                        <?php
                        $status = (string) ($user['status'] ?? '');
                        $statusLabel = match ($status) {
                            'aktif' => 'Aktif',
                            'nonaktif' => 'Nonaktif',
                            'ditangguhkan' => 'Ditangguhkan',
                            default => $status,
                        };
                        $statusClass = match ($status) {
                            'aktif' => 'status-active',
                            'ditangguhkan' => 'status-suspended',
                            default => 'status-inactive',
                        };
                        $lastLogin = $user['terakhir_login_at'] ?? null;
                        ?>
                        <tr>
                            <td><?= (($currentPage - 1) * (int) ($adminPagination['per_page'] ?? 15)) + $index + 1 ?></td>
                            <td><span class="font-weight-bold"><?= $escape($user['nama'] ?? $user['nama_tampilan'] ?? '') ?></span></td>
                            <td><span class="username-text"><?= $escape($user['username'] ?? '') ?></span></td>
                            <td><?= $escape($user['email'] ?? '-') ?></td>
                            <td><span class="text-muted" title="Password disimpan sebagai hash dan tidak dapat ditampilkan.">••••••••</span></td>
                            <td>
                                <span class="status-badge <?= $statusClass ?>"><?= $escape($statusLabel) ?></span>
                            </td>
                            <td><span class="small text-muted"><?= $escape($lastLogin ? date('d M Y, H:i', strtotime((string) $lastLogin)) : '-') ?></span></td>
                            <td class="sl-aksi">
                                <a class="a-detail" href="?page=detail-admin&amp;id=<?= (int) $user['id'] ?>">Detail</a>
                                <a class="a-edit" href="?page=edit-admin&amp;id=<?= (int) $user['id'] ?>">Edit</a>
                                <form method="post" action="?page=manajemen-admin" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus akun ini?')">
                                    <input type="hidden" name="action" value="admin-user-delete">
                                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                    <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                                    <button type="submit" class="a-hapus">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($users === []): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">Belum ada akun Admin yang cocok.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($lastPage > 1): ?>
            <nav class="mt-3" aria-label="Halaman daftar Admin">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="small text-muted">Halaman <?= $currentPage ?> dari <?= $lastPage ?> (<?= (int) ($adminPagination['total'] ?? 0) ?> akun)</span>
                    <div>
                        <?php if ($currentPage > 1): ?>
                            <a class="btn btn-sm btn-outline-primary" href="<?= $escape($pageUrl($currentPage - 1)) ?>">Sebelumnya</a>
                        <?php endif; ?>
                        <?php if ($currentPage < $lastPage): ?>
                            <a class="btn btn-sm btn-outline-primary" href="<?= $escape($pageUrl($currentPage + 1)) ?>">Berikutnya</a>
                        <?php endif; ?>
                    </div>
                </div>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require dirname(__DIR__, 3) . '/layouts/admin.php';
?>