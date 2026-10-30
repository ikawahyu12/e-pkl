<?php
$pageTitle = 'Detail Akun Guru';
$activePage = 'user-guru';

$guru = [
    'id'       => 1,
    'nama'     => 'Budi Santoso, S.Kom.',
    'username' => '1987654321',
    'status'   => 'Aktif',
    'login'    => '04 Okt 2026, 07:45'
];

ob_start();
?>

<div class="container-fluid p-0">
    <div class="mb-3 small text-muted">
        <a href="index.php" class="text-secondary">Manajemen Guru</a> &gt; <span class="text-primary font-weight-bold">Detail Akun</span>
    </div>

    <div class="card border-0 shadow-sm rounded-lg mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-user-tie mr-2"></i>Detail Akun Guru</h6>
            <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Kembali</a>
        </div>
        <div class="card-body">
            <table class="table table-borderless table-sm mb-0">
                <tr>
                    <th width="200" class="text-muted">Nama Guru</th>
                    <td class="font-weight-bold">: <?= htmlspecialchars($guru['nama']) ?></td>
                </tr>
                <tr>
                    <th class="text-muted">NIP / Username</th>
                    <td>: <?= htmlspecialchars($guru['username']) ?></td>
                </tr>
                <tr>
                    <th class="text-muted">Status Akun</th>
                    <td>: <span class="badge badge-success px-2 py-1"><?= htmlspecialchars($guru['status']) ?></span></td>
                </tr>
                <tr>
                    <th class="text-muted">Terakhir Login</th>
                    <td>: <?= htmlspecialchars($guru['login']) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require dirname(__DIR__, 3) . '/layouts/admin.php';
?>