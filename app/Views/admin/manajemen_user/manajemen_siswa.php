<?php

// Asset (tidak hardcode nama folder)
$scriptDir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$assetBaseUrl = $scriptDir . '/assets';

$pageTitle = 'Manajemen Siswa';
$pageSubtitle = 'Kelola akun pengguna siswa.';
$pageStatusLabel = 'Manajemen Siswa';
$activePage = 'manajemen-user';

$users = [
    [
        'id' => 1,
        'nama' => 'Ahmad Fauzan',
        'username' => '0056789123',
        'password' => 'Ahmad@123',
        'kelas' => 'XII RPL 1',
        'status' => 'Aktif',
        'login' => '04 Okt 2026, 08:15'
    ],
    [
        'id' => 2,
        'nama' => 'Budi Santoso',
        'username' => '0056789124',
        'password' => 'Budi@123',
        'kelas' => 'XII RPL 1',
        'status' => 'Aktif',
        'login' => '03 Okt 2026, 13:20'
    ],
    [
        'id' => 3,
        'nama' => 'Citra Lestari',
        'username' => '0056789125',
        'password' => 'Citra@123',
        'kelas' => 'XII RPL 2',
        'status' => 'Aktif',
        'login' => '02 Okt 2026, 09:45'
    ],
    [
        'id' => 4,
        'nama' => 'Dimas Pratama',
        'username' => '0056789126',
        'password' => 'Dimas@123',
        'kelas' => 'XII RPL 2',
        'status' => 'Nonaktif',
        'login' => '28 Sep 2026, 10:30'
    ]
];

ob_start();
?>

<style>
    .card-user {
        border: 1px solid #e8edf2;
        border-radius: .5rem;
        box-shadow: 0 .15rem .5rem rgba(58, 59, 69, .05);
    }

    .role-nav-btn {
        border-radius: 8px;
        padding: 10px 18px;
        font-weight: 600;
        font-size: .85rem;
        color: #5a6573;
        background: #f8f9fc;
        border: 1px solid #e3e6f0;
        transition: all 0.2s ease-in-out;
        display: inline-flex;
        align-items: center;
        text-decoration: none !important;
    }

    .role-nav-btn:hover {
        background: #eaecf4;
        color: #176b8b;
    }

    .role-nav-btn.active {
        background: #176b8b;
        color: #ffffff;
        border-color: #176b8b;
        box-shadow: 0 4px 10px rgba(23, 107, 139, 0.25);
    }

    .table-user thead th {
        background: #f8f9fc;
        color: #5a6573;
        font-size: .78rem;
        font-weight: 700;
        border-top: 0;
        white-space: nowrap;
    }

    .table-user tbody td {
        vertical-align: middle;
        color: #344054;
        font-size: .85rem;
    }

    .username-text {
        color: #176b8b;
        font-weight: 600;
    }

    .password-text {
        font-family: monospace;
        font-size: .82rem;
        color: #344054;
        background: #f5f7fa;
        padding: .3rem .5rem;
        border-radius: .3rem;
    }

    .status-badge {
        font-size: .72rem;
        padding: .35rem .6rem;
        border-radius: 20px;
    }

    .status-active {
        color: #198754;
        background: #e8f7ef;
    }

    .status-inactive {
        color: #dc3545;
        background: #fdebec;
    }

    .action-btn {
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: .35rem;
    }

    .form-control,
    .custom-select {
        border-color: #dce1e7;
    }

    .form-control:focus,
    .custom-select:focus {
        border-color: #176b8b;
        box-shadow: 0 0 0 .2rem rgba(23, 107, 139, .12);
    }

    .search-box {
        max-width: 320px;
    }
</style>

<!-- NAVIGASI PILIH ROLE USER -->
<div class="card card-user mb-4">
    <div class="card-body p-3">
        <label class="small font-weight-bold text-muted d-block mb-2">Pilih Role User yang Ingin Dikelola:</label>
        <div class="d-flex align-items-center flex-wrap">
            <a href="?page=manajemen-admin" class="role-nav-btn mr-2 mb-2">
                <i class="fas fa-user-shield mr-2"></i> Admin
            </a>
            <a href="?page=manajemen-guru" class="role-nav-btn mr-2 mb-2">
                <i class="fas fa-chalkboard-teacher mr-2"></i> Guru
            </a>
            <a href="?page=manajemen-siswa" class="role-nav-btn mr-2 mb-2 active">
                <i class="fas fa-user-graduate mr-2"></i> Siswa
            </a>
        </div>
    </div>
</div>

<div class="card card-user mb-4">
    <div class="card-body">

        <!-- Header -->
        <div class="d-flex align-items-center justify-content-between flex-wrap mb-4">
            <div>
                <h5 class="font-weight-bold text-dark mb-1">
                    Akun Siswa
                </h5>
                <p class="text-muted small mb-0">
                    Kelola username, password, kelas, dan status akun siswa.
                </p>
            </div>

            <button type="button"
                    class="btn btn-info mt-2 mt-md-0"
                    data-toggle="modal"
                    data-target="#modalTambah">
                <i class="fas fa-plus mr-1"></i>
                Tambah Akun Siswa
            </button>
        </div>

        <!-- Informasi -->
        <div class="alert alert-light border small mb-4">
            <i class="fas fa-info-circle text-info mr-2"></i>
            Admin dapat membuat dan mengedit akun siswa melalui halaman ini.
        </div>

        <!-- Search & Filter -->
        <div class="row mb-3">
            <div class="col-md-6 mb-2 mb-md-0">
                <div class="input-group search-box">
                    <div class="input-group-prepend">
                        <span class="input-group-text bg-white">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                    </div>

                    <input type="text"
                           id="searchUser"
                           class="form-control"
                           placeholder="Cari nama atau username...">
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

        <!-- Table -->
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
                        <th width="100">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($users as $index => $user): ?>
                        <tr
                            data-nama="<?= htmlspecialchars(strtolower($user['nama'])) ?>"
                            data-username="<?= htmlspecialchars(strtolower($user['username'])) ?>"
                            data-status="<?= htmlspecialchars($user['status']) ?>"
                        >
                            <td><?= $index + 1 ?></td>

                            <td>
                                <span class="font-weight-bold">
                                    <?= htmlspecialchars($user['nama']) ?>
                                </span>
                            </td>

                            <td>
                                <span class="username-text">
                                    <?= htmlspecialchars($user['username']) ?>
                                </span>
                            </td>

                            <td>
                                <span class="password-text">
                                    <?= htmlspecialchars($user['password']) ?>
                                </span>
                            </td>

                            <td>
                                <?= htmlspecialchars($user['kelas']) ?>
                            </td>

                            <td>
                                <?php if ($user['status'] === 'Aktif'): ?>
                                    <span class="status-badge status-active">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        Aktif
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge status-inactive">
                                        <i class="fas fa-times-circle mr-1"></i>
                                        Nonaktif
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="small text-muted">
                                    <?= htmlspecialchars($user['login']) ?>
                                </span>
                            </td>

                            <td>
                                <!-- Edit -->
                                <button type="button"
                                        class="btn btn-sm btn-outline-info action-btn mr-1"
                                        title="Edit"
                                        onclick='editUser(<?= json_encode($user) ?>)'>
                                    <i class="fas fa-edit"></i>
                                </button>

                                <!-- Delete -->
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger action-btn"
                                        title="Delete"
                                        onclick="deleteUser('<?= htmlspecialchars($user['nama'], ENT_QUOTES) ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>


<!-- MODAL TAMBAH AKUN -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title font-weight-bold">
                    <i class="fas fa-user-plus text-info mr-2"></i>
                    Tambah Akun Siswa
                </h5>

                <button type="button"
                        class="close"
                        data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>

            <form id="formTambah">

                <div class="modal-body">

                    <div class="row">

                        <!-- Nama -->
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold small">
                                Nama Siswa
                            </label>

                            <input type="text"
                                   id="tambahNama"
                                   class="form-control"
                                   placeholder="Masukkan nama siswa"
                                   required>
                        </div>

                        <!-- Username -->
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold small">
                                Username / NISN
                            </label>

                            <input type="text"
                                   id="tambahUsername"
                                   class="form-control"
                                   placeholder="Masukkan NISN"
                                   required>
                        </div>

                        <!-- Password -->
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold small">
                                Password
                            </label>

                            <div class="input-group">

                                <input type="password"
                                       id="tambahPassword"
                                       class="form-control"
                                       placeholder="Masukkan password"
                                       required>

                                <div class="input-group-append">
                                    <button type="button"
                                            class="btn btn-outline-secondary"
                                            onclick="togglePassword('tambahPassword', this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>

                            </div>
                        </div>

                        <!-- Konfirmasi Password -->
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold small">
                                Konfirmasi Password
                            </label>

                            <input type="password"
                                   id="tambahKonfirmasi"
                                   class="form-control"
                                   placeholder="Ulangi password"
                                   required>
                        </div>

                        <!-- Kelas -->
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold small">
                                Kelas
                            </label>

                            <select id="tambahKelas"
                                    class="custom-select"
                                    required>

                                <option value="">Pilih Kelas</option>
                                <option>XII RPL 1</option>
                                <option>XII RPL 2</option>
                                <option>XI RPL 1</option>
                                <option>XI RPL 2</option>
                                <option>X RPL 1</option>
                                <option>X RPL 2</option>

                            </select>
                        </div>

                        <!-- Status -->
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold small">
                                Status
                            </label>

                            <select id="tambahStatus"
                                    class="custom-select"
                                    required>

                                <option value="Aktif">Aktif</option>
                                <option value="Nonaktif">Nonaktif</option>

                            </select>
                        </div>

                    </div>

                </div>

                <div class="modal-footer">

                    <button type="button"
                            class="btn btn-light"
                            data-dismiss="modal">
                        Batal
                    </button>

                    <button type="submit"
                            class="btn btn-info">
                        <i class="fas fa-save mr-1"></i>
                        Simpan Akun
                    </button>

                </div>

            </form>

        </div>
    </div>
</div>


<!-- MODAL EDIT AKUN -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title font-weight-bold">
                    <i class="fas fa-user-edit text-info mr-2"></i>
                    Edit Akun Siswa
                </h5>

                <button type="button"
                        class="close"
                        data-dismiss="modal">
                    <span>&times;</span>
                </button>

            </div>

            <form id="formEdit">

                <div class="modal-body">

                    <input type="hidden" id="editId">

                    <div class="row">

                        <!-- Nama -->
                        <div class="col-md-6 mb-3">

                            <label class="font-weight-bold small">
                                Nama Siswa
                            </label>

                            <input type="text"
                                   id="editNama"
                                   class="form-control"
                                   required>

                        </div>

                        <!-- Username -->
                        <div class="col-md-6 mb-3">

                            <label class="font-weight-bold small">
                                Username / NISN
                            </label>

                            <input type="text"
                                   id="editUsername"
                                   class="form-control"
                                   required>

                        </div>

                        <!-- Password -->
                        <div class="col-md-6 mb-3">

                            <label class="font-weight-bold small">
                                Password
                            </label>

                            <div class="input-group">

                                <input type="password"
                                       id="editPassword"
                                       class="form-control"
                                       required>

                                <div class="input-group-append">

                                    <button type="button"
                                            class="btn btn-outline-secondary"
                                            onclick="togglePassword('editPassword', this)">

                                        <i class="fas fa-eye"></i>

                                    </button>

                                </div>

                            </div>

                            <small class="text-muted">
                                Password dapat diubah langsung melalui form edit.
                            </small>

                        </div>

                        <!-- Kelas -->
                        <div class="col-md-6 mb-3">

                            <label class="font-weight-bold small">
                                Kelas
                            </label>

                            <select id="editKelas"
                                    class="custom-select"
                                    required>

                                <option>XII RPL 1</option>
                                <option>XII RPL 2</option>
                                <option>XI RPL 1</option>
                                <option>XI RPL 2</option>
                                <option>X RPL 1</option>
                                <option>X RPL 2</option>

                            </select>

                        </div>

                        <!-- Status -->
                        <div class="col-md-6 mb-3">

                            <label class="font-weight-bold small">
                                Status
                            </label>

                            <select id="editStatus"
                                    class="custom-select"
                                    required>

                                <option value="Aktif">Aktif</option>
                                <option value="Nonaktif">Nonaktif</option>

                            </select>

                        </div>

                    </div>

                </div>

                <div class="modal-footer">

                    <button type="button"
                            class="btn btn-light"
                            data-dismiss="modal">
                        Batal
                    </button>

                    <button type="submit"
                            class="btn btn-info">
                        <i class="fas fa-save mr-1"></i>
                        Simpan Perubahan
                    </button>

                </div>

            </form>

        </div>
    </div>
</div>


<script>

    function togglePassword(inputId, button) {

        const input = document.getElementById(inputId);
        const icon = button.querySelector('i');

        if (input.type === 'password') {

            input.type = 'text';

            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');

        } else {

            input.type = 'password';

            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');

        }
    }


    function editUser(user) {

        document.getElementById('editId').value = user.id;
        document.getElementById('editNama').value = user.nama;
        document.getElementById('editUsername').value = user.username;
        document.getElementById('editPassword').value = user.password;
        document.getElementById('editKelas').value = user.kelas;
        document.getElementById('editStatus').value = user.status;

        $('#modalEdit').modal('show');
    }


    document.getElementById('formTambah').addEventListener('submit', function(e) {

        e.preventDefault();

        const password = document.getElementById('tambahPassword').value;
        const konfirmasi = document.getElementById('tambahKonfirmasi').value;

        if (password !== konfirmasi) {

            alert('Konfirmasi password tidak sama.');

            return;
        }

        alert('Akun siswa berhasil ditambahkan.');

        $('#modalTambah').modal('hide');

        this.reset();

    });


    document.getElementById('formEdit').addEventListener('submit', function(e) {

        e.preventDefault();

        alert('Data akun siswa berhasil diperbarui.');

        $('#modalEdit').modal('hide');

    });


    function deleteUser(nama) {

        const yakin = confirm(
            'Apakah kamu yakin ingin menghapus akun "' + nama + '"?'
        );

        if (yakin) {

            alert('Akun ' + nama + ' berhasil dihapus.');

        }

    }


    document.getElementById('searchUser').addEventListener('keyup', function() {

        const keyword = this.value.toLowerCase();

        const rows = document.querySelectorAll('#userTable tbody tr');

        rows.forEach(function(row) {

            const nama = row.getAttribute('data-nama');
            const username = row.getAttribute('data-username');

            if (
                nama.includes(keyword) ||
                username.includes(keyword)
            ) {

                row.style.display = '';

            } else {

                row.style.display = 'none';

            }

        });

    });


    document.getElementById('filterStatus').addEventListener('change', function() {

        const status = this.value;

        const rows = document.querySelectorAll('#userTable tbody tr');

        rows.forEach(function(row) {

            const rowStatus = row.getAttribute('data-status');

            if (
                status === '' ||
                rowStatus === status
            ) {

                row.style.display = '';

            } else {

                row.style.display = 'none';

            }

        });

    });

</script>

<?php

$content = ob_get_clean();

require __DIR__ . '/../../layouts/admin.php';
?>