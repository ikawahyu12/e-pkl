<?php
require_once APP_PATH . '/services/ApiClient.php';

/** Riwayat - mengambil presensi dan jurnal dari API Laravel (GET /siswa/riwayat). */
class RiwayatController extends Controller
{
    public function __construct()
    {
        $this->requireLogin();
    }

    /**
     * Token API yang disimpan saat login.
     * SESUAIKAN dengan kunci session yang dipakai proses login-mu
     * (cari dengan: findstr /s /i "token" siswa-web\app\*.php).
     */
    private function tokenApi(): string
    {
        return (string) (
            $_SESSION['api_token']
            ?? $_SESSION['token']
            ?? $_SESSION['user']['token']
            ?? $_SESSION['user']['api_token']
            ?? ''
        );
    }

    public function index()
    {
        $tanggalMulaiPkl = $_SESSION['user']['tanggal_mulai'] ?? '';
        $riwayat  = [];
        $apiError = null;
        $token    = $this->tokenApi();

        if ($token === '') {
            $apiError = 'Token API tidak ditemukan di session. Silakan keluar lalu masuk kembali.';
        } else {
            $api  = new ApiClient();
            $page = 1;

            // Ambil semua halaman (maks. 100 item per halaman), karena filter dikerjakan JS di browser
            do {
                $res = $api->get('siswa/riwayat?per_page=100&page=' . $page, $token);

                if (($res['status'] ?? 0) === 401) {
                    $apiError = 'Sesi API berakhir. Silakan keluar lalu masuk kembali.';
                    break;
                }
                if (($res['status'] ?? 0) !== 200 || empty($res['success'])) {
                    $apiError = $res['message'] ?? ('Riwayat gagal dimuat (kode ' . ($res['status'] ?? 0) . ').');
                    break;
                }

                foreach ($res['data'] ?? [] as $r) {
                    $badge = (string) ($r['badge'] ?? '');
                    $riwayat[] = [
                        'type'  => ($r['tipe'] ?? '') === 'jurnal' ? 'journal' : 'attendance',
                        'date'  => (string) ($r['tanggal'] ?? ''),
                        'label' => (string) ($r['tanggal_label'] ?? ''),
                        'title' => (string) ($r['judul'] ?? ''),
                        'badge' => $badge,
                        'pill'  => in_array($badge, ['Masuk', 'Pulang'], true) ? 'pill-ok' : 'pill-info',
                        'jam'   => !empty($r['jam']) ? $r['jam'] . ' WIB' : '',
                    ];
                }

                $lastPage = (int) ($res['meta']['last_page'] ?? 1);
                $page++;
            } while ($page <= $lastPage && $page <= 10);
        }

        $this->render('siswa/riwayat/riwayat', [
            'title'           => 'Riwayat',
            'showBack'        => true,
            'wide'            => true,
            'tanggalMulaiPkl' => $tanggalMulaiPkl,
            'riwayat'         => $riwayat,
            'apiError'        => $apiError,
        ]);
    }
}