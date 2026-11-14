<?php
require_once dirname(__DIR__, 3) . '/Support/Csrf.php';
require_once __DIR__ . '/../../../../../config/koneksi.php';

$urlDaftar = '?page=data-mitra';

// 1. Validasi Method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Metode permintaan tidak diizinkan.');
}

// 2. Validasi CSRF
if (!AdminCsrf::verify($_POST['csrf_token'] ?? null)) {
    http_response_code(419);
    exit('Sesi formulir tidak valid. Muat ulang halaman lalu coba kembali.');
}

// 3. Validasi ID
$id = filter_var($_POST['delete_id'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id <= 0) {
    http_response_code(400);
    exit('Instansi yang dipilih tidak valid.');
}

// 4. Cek apakah masih ada penempatan AKTIF
$placementStmt = mysqli_prepare(
    $koneksi,
    "SELECT COUNT(*) FROM penempatan_pkl WHERE instansi_id = ? AND status = 'aktif'"
);
if ($placementStmt === false) {
    error_log('Gagal memeriksa penempatan sebelum menghapus instansi: ' . mysqli_error($koneksi));
    http_response_code(500);
    exit('Instansi belum dapat dihapus.');
}
mysqli_stmt_bind_param($placementStmt, 'i', $id);
mysqli_stmt_bind_result($placementStmt, $activePlacements);
$placementQuerySucceeded = mysqli_stmt_execute($placementStmt) && mysqli_stmt_fetch($placementStmt);
if (!$placementQuerySucceeded) {
    error_log('Gagal memeriksa penempatan aktif: ' . mysqli_stmt_error($placementStmt));
}
mysqli_stmt_close($placementStmt);
if (!$placementQuerySucceeded) {
    http_response_code(500);
    exit('Instansi belum dapat dihapus.');
}

// Jika ada siswa aktif, tolak hapus
if ((int) $activePlacements > 0) {
    http_response_code(409);
    exit('Gagal menghapus! Instansi masih memiliki siswa PKL aktif. Selesaikan atau pindahkan penempatan sebelum menghapus.');
}

// 5. Eksekusi Hapus (Menggunakan Transaksi)
mysqli_begin_transaction($koneksi);

try {
    // Langkah 1: Hapus riwayat penempatan_pkl (yang sudah tidak aktif)
    $stmtHapusPenempatan = mysqli_prepare($koneksi, 'DELETE FROM penempatan_pkl WHERE instansi_id = ?');
    if ($stmtHapusPenempatan === false) {
        throw new Exception('Gagal menyiapkan penghapusan riwayat penempatan.');
    }
    mysqli_stmt_bind_param($stmtHapusPenempatan, 'i', $id);
    mysqli_stmt_execute($stmtHapusPenempatan);
    mysqli_stmt_close($stmtHapusPenempatan);

    // Langkah 2: Hapus Instansi secara permanen
    $stmtHapusInstansi = mysqli_prepare($koneksi, 'DELETE FROM instansi WHERE id = ?');
    if ($stmtHapusInstansi === false) {
        throw new Exception('Gagal menyiapkan penghapusan instansi.');
    }
    mysqli_stmt_bind_param($stmtHapusInstansi, 'i', $id);
    mysqli_stmt_execute($stmtHapusInstansi);
    $affectedRows = mysqli_stmt_affected_rows($stmtHapusInstansi);
    mysqli_stmt_close($stmtHapusInstansi);

    // Jika 0, berarti ID tidak ditemukan
    if ($affectedRows === 0) {
        throw new Exception('Instansi tidak ditemukan.', 404);
    }

    // Commit (Simpan perubahan permanen)
    mysqli_commit($koneksi);
    header("Location: $urlDaftar", true, 303);
    exit;

} catch (Exception $e) {
    // Rollback (Batalkan semua perubahan jika ada error)
    mysqli_rollback($koneksi);
    error_log('Gagal menghapus instansi: ' . $e->getMessage());
    
    // Tampilkan error yang sesuai
    http_response_code($e->getCode() === 404 ? 404 : 500);
    exit($e->getCode() === 404 ? 'Instansi tidak ditemukan.' : 'Instansi belum dapat dihapus.');
}