<?php

declare(strict_types=1);

$adminRoot = dirname(__DIR__);

require_once $adminRoot . '/app/Support/Csrf.php';
require_once $adminRoot . '/app/Support/AdminApiClient.php';

$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
$publicDirectory = str_replace('\\', '/', dirname($scriptPath));
$publicDirectory = rtrim($publicDirectory, '/');

if ($publicDirectory === '.' || $publicDirectory === '/') {
    $publicDirectory = '';
}

if ($publicDirectory === '') {
    $sessionCookiePath = '/';
} else {
    $appDirectory = dirname($publicDirectory);
    $sessionCookiePath = rtrim($appDirectory, '/') . '/';
}

$isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== ''
    && strtolower((string) $_SERVER['HTTPS']) !== 'off';

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

session_name('EPKL_ADMIN_SESSION');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => $sessionCookiePath,
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$api = new AdminApiClient();
$routerUrl = $publicDirectory . '/index.php';

$redirectTo = static function (string $destination) use ($routerUrl): never {
    header('Location: ' . $routerUrl . '?page=' . rawurlencode($destination));
    exit;
};

$destroyAdminSession = static function () use ($sessionCookiePath, $isHttps): void {
    $_SESSION = [];

    if (session_status() === PHP_SESSION_ACTIVE) {
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $sessionCookiePath,
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_destroy();
    }
};

$loginError = null;
$requestMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$routePage = $_GET['page'] ?? 'dashboard';

if (!is_string($routePage)) {
    $routePage = 'dashboard';
}

/*
|--------------------------------------------------------------------------
| Proses login dan logout
|--------------------------------------------------------------------------
*/
if ($requestMethod === 'POST') {
    $action = $_POST['action'] ?? '';

    // POST CRUD diteruskan ke route masing-masing.
    if (is_string($action) && in_array($action, ['login', 'logout'], true)) {
        if (!AdminCsrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            echo '419 - Sesi formulir tidak valid atau telah kedaluwarsa.';
            exit;
        }

        if ($action === 'login') {
            $identifier = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            if (
                !is_string($identifier)
                || !is_string($password)
                || trim($identifier) === ''
                || $password === ''
            ) {
                $loginError = 'Username/email dan password wajib diisi.';
            } else {
                try {
                    $result = $api->login(trim($identifier), $password);
                    $status = $result['status'];
                    $responseData = $result['body']['data'] ?? [];

                    if (
                        $status >= 200
                        && $status < 300
                        && is_array($responseData)
                        && isset($responseData['token'], $responseData['user'])
                        && is_string($responseData['token'])
                        && $responseData['token'] !== ''
                        && is_array($responseData['user'])
                    ) {
                        session_regenerate_id(true);

                        $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
                        $_SESSION['admin_token'] = $responseData['token'];
                        $_SESSION['admin_user'] = $responseData['user'];

                        $redirectTo('dashboard');
                    }

                    if ($status === 401) {
                        $loginError = 'Username/email atau password tidak valid.';
                    } elseif ($status === 422) {
                        $loginError = 'Data login tidak memenuhi validasi.';
                    } elseif ($status === 403) {
                        $loginError = 'Akun Admin tidak memiliki akses.';
                    } elseif ($status === 503) {
                        $loginError = 'Layanan autentikasi sedang tidak tersedia.';
                    } else {
                        $loginError = 'Login gagal. Periksa koneksi dan konfigurasi API.';
                    }
                } catch (Throwable $exception) {
                    error_log('Admin login API error: ' . $exception->getMessage());
                    $loginError = 'Tidak dapat menghubungi server autentikasi.';
                }
            }

            $routePage = 'login';
        } elseif ($action === 'logout') {
            $token = $_SESSION['admin_token'] ?? null;

            if (is_string($token) && $token !== '') {
                try {
                    $api->logout($token);
                } catch (Throwable $exception) {
                    error_log('Admin logout API error: ' . $exception->getMessage());
                }
            }

            $destroyAdminSession();
            $redirectTo('login');
        }
    }
}
/*
|--------------------------------------------------------------------------
| Pemeriksaan autentikasi untuk seluruh halaman Admin
|--------------------------------------------------------------------------
*/
if ($routePage !== 'login') {
    $token = $_SESSION['admin_token'] ?? null;

    if (!is_string($token) || $token === '') {
        $redirectTo('login');
    }

    try {
        $result = $api->me($token);

        if ($result['status'] === 401 || $result['status'] === 403) {
            $destroyAdminSession();
            $redirectTo('login');
        }

        if ($result['status'] < 200 || $result['status'] >= 300) {
            http_response_code(503);
            echo 'Layanan autentikasi tidak dapat memvalidasi sesi Admin.';
            exit;
        }

        $user = $result['body']['data']['user'] ?? null;

        if (!is_array($user)) {
            http_response_code(503);
            echo 'Respons autentikasi tidak valid.';
            exit;
        }

        $_SESSION['admin_user'] = $user;
    } catch (Throwable $exception) {
        error_log('Admin session validation error: ' . $exception->getMessage());
        http_response_code(503);
        echo 'Server autentikasi tidak dapat dihubungi. Halaman Admin belum dapat dibuka.';
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Data sesi untuk tampilan
|--------------------------------------------------------------------------
*/
$csrfToken = AdminCsrf::token();

$adminUser = $_SESSION['admin_user'] ?? [];

$adminUserName = is_array($adminUser)
    ? (string) ($adminUser['nama_tampilan'] ?? 'Administrator')
    : 'Administrator';

$adminUserRole = 'Administrator';

if ($routePage === 'login') {
    require $adminRoot . '/app/Views/auth/login.php';
    exit;
}

/*
|--------------------------------------------------------------------------
| Router halaman Admin
|--------------------------------------------------------------------------
*/
switch ($routePage) {
    case 'manajemen-admin':
        require $adminRoot . '/app/Views/admin/manajemen_user/admin/index.php';
        break;

    case 'create-admin':
        require $adminRoot . '/app/Views/admin/manajemen_user/admin/create.php';
        break;

    case 'edit-admin':
        require $adminRoot . '/app/Views/admin/manajemen_user/admin/edit.php';
        break;

    case 'detail-admin':
        require $adminRoot . '/app/Views/admin/manajemen_user/admin/detail.php';
        break;

    case 'manajemen-guru':
        require $adminRoot . '/app/Views/admin/manajemen_user/guru/index.php';
        break;

        case 'create-guru':
        require $adminRoot . '/app/Views/admin/guru/create.php';
        break;

    case 'edit-guru':
        require $adminRoot . '/app/Views/admin/guru/edit.php';
        break;

    case 'detail-guru':
        require $adminRoot . '/app/Views/admin/guru/detail.php';
        break;

    case 'manajemen-siswa':
    case 'manajemen-user':
        require $adminRoot . '/app/Views/admin/manajemen_user/siswa/index.php';
        break;

    case 'create-siswa':
        require $adminRoot . '/app/Views/admin/manajemen_user/siswa/create.php';
        break;

    case 'edit-siswa':
        require $adminRoot . '/app/Views/admin/manajemen_user/siswa/edit.php';
        break;

    case 'detail-siswa':
        require $adminRoot . '/app/Views/admin/manajemen_user/siswa/detail.php';
        break;

    case 'hapus-siswa':
        require $adminRoot . '/app/Views/admin/siswa/delete.php';
        break;

    case 'data-mitra':
        require $adminRoot . '/app/Views/admin/instansi/index.php';
        break;

    case 'data-guru':
        require $adminRoot . '/app/Views/admin/guru/index.php';
        break;

    case 'data-siswa':
        require $adminRoot . '/app/Views/admin/siswa/index.php';
        break;

    case 'create':
        require $adminRoot . '/app/Views/admin/siswa/create.php';
        break;

    case 'edit':
        require $adminRoot . '/app/Views/admin/siswa/edit.php';
        break;

    case 'detail':
        require $adminRoot . '/app/Views/admin/siswa/detail.php';
        break;

    case 'create-mitra':
        require $adminRoot . '/app/Views/admin/instansi/create.php';
        break;

    case 'edit-mitra':
        require $adminRoot . '/app/Views/admin/instansi/edit.php';
        break;

    case 'detail-mitra':
        require $adminRoot . '/app/Views/admin/instansi/detail.php';
        break;

    case 'hapus-mitra':
        require $adminRoot . '/app/Views/admin/instansi/delete.php';
        break;

    case 'laporan':
        require $adminRoot . '/app/Views/admin/laporan/index.php';
        break;

    case 'laporan-export':
        require $adminRoot . '/app/Controllers/LaporanController.php';
        break;

    case 'dashboard':
        require $adminRoot . '/app/Views/admin/dashboard.php';
        break;

    default:
        http_response_code(404);
        echo '404 - Halaman tidak ditemukan.';
        break;
}