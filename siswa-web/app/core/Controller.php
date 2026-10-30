<?php
/**
 * Controller dasar. Semua controller halaman siswa mewarisi class ini.
 *
 *  $this->render('siswa/jurnal/index', [
 *      'title'    => 'Jurnal',   // judul halaman
 *      'showBack' => true,       // tampilkan tombol kembali + judul (false = Beranda)
 *      'backUrl'  => url('beranda'),
 *      'wide'     => false,      // true = konten lebar (dashboard), false = konten ringkas (form)
 *  ]);
 */
class Controller
{
    protected function requireLogin()
    {
        if (DEV_MODE) return;
        if (empty($_SESSION['user'])) {
            $this->redirect('login');
        }
    }

    protected function redirect($path)
    {
        header('Location: ' . url($path));
        exit;
    }

    /** Render view di dalam layout siswa (header + area konten + footer). */
    protected function render($view, $data = [])
    {
        $data += ['title' => APP_NAME, 'showBack' => false, 'backUrl' => url('beranda'), 'wide' => false];
        extract($data, EXTR_SKIP);

        ob_start();
        require APP_PATH . '/views/' . $view . '.php';
        $content = ob_get_clean();

        require APP_PATH . '/views/layouts/siswa.php';
    }

    /** Render view polos tanpa layout (login, dll). */
    protected function renderPlain($view, $data = [])
    {
        extract($data, EXTR_SKIP);
        require APP_PATH . '/views/' . $view . '.php';
    }
}
