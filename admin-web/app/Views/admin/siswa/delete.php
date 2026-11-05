<?php
require_once __DIR__ . '/../../../../../config/koneksi.php';
require_once dirname(__DIR__, 3) . '/Support/Csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metode permintaan tidak diizinkan.');
}

if (!AdminCsrf::verify($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Permintaan tidak valid. Muat ulang halaman dan coba lagi.');
}

$id = filter_var($_POST['id_siswa'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id < 1) {
    http_response_code(400);
    exit('ID Siswa tidak valid.');
}

$transactionStarted = false;

try {
    mysqli_begin_transaction($koneksi);
    $transactionStarted = true;

    $lookupStatement = mysqli_prepare($koneksi, 'SELECT user_id FROM siswa WHERE id = ? FOR UPDATE');
    mysqli_stmt_bind_param($lookupStatement, 'i', $id);
    mysqli_stmt_execute($lookupStatement);
    mysqli_stmt_bind_result($lookupStatement, $userId);
    $studentFound = mysqli_stmt_fetch($lookupStatement);
    mysqli_stmt_close($lookupStatement);

    if (!$studentFound) {
        mysqli_rollback($koneksi);
        http_response_code(404);
        exit('Data Siswa tidak ditemukan.');
    }

    $studentStatement = mysqli_prepare($koneksi, "UPDATE siswa SET status_siswa = 'keluar' WHERE id = ?");
    mysqli_stmt_bind_param($studentStatement, 'i', $id);
    mysqli_stmt_execute($studentStatement);
    mysqli_stmt_close($studentStatement);

    $userStatus = 'nonaktif';
    $userStatement = mysqli_prepare($koneksi, 'UPDATE users SET status = ? WHERE id = ?');
    mysqli_stmt_bind_param($userStatement, 'si', $userStatus, $userId);
    mysqli_stmt_execute($userStatement);
    mysqli_stmt_close($userStatement);

    mysqli_commit($koneksi);
    $transactionStarted = false;
    header('Location: ?page=data-siswa');
    exit;
} catch (mysqli_sql_exception $exception) {
    if ($transactionStarted) {
        mysqli_rollback($koneksi);
    }

    error_log('Admin Siswa deactivation failed: ' . $exception->getMessage());
    http_response_code(500);
    exit('Siswa gagal dinonaktifkan. Silakan coba lagi atau hubungi administrator.');
}
