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

$sessionStoragePath = $adminRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'sessions';

if (!is_dir($sessionStoragePath) && !mkdir($sessionStoragePath, 0700, true) && !is_dir($sessionStoragePath)) {
    throw new RuntimeException('Direktori penyimpanan sesi Admin tidak dapat dibuat.');
}

if (!is_writable($sessionStoragePath) || session_save_path($sessionStoragePath) === false) {
    throw new RuntimeException('Direktori penyimpanan sesi Admin tidak dapat digunakan.');
}

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
| Manajemen akun Admin
|--------------------------------------------------------------------------
*/
$redirectToAdminPage = static function (string $page, array $parameters = []) use ($routerUrl): never {
    $query = http_build_query(['page' => $page] + $parameters);
    header('Location: ' . $routerUrl . '?' . $query);
    exit;
};

$adminUserAction = $_POST['action'] ?? null;

if (
    $requestMethod === 'POST'
    && is_string($adminUserAction)
    && in_array($adminUserAction, [
        'admin-user-create',
        'admin-user-update',
        'admin-user-delete',
        'student-user-create',
        'student-user-update',
        'student-user-delete',
        'teacher-user-create',
        'teacher-user-update',
        'teacher-user-delete',
    ], true)
) {
    if (!AdminCsrf::verify($_POST['csrf_token'] ?? null)) {
        http_response_code(419);
        echo '419 - Sesi formulir tidak valid atau telah kedaluwarsa.';
        exit;
    }

    $userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);
    $accountType = str_starts_with($adminUserAction, 'student-')
        ? 'student'
        : (str_starts_with($adminUserAction, 'teacher-') ? 'teacher' : 'admin');
    $operation = match (true) {
        str_ends_with($adminUserAction, '-create') => 'create',
        str_ends_with($adminUserAction, '-update') => 'update',
        default => 'delete',
    };
    $listPage = match ($accountType) {
        'student' => 'manajemen-siswa',
        'teacher' => 'manajemen-guru',
        default => 'manajemen-admin',
    };
    $pageAfterFailure = match ([$accountType, $operation]) {
        ['admin', 'create'] => 'create-admin',
        ['admin', 'update'] => 'edit-admin',
        ['student', 'create'] => 'create-siswa',
        ['student', 'update'] => 'edit-siswa',
        ['teacher', 'create'] => 'create-guru',
        ['teacher', 'update'] => 'edit-guru',
        default => $listPage,
    };
    $redirectParameters = $operation === 'update' && $userId !== false
        ? ['id' => $userId]
        : [];
    $formInput = [];

    foreach (['nama', 'nama_tampilan', 'username', 'email', 'kelas_id', 'status'] as $field) {
        if (isset($_POST[$field]) && is_string($_POST[$field])) {
            $formInput[$field] = $_POST[$field];
        }
    }

    try {
        $token = $_SESSION['admin_token'] ?? '';

        if (!is_string($token) || $token === '') {
            throw new RuntimeException('Sesi Admin tidak valid. Silakan login kembali.');
        }

        if ($accountType === 'admin' && $operation === 'create') {
            $payload = array_intersect_key($_POST, array_flip([
                'nama',
                'nama_tampilan',
                'username',
                'email',
                'password',
                'status',
            ]));
            $result = $api->createAdminUser($token, $payload);
        } elseif ($accountType === 'admin' && $operation === 'update') {
            if ($userId === false) {
                throw new InvalidArgumentException('ID Admin tidak valid.');
            }

            $payload = array_intersect_key($_POST, array_flip([
                'nama',
                'nama_tampilan',
                'username',
                'email',
                'status',
            ]));

            if (isset($_POST['password']) && is_string($_POST['password']) && $_POST['password'] !== '') {
                $payload['password'] = $_POST['password'];
            }

            $result = $api->updateAdminUser($token, $userId, $payload);
        } elseif ($accountType === 'student' && $operation === 'create') {
            $payload = array_intersect_key($_POST, array_flip(['nama', 'username', 'password', 'kelas_id', 'status']));
            $result = $api->createAdminStudent($token, $payload);
        } elseif ($accountType === 'student' && $operation === 'update') {
            if ($userId === false) {
                throw new InvalidArgumentException('ID siswa tidak valid.');
            }

            $payload = array_intersect_key($_POST, array_flip(['nama', 'username', 'kelas_id', 'status']));
            if (isset($_POST['password']) && is_string($_POST['password']) && $_POST['password'] !== '') {
                $payload['password'] = $_POST['password'];
            }
            $result = $api->updateAdminStudent($token, $userId, $payload);
        } elseif ($accountType === 'teacher' && $operation === 'create') {
            $payload = array_intersect_key($_POST, array_flip(['nama', 'username', 'password', 'status']));
            $result = $api->createAdminTeacher($token, $payload);
        } elseif ($accountType === 'teacher' && $operation === 'update') {
            if ($userId === false) {
                throw new InvalidArgumentException('ID guru tidak valid.');
            }

            $payload = array_intersect_key($_POST, array_flip(['nama', 'username', 'status']));
            if (isset($_POST['password']) && is_string($_POST['password']) && $_POST['password'] !== '') {
                $payload['password'] = $_POST['password'];
            }
            $result = $api->updateAdminTeacher($token, $userId, $payload);
        } elseif ($accountType === 'student') {
            if ($userId === false) {
                throw new InvalidArgumentException('ID siswa tidak valid.');
            }
            $result = $api->deleteAdminStudent($token, $userId);
        } elseif ($accountType === 'teacher') {
            if ($userId === false) {
                throw new InvalidArgumentException('ID guru tidak valid.');
            }
            $result = $api->deleteAdminTeacher($token, $userId);
        } else {
            if ($userId === false) {
                throw new InvalidArgumentException('ID Admin tidak valid.');
            }
            $result = $api->deleteAdminUser($token, $userId);
        }

        if ($result['status'] >= 200 && $result['status'] < 300) {
            $_SESSION['admin_flash'] = [
                'type' => 'success',
                'message' => $result['body']['message'] ?? 'Perubahan akun berhasil disimpan.',
            ];
            $redirectToAdminPage($listPage);
        }

        $_SESSION['admin_flash'] = [
            'type' => 'danger',
            'message' => $result['body']['message'] ?? 'Perubahan akun gagal disimpan.',
            'errors' => $result['body']['errors'] ?? [],
            'input' => $formInput,
        ];
    } catch (Throwable $exception) {
        error_log('Admin user management API error: ' . $exception->getMessage());
        $_SESSION['admin_flash'] = [
            'type' => 'danger',
            'message' => $exception instanceof InvalidArgumentException
                ? $exception->getMessage()
                    : 'Tidak dapat menyimpan perubahan akun. Periksa koneksi server autentikasi.',
            'input' => $formInput,
        ];
    }

    $redirectToAdminPage($pageAfterFailure, $redirectParameters);
}

$adminFlash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);

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
$adminApiToken = $_SESSION['admin_token'] ?? '';
$adminPageError = null;
$adminPageSuccess = is_array($adminFlash) && ($adminFlash['type'] ?? null) === 'success'
    ? ($adminFlash['message'] ?? null)
    : null;

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
        $search = isset($_GET['search']) && is_string($_GET['search']) ? trim($_GET['search']) : '';
        $statusFilter = isset($_GET['status']) && is_string($_GET['status']) ? $_GET['status'] : '';
        $listPage = isset($_GET['list_page']) && is_string($_GET['list_page'])
            ? filter_var($_GET['list_page'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
            : 1;
        $listPage = $listPage === false ? 1 : $listPage;

        try {
            $result = $api->adminUsers($adminApiToken, [
                'search' => $search,
                'status' => $statusFilter,
                'per_page' => 15,
                'page' => $listPage,
            ]);

            if ($result['status'] >= 200 && $result['status'] < 300) {
                $adminUsers = $result['body']['data']['users'] ?? [];
                $adminPagination = $result['body']['data']['pagination'] ?? [];
            } else {
                $adminPageError = $result['body']['message'] ?? 'Daftar akun Admin tidak dapat dimuat.';
                $adminUsers = [];
                $adminPagination = [];
            }
        } catch (Throwable $exception) {
            error_log('Admin user list API error: ' . $exception->getMessage());
            $adminPageError = 'Tidak dapat memuat akun Admin dari server. Periksa koneksi API.';
            $adminUsers = [];
            $adminPagination = [];
        }

        require $adminRoot . '/app/Views/admin/manajemen_user/admin/index.php';
        break;

    case 'create-admin':
        $admin = [
            'nama' => '',
            'nama_tampilan' => '',
            'username' => '',
            'email' => '',
            'status' => 'aktif',
        ];

        if (is_array($adminFlash['input'] ?? null)) {
            $admin = array_merge($admin, $adminFlash['input']);
        }

        require $adminRoot . '/app/Views/admin/manajemen_user/admin/create.php';
        break;

    case 'edit-admin':
    case 'detail-admin':
        $adminId = isset($_GET['id']) && is_string($_GET['id'])
            ? filter_var($_GET['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
            : false;

        if ($adminId === false) {
            http_response_code(404);
            $adminPageError = 'ID Admin tidak valid.';
        } else {
            try {
                $result = $api->adminUser($adminApiToken, $adminId);

                if ($result['status'] >= 200 && $result['status'] < 300) {
                    $admin = $result['body']['data'] ?? null;

                    if (!is_array($admin)) {
                        $adminPageError = 'Respons detail Admin tidak valid.';
                    } elseif (is_array($adminFlash['input'] ?? null)) {
                        $admin = array_merge($admin, $adminFlash['input']);
                    }
                } else {
                    http_response_code($result['status'] === 404 ? 404 : 503);
                    $adminPageError = $result['body']['message'] ?? 'Data Admin tidak dapat dimuat.';
                }
            } catch (Throwable $exception) {
                error_log('Admin user detail API error: ' . $exception->getMessage());
                http_response_code(503);
                $adminPageError = 'Tidak dapat memuat detail Admin dari server. Periksa koneksi API.';
            }
        }

        if ($routePage === 'detail-admin') {
            require $adminRoot . '/app/Views/admin/manajemen_user/admin/detail.php';
            break;
        }

        require $adminRoot . '/app/Views/admin/manajemen_user/admin/edit.php';
        break;

    case 'manajemen-guru':
        $search = isset($_GET['search']) && is_string($_GET['search']) ? trim($_GET['search']) : '';
        $statusFilter = isset($_GET['status']) && is_string($_GET['status']) ? $_GET['status'] : '';
        $listPage = isset($_GET['list_page']) && is_string($_GET['list_page'])
            ? filter_var($_GET['list_page'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
            : 1;
        $listPage = $listPage === false ? 1 : $listPage;

        try {
            $result = $api->adminTeachers($adminApiToken, [
                'search' => $search,
                'status' => $statusFilter,
                'per_page' => 15,
                'page' => $listPage,
            ]);

            if ($result['status'] >= 200 && $result['status'] < 300) {
                $teacherUsers = $result['body']['data']['users'] ?? [];
                $teacherPagination = $result['body']['data']['pagination'] ?? [];
            } else {
                $adminPageError = $result['body']['message'] ?? 'Daftar akun guru tidak dapat dimuat.';
                $teacherUsers = [];
                $teacherPagination = [];
            }
        } catch (Throwable $exception) {
            error_log('Teacher user list API error: ' . $exception->getMessage());
            $adminPageError = 'Tidak dapat memuat akun guru dari server. Periksa koneksi API.';
            $teacherUsers = [];
            $teacherPagination = [];
        }

        require $adminRoot . '/app/Views/admin/manajemen_user/guru/index.php';
        break;

    case 'create-guru':
        $guru = [
            'nama' => '',
            'username' => '',
            'status' => 'aktif',
        ];
        if (is_array($adminFlash['input'] ?? null)) {
            $guru = array_merge($guru, $adminFlash['input']);
        }
        require $adminRoot . '/app/Views/admin/manajemen_user/guru/create.php';
        break;

    case 'edit-guru':
    case 'detail-guru':
        $teacherId = isset($_GET['id']) && is_string($_GET['id'])
            ? filter_var($_GET['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
            : false;
        $guru = null;
        if ($teacherId === false) {
            http_response_code(404);
            $adminPageError = 'ID guru tidak valid.';
        } else {
            try {
                $result = $api->adminTeacher($adminApiToken, $teacherId);
                if ($result['status'] >= 200 && $result['status'] < 300) {
                    $guru = $result['body']['data'] ?? null;
                    if (!is_array($guru)) {
                        $adminPageError = 'Respons detail guru tidak valid.';
                    } elseif (is_array($adminFlash['input'] ?? null)) {
                        $guru = array_merge($guru, $adminFlash['input']);
                    }
                } else {
                    http_response_code($result['status'] === 404 ? 404 : 503);
                    $adminPageError = $result['body']['message'] ?? 'Data guru tidak dapat dimuat.';
                }
            } catch (Throwable $exception) {
                error_log('Teacher user detail API error: ' . $exception->getMessage());
                http_response_code(503);
                $adminPageError = 'Tidak dapat memuat detail guru dari server. Periksa koneksi API.';
            }
        }

        if ($routePage === 'detail-guru') {
            require $adminRoot . '/app/Views/admin/manajemen_user/guru/detail.php';
            break;
        }

        require $adminRoot . '/app/Views/admin/manajemen_user/guru/edit.php';
        break;

    case 'manajemen-siswa':
    case 'manajemen-user':
        $search = isset($_GET['search']) && is_string($_GET['search']) ? trim($_GET['search']) : '';
        $statusFilter = isset($_GET['status']) && is_string($_GET['status']) ? $_GET['status'] : '';
        $listPage = isset($_GET['list_page']) && is_string($_GET['list_page'])
            ? filter_var($_GET['list_page'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
            : 1;
        $listPage = $listPage === false ? 1 : $listPage;

        try {
            $result = $api->adminStudents($adminApiToken, [
                'search' => $search,
                'status' => $statusFilter,
                'per_page' => 15,
                'page' => $listPage,
            ]);

            if ($result['status'] >= 200 && $result['status'] < 300) {
                $studentUsers = $result['body']['data']['users'] ?? [];
                $studentPagination = $result['body']['data']['pagination'] ?? [];
                $studentClasses = $result['body']['data']['classes'] ?? [];
            } else {
                $adminPageError = $result['body']['message'] ?? 'Daftar akun siswa tidak dapat dimuat.';
                $studentUsers = [];
                $studentPagination = [];
                $studentClasses = [];
            }
        } catch (Throwable $exception) {
            error_log('Student user list API error: ' . $exception->getMessage());
            $adminPageError = 'Tidak dapat memuat akun siswa dari server. Periksa koneksi API.';
            $studentUsers = [];
            $studentPagination = [];
            $studentClasses = [];
        }

        require $adminRoot . '/app/Views/admin/manajemen_user/siswa/index.php';
        break;

    case 'create-siswa':
        $siswa = [
            'nama' => '',
            'username' => '',
            'kelas_id' => '',
            'status' => 'aktif',
        ];
        if (is_array($adminFlash['input'] ?? null)) {
            $siswa = array_merge($siswa, $adminFlash['input']);
        }
        try {
            $result = $api->adminStudentClasses($adminApiToken);
            $studentClasses = $result['status'] >= 200 && $result['status'] < 300
                ? ($result['body']['data'] ?? [])
                : [];
            if ($studentClasses === []) {
                $adminPageError = $result['body']['message'] ?? 'Daftar kelas belum tersedia.';
            }
        } catch (Throwable $exception) {
            error_log('Student class list API error: ' . $exception->getMessage());
            $studentClasses = [];
            $adminPageError = 'Tidak dapat memuat daftar kelas dari server.';
        }
        require $adminRoot . '/app/Views/admin/manajemen_user/siswa/create.php';
        break;

    case 'edit-siswa':
    case 'detail-siswa':
        $studentId = isset($_GET['id']) && is_string($_GET['id'])
            ? filter_var($_GET['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
            : false;
        $siswa = null;
        $studentClasses = [];
        if ($studentId === false) {
            http_response_code(404);
            $adminPageError = 'ID siswa tidak valid.';
        } else {
            try {
                $result = $api->adminStudent($adminApiToken, $studentId);
                if ($result['status'] >= 200 && $result['status'] < 300) {
                    $siswa = $result['body']['data'] ?? null;
                    if (!is_array($siswa)) {
                        $adminPageError = 'Respons detail siswa tidak valid.';
                    } elseif (is_array($adminFlash['input'] ?? null)) {
                        $siswa = array_merge($siswa, $adminFlash['input']);
                    }
                } else {
                    http_response_code($result['status'] === 404 ? 404 : 503);
                    $adminPageError = $result['body']['message'] ?? 'Data siswa tidak dapat dimuat.';
                }

                $classResult = $api->adminStudentClasses($adminApiToken);
                if ($classResult['status'] >= 200 && $classResult['status'] < 300) {
                    $studentClasses = $classResult['body']['data'] ?? [];
                }
            } catch (Throwable $exception) {
                error_log('Student user detail API error: ' . $exception->getMessage());
                http_response_code(503);
                $adminPageError = 'Tidak dapat memuat detail siswa dari server. Periksa koneksi API.';
            }
        }

        if ($routePage === 'detail-siswa') {
            require $adminRoot . '/app/Views/admin/manajemen_user/siswa/detail.php';
            break;
        }

        require $adminRoot . '/app/Views/admin/manajemen_user/siswa/edit.php';
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

    case 'delete-mitra':
        require $adminRoot . '/app/Views/admin/instansi/delete.php';
        break;

    case 'laporan':
        require $adminRoot . '/app/Views/admin/laporan/index.php';
        break;

    case 'laporan-export':
        require $adminRoot . '/app/Controllers/LaporanController.php';
        break;

    case 'dashboard':
        require_once $adminRoot . '/app/Models/GeofenceData.php';
        require_once $adminRoot . '/app/Controllers/DashboardController.php';
        (new App\Controllers\DashboardController())->index($routerUrl . '?page=dashboard-instansi');
        break;

    case 'dashboard-instansi':
        require_once $adminRoot . '/app/Models/GeofenceData.php';
        require_once $adminRoot . '/app/Controllers/DashboardController.php';
        (new App\Controllers\DashboardController())->instanceDetails();
        break;

    default:
        http_response_code(404);
        echo '404 - Halaman tidak ditemukan.';
        break;
}