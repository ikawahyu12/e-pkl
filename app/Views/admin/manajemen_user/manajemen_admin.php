<?php

$assetBaseUrl = '/epkl_website/e-pkl/public/assets';

$pageTitle = 'Manajemen Admin';
$pageSubtitle = 'Kelola akun administrator sistem.';
$pageStatusLabel = 'Manajemen Admin';
$activePage = 'manajemen-user';

$users = [
    [
        'id' => 1,
        'nama' => 'Admin Utama',
        'username' => 'admin',
        'password' => 'Admin@123',
        'status' => 'Aktif',
        'login' => '04 Okt 2026, 08:00'
    ],
    [
        'id' => 2,
        'nama' => 'Admin Akademik',
        'username' => 'admin.akademik',
        'password' => 'Akademik@123',
        'status' => 'Aktif',
        'login' => '03 Okt 2026, 09:15'
    ],
    [
        'id' => 3,
        'nama' => 'Admin Sekolah',
        'username' => 'admin.sekolah',
        'password' => 'Sekolah@123',
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

<div class="card card-user mb-4">
    <div class="card-body">

        <div class="d-flex align-items-center justify-content-between flex-wrap mb-4">
            <div>
                <h5 class="font-weight-bold text-dark mb-1">
                    Akun Admin
                </h5>

                <p class="text-muted small mb-0">
                    Kelola username, password, dan status akun administrator.
                </p>
            </div>

            <button type="button"
                    class="btn btn-info mt-2 mt-md-0"
                    data-toggle="modal"
                    data-target="#modalTambahAdmin">
                <i class="fas fa-plus mr-1"></i>
                Tambah Akun Admin
            </button>
        </div>

        <div class="alert alert-light border small mb-4">
            <i class="fas fa-info-circle text-info mr-2"></i>
            Admin dapat membuat dan mengedit akun administrator melalui halaman ini.
        </div>

        <div class="row mb-3">

            <div class="col-md-6 mb-2 mb-md-0">

                <div class="input-group search-box">

                    <div class="input-group-prepend">
                        <span class="input-group-text bg-white">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                    </div>

                    <input type="text"
                           id="searchAdmin"
                           class="form-control"
                           placeholder="Cari nama atau username...">

                </div>

            </div>

            <div class="col-md-3 ml-auto">

                <select id="filterStatusAdmin"
                        class="custom-select">

                    <option value="">Semua Status</option>
                    <option value="Aktif">Aktif</option>
                    <option value="Nonaktif">Nonaktif</option>

                </select>

            </div>

        </div>

        <div class="table-responsive">

            <table class="table table-hover table-user mb-0"
                   id="adminTable">

                <thead>

                    <tr>
                        <th width="50">No</th>
                        <th>Nama Admin</th>
                        <th>Username</th>
                        <th>Password</th>
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

                            <td>
                                <?= $index + 1 ?>
                            </td>

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

                                <button type="button"
                                        class="btn btn-sm btn-outline-info action-btn mr-1"
                                        title="Edit"
                                        onclick='editAdmin(<?= json_encode($user) ?>)'>

                                    <i class="fas fa-edit"></i>

                                </button>

                                <button type="button"
                                        class="btn btn-sm btn-outline-danger action-btn"
                                        title="Delete"
                                        onclick="deleteAdmin('<?= htmlspecialchars($user['nama'], ENT_QUOTES) ?>')">

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


<!-- MODAL TAMBAH ADMIN -->

<div class="modal fade"
     id="modalTambahAdmin"
     tabindex="-1">

    <div class="modal-dialog modal-lg">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title font-weight-bold">
                    <i class="fas fa-user-plus text-info mr-2"></i>
                    Tambah Akun Admin
                </h5>

                <button type="button"
                        class="close"
                        data-dismiss="modal">

                    <span>&times;</span>

                </button>

            </div>

            <form id="formTambahAdmin">

                <div class="modal-body">

                    <div class="row">

                        <div class="col-md-6 mb-3">

                            <label class="font-weight-bold small">
                                Nama Admin
                            </label>

                            <input type="text"
                                   id="tambahNamaAdmin"
                                   class="form-control"
                                   placeholder="Masukkan nama admin"
                                   required>

                        </div>

                        <div class="col-md-6 mb-3">

                            <label class="font-weight-bold small">
                                Username
                            </label>

                            <input type="text"
                                   id="tambahUsernameAdmin"
                                   class="form-control"
                                   placeholder="Masukkan username"
                                   required>

                        </div>

                        <div class="col-md-6 mb-3">

                            <label class="font-weight-bold small">
                                Password
                            </label>

                            <div class="input-group">

                                <input type="password"
                                       id="tambahPasswordAdmin"
                                       class="form-control"
                                       placeholder="Masukkan password"
                                       required>

                                <div class="input-group-append">

                                    <button type="button"
                                            class="btn btn-outline-secondary"
                                            onclick="togglePasswordAdmin('tambahPasswordAdmin', this)">

                                        <i class="fas fa-eye"></i>

                                    </button>

                                </div>

                            </div>

                        </div>

                        <div class="col-md-6 mb-3">

                            <label class="font-weight-bold small">
                                Konfirmasi Password
                            </label>

                            <input type="password"
                                   id="tambahKonfirmasiAdmin"
                                   class="form-control"
                                   placeholder="Ulangi password"
                                   required>

                        </div>

                        <div class="col-md-6 mb-3">

                            <label class="font-weight-bold small">
                                Status
                            </label>

                            <select id="tambahStatusAdmin"
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


<!-- MODAL EDIT ADMIN -->

<div class="modal fade"
     id="modalEditAdmin"
     tabindex="-1">

    <div class="modal-dialog modal-lg">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title font-weight-bold">

                    <i class="fas fa-user-edit text-info mr-2"></i>
                    Edit Akun Admin

                </h5>

                <button type="button"
                        class="close"
                        data-dismiss="modal">

                    <span>&times;</span>

                </button>

            </div>

            <form id="formEditAdmin">

                <div class="modal-body">

                    <input type="hidden"
                           id="editIdAdmin">

                    <div class="row">

                        <div class="col-md-6 mb-3">

                            <label class="font-weight-bold small">
                                Nama Admin
                            </label>

                            <input type="text"
                                   id="editNamaAdmin"
                                   class="form-control"
                                   required>

                        </div>

                        <div class="col-md-6 mb-3">

                            <label class="font-weight-bold small">
                                Username
                            </label>

                            <input type="text"
                                   id="editUsernameAdmin"
                                   class="form-control"
                                   required>

                        </div>

                        <div class="col-md-6 mb-3">

                            <label class="font-weight-bold small">
                                Password
                            </label>

                            <div class="input-group">

                                <input type="password"
                                       id="editPasswordAdmin"
                                       class="form-control"
                                       required>

                                <div class="input-group-append">

                                    <button type="button"
                                            class="btn btn-outline-secondary"
                                            onclick="togglePasswordAdmin('editPasswordAdmin', this)">

                                        <i class="fas fa-eye"></i>

                                    </button>

                                </div>

                            </div>

                            <small class="text-muted">
                                Password dapat diubah melalui form edit.
                            </small>

                        </div>

                        <div class="col-md-6 mb-3">

                            <label class="font-weight-bold small">
                                Status
                            </label>

                            <select id="editStatusAdmin"
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

function togglePasswordAdmin(inputId, button) {

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


function editAdmin(user) {

    document.getElementById('editIdAdmin').value = user.id;
    document.getElementById('editNamaAdmin').value = user.nama;
    document.getElementById('editUsernameAdmin').value = user.username;
    document.getElementById('editPasswordAdmin').value = user.password;
    document.getElementById('editStatusAdmin').value = user.status;

    $('#modalEditAdmin').modal('show');

}


document.getElementById('formTambahAdmin')
    .addEventListener('submit', function(e) {

        e.preventDefault();

        const password =
            document.getElementById('tambahPasswordAdmin').value;

        const konfirmasi =
            document.getElementById('tambahKonfirmasiAdmin').value;

        if (password !== konfirmasi) {

            alert('Konfirmasi password tidak sama.');

            return;

        }

        alert('Akun admin berhasil ditambahkan.');

        $('#modalTambahAdmin').modal('hide');

        this.reset();

    });


document.getElementById('formEditAdmin')
    .addEventListener('submit', function(e) {

        e.preventDefault();

        alert('Data akun admin berhasil diperbarui.');

        $('#modalEditAdmin').modal('hide');

    });


function deleteAdmin(nama) {

    const yakin = confirm(
        'Apakah kamu yakin ingin menghapus akun "' +
        nama +
        '"?'
    );

    if (yakin) {

        alert('Akun ' + nama + ' berhasil dihapus.');

    }

}


document.getElementById('searchAdmin')
    .addEventListener('keyup', function() {

        const keyword = this.value.toLowerCase();

        const rows =
            document.querySelectorAll('#adminTable tbody tr');

        rows.forEach(function(row) {

            const nama =
                row.getAttribute('data-nama');

            const username =
                row.getAttribute('data-username');

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


document.getElementById('filterStatusAdmin')
    .addEventListener('change', function() {

        const status = this.value;

        const rows =
            document.querySelectorAll('#adminTable tbody tr');

        rows.forEach(function(row) {

            const rowStatus =
                row.getAttribute('data-status');

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

require __DIR__ . '/../layouts/admin.php';

?>