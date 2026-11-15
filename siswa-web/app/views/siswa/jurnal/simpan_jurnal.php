<?php
// simpan_jurnal.php
require 'koneksi.php';
header('Content-Type: application/json');

// Tangkap data form
$tanggal  = $_POST['tanggal'] ?? '';
$judul    = trim($_POST['judul'] ?? '');
$kegiatan = trim($_POST['kegiatan'] ?? '');
$hasil    = trim($_POST['hasil'] ?? '');
$kendala  = trim($_POST['kendala'] ?? '');

// Validasi input wajib
if (empty($tanggal) || empty($judul) || empty($kegiatan) || empty($hasil)) {
    echo json_encode(['success' => false, 'message' => 'Tanggal, Judul, Kegiatan, dan Hasil wajib diisi!']);
    exit;
}

// =========================================================================
// CARI PENEMPATAN_ID (Karena tabel jurnal wajib punya penempatan_id)
// Di sistem nyata, ini diambil dari Session user yang login.
// Untuk testing, kita ambil ID penempatan pertama yang ada di database.
// =========================================================================
$query_penempatan = $conn->query("SELECT id FROM penempatan_pkl LIMIT 1");
if ($query_penempatan->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Gagal: Tidak ada data Penempatan PKL di database. Silakan isi tabel penempatan_pkl terlebih dahulu.']);
    exit;
}
$row_penempatan = $query_penempatan->fetch_assoc();
$penempatan_id  = $row_penempatan['id'];
// =========================================================================

// MULAI TRANSACTION
$conn->begin_transaction();

try {
    // 1. Simpan ke tabel `jurnal`
    // Status otomatis 'menunggu' sesuai default ENUM di SQL Anda
    $status = 'menunggu';
    
    $stmt1 = $conn->prepare("INSERT INTO jurnal (penempatan_id, tanggal, judul, kegiatan, hasil, kendala, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
    $stmt1->bind_param("issssss", $penempatan_id, $tanggal, $judul, $kegiatan, $hasil, $kendala, $status);
    $stmt1->execute();
    
    // Ambil ID jurnal yang baru saja dibuat
    $jurnal_id = $conn->insert_id;
    $stmt1->close();

    // 2. Proses Upload Foto
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['foto'];
        
        // Validasi Ukuran (Maks 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception("Ukuran foto maksimal 5MB.");
        }

        // Validasi MIME Type (Berdasarkan isi file, bukan sekadar ekstensi)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mime_type, $allowed_mimes)) {
            throw new Exception("Format foto harus JPG, PNG, atau WEBP.");
        }

        // Tentukan ekstensi
        $ext_map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $ext = $ext_map[$mime_type];
        
        // Buat nama file unik
        $nama_file = 'jurnal_' . date('Ymd_His') . '_' . uniqid() . '.' . $ext;
        $path_file = 'uploads/' . $nama_file;
        
        // Pastikan folder uploads ada
        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }

        // Pindahkan file
        if (move_uploaded_file($file['tmp_name'], $path_file)) {
            
            // 3. Simpan ke tabel `jurnal_lampiran`
            $stmt2 = $conn->prepare("INSERT INTO jurnal_lampiran (jurnal_id, nama_file, path_file, mime_type, ukuran_byte, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt2->bind_param("isssi", $jurnal_id, $nama_file, $path_file, $mime_type, $file['size']);
            $stmt2->execute();
            $stmt2->close();
            
        } else {
            throw new Exception("Gagal mengunggah foto ke server.");
        }
    }

    // Jika semua sukses, Commit
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Jurnal dan foto berhasil disimpan!']);

} catch (Exception $e) {
    // Batalkan semua jika ada error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>