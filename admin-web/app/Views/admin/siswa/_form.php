<?php
// 1. Panggil koneksi
require_once __DIR__ . '/../../../../../config/koneksi.php';
require_once dirname(__DIR__, 3) . '/Support/Csrf.php';

$urlDaftar = '?page=data-siswa';
$mode      = isset($_GET['id']) ? 'edit' : 'create';
$id        = (int) ($_GET['id'] ?? 0);
$data      = [];
$pesanError = '';
$studentUserId = 0;
$csrfToken = AdminCsrf::token();
$opsiKelas = [];

// ==========================================
// 1. LOAD DATA JIKA MODE EDIT
// ==========================================
if ($mode === 'edit' && $id > 0) {
    $lookup = mysqli_prepare($koneksi, 'SELECT user_id FROM siswa WHERE id = ? LIMIT 1');
    mysqli_stmt_bind_param($lookup, 'i', $id);
    mysqli_stmt_execute($lookup);
    mysqli_stmt_bind_result($lookup, $studentUserId);
    $studentFound = mysqli_stmt_fetch($lookup);
    mysqli_stmt_close($lookup);
    $studentUserId = $studentFound ? (int) $studentUserId : 0;

    $token = $_SESSION['admin_token'] ?? '';
    if ($studentUserId < 1 || !is_string($token) || $token === '') {
        $pesanError = 'Akun Siswa atau sesi Admin tidak valid.';
    } else {
        try {
            $result = $api->adminStudent($token, $studentUserId);
            if ($result['status'] >= 200 && $result['status'] < 300 && is_array($result['body']['data'] ?? null)) {
                $student = $result['body']['data'];
                $data = $student + [
                    'nama_siswa' => $student['nama'] ?? '',
                    'kelas' => $student['kelas'] ?? '',
                    'hp_orang_tua' => $student['no_hp_orang_tua'] ?? '',
                ];
                $classResult = $api->adminStudentClasses($token);
                if (
                    $classResult['status'] >= 200
                    && $classResult['status'] < 300
                    && is_array($classResult['body']['data'] ?? null)
                ) {
                    $opsiKelas = $classResult['body']['data'];
                } else {
                    $pesanError = $classResult['body']['message'] ?? 'Daftar kelas tidak dapat dimuat.';
                }
            } else {
                $pesanError = $result['body']['message'] ?? 'Data Siswa tidak dapat dimuat.';
            }
        } catch (Throwable $exception) {
            error_log('Legacy Siswa edit API load error: ' . $exception->getMessage());
            $pesanError = 'Data Siswa tidak dapat dimuat melalui API.';
        }
    }
}

// ==========================================
// 2. PROSES SIMPAN (INSERT / UPDATE)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'edit') {
        if (!AdminCsrf::verify($_POST['csrf_token'] ?? null)) {
            $pesanError = 'Formulir kedaluwarsa atau tidak valid. Muat ulang halaman dan coba lagi.';
        } else {
            $nisn = trim((string) ($_POST['nisn'] ?? ''));
            $nama = trim((string) ($_POST['nama'] ?? ''));
            $kelasId = filter_var($_POST['kelas'] ?? null, FILTER_VALIDATE_INT);
            $jenisKelamin = match ($_POST['jenis_kelamin'] ?? '') {
                'L', 'Laki-laki' => 'L',
                'P', 'Perempuan' => 'P',
                default => '',
            };

            if (
                $studentUserId < 1
                || $nisn === ''
                || $nama === ''
                || $kelasId === false
                || $kelasId < 1
                || !in_array($jenisKelamin, ['L', 'P'], true)
            ) {
                $pesanError = 'Lengkapi NISN, nama, kelas, dan jenis kelamin dengan nilai yang valid.';
            } else {
                $token = $_SESSION['admin_token'] ?? '';
                try {
                    if (!is_string($token) || $token === '') {
                        throw new RuntimeException('Sesi Admin tidak valid.');
                    }

                    $payload = [
                        'nisn' => $nisn,
                        'nama' => $nama,
                        'kelas_id' => $kelasId,
                        'jenis_kelamin' => $jenisKelamin,
                        'no_hp' => trim((string) ($_POST['no_hp'] ?? '')) ?: null,
                        'alamat' => trim((string) ($_POST['alamat'] ?? '')) ?: null,
                        'nama_orang_tua' => trim((string) ($_POST['nama_orang_tua'] ?? '')) ?: null,
                        'no_hp_orang_tua' => trim((string) ($_POST['hp_orang_tua'] ?? '')) ?: null,
                    ];

                    $result = $api->updateAdminStudent($token, $studentUserId, $payload);
                    if ($result['status'] >= 200 && $result['status'] < 300) {
                        header('Location: ' . $urlDaftar);
                        exit;
                    }

                    $messages = [];
                    foreach (($result['body']['errors'] ?? []) as $fieldErrors) {
                        foreach ((array) $fieldErrors as $fieldError) {
                            $messages[] = (string) $fieldError;
                        }
                    }
                    $pesanError = $messages !== []
                        ? implode(' ', $messages)
                        : ($result['body']['message'] ?? 'Data Siswa gagal diperbarui.');
                } catch (Throwable $exception) {
                    error_log('Legacy Siswa edit API update error: ' . $exception->getMessage());
                    $pesanError = 'Data Siswa gagal diperbarui melalui API. Periksa koneksi dan sesi Admin.';
                }
            }
        }
    } else {
        $nisn = mysqli_real_escape_string($koneksi, $_POST['nisn'] ?? '');
        $nama_siswa = mysqli_real_escape_string($koneksi, $_POST['nama'] ?? '');
        $kelas = mysqli_real_escape_string($koneksi, $_POST['kelas'] ?? '');
        $jenis_kelamin = mysqli_real_escape_string($koneksi, $_POST['jenis_kelamin'] ?? '');
        $no_hp = mysqli_real_escape_string($koneksi, $_POST['no_hp'] ?? '');
        $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat'] ?? '');
        $nama_orang_tua = mysqli_real_escape_string($koneksi, $_POST['nama_orang_tua'] ?? '');
        $hp_orang_tua = mysqli_real_escape_string($koneksi, $_POST['hp_orang_tua'] ?? '');
        $id_instansi = (int) ($_POST['instansi_id'] ?? 0);
        $guru_pembimbing = mysqli_real_escape_string($koneksi, $_POST['guru_id'] ?? '');
        $mulai_pkl = mysqli_real_escape_string($koneksi, $_POST['mulai_pkl'] ?? '');
        $selesai_pkl = mysqli_real_escape_string($koneksi, $_POST['selesai_pkl'] ?? '');
        $status = mysqli_real_escape_string($koneksi, $_POST['status'] ?? 'Aktif');

        if (empty($nisn) || empty($nama_siswa) || empty($kelas) || empty($jenis_kelamin)) {
            $pesanError = 'Harap isi kolom NISN, Nama, Kelas, dan Jenis Kelamin.';
        } else {
            $sql = "INSERT INTO siswa (
                        nisn, nama_siswa, kelas, jenis_kelamin, no_hp, alamat,
                        nama_orang_tua, hp_orang_tua, id_instansi, guru_pembimbing,
                        mulai_pkl, selesai_pkl, status
                    ) VALUES (
                        '$nisn', '$nama_siswa', '$kelas', '$jenis_kelamin', '$no_hp', '$alamat',
                        '$nama_orang_tua', '$hp_orang_tua', " . ($id_instansi > 0 ? $id_instansi : "NULL") . ", '$guru_pembimbing',
                        " . ($mulai_pkl ? "'$mulai_pkl'" : "NULL") . ",
                        " . ($selesai_pkl ? "'$selesai_pkl'" : "NULL") . ", '$status'
                    )";

            if (mysqli_query($koneksi, $sql)) {
                echo "<script>alert('Data siswa berhasil disimpan!'); window.location='$urlDaftar';</script>";
                exit;
            }
            $pesanError = 'Gagal menyimpan: ' . mysqli_error($koneksi);
        }
    }
}

// ==========================================
// 3. AMBIL DATA DROPDOWN
// ==========================================
// Dropdown Instansi
$opsiInstansi = [];
$qInst = mysqli_query($koneksi, "SELECT id_instansi, nama_instansi FROM instansi WHERE status = 'aktif' ORDER BY nama_instansi ASC");
while ($i = mysqli_fetch_assoc($qInst)) {
    $opsiInstansi[] = $i;
}

// Dropdown Guru (sementara pakai array manual, ganti dengan query tabel guru jika sudah ada)
$opsiGuru = [
    'Alfredo H, S.Kom', 
    'Dewi Sartika, M.Sn', 
    'Eko Prasetyo, S.T'
];

$pageTitle  = ($mode === 'edit') ? 'Edit Data Siswa' : 'Tambah Data Siswa';
$activePage = 'data-siswa';

ob_start();
?>
<style>
    .form-wrap { background: #fff; border-radius: 16px; padding: 1.5rem; border: 1px solid #eef2f7; box-shadow: 0 1px 3px rgba(16,24,40,.06); }
    .alert-info { background: #eaf2ff; border: 1px solid #dbe8fb; border-radius: 10px; padding: .75rem 1rem; font-size: .78rem; color: #1e293b; margin-bottom: 1.5rem; display: flex; gap: .6rem; }
    .alert-info i { color: #2563eb; margin-top: 2px; }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    .form-group { display: flex; flex-direction: column; }
    .form-group.full { grid-column: 1 / -1; }
    .form-group label { font-size: .74rem; font-weight: 700; color: #1e293b; margin-bottom: .35rem; }
    .form-group label.required::after { content: ' *'; color: #e11d48; }
    .form-control { border: 1px solid #e2e8f0; border-radius: 8px; height: 38px; font-size: .76rem; padding: 0 .85rem; color: #1e293b; width: 100%; outline: 0; }
    textarea.form-control { height: auto; padding: .65rem .85rem; }
    .form-control:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.12); }
    .form-inline { display: flex; gap: .5rem; align-items: center; }
    .form-inline .form-control { flex: 1; }
    .radio-group { display: flex; gap: 1rem; align-items: center; height: 38px; }
    .radio-item { display: flex; align-items: center; gap: .35rem; font-size: .78rem; cursor: pointer; }
    .upload-area { border: 2px dashed #cbd5e1; border-radius: 10px; padding: 1.5rem; text-align: center; color: #64748b; background: #f8fafc; }
    .upload-area i { font-size: 1.5rem; color: #94a3b8; margin-bottom: .5rem; display: block; }
    .upload-area strong { display: block; color: #1e293b; font-size: .8rem; }
    .upload-area small { font-size: .7rem; }
    .form-actions { display: flex; justify-content: flex-end; gap: .6rem; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #eef2f7; }
    .form-actions .btn { height: 36px; border-radius: 8px; font-size: .76rem; font-weight: 600; padding: 0 1.2rem; display: inline-flex; align-items: center; gap: .4rem; cursor: pointer; }
    .form-actions .btn-light { background: #fff; border: 1px solid #e2e8f0; color: #334155; }
    .form-actions .btn-primary { background: #1e56a0; border: 0; color: #fff; }
</style>

<div class="form-wrap">
    <?php if ($pesanError): ?>
        <div class="alert alert-danger border-0 rounded-lg p-3 mb-3 small">
            <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($pesanError, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="alert-info">
        <i class="fa-solid fa-circle-info"></i>
        <span>
            <?php if ($mode === 'edit'): ?>
                Anda sedang mengubah data <strong><?= htmlspecialchars($data['nama_siswa'] ?? 'Siswa'); ?></strong>
            <?php else: ?>
                Formulir pendaftaran siswa baru
            <?php endif; ?>
        </span>
    </div>
    <div class="alert alert-secondary border-0 rounded-lg p-3 mb-3 small">
        Field TTL belum didukung oleh skema Siswa saat ini dan tidak disimpan.
    </div>

    <form action="" method="POST">
        <?php if ($mode === 'edit'): ?>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
            <div class="alert alert-info border-0 rounded-lg p-3 mb-3 small">
                Data penempatan diubah melalui Manajemen PKL.
            </div>
        <?php endif; ?>
        <div class="form-grid">
            <div class="form-group full">
                <label class="required">NISN / NIS</label>
                <input type="text" name="nisn" class="form-control" value="<?= htmlspecialchars($data['nisn'] ?? ''); ?>" placeholder="0068472910" required>
            </div>

            <div class="form-group full">
                <label class="required">Nama Lengkap Siswa</label>
                <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($data['nama_siswa'] ?? ''); ?>" placeholder="Ahmad Rizky Pratama" required>
            </div>

            <div class="form-group">
                <label class="required">Kelas & Konsentrasi Keahlian</label>
                <select name="kelas" class="form-control" required>
                    <option value="">Pilih Jurusan & Kelas</option>
                    <?php if ($mode === 'edit'): ?>
                        <?php foreach ($opsiKelas as $kelasOption): ?>
                            <option value="<?= (int) $kelasOption['id'] ?>" <?= (int) ($data['kelas_id'] ?? 0) === (int) $kelasOption['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) $kelasOption['nama'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="XII RPL 1">XII RPL 1 (Rekayasa Perangkat Lunak)</option>
                        <option value="XII RPL 2">XII RPL 2 (Rekayasa Perangkat Lunak)</option>
                        <option value="XII TKJ 1">XII TKJ 1 (Teknik Komputer Jaringan)</option>
                        <option value="XII DKV 1">XII DKV 1 (Desain Komunikasi Visual)</option>
                        <option value="XII DKV 2">XII DKV 2 (Desain Komunikasi Visual)</option>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="required">Jenis Kelamin</label>
                <select name="jenis_kelamin" class="form-control" required>
                    <option value="">Pilih Jenis Kelamin</option>
                    <option value="<?= $mode === 'edit' ? 'L' : 'Laki-laki' ?>" <?= in_array($data['jenis_kelamin'] ?? '', ['L', 'Laki-laki'], true) ? 'selected' : ''; ?>>Laki-laki</option>
                    <option value="<?= $mode === 'edit' ? 'P' : 'Perempuan' ?>" <?= in_array($data['jenis_kelamin'] ?? '', ['P', 'Perempuan'], true) ? 'selected' : ''; ?>>Perempuan</option>
                </select>
            </div>

            <div class="form-group">
                <label>No. Kontak / WhatsApp Siswa</label>
                <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($data['no_hp'] ?? ''); ?>" placeholder="0812-xxxx-xxxx">
            </div>

            <div class="form-group full">
                <label>Alamat Lengkap Siswa</label>
                <textarea name="alamat" class="form-control" placeholder="Jalan, RT/RW, kelurahan, kecamatan, kota"><?= htmlspecialchars($data['alamat'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label>Nama Orang Tua / Wali</label>
                <input type="text" name="nama_orang_tua" class="form-control" value="<?= htmlspecialchars($data['nama_orang_tua'] ?? ''); ?>" placeholder="Nama lengkap ayah / ibu / wali">
            </div>

            <div class="form-group">
                <label>No. Telepon Orang Tua / Wali</label>
                <input type="text" name="hp_orang_tua" class="form-control" value="<?= htmlspecialchars($data['hp_orang_tua'] ?? ''); ?>" placeholder="Contoh: 0813-xxxx-xxxx">
            </div>

            <?php if ($mode !== 'edit'): ?>
                <div class="form-group">
                    <label>Instansi Mitra</label>
                    <select name="instansi_id" class="form-control">
                        <option value="">Pilih Instansi Mitra (opsional)</option>
                        <?php foreach ($opsiInstansi as $inst): ?>
                            <option value="<?= $inst['id_instansi'] ?>"><?= htmlspecialchars($inst['nama_instansi']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Guru Pembimbing Sekolah</label>
                    <select name="guru_id" class="form-control">
                        <option value="">Pilih Guru Pembimbing</option>
                        <?php foreach ($opsiGuru as $g): ?>
                            <option value="<?= htmlspecialchars($g) ?>"><?= htmlspecialchars($g) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Periode PKL (Mulai - Selesai)</label>
                    <div class="form-inline">
                        <input type="date" name="mulai_pkl" class="form-control" value="<?= htmlspecialchars($data['mulai_pkl'] ?? ''); ?>">
                        <input type="date" name="selesai_pkl" class="form-control" value="<?= htmlspecialchars($data['selesai_pkl'] ?? ''); ?>">
                    </div>
                </div>
            <?php endif; ?>
            <div class="form-group">
                <?php if ($mode === 'edit'): ?>
                    <label>Status Akun dan Profil</label>
                    <div class="form-control" aria-readonly="true"><?= htmlspecialchars(ucfirst((string) ($data['status'] ?? 'tidak diketahui')), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
                <?php else: ?>
                    <label class="required">Status Siswa</label>
                    <div class="radio-group">
                        <label class="radio-item">
                            <input type="radio" name="status" value="Aktif" checked>
                            <span>Aktif</span>
                        </label>
                        <label class="radio-item">
                            <input type="radio" name="status" value="Selesai">
                            <span>Selesai</span>
                        </label>
                        <label class="radio-item">
                            <input type="radio" name="status" value="Pending">
                            <span>Pending</span>
                        </label>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-actions">
            <a href="<?= $urlDaftar ?>" class="btn btn-light">Batal</a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk"></i>
                <?= ($mode === 'edit') ? 'Simpan Perubahan' : 'Simpan Data Siswa'; ?>
            </button>
        </div>
    </form>
</div>
<?php
$content = ob_get_clean();
require dirname(__DIR__, 2) . '/layouts/admin.php';