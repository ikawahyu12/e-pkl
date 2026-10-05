<?php
$pageTitle = 'Detail Admin';
$activePage = 'user-admin';

$admin = [
    'id'       => 1,
    'nama'     => 'Admin Utama',
    'username' => 'admin',
    'status'   => 'Aktif',
    'login'    => '04 Okt 2026, 08:00'
];

ob_start();
?>

<div class="container-fluid p-0">
    <div class="mb-3 small text-muted">
        <a href="index.php" class="text-secondary">Manajemen Admin</a> &gt; <span class="text-primary font-weight-bold">Detail Admin</span>
    </div>

    <div class="card border-0 shadow-sm rounded-lg mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-user-shield mr-2"></i>Detail Akun Admin</h6>
            <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Kembali</a>
        </div>
        <div class="card-body">
            <table class="table table-borderless table-sm mb-0">
                <tr>
                    <th width="200" class="text-muted">Nama Admin</th>
                    <td class="font-weight-bold">: <?= htmlspecialchars($admin['nama']) ?></td>
                </tr>
                <tr>
                    <th class="text-muted">Username</th>
                    <td>: <?= htmlspecialchars($admin['username']) ?></td>
                </tr>
                <tr>
                    <th class="text-muted">Status Akun</th>
                    <td>: <span class="badge badge-success px-2 py-1"><?= htmlspecialchars($admin['status']) ?></span></td>
                </tr>
                <tr>
                    <th class="text-muted">Terakhir Login</th>
                    <td>: <?= htmlspecialchars($admin['login']) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require dirname(__DIR__, 3) . '/layouts/admin.php';
?>