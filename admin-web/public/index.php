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

if (in_array($routePage, [
    'tahun-ajaran',
    'create-tahun-ajaran',
    'edit-tahun-ajaran',
    'periode-pkl',
    'create-periode-pkl',
    'edit-periode-pkl',
    'kuota-instansi',
], true)) {
    $redirectTo('dashboard');
}

/*
|--------------------------------------------------------------------------
| Proses login dan logout
|--------------------------------------------------------------------------
*/
if ($requestMethod === 'POST') {
    $action = $_POST['action'] ?? '';

    if (
        $routePage === 'forgot-password'
        && $action === 'request-password-reset'
    ) {
        if (!AdminCsrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            echo '419 - Sesi formulir tidak valid atau telah kedaluwarsa.';
            exit;
        }

        try {
            $email = $_POST['email'] ?? '';
            $result = is_string($email)
                ? $api->requestPasswordReset(trim($email))
                : ['status' => 422, 'body' => []];

            $_SESSION['password_reset_flash'] = [
                'type' => $result['status'] >= 200 && $result['status'] < 300 ? 'success' : 'danger',
                'message' => $result['status'] >= 200 && $result['status'] < 300
                    ? ($result['body']['message'] ?? 'Jika alamat email terdaftar, instruksi reset akan dikirim.')
                    : 'Permintaan reset tidak dapat diproses. Periksa email dan coba lagi.',
            ];
        } catch (Throwable $exception) {
            error_log('Admin password reset request failed: ' . $exception::class);
            $_SESSION['password_reset_flash'] = [
                'type' => 'danger',
                'message' => 'Layanan reset kata sandi sedang tidak tersedia. Coba lagi nanti.',
            ];
        }

        $redirectTo('forgot-password');
    }

    if (
        $routePage === 'reset-password'
        && $action === 'reset-password'
    ) {
        if (!AdminCsrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            echo '419 - Sesi formulir tidak valid atau telah kedaluwarsa.';
            exit;
        }

        $success = false;
        try {
            $result = $api->resetPassword([
                'email' => $_POST['email'] ?? '',
                'token' => $_POST['token'] ?? '',
                'password_baru' => $_POST['password_baru'] ?? '',
                'password_baru_confirmation' => $_POST['password_baru_confirmation'] ?? '',
            ]);
            $success = $result['status'] >= 200 && $result['status'] < 300;
            $errors = $result['body']['errors'] ?? [];
            $firstError = is_array($errors) ? reset($errors) : null;
            $_SESSION['password_reset_flash'] = [
                'type' => $success ? 'success' : 'danger',
                'message' => $success
                    ? ($result['body']['message'] ?? 'Kata sandi berhasil diubah. Silakan masuk.')
                    : (is_array($firstError) ? ($firstError[0] ?? null) : null)
                        ?? $result['body']['message']
                        ?? 'Tautan reset tidak valid atau telah kedaluwarsa.',
            ];
        } catch (Throwable $exception) {
            error_log('Admin password reset failed: ' . $exception::class);
            $_SESSION['password_reset_flash'] = [
                'type' => 'danger',
                'message' => 'Layanan reset kata sandi sedang tidak tersedia. Coba lagi nanti.',
            ];
        }

        if ($success) {
            $redirectTo('login');
        }

        header('Location: ' . $routerUrl . '?' . http_build_query([
            'page' => 'reset-password',
            'email' => is_string($_POST['email'] ?? null) ? $_POST['email'] : '',
            'token' => is_string($_POST['token'] ?? null) ? $_POST['token'] : '',
        ]));
        exit;
    }

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
$publicPages = ['login', 'forgot-password', 'reset-password'];

if (!in_array($routePage, $publicPages, true)) {
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

if (
    !in_array($routePage, $publicPages, true)
    && !empty($_SESSION['admin_user']['wajib_ganti_password'])
    && $routePage !== 'ganti-password'
) {
    $redirectTo('ganti-password');
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

$placementAction = $_POST['action'] ?? null;
$placementActions = [
    'placement-create',
    'placement-cancel',
    'placement-reactivate',
    'placement-move',
];
if ($requestMethod === 'POST' && is_string($placementAction) && in_array($placementAction, $placementActions, true)) {
    if (!AdminCsrf::verify($_POST['csrf_token'] ?? null)) {
        http_response_code(419);
        echo '419 - Sesi formulir tidak valid atau telah kedaluwarsa.';
        exit;
    }

    $formInput = [];
    foreach ([
        'siswa_id',
        'periode_id',
        'instansi_id',
        'guru_id',
        'pic_instansi_id',
        'tanggal_mulai',
        'tanggal_selesai',
        'alasan_kode',
        'alasan_detail',
        'alasan',
        'siswa_search',
        'guru_search',
    ] as $field) {
        if (isset($_POST[$field]) && (is_string($_POST[$field]) || is_numeric($_POST[$field]))) {
            $formInput[$field] = (string) $_POST[$field];
        }
    }

    $placementId = filter_var($_POST['placement_id'] ?? null, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);
    $redirectPage = $placementAction === 'placement-create'
        ? 'create-penempatan-pkl'
        : 'detail-penempatan-pkl';
    $redirectParams = $placementAction === 'placement-create' || $placementId === false
        ? []
        : ['id' => $placementId];

    try {
        $token = $_SESSION['admin_token'] ?? '';
        if (!is_string($token) || $token === '') {
            throw new RuntimeException('Sesi Admin tidak valid. Silakan login kembali.');
        }

        if ($placementAction === 'placement-create') {
            $payload = array_intersect_key($formInput, array_flip([
                'siswa_id',
                'periode_id',
                'instansi_id',
                'guru_id',
                'tanggal_mulai',
                'tanggal_selesai',
            ]));
            if (($formInput['pic_instansi_id'] ?? '') !== '') {
                $payload['pic_instansi_id'] = $formInput['pic_instansi_id'];
            }
            $result = $api->createAdminPlacement($token, $payload);
        } elseif ($placementId === false) {
            throw new InvalidArgumentException('ID penempatan tidak valid.');
        } elseif ($placementAction === 'placement-cancel') {
            $result = $api->cancelAdminPlacement($token, $placementId, array_intersect_key(
                $formInput,
                array_flip(['alasan_kode', 'alasan_detail'])
            ));
        } elseif ($placementAction === 'placement-reactivate') {
            $result = $api->reactivateAdminPlacement($token, $placementId, array_intersect_key(
                $formInput,
                array_flip(['alasan'])
            ));
        } else {
            $payload = array_intersect_key($formInput, array_flip([
                'instansi_id',
                'guru_id',
                'tanggal_mulai',
                'tanggal_selesai',
                'alasan_kode',
                'alasan_detail',
            ]));
            if (($formInput['pic_instansi_id'] ?? '') !== '') {
                $payload['pic_instansi_id'] = $formInput['pic_instansi_id'];
            }
            $result = $api->moveAdminPlacement($token, $placementId, $payload);
        }

        $responsePlacementId = filter_var(
            $result['body']['data']['penempatan']['id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        if (
            $result['status'] >= 200
            && $result['status'] < 300
            && ($result['body']['success'] ?? false) === true
            && $responsePlacementId !== false
        ) {
            $_SESSION['admin_flash'] = [
                'type' => 'success',
                'message' => $result['body']['message'] ?? 'Perubahan penempatan berhasil disimpan.',
            ];
            $redirectToAdminPage('detail-penempatan-pkl', ['id' => $responsePlacementId]);
        }

        $errors = $result['body']['errors'] ?? [];
        $firstError = is_array($errors) ? reset($errors) : null;
        $errorMessage = is_array($firstError) ? ($firstError[0] ?? null) : null;
        $_SESSION['admin_flash'] = [
            'type' => 'danger',
            'message' => $errorMessage ?? $result['body']['message'] ?? 'Perubahan penempatan gagal disimpan.',
            'errors' => is_array($errors) ? $errors : [],
            'input' => $formInput,
        ];
    } catch (Throwable $exception) {
        error_log('Admin placement action failed: ' . $exception->getMessage());
        $_SESSION['admin_flash'] = [
            'type' => 'danger',
            'message' => $exception instanceof InvalidArgumentException
                ? $exception->getMessage()
                : 'Layanan Manajemen PKL tidak dapat dihubungi. Perubahan belum dapat dipastikan.',
            'input' => $formInput,
        ];
    }

    $redirectToAdminPage($redirectPage, $redirectParams);
}

$adminPasswordAction = $_POST['action'] ?? null;

if (
    $requestMethod === 'POST'
    && $routePage === 'ganti-password'
    && $adminPasswordAction === 'change-admin-password'
) {
    if (!AdminCsrf::verify($_POST['csrf_token'] ?? null)) {
        http_response_code(419);
        echo '419 - Sesi formulir tidak valid atau telah kedaluwarsa.';
        exit;
    }

    $token = $_SESSION['admin_token'] ?? '';

    try {
        if (!is_string($token) || $token === '') {
            throw new RuntimeException('Sesi Admin tidak valid. Silakan login kembali.');
        }

        $result = $api->changeAdminPassword($token, [
            'password_lama' => $_POST['password_lama'] ?? '',
            'password_baru' => $_POST['password_baru'] ?? '',
            'password_baru_confirmation' => $_POST['password_baru_confirmation'] ?? '',
        ]);

        if ($result['status'] >= 200 && $result['status'] < 300) {
            $_SESSION['admin_user']['wajib_ganti_password'] = false;
            $_SESSION['admin_flash'] = [
                'type' => 'success',
                'message' => $result['body']['message'] ?? 'Kata sandi berhasil diubah.',
            ];
            $redirectTo('dashboard');
        }

        $errors = $result['body']['errors'] ?? [];
        $firstError = is_array($errors) ? reset($errors) : null;
        $message = is_array($firstError) ? ($firstError[0] ?? null) : null;
        $_SESSION['admin_flash'] = [
            'type' => 'danger',
            'message' => $message ?? $result['body']['message'] ?? 'Kata sandi gagal diubah.',
        ];
    } catch (Throwable $exception) {
        error_log('Admin password change API error: ' . $exception->getMessage());
        $_SESSION['admin_flash'] = [
            'type' => 'danger',
            'message' => 'Tidak dapat mengubah kata sandi. Periksa koneksi server autentikasi.',
        ];
    }

    $redirectTo('ganti-password');
}

$masterDataAction = $_POST['action'] ?? null;
$masterDataActions = [
    'master-instance-create',
    'master-instance-update',
    'master-instance-status',
    'master-year-create',
    'master-year-update',
    'master-period-create',
    'master-period-update',
    'master-pic-create',
    'master-pic-update',
    'master-pic-status',
    'master-quota-save',
];

if ($requestMethod === 'POST' && is_string($masterDataAction) && in_array($masterDataAction, $masterDataActions, true)) {
    if (!AdminCsrf::verify($_POST['csrf_token'] ?? null)) {
        http_response_code(419);
        echo '419 - Sesi formulir tidak valid atau telah kedaluwarsa.';
        exit;
    }

    $positiveId = static function (string $key): int|false {
        return filter_var($_POST[$key] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    };
    $instanceId = $positiveId('instansi_id');
    $yearId = $positiveId('tahun_ajaran_id');
    $periodId = $positiveId('periode_id');
    $picId = $positiveId('pic_id');
    $input = [];
    foreach ([
        'nama', 'jenis', 'alamat', 'kota', 'provinsi', 'telepon', 'email',
        'nama_penanggung_jawab', 'status', 'tanggal_mulai', 'tanggal_selesai',
        'aktif', 'tahun_ajaran_id', 'jabatan', 'no_hp', 'kuota_siswa',
    ] as $field) {
        if (isset($_POST[$field]) && is_string($_POST[$field])) {
            $input[$field] = $_POST[$field];
        }
    }

    $destination = 'data-mitra';
    try {
        $token = $_SESSION['admin_token'] ?? '';
        if (!is_string($token) || $token === '') {
            throw new RuntimeException('Sesi Admin tidak valid. Silakan login kembali.');
        }

        $payloadFor = static function (array $fields): array {
            $payload = [];
            foreach ($fields as $field) {
                if (array_key_exists($field, $_POST) && is_string($_POST[$field])) {
                    $value = trim($_POST[$field]);
                    $payload[$field] = $value === '' ? null : $value;
                }
            }
            return $payload;
        };

        switch ($masterDataAction) {
            case 'master-instance-create':
                $destination = 'create-mitra';
                $result = $api->createAdminMasterInstance($token, $payloadFor([
                    'nama', 'jenis', 'alamat', 'kota', 'provinsi', 'telepon', 'email',
                    'nama_penanggung_jawab', 'status',
                ]));
                break;
            case 'master-instance-update':
                if ($instanceId === false) {
                    throw new InvalidArgumentException('ID Instansi tidak valid.');
                }
                $destination = 'edit-mitra';
                $result = $api->updateAdminMasterInstance($token, $instanceId, $payloadFor([
                    'nama', 'jenis', 'alamat', 'kota', 'provinsi', 'telepon', 'email',
                    'nama_penanggung_jawab', 'status',
                ]));
                break;
            case 'master-instance-status':
                if ($instanceId === false || !in_array($_POST['status'] ?? null, ['aktif', 'nonaktif'], true)) {
                    throw new InvalidArgumentException('Data status Instansi tidak valid.');
                }
                $destination = 'detail-mitra';
                $result = $api->updateAdminMasterInstanceStatus($token, $instanceId, $_POST['status']);
                break;
            case 'master-year-create':
                $destination = 'create-tahun-ajaran';
                $result = $api->createAdminMasterAcademicYear($token, $payloadFor([
                    'nama', 'tanggal_mulai', 'tanggal_selesai', 'aktif',
                ]));
                break;
            case 'master-year-update':
                if ($yearId === false) {
                    throw new InvalidArgumentException('ID Tahun Ajaran tidak valid.');
                }
                $destination = 'edit-tahun-ajaran';
                $result = $api->updateAdminMasterAcademicYear($token, $yearId, $payloadFor([
                    'nama', 'tanggal_mulai', 'tanggal_selesai', 'aktif',
                ]));
                break;
            case 'master-period-create':
                $destination = 'create-periode-pkl';
                $result = $api->createAdminMasterPeriod($token, $payloadFor([
                    'tahun_ajaran_id', 'nama', 'tanggal_mulai', 'tanggal_selesai', 'status',
                ]));
                break;
            case 'master-period-update':
                if ($periodId === false) {
                    throw new InvalidArgumentException('ID Periode PKL tidak valid.');
                }
                $destination = 'edit-periode-pkl';
                $result = $api->updateAdminMasterPeriod($token, $periodId, $payloadFor([
                    'tahun_ajaran_id', 'nama', 'tanggal_mulai', 'tanggal_selesai', 'status',
                ]));
                break;
            case 'master-pic-create':
                if ($instanceId === false) {
                    throw new InvalidArgumentException('ID Instansi tidak valid.');
                }
                $destination = 'detail-mitra';
                $result = $api->createAdminMasterInstancePic($token, $instanceId, $payloadFor([
                    'nama', 'jabatan', 'no_hp', 'email',
                ]));
                break;
            case 'master-pic-update':
                if ($instanceId === false || $picId === false) {
                    throw new InvalidArgumentException('ID PIC atau Instansi tidak valid.');
                }
                $destination = 'detail-mitra';
                $result = $api->updateAdminMasterInstancePic($token, $instanceId, $picId, $payloadFor([
                    'nama', 'jabatan', 'no_hp', 'email',
                ]));
                break;
            case 'master-pic-status':
                if (
                    $instanceId === false
                    || $picId === false
                    || !in_array($_POST['aktif'] ?? null, ['0', '1'], true)
                ) {
                    throw new InvalidArgumentException('Data status PIC tidak valid.');
                }
                $destination = 'detail-mitra';
                $result = $api->updateAdminMasterInstancePicStatus(
                    $token,
                    $instanceId,
                    $picId,
                    $_POST['aktif'] === '1'
                );
                break;
            case 'master-quota-save':
                if ($instanceId === false || $periodId === false) {
                    throw new InvalidArgumentException('Pilih Instansi dan Periode yang valid.');
                }
                $destination = 'kuota-instansi';
                $quota = $_POST['kuota_siswa'] ?? null;
                if (!is_string($quota)) {
                    throw new InvalidArgumentException('Nilai kuota tidak valid.');
                }
                $result = $api->saveAdminMasterQuota($token, $instanceId, $periodId, [
                    'kuota_siswa' => trim($quota) === '' ? null : trim($quota),
                ]);
                break;
            default:
                throw new LogicException('Aksi master data tidak dikenali.');
        }

        $success = $result['status'] >= 200
            && $result['status'] < 300
            && ($result['body']['success'] ?? false) === true;
        $errors = $result['body']['errors'] ?? [];
        $firstError = is_array($errors) ? reset($errors) : null;
        $message = is_array($firstError) ? ($firstError[0] ?? null) : null;
        $_SESSION['admin_flash'] = [
            'type' => $success ? 'success' : 'danger',
            'message' => $success
                ? ($result['body']['message'] ?? 'Data master berhasil disimpan.')
                : ($message ?? $result['body']['message'] ?? 'Data master gagal disimpan.'),
            'input' => $success ? [] : $input,
        ];
    } catch (Throwable $exception) {
        error_log('Admin master data request failed: ' . $exception::class);
        $_SESSION['admin_flash'] = [
            'type' => 'danger',
            'message' => $exception instanceof InvalidArgumentException
                ? $exception->getMessage()
                : 'Data master tidak dapat disimpan. Periksa koneksi API.',
            'input' => $input,
        ];
    }

    $redirectParameters = [];
    if (in_array($destination, ['edit-mitra', 'detail-mitra'], true) && $instanceId !== false) {
        $redirectParameters['id'] = $instanceId;
        if ($picId !== false && $masterDataAction === 'master-pic-update') {
            $redirectParameters['pic_id'] = $picId;
        }
    } elseif ($destination === 'edit-tahun-ajaran' && $yearId !== false) {
        $redirectParameters['id'] = $yearId;
    } elseif ($destination === 'edit-periode-pkl' && $periodId !== false) {
        $redirectParameters['id'] = $periodId;
    } elseif ($destination === 'kuota-instansi' && $instanceId !== false && $periodId !== false) {
        $redirectParameters['instansi_id'] = $instanceId;
        $redirectParameters['periode_id'] = $periodId;
    }
    header('Location: ' . $routerUrl . '?' . http_build_query(['page' => $destination] + $redirectParameters));
    exit;
}

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

    foreach (['nama', 'nama_tampilan', 'username', 'email', 'kelas_id', 'status', 'alasan_kode', 'alasan_detail'] as $field) {
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

            $payload = array_intersect_key($_POST, array_flip(['nama', 'username', 'kelas_id']));
            if (isset($_POST['password']) && is_string($_POST['password']) && $_POST['password'] !== '') {
                $payload['password'] = $_POST['password'];
            }
            $result = $api->updateAdminStudent($token, $userId, $payload);
            if (
                $result['status'] >= 200
                && $result['status'] < 300
                && isset($_POST['status'])
                && is_string($_POST['status'])
            ) {
                $status = strtolower(trim($_POST['status']));
                $reasonCode = isset($_POST['alasan_kode']) && is_string($_POST['alasan_kode'])
                    ? $_POST['alasan_kode']
                    : null;
                $reasonDetail = isset($_POST['alasan_detail']) && is_string($_POST['alasan_detail'])
                    ? $_POST['alasan_detail']
                    : null;
                $result = $api->updateAdminStudentStatus(
                    $token,
                    $userId,
                    $status,
                    $reasonCode,
                    $reasonDetail
                );
            }
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
            if (isset($payload['status']) && is_string($payload['status'])) {
                $payload['status'] = strtolower(trim($payload['status']));
            }
            if (($payload['status'] ?? null) === 'nonaktif') {
                try {
                    $assignmentResult = $api->adminTeacherActivePlacements($token, $userId);
                } catch (RuntimeException $exception) {
                    throw new InvalidArgumentException(
                        'Penonaktifan dibatalkan karena daftar penempatan aktif Guru tidak dapat dimuat.',
                        0,
                        $exception
                    );
                }
                $assignmentData = $assignmentResult['body']['data'] ?? null;
                if (
                    $assignmentResult['status'] < 200
                    || $assignmentResult['status'] >= 300
                    || ($assignmentResult['body']['success'] ?? false) !== true
                    || !is_array($assignmentData)
                    || !is_array($assignmentData['penempatan'] ?? null)
                    || !is_array($assignmentData['guru_pengganti'] ?? null)
                ) {
                    throw new InvalidArgumentException(
                        'Penonaktifan dibatalkan karena daftar penempatan aktif Guru gagal dimuat '
                        . 'atau respons API tidak valid.'
                    );
                }

                $formReassignments = $_POST['reassignments'] ?? [];
                if (!is_array($formReassignments)) {
                    $formReassignments = [];
                }
                $payload['reassignments'] = [];
                foreach ($formReassignments as $assignment) {
                    if (!is_array($assignment)) {
                        continue;
                    }
                    $payload['reassignments'][] = [
                        'penempatan_id' => $assignment['penempatan_id'] ?? null,
                        'guru_baru_id' => $assignment['guru_baru_id'] ?? null,
                        'alasan' => $assignment['alasan'] ?? null,
                    ];
                }
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
$scriptDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$scriptDirectory = rtrim($scriptDirectory, '/');
$assetBaseUrl = $scriptDirectory === '.' ? '' : $scriptDirectory . '/assets';

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
$loadPlacementReferences = static function (array $filters) use ($api, $adminApiToken): array {
    $result = $api->adminPlacementReferences($adminApiToken, $filters);
    $data = $result['body']['data'] ?? null;
    if (
        $result['status'] < 200
        || $result['status'] >= 300
        || ($result['body']['success'] ?? false) !== true
        || !is_array($data)
        || !is_array($data['siswa'] ?? null)
        || !is_array($data['guru'] ?? null)
        || !is_array($data['selected'] ?? null)
        || !is_array($data['pagination'] ?? null)
        || !is_array($data['pagination']['siswa'] ?? null)
        || !is_array($data['pagination']['guru'] ?? null)
        || !is_array($data['periode'] ?? null)
        || !is_array($data['instansi'] ?? null)
        || !is_array($data['pic_instansi'] ?? null)
        || !array_key_exists('kuota', $data)
        || ($data['kuota'] !== null && !is_array($data['kuota']))
    ) {
        throw new RuntimeException(
            is_string($result['body']['message'] ?? null)
                ? $result['body']['message']
                : 'Referensi penempatan PKL tidak dapat dimuat.'
        );
    }

    return $data;
};
$loadMasterCollection = static function (array $result, string $key): array {
    $data = $result['body']['data'] ?? null;
    if (
        $result['status'] < 200
        || $result['status'] >= 300
        || ($result['body']['success'] ?? false) !== true
        || !is_array($data)
        || !is_array($data[$key] ?? null)
        || !is_array($data['pagination'] ?? null)
    ) {
        throw new RuntimeException(
            is_string($result['body']['message'] ?? null)
                ? $result['body']['message']
                : 'Data master tidak dapat dimuat.'
        );
    }

    return ['items' => $data[$key], 'pagination' => $data['pagination']];
};
$loadMasterRecord = static function (array $result, string $key): array {
    $record = $result['body']['data'][$key] ?? null;
    if (
        $result['status'] < 200
        || $result['status'] >= 300
        || ($result['body']['success'] ?? false) !== true
        || !is_array($record)
    ) {
        throw new RuntimeException(
            is_string($result['body']['message'] ?? null)
                ? $result['body']['message']
                : 'Data master tidak dapat dimuat.'
        );
    }

    return $record;
};

if (in_array($routePage, $publicPages, true)) {
    $passwordResetFlash = $_SESSION['password_reset_flash'] ?? null;
    unset($_SESSION['password_reset_flash']);

    if ($routePage === 'forgot-password') {
        require $adminRoot . '/app/Views/auth/forgot-password.php';
    } elseif ($routePage === 'reset-password') {
        require $adminRoot . '/app/Views/auth/reset-password.php';
    } else {
        require $adminRoot . '/app/Views/auth/login.php';
    }
    exit;
}

/*
|--------------------------------------------------------------------------
| Router halaman Admin
|--------------------------------------------------------------------------
*/
switch ($routePage) {
    case 'ganti-password':
        require $adminRoot . '/app/Views/auth/change-password.php';
        break;

    case 'data-mitra':
    case 'create-mitra':
    case 'edit-mitra':
    case 'detail-mitra':
    case 'hapus-mitra':
        $masterMode = match ($routePage) {
            'create-mitra' => 'create',
            'edit-mitra' => 'edit',
            'detail-mitra' => 'detail',
            default => 'list',
        };
        $instance = is_array($adminFlash['input'] ?? null) ? $adminFlash['input'] : [];
        $instances = [];
        $pics = [];
        $picForEdit = [];
        $picInput = is_array($adminFlash['input'] ?? null) ? $adminFlash['input'] : [];
        $pagination = [];
        if ($routePage === 'hapus-mitra') {
            $adminPageError = 'Instansi tidak dihapus agar relasi dan riwayat penempatan tetap terjaga. Gunakan status nonaktif.';
        }

        try {
            if ($masterMode === 'list') {
                $filters = [
                    'search' => isset($_GET['search']) && is_string($_GET['search']) ? trim($_GET['search']) : '',
                    'status' => isset($_GET['status']) && in_array($_GET['status'], ['aktif', 'nonaktif'], true) ? $_GET['status'] : '',
                    'page' => isset($_GET['list_page']) ? max(1, (int) $_GET['list_page']) : 1,
                    'per_page' => 15,
                ];
                $filters = array_filter($filters, static fn ($value) => $value !== '');
                $list = $loadMasterCollection($api->adminMasterInstances($adminApiToken, $filters), 'instansi');
                $instances = $list['items'];
                $pagination = $list['pagination'];
            } elseif ($masterMode === 'create') {
                $instance = array_merge([
                    'status' => 'aktif',
                    'alamat' => '',
                ], $instance);
            } else {
                $instanceId = isset($_GET['id']) && is_string($_GET['id'])
                    ? filter_var($_GET['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
                    : false;
                if ($instanceId === false) {
                    http_response_code(404);
                    throw new InvalidArgumentException('ID Instansi tidak valid.');
                }
                $instance = $loadMasterRecord($api->adminMasterInstance($adminApiToken, $instanceId), 'instansi');
                if ($routePage === 'edit-mitra' && is_array($adminFlash['input'] ?? null)) {
                    $instance = array_merge($instance, $adminFlash['input']);
                }
                if ($masterMode === 'detail') {
                    $pics = is_array($instance['pic_instansi'] ?? null) ? $instance['pic_instansi'] : [];
                    $picId = isset($_GET['pic_id']) && is_string($_GET['pic_id'])
                        ? filter_var($_GET['pic_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
                        : false;
                    if ($picId !== false) {
                        foreach ($pics as $pic) {
                            if ((int) ($pic['id'] ?? 0) === $picId) {
                                $picForEdit = $pic;
                                break;
                            }
                        }
                        if ($picForEdit === []) {
                            $adminPageError = 'PIC tidak ditemukan pada Instansi ini.';
                        }
                    }
                }
            }
        } catch (Throwable $exception) {
            error_log('Admin master Instansi load failed: ' . $exception::class);
            $adminPageError ??= $exception instanceof InvalidArgumentException
                ? $exception->getMessage()
                : 'Data Instansi tidak dapat dimuat. Periksa koneksi API.';
        }
        require $adminRoot . '/app/Views/admin/master_data/instansi.php';
        break;

    case 'tahun-ajaran':
    case 'create-tahun-ajaran':
    case 'edit-tahun-ajaran':
        $masterMode = $routePage === 'create-tahun-ajaran'
            ? 'create'
            : ($routePage === 'edit-tahun-ajaran' ? 'edit' : 'list');
        $years = [];
        $year = is_array($adminFlash['input'] ?? null) ? $adminFlash['input'] : [];
        $pagination = [];
        try {
            if ($masterMode === 'list') {
                $filters = [
                    'search' => isset($_GET['search']) && is_string($_GET['search']) ? trim($_GET['search']) : '',
                    'page' => isset($_GET['list_page']) ? max(1, (int) $_GET['list_page']) : 1,
                    'per_page' => 15,
                ];
                $list = $loadMasterCollection($api->adminMasterAcademicYears($adminApiToken, $filters), 'tahun_ajaran');
                $years = $list['items'];
                $pagination = $list['pagination'];
            } elseif ($masterMode === 'create') {
                $year = array_merge(['aktif' => false], $year);
            } else {
                $id = isset($_GET['id']) && is_string($_GET['id'])
                    ? filter_var($_GET['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
                    : false;
                if ($id === false) {
                    http_response_code(404);
                    throw new InvalidArgumentException('ID Tahun Ajaran tidak valid.');
                }
                $year = $loadMasterRecord($api->adminMasterAcademicYear($adminApiToken, $id), 'tahun_ajaran');
                if (is_array($adminFlash['input'] ?? null)) {
                    $year = array_merge($year, $adminFlash['input']);
                }
            }
        } catch (Throwable $exception) {
            error_log('Admin master Tahun Ajaran load failed: ' . $exception::class);
            $adminPageError ??= $exception instanceof InvalidArgumentException
                ? $exception->getMessage()
                : 'Data Tahun Ajaran tidak dapat dimuat. Periksa koneksi API.';
        }
        require $adminRoot . '/app/Views/admin/master_data/tahun_ajaran.php';
        break;

    case 'periode-pkl':
    case 'create-periode-pkl':
    case 'edit-periode-pkl':
        $masterMode = $routePage === 'create-periode-pkl'
            ? 'create'
            : ($routePage === 'edit-periode-pkl' ? 'edit' : 'list');
        $periods = [];
        $period = is_array($adminFlash['input'] ?? null) ? $adminFlash['input'] : [];
        $years = [];
        $pagination = [];
        try {
            $yearList = $loadMasterCollection(
                $api->adminMasterAcademicYears($adminApiToken, ['per_page' => 100]),
                'tahun_ajaran'
            );
            $years = $yearList['items'];
            if ($masterMode === 'list') {
                $filters = [
                    'search' => isset($_GET['search']) && is_string($_GET['search']) ? trim($_GET['search']) : '',
                    'page' => isset($_GET['list_page']) ? max(1, (int) $_GET['list_page']) : 1,
                    'per_page' => 15,
                ];
                $list = $loadMasterCollection($api->adminMasterPeriods($adminApiToken, $filters), 'periode_pkl');
                $periods = $list['items'];
                $pagination = $list['pagination'];
            } elseif ($masterMode === 'create') {
                $period = array_merge(['status' => 'persiapan'], $period);
            } else {
                $id = isset($_GET['id']) && is_string($_GET['id'])
                    ? filter_var($_GET['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
                    : false;
                if ($id === false) {
                    http_response_code(404);
                    throw new InvalidArgumentException('ID Periode PKL tidak valid.');
                }
                $period = $loadMasterRecord($api->adminMasterPeriod($adminApiToken, $id), 'periode_pkl');
                if (is_array($adminFlash['input'] ?? null)) {
                    $period = array_merge($period, $adminFlash['input']);
                }
            }
        } catch (Throwable $exception) {
            error_log('Admin master Periode PKL load failed: ' . $exception::class);
            $adminPageError ??= $exception instanceof InvalidArgumentException
                ? $exception->getMessage()
                : 'Data Periode PKL tidak dapat dimuat. Periksa koneksi API.';
        }
        require $adminRoot . '/app/Views/admin/master_data/periode_pkl.php';
        break;

    case 'kuota-instansi':
        $quotas = [];
        $instances = [];
        $periods = [];
        $pagination = [];
        $quotaInput = is_array($adminFlash['input'] ?? null) ? $adminFlash['input'] : [];
        try {
            $filters = [];
            foreach (['instansi_id', 'periode_id'] as $field) {
                if (isset($_GET[$field]) && is_string($_GET[$field]) && $_GET[$field] !== '') {
                    $filters[$field] = $_GET[$field];
                }
            }
            $filters['page'] = isset($_GET['list_page']) ? max(1, (int) $_GET['list_page']) : 1;
            $filters['per_page'] = 15;
            $quotaList = $loadMasterCollection($api->adminMasterQuotas($adminApiToken, $filters), 'kuota');
            $quotas = $quotaList['items'];
            $pagination = $quotaList['pagination'];
            $instanceList = $loadMasterCollection(
                $api->adminMasterInstances($adminApiToken, ['per_page' => 100]),
                'instansi'
            );
            $instances = $instanceList['items'];
            $periodList = $loadMasterCollection(
                $api->adminMasterPeriods($adminApiToken, ['per_page' => 100]),
                'periode_pkl'
            );
            $periods = $periodList['items'];
        } catch (Throwable $exception) {
            error_log('Admin master kuota load failed: ' . $exception::class);
            $adminPageError = 'Data kuota atau referensi tidak dapat dimuat. Periksa koneksi API.';
        }
        require $adminRoot . '/app/Views/admin/master_data/kuota.php';
        break;

    case 'referensi-penempatan-pkl':
        $referenceFilters = [];
        foreach ([
            'siswa_search',
            'guru_search',
            'siswa_page',
            'guru_page',
            'per_page',
            'instansi_id',
            'periode_id',
            'siswa_id',
            'guru_id',
        ] as $field) {
            if (isset($_GET[$field]) && is_string($_GET[$field])) {
                $referenceFilters[$field] = $_GET[$field];
            }
        }

        try {
            $referenceData = $loadPlacementReferences($referenceFilters);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'data' => $referenceData,
            ], JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            error_log('Admin placement reference proxy failed: ' . $exception->getMessage());
            http_response_code(503);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Referensi penempatan tidak tersedia. Coba muat ulang halaman.',
            ], JSON_THROW_ON_ERROR);
        }
        exit;

    case 'manajemen-pkl':
        $placementFilters = [
            'search' => isset($_GET['search']) && is_string($_GET['search']) ? trim($_GET['search']) : '',
            'periode_id' => isset($_GET['periode_id']) && is_string($_GET['periode_id']) ? $_GET['periode_id'] : '',
            'status' => isset($_GET['status']) && is_string($_GET['status']) ? $_GET['status'] : '',
            'instansi_id' => isset($_GET['instansi_id']) && is_string($_GET['instansi_id']) ? $_GET['instansi_id'] : '',
            'guru_id' => isset($_GET['guru_id']) && is_string($_GET['guru_id']) ? $_GET['guru_id'] : '',
        ];
        $listPage = isset($_GET['list_page']) && is_string($_GET['list_page'])
            ? filter_var($_GET['list_page'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
            : 1;
        $listPage = $listPage === false ? 1 : $listPage;
        $placementFilters = array_filter(
            $placementFilters,
            static fn ($value): bool => $value !== ''
        );
        $placementFilters['per_page'] = 15;
        $placementFilters['page'] = $listPage;
        $placements = [];
        $placementPagination = [];

        try {
            $result = $api->adminPlacements($adminApiToken, $placementFilters);
            if (
                $result['status'] >= 200
                && $result['status'] < 300
                && ($result['body']['success'] ?? false) === true
                && is_array($result['body']['data']['penempatan'] ?? null)
                && is_array($result['body']['data']['pagination'] ?? null)
            ) {
                $placements = $result['body']['data']['penempatan'];
                $placementPagination = $result['body']['data']['pagination'];
            } else {
                $adminPageError = $result['body']['message'] ?? 'Daftar penempatan PKL tidak dapat dimuat.';
            }
        } catch (Throwable $exception) {
            error_log('Admin placement list failed: ' . $exception->getMessage());
            $adminPageError = 'Daftar penempatan PKL tidak dapat dimuat. Periksa koneksi API.';
        }

        require $adminRoot . '/app/Views/admin/manajemen_pkl/index.php';
        break;

    case 'create-penempatan-pkl':
        $placementInput = is_array($adminFlash['input'] ?? null) ? $adminFlash['input'] : [];
        $placementReferences = [];
        $placementReferencesLoaded = false;
        try {
            $placementReferences = $loadPlacementReferences([
                'per_page' => 20,
                'siswa_search' => $placementInput['siswa_search'] ?? '',
                'guru_search' => $placementInput['guru_search'] ?? '',
                'siswa_id' => $placementInput['siswa_id'] ?? '',
                'guru_id' => $placementInput['guru_id'] ?? '',
                'instansi_id' => $placementInput['instansi_id'] ?? '',
                'periode_id' => $placementInput['periode_id'] ?? '',
            ]);
            $placementReferencesLoaded = true;
        } catch (Throwable $exception) {
            error_log('Admin placement form references failed: ' . $exception->getMessage());
            $adminPageError = 'Data referensi penempatan tidak dapat dimuat. Coba muat ulang halaman.';
        }
        require $adminRoot . '/app/Views/admin/manajemen_pkl/create.php';
        break;

    case 'detail-penempatan-pkl':
        $placementId = isset($_GET['id']) && is_string($_GET['id'])
            ? filter_var($_GET['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
            : false;
        $placement = null;
        $placementReferences = [];
        $placementReferencesLoaded = false;
        $placementInput = is_array($adminFlash['input'] ?? null) ? $adminFlash['input'] : [];

        if ($placementId === false) {
            http_response_code(404);
            $adminPageError = 'ID penempatan tidak valid.';
        } else {
            try {
                $result = $api->adminPlacement($adminApiToken, $placementId);
                if (
                    $result['status'] >= 200
                    && $result['status'] < 300
                    && ($result['body']['success'] ?? false) === true
                    && is_array($result['body']['data']['penempatan'] ?? null)
                ) {
                    $placement = $result['body']['data']['penempatan'];
                } else {
                    http_response_code($result['status'] === 404 ? 404 : 503);
                    $adminPageError = $result['body']['message'] ?? 'Detail penempatan tidak dapat dimuat.';
                }
            } catch (Throwable $exception) {
                error_log('Admin placement detail failed: ' . $exception->getMessage());
                http_response_code(503);
                $adminPageError = 'Detail penempatan tidak dapat dimuat. Periksa koneksi API.';
            }

            if (
                is_array($placement)
                && in_array($placement['status'] ?? null, ['belum_mulai', 'aktif'], true)
                && in_array($placement['periode']['status'] ?? null, ['persiapan', 'berjalan'], true)
            ) {
                try {
                    $placementReferences = $loadPlacementReferences([
                        'per_page' => 20,
                        'instansi_id' => $placementInput['instansi_id'] ?? '',
                        'periode_id' => $placement['periode']['id'] ?? '',
                        'guru_search' => $placementInput['guru_search'] ?? '',
                        'guru_id' => $placementInput['guru_id'] ?? '',
                    ]);
                    $placementReferencesLoaded = true;
                } catch (Throwable $exception) {
                    error_log('Admin placement move references failed: ' . $exception->getMessage());
                    $adminPageError ??= 'Referensi pemindahan tidak dapat dimuat. Aksi pemindahan dinonaktifkan.';
                }
            }
        }

        require $adminRoot . '/app/Views/admin/manajemen_pkl/detail.php';
        break;

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
        $teacherResponsibilities = ['penempatan' => [], 'guru_pengganti' => []];
        $teacherResponsibilitiesLoaded = false;
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
                    if (is_array($guru)) {
                        $assignmentResult = $api->adminTeacherActivePlacements($adminApiToken, $teacherId);
                        if (
                            $assignmentResult['status'] >= 200
                            && $assignmentResult['status'] < 300
                            && ($assignmentResult['body']['success'] ?? false) === true
                        ) {
                            $assignmentData = $assignmentResult['body']['data'] ?? null;
                            if (
                                is_array($assignmentData)
                                && is_array($assignmentData['penempatan'] ?? null)
                                && is_array($assignmentData['guru_pengganti'] ?? null)
                            ) {
                                $teacherResponsibilities = $assignmentData;
                                $teacherResponsibilitiesLoaded = true;
                            } else {
                                http_response_code(503);
                                $adminPageError = 'Respons daftar penempatan aktif Guru tidak valid. '
                                    . 'Status akun tidak dapat diubah sampai data berhasil dimuat.';
                            }
                        } else {
                            http_response_code(503);
                            $adminPageError = $assignmentResult['body']['message']
                                ?? 'Daftar penempatan aktif Guru tidak dapat dimuat. '
                                    . 'Status akun tidak dapat diubah sampai data berhasil dimuat.';
                        }
                    }
                } else {
                    http_response_code($result['status'] === 404 ? 404 : 503);
                    $adminPageError = $result['body']['message'] ?? 'Data guru tidak dapat dimuat.';
                }
            } catch (Throwable $exception) {
                error_log('Teacher user detail API error: ' . $exception->getMessage());
                http_response_code(503);
                $adminPageError = 'Tidak dapat memuat detail atau daftar penempatan aktif Guru. '
                    . 'Periksa koneksi API; status akun tidak dapat diubah sampai data berhasil dimuat.';
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

    // Rute Instansi milik tim (dari main). create/edit/detail-mitra sudah
    // ditangani lebih dulu oleh blok master_data di atas, jadi tiga case ini
    // tidak akan tercapai selama blok di atas ada. Pastikan dengan tim
    // versi mana yang dipakai.
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