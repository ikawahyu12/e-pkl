<?php
require 'koneksi.php';

$pesan = "";
$status_pesan = "";

// ==========================================
// PROSES SIMPAN JURNAL
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal   = mysqli_real_escape_string($koneksi, $_POST['tanggal']);
    $aktivitas = mysqli_real_escape_string($koneksi, trim($_POST['aktivitas']));

    // Validasi input teks
    if (empty($tanggal) || empty($aktivitas)) {
        $pesan = "Tanggal dan aktivitas wajib diisi!";
        $status_pesan = "error";
    } 
    // Validasi file
    elseif (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
        $pesan = "Foto dokumentasi wajib diunggah!";
        $status_pesan = "error";
    } 
    else {
        $fileTmpPath = $_FILES['foto']['tmp_name'];
        $fileName    = $_FILES['foto']['name'];
        $fileSize    = $_FILES['foto']['size'];
        
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExt    = ['jpg', 'jpeg', 'png', 'webp'];
        $maxSize       = 5 * 1024 * 1024; // 5 MB

        // Validasi ekstensi & ukuran
        if (!in_array($fileExtension, $allowedExt)) {
            $pesan = "Format file tidak didukung. Gunakan JPG, PNG, atau WEBP.";
            $status_pesan = "error";
        } 
        elseif ($fileSize > $maxSize) {
            $pesan = "Ukuran file melebihi 5MB.";
            $status_pesan = "error";
        } 
        else {
            // Buat folder uploads jika belum ada
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Generate nama file unik
            $newFileName = 'jurnal_' . date('YmdHis') . '_' . uniqid() . '.' . $fileExtension;
            $destPath    = $uploadDir . $newFileName;

            // Pindahkan file dari tmp ke folder uploads
            if (move_uploaded_file($fileTmpPath, $destPath)) {
                // Simpan ke database
                $query = "INSERT INTO jurnal_magang (tanggal, aktivitas, foto_dokumentasi) 
                          VALUES ('$tanggal', '$aktivitas', '$newFileName')";
                
                if (mysqli_query($koneksi, $query)) {
                    $pesan = "Jurnal dan foto berhasil disimpan!";
                    $status_pesan = "success";
                } else {
                    $pesan = "Gagal menyimpan ke database: " . mysqli_error($koneksi);
                    $status_pesan = "error";
                }
            } else {
                $pesan = "Gagal mengunggah file ke server.";
                $status_pesan = "error";
            }
        }
    }
}

// ==========================================
// AMBIL LAPORAN KEMARIN (Untuk ditampilkan di bawah)
// ==========================================
$queryKemarin = "SELECT * FROM jurnal_magang 
                 WHERE DATE(tanggal) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) 
                 ORDER BY id DESC LIMIT 1";
$resultKemarin = mysqli_query($koneksi, $queryKemarin);
$laporanKemarin = mysqli_fetch_assoc($resultKemarin);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jurnal Harian Magang</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header class="top-header">
        <span class="app-title">jurnal-mobile</span>
    </header>

    <main class="main-container">
        <nav class="nav-bar">
            <div class="nav-left">
                <span class="back-icon">&#10094;</span>
                <span class="nav-title">Jurnal</span>
            </div>
            <div class="nav-right">
                <span class="logo-text">GNL</span>
            </div>
        </nav>

        <div class="page-header">
            <h1>Jurnal Harian Magang</h1>
            <p>PT Sinergi Teknologi Indonesia - <?= date('l, d M Y') ?></p>
        </div>

        <!-- Notifikasi -->
        <?php if ($pesan): ?>
            <div class="alert alert-<?= $status_pesan ?>">
                <?= htmlspecialchars($pesan) ?>
            </div>
        <?php endif; ?>

        <section class="activity-card">
            <div class="card-heading">
                <h2>Tulis Laporan Baru</h2>
            </div>

            <form action="" method="POST" enctype="multipart/form-data">
                
                <div class="form-group">
                    <label for="tanggal">Tanggal</label>
                    <input type="date" id="tanggal" name="tanggal" class="input-custom" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="form-group">
                    <label for="aktivitas">Pekerjaan / Aktivitas</label>
                    <textarea id="aktivitas" name="aktivitas" rows="5" placeholder="Tuliskan di sini apa saja pekerjaan magang yang Anda rampungkan hari ini..." required></textarea>
                </div>

                <div class="form-group">
                    <label>Foto Dokumentasi</label>
                    <div class="upload-area" onclick="document.getElementById('foto').click()">
                        <div class="upload-icon">📄</div>
                        <div class="upload-text" id="upload-text-visual">
                            <strong>Tarik & lepas foto di sini</strong>
                            <span>JPG, PNG max 5MB</span>
                        </div>
                        <input type="file" id="foto" name="foto" class="hidden-file-input" accept=".jpg,.jpeg,.png,.webp" required onchange="updateFileName(this)">
                    </div>
                </div>

                <button type="submit" class="btn-primary">Kirim Jurnal</button>
            </form>
        </section>

        <section class="history-section">
            <h3>Laporan Jurnal Kemarin</h3>
            <div class="history-card">
                <div class="history-date">
                    <?php if ($laporanKemarin): ?>
                        <strong><?= date('l, d F Y', strtotime($laporanKemarin['tanggal'])) ?></strong>
                    <?php else: ?>
                        <strong style="color:#94a3b8; font-weight:500;">Belum ada laporan</strong>
                    <?php endif; ?>
                </div>
                <?php if ($laporanKemarin): ?>
                    <span class="pill pill-ok">Selesai</span>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <script>
        function updateFileName(input) {
            const textVisual = document.getElementById('upload-text-visual');
            if (input.files && input.files.length > 0) {
                textVisual.innerHTML = `<strong>${input.files[0].name}</strong><span>Foto siap diunggah</span>`;
            } else {
                textVisual.innerHTML = `<strong>Tarik & lepas foto di sini</strong><span>JPG, PNG max 5MB</span>`;
            }
        }
    </script>
</body>
</html>