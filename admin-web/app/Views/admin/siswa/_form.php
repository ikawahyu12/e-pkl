<?php
// 1. Panggil koneksi
require_once __DIR__ . '/../../../../../config/koneksi.php';

$urlDaftar = '?page=data-siswa';
$mode      = isset($_GET['id']) ? 'edit' : 'create';
$id        = (int) ($_GET['id'] ?? 0);
$data      = [];
$pesanError = '';

// ==========================================
// 1. LOAD DATA JIKA MODE EDIT
// ==========================================
if ($mode === 'edit' && $id > 0) {
    $qLoad = mysqli_query($koneksi, "SELECT * FROM siswa WHERE id_siswa = {$id} LIMIT 1");

    if ($qLoad && mysqli_num_rows($qLoad) > 0) {
        $data = mysqli_fetch_assoc($qLoad);
    } else {
        $pesanError = 'Data siswa tidak ditemukan.';
        $mode = 'create'; // fallback ke create jika ID salah
    }
}

// ==========================================
// 2. PROSES SIMPAN (INSERT / UPDATE)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form dan amankan
    $nisn           = mysqli_real_escape_string($koneksi, $_POST['nisn'] ?? '');
    $nama_siswa     = mysqli_real_escape_string($koneksi, $_POST['nama'] ?? '');
    $kelas          = mysqli_real_escape_string($koneksi, $_POST['kelas'] ?? '');
    $jenis_kelamin  = mysqli_real_escape_string($koneksi, $_POST['jenis_kelamin'] ?? '');
    $ttl            = mysqli_real_escape_string($koneksi, $_POST['ttl'] ?? '');
    $no_hp          = mysqli_real_escape_string($koneksi, $_POST['no_hp'] ?? '');
    $alamat         = mysqli_real_escape_string($koneksi, $_POST['alamat'] ?? '');
    $nama_orang_tua = mysqli_real_escape_string($koneksi, $_POST['nama_orang_tua'] ?? '');
    $hp_orang_tua   = mysqli_real_escape_string($koneksi, $_POST['hp_orang_tua'] ?? '');
    $id_instansi    = (int) ($_POST['instansi_id'] ?? 0);
    $guru_pembimbing= mysqli_real_escape_string($koneksi, $_POST['guru_id'] ?? '');
    $mulai_pkl      = mysqli_real_escape_string($koneksi, $_POST['mulai_pkl'] ?? '');
    $selesai_pkl    = mysqli_real_escape_string($koneksi, $_POST['selesai_pkl'] ?? '');
    $status         = mysqli_real_escape_string($koneksi, $_POST['status'] ?? 'Aktif');

    if (empty($nisn) || empty($nama_siswa) || empty($kelas) || empty($jenis_kelamin)) {
        $pesanError = 'Harap isi kolom NISN, Nama, Kelas, dan Jenis Kelamin.';
    } else {
        if ($mode === 'edit') {
            // UPDATE data
            $sql = "UPDATE siswa SET 
                        nisn = '$nisn',
                        nama_siswa = '$nama_siswa',
                        kelas = '$kelas',
                        jenis_kelamin = '$jenis_kelamin',
                        ttl = '$ttl',
                        no_hp = '$no_hp',
                        alamat = '$alamat',
                        nama_orang_tua = '$nama_orang_tua',
                        hp_orang_tua = '$hp_orang_tua',
                        id_instansi = " . ($id_instansi > 0 ? $id_instansi : "NULL") . ",
                        guru_pembimbing = '$guru_pembimbing',
                        mulai_pkl = " . ($mulai_pkl ? "'$mulai_pkl'" : "NULL") . ",
                        selesai_pkl = " . ($selesai_pkl ? "'$selesai_pkl'" : "NULL") . ",
                        status = '$status'
                    WHERE id_siswa = $id";
        } else {
            // INSERT data baru
            $sql = "INSERT INTO siswa (
                        nisn, nama_siswa, kelas, jenis_kelamin, ttl, no_hp, alamat, 
                        nama_orang_tua, hp_orang_tua, id_instansi, guru_pembimbing, 
                        mulai_pkl, selesai_pkl, status
                    ) VALUES (
                        '$nisn', '$nama_siswa', '$kelas', '$jenis_kelamin', '$ttl', '$no_hp', '$alamat',
                        '$nama_orang_tua', '$hp_orang_tua', " . ($id_instansi > 0 ? $id_instansi : "NULL") . ", '$guru_pembimbing',
                        " . ($mulai_pkl ? "'$mulai_pkl'" : "NULL") . ", 
                        " . ($selesai_pkl ? "'$selesai_pkl'" : "NULL") . ", '$status'
                    )";
        }

        if (mysqli_query($koneksi, $sql)) {
            echo "<script>alert('Data siswa berhasil disimpan!'); window.location='$urlDaftar';</script>";
            exit;
        } else {
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
            <i class="fas fa-exclamation-circle mr-2"></i><?= $pesanError ?>
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

    <form action="" method="POST">
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
                    <option value="XII RPL 1" <?= (($data['kelas'] ?? '') === 'XII RPL 1') ? 'selected' : ''; ?>>XII RPL 1 (Rekayasa Perangkat Lunak)</option>
                    <option value="XII RPL 2" <?= (($data['kelas'] ?? '') === 'XII RPL 2') ? 'selected' : ''; ?>>XII RPL 2 (Rekayasa Perangkat Lunak)</option>
                    <option value="XII TKJ 1" <?= (($data['kelas'] ?? '') === 'XII TKJ 1') ? 'selected' : ''; ?>>XII TKJ 1 (Teknik Komputer Jaringan)</option>
                    <option value="XII DKV 1" <?= (($data['kelas'] ?? '') === 'XII DKV 1') ? 'selected' : ''; ?>>XII DKV 1 (Desain Komunikasi Visual)</option>
                    <option value="XII DKV 2" <?= (($data['kelas'] ?? '') === 'XII DKV 2') ? 'selected' : ''; ?>>XII DKV 2 (Desain Komunikasi Visual)</option>
                </select>
            </div>

            <div class="form-group">
                <label class="required">Jenis Kelamin</label>
                <select name="jenis_kelamin" class="form-control" required>
                    <option value="">Pilih Jenis Kelamin</option>
                    <option value="Laki-laki" <?= (($data['jenis_kelamin'] ?? '') === 'Laki-laki') ? 'selected' : ''; ?>>Laki-laki</option>
                    <option value="Perempuan" <?= (($data['jenis_kelamin'] ?? '') === 'Perempuan') ? 'selected' : ''; ?>>Perempuan</option>
                </select>
            </div>

            <div class="form-group">
                <label>Tempat & Tanggal Lahir</label>
                <input type="text" name="ttl" class="form-control" value="<?= htmlspecialchars($data['ttl'] ?? ''); ?>" placeholder="Bandung, 14 Mei 2007">
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

            <div class="form-group">
                <label>Instansi Mitra</label>
                <select name="instansi_id" class="form-control">
                    <option value="">Pilih Instansi Mitra (opsional)</option>
                    <?php foreach ($opsiInstansi as $inst): ?>
                        <option value="<?= $inst['id_instansi'] ?>" <?= (($data['id_instansi'] ?? '') == $inst['id_instansi']) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($inst['nama_instansi']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Guru Pembimbing Sekolah</label>
                <select name="guru_id" class="form-control">
                    <option value="">Pilih Guru Pembimbing</option>
                    <?php foreach ($opsiGuru as $g): ?>
                        <option value="<?= htmlspecialchars($g) ?>" <?= (($data['guru_pembimbing'] ?? '') === $g) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($g) ?>
                        </option>
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

            <div class="form-group">
                <label class="required">Status Siswa</label>
                <div class="radio-group">
                    <label class="radio-item">
                        <input type="radio" name="status" value="Aktif" <?= (($data['status'] ?? 'Aktif') === 'Aktif') ? 'checked' : ''; ?>>
                        <span>Aktif</span>
                    </label>
                    <label class="radio-item">
                        <input type="radio" name="status" value="Selesai" <?= (($data['status'] ?? '') === 'Selesai') ? 'checked' : ''; ?>>
                        <span>Selesai</span>
                    </label>
                    <label class="radio-item">
                        <input type="radio" name="status" value="Pending" <?= (($data['status'] ?? '') === 'Pending') ? 'checked' : ''; ?>>
                        <span>Pending</span>
                    </label>
                </div>
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