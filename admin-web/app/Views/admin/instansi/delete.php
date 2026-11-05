<?php
require_once dirname(__DIR__, 3) . '/Support/Csrf.php';
require_once __DIR__ . '/../../../../../config/koneksi.php';

$urlDaftar = '?page=data-mitra';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Metode permintaan tidak diizinkan.');
}

if (!AdminCsrf::verify($_POST['csrf_token'] ?? null)) {
    http_response_code(419);
    exit('Sesi formulir tidak valid. Muat ulang halaman lalu coba kembali.');
}

$id = filter_var($_POST['delete_id'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id <= 0) {
    http_response_code(400);
    exit('Instansi yang dipilih tidak valid.');
}

$placementStmt = mysqli_prepare(
    $koneksi,
    "SELECT COUNT(*) FROM penempatan_pkl WHERE instansi_id = ? AND status = 'aktif'"
);
if ($placementStmt === false) {
    error_log('Gagal memeriksa penempatan sebelum menonaktifkan instansi: ' . mysqli_error($koneksi));
    http_response_code(500);
    exit('Status instansi belum dapat diperbarui.');
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
    exit('Status instansi belum dapat diperbarui.');
}
if ((int) $activePlacements > 0) {
    http_response_code(409);
    exit('Instansi masih memiliki penempatan aktif. Selesaikan atau pindahkan penempatan sebelum menonaktifkan.');
}

$status = 'nonaktif';
$stmt = mysqli_prepare($koneksi, 'UPDATE instansi SET status = ? WHERE id = ?');
if ($stmt === false) {
    error_log('Gagal menyiapkan penonaktifan instansi: ' . mysqli_error($koneksi));
    http_response_code(500);
    exit('Instansi belum dapat dinonaktifkan.');
}

mysqli_stmt_bind_param($stmt, 'si', $status, $id);
$updated = mysqli_stmt_execute($stmt);
$affectedRows = mysqli_stmt_affected_rows($stmt);
if (!$updated) {
    error_log('Gagal menonaktifkan instansi: ' . mysqli_stmt_error($stmt));
    mysqli_stmt_close($stmt);
    http_response_code(500);
    exit('Instansi belum dapat dinonaktifkan.');
}
mysqli_stmt_close($stmt);

if ($affectedRows === 0) {
    http_response_code(404);
    exit('Instansi tidak ditemukan atau sudah nonaktif.');
}

header("Location: $urlDaftar", true, 303);
exit;