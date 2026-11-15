<?php

require_once APP_PATH . '/services/ApiClient.php';

class AuthController extends Controller
{
    public function register()
    {
        $csrfToken = $this->csrfToken();
        $message = null;
        $messageType = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verifyCsrfToken()) {
                $message = 'Sesi formulir tidak valid. Muat ulang halaman.';
                $messageType = 'error';
            } else {
                $nisn = is_string($_POST['nisn'] ?? null) ? trim($_POST['nisn']) : '';
                $nama = is_string($_POST['nama_lengkap'] ?? null) ? trim($_POST['nama_lengkap']) : '';
                $email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
                $password = $_POST['password'] ?? '';
                $confirmation = $_POST['password_confirmation'] ?? '';

                if ($nisn === '' || $nama === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $message = 'Lengkapi NISN, nama, dan email dengan benar.';
                    $messageType = 'error';
                } elseif (!is_string($password) || strlen($password) < 12) {
                    $message = 'Kata sandi minimal 12 karakter.';
                    $messageType = 'error';
                } elseif (!is_string($confirmation) || $password !== $confirmation) {
                    $message = 'Konfirmasi kata sandi tidak cocok.';
                    $messageType = 'error';
                } else {
                    $response = (new ApiClient())->post('siswa/auth/register', [
                        'nisn' => $nisn,
                        'nama_lengkap' => $nama,
                        'email' => $email,
                        'password' => $password,
                        'password_confirmation' => $confirmation,
                    ]);

                    if (($response['status'] ?? 0) === 202) {
                        $message = $response['message']
                            ?? 'Jika data sesuai dengan master, instruksi verifikasi akan dikirim ke email.';
                        $messageType = 'success';
                    } elseif (($response['status'] ?? 0) === 422) {
                        $message = $response['message']
                            ?? 'Data tidak dapat digunakan untuk registrasi. Periksa kembali isian Anda.';
                        $messageType = 'error';
                    } else {
                        $message = $response['message']
                            ?? 'Registrasi sementara tidak tersedia. Coba lagi nanti.';
                        $messageType = 'error';
                    }
                }
            }
        }

        $this->renderPlain('auth/register', [
            'csrfToken' => $csrfToken,
            'message' => $message,
            'messageType' => $messageType,
        ]);
    }

    public function resendVerification()
    {
        $csrfToken = $this->csrfToken();
        $message = null;
        $messageType = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verifyCsrfToken()) {
                $message = 'Sesi formulir tidak valid. Muat ulang halaman.';
                $messageType = 'error';
            } else {
                $email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $message = 'Masukkan alamat email yang valid.';
                    $messageType = 'error';
                } else {
                    $response = (new ApiClient())->post('siswa/auth/resend-verification', [
                        'email' => $email,
                    ]);
                    if (($response['status'] ?? 0) === 202) {
                        $message = $response['message']
                            ?? 'Jika ada akun yang menunggu verifikasi, tautan baru akan dikirim.';
                        $messageType = 'success';
                    } else {
                        $message = 'Permintaan belum dapat diproses. Coba lagi nanti.';
                        $messageType = 'error';
                    }
                }
            }
        }

        $this->renderPlain('auth/resend_verification', [
            'csrfToken' => $csrfToken,
            'message' => $message,
            'messageType' => $messageType,
        ]);
    }

    public function lupaPassword()
    {
        $csrfToken = $this->csrfToken();
        $message = null;
        $messageType = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verifyCsrfToken()) {
                $message = 'Sesi formulir tidak valid. Muat ulang halaman.';
                $messageType = 'error';
            } else {
                $email = trim($_POST['email'] ?? '');

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $message = 'Masukkan alamat email yang valid.';
                    $messageType = 'error';
                } else {
                    $response = (new ApiClient())->post('siswa/auth/forgot-password', [
                        'email' => $email,
                    ]);

                    if (($response['status'] ?? 0) === 202) {
                        $message = $response['message']
                            ?? 'Jika alamat email terdaftar, instruksi reset kata sandi akan dikirim.';
                        $messageType = 'success';
                    } else {
                        $message = 'Permintaan reset tidak dapat diproses. Coba lagi nanti.';
                        $messageType = 'error';
                    }
                }
            }
        }

        $this->renderPlain('auth/lupa_password', [
            'csrfToken' => $csrfToken,
            'message' => $message,
            'messageType' => $messageType,
        ]);
    }

    public function resetPassword()
    {
        $csrfToken = $this->csrfToken();
        $email = is_string($_REQUEST['email'] ?? null) ? $_REQUEST['email'] : '';
        $token = is_string($_REQUEST['token'] ?? null) ? $_REQUEST['token'] : '';
        $message = null;
        $messageType = null;
        $completed = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verifyCsrfToken()) {
                $message = 'Sesi formulir tidak valid. Muat ulang halaman.';
                $messageType = 'error';
            } else {
                $password = $_POST['password_baru'] ?? '';
                $confirmation = $_POST['password_baru_confirmation'] ?? '';

                if (!is_string($password) || strlen($password) < 8) {
                    $message = 'Kata sandi baru minimal 8 karakter.';
                    $messageType = 'error';
                } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
                    $message = 'Kata sandi baru harus mengandung huruf dan angka.';
                    $messageType = 'error';
                } elseif (!is_string($confirmation) || $password !== $confirmation) {
                    $message = 'Konfirmasi kata sandi baru tidak cocok.';
                    $messageType = 'error';
                } else {
                    $response = (new ApiClient())->post('siswa/auth/reset-password', [
                        'email' => $email,
                        'token' => $token,
                        'password_baru' => $password,
                        'password_baru_confirmation' => $confirmation,
                    ]);

                    if (($response['success'] ?? false) === true) {
                        $message = 'Kata sandi berhasil diubah. Silakan masuk dengan kata sandi baru.';
                        $messageType = 'success';
                        $completed = true;
                    } else {
                        $message = $response['message']
                            ?? 'Tautan reset tidak valid atau telah kedaluwarsa.';
                        $messageType = 'error';
                    }
                }
            }
        }

        $this->renderPlain('auth/reset_password', [
            'csrfToken' => $csrfToken,
            'email' => $email,
            'token' => $token,
            'message' => $message,
            'messageType' => $messageType,
            'completed' => $completed,
        ]);
    }
    public function login()
    {
        if (
            !empty($_SESSION['api_token'])
            && !empty($_SESSION['user'])
            && ($_SESSION['user']['role'] ?? '') === 'siswa'
        ) {
            $destination = !empty($_SESSION['user']['wajib_ganti_password'])
                ? 'profil/password'
                : 'beranda';
            $this->redirect($destination);
        }

        $error = '';
        $identifier = '';
        $csrfToken = $this->csrfToken();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (!$this->verifyCsrfToken()) {
                $error = 'Sesi formulir tidak valid. Muat ulang halaman login.';
            } else {
                $identifier = trim($_POST['identifier'] ?? '');
                $password = $_POST['password'] ?? '';

                if ($identifier === '' || $password === '') {
                    $error = 'NIS, email, atau username dan password wajib diisi.';
                } else {
                    $api = new ApiClient();

                    $response = $api->post('siswa/auth/login', [
                        'login' => $identifier,
                        'password' => $password,
                    ]);

                    if (
                        ($response['success'] ?? false) === true
                        && !empty($response['data']['token'])
                        && !empty($response['data']['user'])
                    ) {
                        $user = $response['data']['user'];

                        if (($user['role'] ?? '') !== 'siswa') {
                            $error = 'Akun ini bukan akun Siswa.';
                        } else {
                            session_regenerate_id(true);

                            $_SESSION['api_token'] = $response['data']['token'];
                            $_SESSION['user'] = $user;

                            unset($_SESSION['csrf_token']);

                            $destination = !empty($user['wajib_ganti_password'])
                                ? 'profil/password'
                                : 'beranda';
                            $this->redirect($destination);
                        }
                    } else {
                        $status = $response['status'] ?? 0;

                        if (in_array($status, [401, 403, 422], true)) {
                            $error = $response['message']
                                ?? 'NIS, email, username, atau password salah.';
                        } else {
                            $error = 'Server autentikasi tidak dapat dihubungi. Coba lagi.';
                        }
                    }
                }
            }
        }

        $this->renderPlain('auth/login', [
            'error' => $error,
            'identifier' => $identifier,
            'csrfToken' => $csrfToken,
        ]);
    }

    public function logout()
    {
        if (
            $_SERVER['REQUEST_METHOD'] !== 'POST'
            || !$this->verifyCsrfToken()
        ) {
            http_response_code(419);
            exit('Permintaan logout tidak valid. Muat ulang halaman.');
        }

        $token = $_SESSION['api_token'] ?? null;

        if ($token) {
            $api = new ApiClient();
            $api->post('siswa/auth/logout', [], $token);
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();

        header('Location: ' . url('login'));
        exit;
    }
}