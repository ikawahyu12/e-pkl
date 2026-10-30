<?php
/**
 * Router sederhana:  /controller/method/param1/param2
 * contoh: /presensi            -> PresensiController::index()
 *         /presensi/checkin    -> PresensiController::checkin()
 */
class App
{
    private $controller = 'BerandaController';
    private $method     = 'index';
    private $params     = [];

    public function __construct()
    {
        $url = $this->parseUrl();

        if (!empty($url[0])) {
            $name = ucfirst(strtolower($url[0])) . 'Controller';
            if (!file_exists(APP_PATH . '/controllers/' . $name . '.php')) {
                return $this->notFound();
            }
            $this->controller = $name;
            unset($url[0]);
        }

        require_once APP_PATH . '/controllers/' . $this->controller . '.php';
        $obj = new $this->controller;

        if (isset($url[1])) {
            $m = $url[1];
            if ($m[0] !== '_' && is_callable([$obj, $m])) {
                $this->method = $m;
                unset($url[1]);
            } else {
                return $this->notFound();
            }
        }

        $this->params = $url ? array_values($url) : [];
        call_user_func_array([$obj, $this->method], $this->params);
    }

    private function parseUrl()
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if (BASE_PATH !== '' && strpos($path, BASE_PATH) === 0) {
            $path = substr($path, strlen(BASE_PATH));
        }
        $path = trim($path, '/');
        if ($path === '' || $path === 'index.php') return [];
        return explode('/', filter_var($path, FILTER_SANITIZE_URL));
    }

    private function notFound()
    {
        require_once APP_PATH . '/controllers/ErrorController.php';
        (new ErrorController)->notFound();
    }
}
