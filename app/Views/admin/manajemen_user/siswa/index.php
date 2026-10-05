<?php
$pageTitle = 'Manajemen Siswa';
$activePage = 'user-siswa';

$users = [
    [
        'id'       => 1,
        'nama'     => 'Ahmad Fauzan',
        'username' => '0056789123',
        'password' => 'Ahmad@123',
        'kelas'    => 'XII RPL 1',
        'status'   => 'Aktif',
        'login'    => '04 Okt 2026, 08:15'
    ],
    [
        'id'       => 2,
        'nama'     => 'Budi Santoso',
        'username' => '0056789124',
        'password' => 'Budi@123',
        'kelas'    => 'XII RPL 1',
        'status'   => 'Aktif',
        'login'    => '03 Okt 2026, 13:20'
    ],
    [
        'id'       => 3,
        'nama'     => 'Citra Lestari',
        'username' => '0056789125',
        'password' => 'Citra@123',
        'kelas'    => 'XII RPL 2',
        'status'   => 'Aktif',
        'login'    => '02 Okt 2026, 09:45'
    ],
    [
        'id'       => 4,
        'nama'     => 'Dimas Pratama',
        'username' => '0056789126',
        'password' => 'Dimas@123',
        'kelas'    => 'XII RPL 2',
        'status'   => 'Nonaktif',
        'login'    => '28 Sep 2026, 10:30'
    ]
];

ob_start();
?>

<style>
    .card-user { border: 1px solid #e8edf2; border-radius: .5rem; box-shadow: 0 .15rem .5rem rgba(58, 59, 69, .05); }
    .table-user thead th { background: #f8f9fc; color: #5a6573; font-size: .78rem; font-weight: 700; border-top: 0; white-space: nowrap; }
    .table-user tbody td { vertical-align: middle; color: #344054; font-size: .85rem; }
    .username-text { color: #176b8b; font-weight: 600; }
    .password-text { font-family: monospace; font-size: .82rem; color: #344054; background: #f5f7fa; padding: .3rem .5rem; border-radius: .3rem; }
    .status-badge { font-size: .72rem; padding: .35rem .6rem; border-radius: 20px; }
    .status-active { color: #198754; background: #e8f7ef; }
    .status-inactive { color: #dc3545; background: #fdebec; }
    .action-btn { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: .35rem; }
    .search-box { max-width: 320px; }
</style>

<div class="card card-user mb-4">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between flex-wrap mb-4">
            <div>
                <h5 class="font-weight-bold text-dark mb-1">Akun Siswa</h5>
                <p class="text-muted small mb-0">Kelola username, password, kelas, dan status akun siswa.</p>
            </div>
            <a href="create.php" class="btn btn-info mt-2 mt-md-0">
                <i class="fas fa-plus mr-1"></i> Tambah Akun Siswa
            </a>
        </div>

        <div class="alert alert-light border small mb-4">
            <i class="fas fa-info-circle text-info mr-2"></i>
            Admin dapat membuat, melihat detail, dan mengedit akun siswa melalui halaman ini.
        </div>

        <div class="row mb-3">
            <div class="col-md-6 mb-2 mb-md-0">
                <div class="input-group search-box">
                    <div class="input-group-prepend"><span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span></div>
                    <input type="text" id="searchUser" class="form-control" placeholder="Cari nama atau username...">
                </div>
            </div>
            <div class="col-md-3 ml-auto">
                <select id="filterStatus" class="custom-select">
                    <option value="">Semua Status</option>
                    <option value="Aktif">Aktif</option>
                    <option value="Nonaktif">Nonaktif</option>
                </select>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-user mb-0" id="userTable">
                <thead>
                    <tr>
                        <th width="50">No</th>
                        <th>Nama Siswa</th>
                        <th>Username</th>
                        <th>Password</th>
                        <th>Kelas</th>
                        <th>Status</th>
                        <th>Terakhir Login</th>
                        <th width="120">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $index => $user): ?>
                        <tr data-nama="<?= htmlspecialchars(strtolower($user['nama'])) ?>" data-username="<?= htmlspecialchars(strtolower($user['username'])) ?>" data-status="<?= htmlspecialchars($user['status']) ?>">
                            <td><?= $index + 1 ?></td>
                            <td><span class="font-weight-bold"><?= htmlspecialchars($user['nama']) ?></span></td>
                            <td><span class="username-text"><?= htmlspecialchars($user['username']) ?></span></td>
                            <td><span class="password-text"><?= htmlspecialchars($user['password']) ?></span></td>
                            <td><?= htmlspecialchars($user['kelas']) ?></td>
                            <td>
                                <?php if ($user['status'] === 'Aktif'): ?>
                                    <span class="status-badge status-active"><i class="fas fa-check-circle mr-1"></i>Aktif</span>
                                <?php else: ?>
                                    <span class="status-badge status-inactive"><i class="fas fa-times-circle mr-1"></i>Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="small text-muted"><?= htmlspecialchars($user['login']) ?></span></td>
                            <td>
                                <a href="detail.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-primary action-btn mr-1" title="Detail"><i class="fas fa-eye"></i></a>
                                <a href="edit.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-info action-btn mr-1" title="Edit"><i class="fas fa-edit"></i></a>
                                <button type="button" class="btn btn-sm btn-outline-danger action-btn" title="Delete" onclick="deleteUser('<?= htmlspecialchars($user['nama'], ENT_QUOTES) ?>')"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function deleteUser(nama) {
    if (confirm('Apakah kamu yakin ingin menghapus akun "' + nama + '"?')) {
        alert('Akun ' + nama + ' berhasil dihapus.');
    }
}

document.getElementById('searchUser').addEventListener('keyup', function() {
    const keyword = this.value.toLowerCase();
    document.querySelectorAll('#userTable tbody tr').forEach(function(row) {
        const nama = row.getAttribute('data-nama');
        const username = row.getAttribute('data-username');
        row.style.display = (nama.includes(keyword) || username.includes(keyword)) ? '' : 'none';
    });
});

document.getElementById('filterStatus').addEventListener('change', function() {
    const status = this.value;
    document.querySelectorAll('#userTable tbody tr').forEach(function(row) {
        const rowStatus = row.getAttribute('data-status');
        row.style.display = (status === '' || rowStatus === status) ? '' : 'none';
    });
});
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__, 3) . '/layouts/admin.php';
?>