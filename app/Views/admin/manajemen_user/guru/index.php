<?php
$pageTitle = 'Manajemen Guru';
$activePage = 'user-guru';

$users = [
    [
        'id'       => 1,
        'nama'     => 'Budi Santoso, S.Kom.',
        'username' => '1987654321',
        'password' => 'Budi@123',
        'status'   => 'Aktif',
        'login'    => '04 Okt 2026, 07:45'
    ],
    [
        'id'       => 2,
        'nama'     => 'Siti Aminah, S.Pd.',
        'username' => '1987654322',
        'password' => 'Siti@123',
        'status'   => 'Aktif',
        'login'    => '03 Okt 2026, 10:20'
    ],
    [
        'id'       => 3,
        'nama'     => 'Andi Pratama, S.Kom.',
        'username' => '1987654323',
        'password' => 'Andi@123',
        'status'   => 'Aktif',
        'login'    => '01 Okt 2026, 08:30'
    ],
    [
        'id'       => 4,
        'nama'     => 'Dewi Lestari, S.Pd.',
        'username' => '1987654324',
        'password' => 'Dewi@123',
        'status'   => 'Nonaktif',
        'login'    => '25 Sep 2026, 11:15'
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

    .action-link {
        display: inline-block;
        background: none;
        border: 0;
        padding: 0;
        margin: 0 .75rem 0 0;
        font-size: .82rem;
        font-weight: 500;
        line-height: 1;
        cursor: pointer;
        text-decoration: none;
        box-shadow: none;
    }
    .action-link:last-child { margin-right: 0; }
    .action-link:hover { text-decoration: underline; }
    .action-link:focus { outline: 0; box-shadow: none; }
    .action-link.a-detail { color: #1f2937; }
    .action-link.a-edit { color: #2563eb; }
    .action-link.a-hapus { color: #e0264a; }
    .table-user td.col-aksi { white-space: nowrap; }

    .search-box { max-width: 320px; }
</style>

<div class="card card-user mb-4">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between flex-wrap mb-4">
            <div>
                <h5 class="font-weight-bold text-dark mb-1">Akun Guru</h5>
                <p class="text-muted small mb-0">Kelola username, password, dan status akun guru pembimbing.</p>
            </div>
            <a href="create.php" class="btn btn-info mt-2 mt-md-0">
                <i class="fas fa-plus mr-1"></i> Tambah Akun Guru
            </a>
        </div>

        <div class="alert alert-light border small mb-4">
            <i class="fas fa-info-circle text-info mr-2"></i>
            Admin dapat membuat, melihat detail, dan mengedit akun guru melalui halaman ini.
        </div>

        <div class="row mb-3">
            <div class="col-md-6 mb-2 mb-md-0">
                <div class="input-group search-box">
                    <div class="input-group-prepend"><span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span></div>
                    <input type="text" id="searchGuru" class="form-control" placeholder="Cari nama atau NIP...">
                </div>
            </div>
            <div class="col-md-3 ml-auto">
                <select id="filterStatusGuru" class="custom-select">
                    <option value="">Semua Status</option>
                    <option value="Aktif">Aktif</option>
                    <option value="Nonaktif">Nonaktif</option>
                </select>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-user mb-0" id="guruTable">
                <thead>
                    <tr>
                        <th width="50">No</th>
                        <th>Nama Guru</th>
                        <th>NIP / Username</th>
                        <th>Password</th>
                        <th>Status</th>
                        <th>Terakhir Login</th>
                        <th width="160">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $index => $user): ?>
                        <tr data-nama="<?= htmlspecialchars(strtolower($user['nama'])) ?>" data-username="<?= htmlspecialchars(strtolower($user['username'])) ?>" data-status="<?= htmlspecialchars($user['status']) ?>">
                            <td><?= $index + 1 ?></td>
                            <td><span class="font-weight-bold"><?= htmlspecialchars($user['nama']) ?></span></td>
                            <td><span class="username-text"><?= htmlspecialchars($user['username']) ?></span></td>
                            <td><span class="password-text"><?= htmlspecialchars($user['password']) ?></span></td>
                            <td>
                                <?php if ($user['status'] === 'Aktif'): ?>
                                    <span class="status-badge status-active"><i class="fas fa-check-circle mr-1"></i>Aktif</span>
                                <?php else: ?>
                                    <span class="status-badge status-inactive"><i class="fas fa-times-circle mr-1"></i>Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="small text-muted"><?= htmlspecialchars($user['login']) ?></span></td>
                            <td class="col-aksi">
                                <a href="detail.php?id=<?= $user['id'] ?>" class="action-link a-detail">Detail</a>
                                <a href="edit.php?id=<?= $user['id'] ?>" class="action-link a-edit">Edit</a>
                                <button type="button" class="action-link a-hapus" onclick="deleteGuru('<?= htmlspecialchars($user['nama'], ENT_QUOTES) ?>')">Hapus</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function deleteGuru(nama) {
    if (confirm('Apakah kamu yakin ingin menghapus akun "' + nama + '"?')) {
        alert('Akun ' + nama + ' berhasil dihapus.');
    }
}

document.getElementById('searchGuru').addEventListener('keyup', function() {
    const keyword = this.value.toLowerCase();
    document.querySelectorAll('#guruTable tbody tr').forEach(function(row) {
        const nama = row.getAttribute('data-nama');
        const username = row.getAttribute('data-username');
        row.style.display = (nama.includes(keyword) || username.includes(keyword)) ? '' : 'none';
    });
});

document.getElementById('filterStatusGuru').addEventListener('change', function() {
    const status = this.value;
    document.querySelectorAll('#guruTable tbody tr').forEach(function(row) {
        const rowStatus = row.getAttribute('data-status');
        row.style.display = (status === '' || rowStatus === status) ? '' : 'none';
    });
});
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__, 3) . '/layouts/admin.php';
?>