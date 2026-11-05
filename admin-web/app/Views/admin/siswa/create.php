<?php
/**
 * Admin > Data Siswa > Tambah Siswa Baru (Terhubung Database)
 * Letak file: app/Views/admin/siswa/create.php
 */

// 1. Panggil koneksi database
require_once __DIR__ . '/../../../../../config/koneksi.php';

// 2. Alamat asset
if (!isset($assetBaseUrl)) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    if (preg_match('#^(.*?)/app/Views(?:/.*)?$#', $scriptDir, $m)) {
        $assetBaseUrl = $m[1] . '/public/assets';
    }
}

$urlDaftar = '?page=data-siswa';
$pesanError = '';

// ==========================================
// PROSES SIMPAN (INSERT) SAAT FORM DISUBMIT
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form dan amankan
    $nis            = mysqli_real_escape_string($koneksi, $_POST['nis'] ?? '');
    $nama_siswa     = mysqli_real_escape_string($koneksi, $_POST['nama'] ?? '');
    $id_kelas       = (int) ($_POST['kelas'] ?? 0);
    $jenis_kelamin  = mysqli_real_escape_string($koneksi, $_POST['jk'] ?? '');
    $no_hp          = mysqli_real_escape_string($koneksi, $_POST['no_hp'] ?? '');
    $alamat         = mysqli_real_escape_string($koneksi, $_POST['alamat'] ?? '');
    $id_instansi    = (int) ($_POST['instansi'] ?? 0);
    $status         = mysqli_real_escape_string($koneksi, $_POST['status'] ?? 'Aktif');

    if (empty($nis) || empty($nama_siswa) || empty($id_kelas) || empty($jenis_kelamin)) {
        $pesanError = 'Harap isi kolom NIS, Nama, Kelas, dan Jenis Kelamin.';
    } else {
        // Insert ke tabel siswa
        $sql = "INSERT INTO siswa (
                    nis, nama_siswa, id_kelas, no_hp, alamat, 
                    status, id_instansi
                ) VALUES (
                    '$nis', '$nama_siswa', $id_kelas, '$no_hp', '$alamat',
                    '$status', " . ($id_instansi > 0 ? $id_instansi : "NULL") . "
                )";

        try {
            if (mysqli_query($koneksi, $sql)) {
                echo "<script>alert('Data siswa berhasil ditambahkan!'); window.location='$urlDaftar';</script>";
                exit;
            }
        } catch (mysqli_sql_exception $ex) {
            if (strpos($ex->getMessage(), 'Duplicate entry') !== false) {
                $pesanError = 'NIS <strong>' . htmlspecialchars($nis, ENT_QUOTES) . '</strong> sudah terdaftar di sistem. Silakan gunakan NIS lain.';
            } else {
                $pesanError = 'Gagal menyimpan: ' . htmlspecialchars($ex->getMessage(), ENT_QUOTES);
            }
        }
    }
}

// ==========================================
// AMBIL DATA DROPDOWN DARI DATABASE
// ==========================================
// Dropdown Kelas (dari tabel kelas)
$opsiKelas = [];
$qKelas = mysqli_query($koneksi, "SELECT id_kelas, nama_kelas FROM kelas ORDER BY nama_kelas ASC");
if ($qKelas) {
    while ($k = mysqli_fetch_assoc($qKelas)) {
        $opsiKelas[] = $k;
    }
}

// Dropdown Instansi (dari tabel instansi)
$opsiInstansi = [];
$qInst = mysqli_query($koneksi, "SELECT id_instansi, nama_instansi FROM instansi WHERE status = 'aktif' ORDER BY nama_instansi ASC");
if ($qInst) {
    while ($i = mysqli_fetch_assoc($qInst)) {
        $opsiInstansi[] = $i;
    }
}

$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

// 3. Variabel untuk layouts/admin.php
$pageTitle  = 'Tambah Data Siswa';
$activePage = 'siswa';

ob_start();
?>
<style>
    .page-heading { display: none !important; }
    .sf-crumb { font-size: .72rem; color: #64748b; margin-bottom: .5rem; }
    .sf-crumb a { color: #64748b; text-decoration: none; }
    .sf-crumb .now { color: #2563eb; font-weight: 700; }
    .sf-crumb .sep { margin: 0 .35rem; }
    .sf-title { font-size: 1.45rem; font-weight: 800; color: #0f1d3a; margin: 0; }
    .sf-sub { font-size: .82rem; color: #64748b; margin: .25rem 0 1.25rem; }

    .sf-card { background: #fff; border-radius: 16px; box-shadow: 0 1px 3px rgba(16,24,40,.07); padding: 1.5rem; }
    .sf-label { display: block; font-size: .64rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: #475569; margin-bottom: .4rem; }
    .sf-label .req { color: #e11d48; }

    .sf-ic { position: relative; }
    .sf-ic > i { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: .8rem; pointer-events: none; }
    .sf-ic > .sf-caret { left: auto; right: 13px; font-size: .7rem; }
    .sf-input { width: 100%; height: 40px; border: 1px solid #e2e8f0; border-radius: 10px; background: #fff; font-size: .8rem; color: #1e293b; padding: 0 .85rem 0 36px; outline: 0; }
    .sf-input::placeholder { color: #94a3b8; }
    .sf-input:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.12); }
    select.sf-input { -webkit-appearance: none; -moz-appearance: none; appearance: none; padding-right: 34px; cursor: pointer; }
    select.sf-input:invalid { color: #94a3b8; }
    select.sf-input option { color: #1e293b; }

    .sf-radios { display: flex; gap: .6rem; }
    .sf-radio { flex: 1; display: flex; align-items: center; justify-content: space-between; gap: .4rem; min-height: 40px; border: 1px solid #e2e8f0; border-radius: 10px; padding: .35rem .75rem; margin: 0; cursor: pointer; font-size: .76rem; color: #334155; background: #fff; }
    .sf-radio input { accent-color: #2563eb; margin: 0 .5rem 0 0; }
    .sf-radio .txt { display: flex; align-items: center; }
    .sf-radio.on { border-color: #2563eb; background: #fafcff; }
    .sf-tag { font-size: .6rem; font-weight: 600; padding: .1rem .5rem; border-radius: 5px; border: 1px solid; white-space: nowrap; }
    .sf-tag-aktif { background: #ecfdf5; color: #059669; border-color: #a7f3d0; }
    .sf-tag-pending { background: #fffbeb; color: #d97706; border-color: #fde68a; }

    .sf-drop { border: 1.5px dashed #cbd5e1; border-radius: 14px; background: #fafcff; padding: 2rem 1rem; text-align: center; cursor: pointer; }
    .sf-drop.over { border-color: #2563eb; background: #eff6ff; }
    .sf-drop-ic { width: 40px; height: 40px; border-radius: 10px; background: #e6effc; color: #2563eb; display: inline-flex; align-items: center; justify-content: center; margin-bottom: .75rem; }
    .sf-drop-main { font-size: .82rem; color: #475569; }
    .sf-drop-main b { color: #2563eb; }
    .sf-drop-hint { font-size: .66rem; color: #94a3b8; margin-top: .3rem; }
    .sf-drop-file { font-size: .76rem; color: #059669; margin-top: .6rem; font-weight: 600; }

    .sf-foot { display: flex; justify-content: flex-end; gap: .6rem; margin-top: 1.5rem; }
    .sf-btn { height: 36px; border-radius: 9px; font-size: .76rem; font-weight: 600; padding: 0 1.15rem; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; cursor: pointer; }
    .sf-btn-cancel { background: #fff; color: #334155; border: 1px solid #e2e8f0; }
    .sf-btn-cancel:hover { background: #f8fafc; color: #334155; text-decoration: none; }
    .sf-btn-save { background: #1d56c9; color: #fff; border: 0; }
    .sf-btn-save:hover { background: #1948a8; }

    .sf-toast { display: none; margin-top: 1rem; padding: .65rem .9rem; border-radius: 10px; background: #ecfdf5; color: #047857; font-size: .78rem; }

    @media (max-width: 767.98px) {
        .sf-card { padding: 1.1rem; }
    }
    @media (max-width: 575.98px) {
        .sf-radios { flex-direction: column; }
        .sf-foot .sf-btn { flex: 1; }
    }
</style>

<div class="sf">
    <div class="sf-crumb">
        <a href="<?= $urlDaftar ?>">Data Siswa</a><span class="sep">&gt;</span><span class="now">Tambah Siswa Baru</span>
    </div>
    <h1 class="sf-title">Tambah Data Siswa</h1>
    <p class="sf-sub">Formulir pendaftaran data siswa baru untuk penempatan Praktik Kerja Lapangan (PKL)</p>

    <?php if ($pesanError): ?>
        <div class="alert alert-danger border-0 rounded-lg p-3 mb-3 small">
            <i class="fas fa-exclamation-circle mr-2"></i><?= $pesanError ?>
        </div>
    <?php endif; ?>

    <form id="sfForm" action="" method="post" enctype="multipart/form-data" novalidate>
        <div class="sf-card">
            <div class="row">
                <div class="col-md-6 form-group">
                    <label class="sf-label" for="nama">Nama Lengkap Siswa <span class="req">*</span></label>
                    <div class="sf-ic"><i class="far fa-user"></i>
                        <input class="sf-input" type="text" id="nama" name="nama" placeholder="Contoh: Ahmad Rizky Pratama" value="<?= $e($_POST['nama'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="col-md-6 form-group">
                    <label class="sf-label" for="nis">NIS (Nomor Induk Siswa) <span class="req">*</span></label>
                    <div class="sf-ic"><i class="fas fa-hashtag"></i>
                        <input class="sf-input" type="text" id="nis" name="nis" placeholder="Contoh: 0061829102" inputmode="numeric" maxlength="10" value="<?= $e($_POST['nis'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="col-md-6 form-group">
                    <label class="sf-label" for="kelas">Kelas &amp; Jurusan <span class="req">*</span></label>
                    <div class="sf-ic"><i class="fas fa-layer-group"></i>
                        <select class="sf-input" id="kelas" name="kelas" required>
                            <option value="" selected disabled hidden>Pilih Jurusan &amp; Kelas</option>
                            <?php foreach ($opsiKelas as $k): ?>
                                <option value="<?= $k['id_kelas'] ?>" <?= (($_POST['kelas'] ?? '') == $k['id_kelas']) ? 'selected' : '' ?>>
                                    <?= $e($k['nama_kelas']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-chevron-down sf-caret"></i>
                    </div>
                </div>
                <div class="col-md-6 form-group">
                    <label class="sf-label" for="jk">Jenis Kelamin <span class="req">*</span></label>
                    <div class="sf-ic"><i class="far fa-user"></i>
                        <select class="sf-input" id="jk" name="jk" required>
                            <option value="" selected disabled hidden>Pilih Jenis Kelamin</option>
                            <option value="Laki-laki" <?= (($_POST['jk'] ?? '') === 'Laki-laki') ? 'selected' : '' ?>>Laki-laki</option>
                            <option value="Perempuan" <?= (($_POST['jk'] ?? '') === 'Perempuan') ? 'selected' : '' ?>>Perempuan</option>
                        </select>
                        <i class="fas fa-chevron-down sf-caret"></i>
                    </div>
                </div>

                <div class="col-md-6 form-group">
                    <label class="sf-label" for="ttl">Tempat &amp; Tanggal Lahir</label>
                    <div class="sf-ic"><i class="far fa-calendar-alt"></i>
                        <input class="sf-input" type="text" id="ttl" name="ttl" placeholder="Bandung, 14 Mei 2007" value="<?= $e($_POST['ttl'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-6 form-group">
                    <label class="sf-label" for="no_hp">No. Kontak / WhatsApp Siswa <span class="req">*</span></label>
                    <div class="sf-ic"><i class="fas fa-phone-alt"></i>
                        <input class="sf-input" type="tel" id="no_hp" name="no_hp" placeholder="Contoh: 0812-xxxx-xxxx" value="<?= $e($_POST['no_hp'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="col-12 form-group">
                    <label class="sf-label" for="alamat">Alamat Lengkap Siswa</label>
                    <div class="sf-ic"><i class="fas fa-map-marker-alt"></i>
                        <input class="sf-input" type="text" id="alamat" name="alamat" placeholder="Jalan, RT/RW, kelurahan, kecamatan, kota" value="<?= $e($_POST['alamat'] ?? '') ?>">
                    </div>
                </div>

                <div class="col-md-6 form-group">
                    <label class="sf-label" for="ortu">Nama Orang Tua / Wali</label>
                    <div class="sf-ic"><i class="fas fa-user-friends"></i>
                        <input class="sf-input" type="text" id="ortu" name="ortu" placeholder="Nama lengkap ayah / ibu / wali" value="<?= $e($_POST['ortu'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-6 form-group">
                    <label class="sf-label" for="tlp_ortu">No. Telepon Orang Tua / Wali</label>
                    <div class="sf-ic"><i class="fas fa-phone-alt"></i>
                        <input class="sf-input" type="tel" id="tlp_ortu" name="tlp_ortu" placeholder="Contoh: 0813-xxxx-xxxx" value="<?= $e($_POST['tlp_ortu'] ?? '') ?>">
                    </div>
                </div>

                <div class="col-md-6 form-group">
                    <label class="sf-label" for="instansi">Pilihan Instansi Penempatan</label>
                    <div class="sf-ic"><i class="far fa-building"></i>
                        <select class="sf-input" id="instansi" name="instansi">
                            <option value="" selected>Pilih Instansi Mitra (opsional)</option>
                            <?php foreach ($opsiInstansi as $i): ?>
                                <option value="<?= $i['id_instansi'] ?>" <?= (($_POST['instansi'] ?? '') == $i['id_instansi']) ? 'selected' : '' ?>>
                                    <?= $e($i['nama_instansi']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-chevron-down sf-caret"></i>
                    </div>
                </div>
                <div class="col-md-6 form-group">
                    <span class="sf-label">Status Siswa <span class="req">*</span></span>
                    <div class="sf-radios">
                        <label class="sf-radio <?= (($_POST['status'] ?? 'Aktif') === 'Aktif') ? 'on' : '' ?>">
                            <span class="txt"><input type="radio" name="status" value="Aktif" <?= (($_POST['status'] ?? 'Aktif') === 'Aktif') ? 'checked' : '' ?>> Aktif / Terdaftar</span>
                            <span class="sf-tag sf-tag-aktif">Aktif</span>
                        </label>
                        <label class="sf-radio <?= (($_POST['status'] ?? '') === 'Pending') ? 'on' : '' ?>">
                            <span class="txt"><input type="radio" name="status" value="Pending" <?= (($_POST['status'] ?? '') === 'Pending') ? 'checked' : '' ?>> Menunggu Penempatan</span>
                            <span class="sf-tag sf-tag-pending">Pending</span>
                        </label>
                    </div>
                </div>

                <div class="col-12 form-group mb-0 mt-3">
                    <span class="sf-label">Upload Berkas Persyaratan / Surat Pernyataan Orang Tua</span>
                    <div class="sf-drop" id="sfDrop" tabindex="0" role="button" aria-label="Unggah berkas persyaratan">
                        <div class="sf-drop-ic"><i class="fas fa-cloud-upload-alt"></i></div>
                        <div class="sf-drop-main"><b>Unggah berkas persyaratan</b> atau tarik dan lepas di sini</div>
                        <div class="sf-drop-hint">Format dokumen PDF resmi atau Foto Surat Pernyataan (Maksimal ukuran file: 5MB)</div>
                        <div class="sf-drop-file" id="sfFile"></div>
                        <input type="file" id="sfInput" name="berkas" accept=".pdf,.jpg,.jpeg,.png" hidden>
                    </div>
                </div>
            </div>

            <div class="sf-foot">
                <a href="<?= $urlDaftar ?>" class="sf-btn sf-btn-cancel">Batal</a>
                <button type="submit" class="sf-btn sf-btn-save"><i class="fas fa-check mr-2"></i>Simpan Data Siswa</button>
            </div>
        </div>
    </form>
</div>

<script>
    window.addEventListener('load', function () {
        var $ = window.jQuery;

        // Kartu status terpilih
        $('.sf-radio input').on('change', function () {
            $('.sf-radio').removeClass('on');
            $(this).closest('.sf-radio').addClass('on');
        });

        // Dropzone: klik, pilih berkas, tarik dan lepas (maks 5MB)
        var $drop = $('#sfDrop'), input = document.getElementById('sfInput');
        function tampil(f) {
            if (f && f.size > 5 * 1024 * 1024) { alert('Ukuran file maksimal 5MB.'); input.value = ''; f = null; }
            $('#sfFile').text(f ? f.name : '');
        }
        $drop.on('click keydown', function (ev) {
            if (ev.type === 'keydown' && ev.key !== 'Enter' && ev.key !== ' ') { return; }
            ev.preventDefault();
            input.click();
        });
        $(input).on('click', function (ev) { ev.stopPropagation(); }).on('change', function () { tampil(this.files[0]); });
        $drop.on('dragover', function (ev) { ev.preventDefault(); $drop.addClass('over'); });
        $drop.on('dragleave drop', function () { $drop.removeClass('over'); });
        $drop.on('drop', function (ev) {
            ev.preventDefault();
            var files = ev.originalEvent.dataTransfer.files;
            if (files.length) { input.files = files; tampil(files[0]); }
        });
    });
</script>
<?php
$content = ob_get_clean();

require dirname(__DIR__, 2) . '/layouts/admin.php';