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

$studentId = filter_var($_POST['id_siswa'] ?? null, FILTER_VALIDATE_INT);
if ($studentId === false || $studentId < 1) {
    http_response_code(400);
    exit('ID Siswa tidak valid.');
}

$statement = mysqli_prepare($koneksi, 'SELECT user_id FROM siswa WHERE id = ? LIMIT 1');
mysqli_stmt_bind_param($statement, 'i', $studentId);
mysqli_stmt_execute($statement);
mysqli_stmt_bind_result($statement, $userId);
$found = mysqli_stmt_fetch($statement);
mysqli_stmt_close($statement);

if (!$found || (int) $userId < 1) {
    http_response_code(404);
    exit('Akun Siswa tidak ditemukan.');
}

$token = $_SESSION['admin_token'] ?? '';
if (!is_string($token) || $token === '') {
    http_response_code(401);
    exit('Sesi Admin tidak valid. Silakan login kembali.');
}

try {
    $reasonCode = isset($_POST['alasan_kode']) && is_string($_POST['alasan_kode'])
        ? $_POST['alasan_kode']
        : null;
    $reasonDetail = isset($_POST['alasan_detail']) && is_string($_POST['alasan_detail'])
        ? $_POST['alasan_detail']
        : null;
    $result = $api->deleteAdminStudent($token, (int) $userId, $reasonCode, $reasonDetail);
    if ($result['status'] >= 200 && $result['status'] < 300) {
        header('Location: ?page=data-siswa');
        exit;
    }

    $messages = [];
    foreach (($result['body']['errors'] ?? []) as $fieldErrors) {
        foreach ((array) $fieldErrors as $fieldError) {
            $messages[] = (string) $fieldError;
        }
    }
    http_response_code($result['status'] >= 400 && $result['status'] < 600 ? $result['status'] : 502);
    $message = $messages !== []
        ? implode(' ', $messages)
        : ($result['body']['message'] ?? 'Akun Siswa gagal dinonaktifkan.');
    exit(htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
} catch (Throwable $exception) {
    error_log('Admin Siswa deactivation API error: ' . $exception->getMessage());
    http_response_code(503);
    exit('Akun Siswa gagal dinonaktifkan melalui API. Silakan coba lagi.');
}
