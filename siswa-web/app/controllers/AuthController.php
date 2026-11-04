<?php

require_once APP_PATH . '/services/ApiClient.php';

class AuthController extends Controller
{
    public function login()
    {
        if (
            !empty($_SESSION['api_token'])
            && !empty($_SESSION['user'])
            && ($_SESSION['user']['role'] ?? '') === 'siswa'
        ) {
            $this->redirect('beranda');
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
                    $error = 'Email/username dan password wajib diisi.';
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

                            $this->redirect('beranda');
                        }
                    } else {
                        $status = $response['status'] ?? 0;

                        if (in_array($status, [401, 403, 422], true)) {
                            $error = $response['message']
                                ?? 'Email/username atau password salah.';
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