<?php

class Controller
{
    protected function requireLogin()
    {
        if (
            empty($_SESSION['api_token'])
            || empty($_SESSION['user'])
            || ($_SESSION['user']['role'] ?? '') !== 'siswa'
        ) {
            $this->redirect('login');
        }
    }

    protected function redirect($path)
    {
        header('Location: ' . url($path));
        exit;
    }

    protected function render($view, $data = [])
    {
        $data += [
            'title' => APP_NAME,
            'showBack' => false,
            'backUrl' => url('beranda'),
            'wide' => false,
            'csrfToken' => $this->csrfToken(),
        ];

        extract($data, EXTR_SKIP);

        ob_start();
        require APP_PATH . '/views/' . $view . '.php';
        $content = ob_get_clean();

        require APP_PATH . '/views/layouts/siswa.php';
    }

    protected function renderPlain($view, $data = [])
    {
        extract($data, EXTR_SKIP);
        require APP_PATH . '/views/' . $view . '.php';
    }

    protected function csrfToken()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    protected function verifyCsrfToken()
    {
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        $formToken = $_POST['csrf_token'] ?? '';

        return is_string($formToken)
            && $sessionToken !== ''
            && hash_equals($sessionToken, $formToken);
    }
}