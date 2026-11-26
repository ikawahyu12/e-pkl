<?php
require_once APP_PATH . '/services/ApiClient.php';

/** Riwayat - mengambil presensi, jurnal, dan izin dari API Laravel */
class RiwayatController extends Controller
{
    public function __construct()
    {
        $this->requireLogin();
    }

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
            
            // ==========================================
            // 1. AMBIL DATA PRESENSI & JURNAL
            // ==========================================
            $page = 1;
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
                    $type = (string) ($r['tipe'] ?? '');
                    $badge = (string) ($r['badge'] ?? '');

                    $historyType = match ($type) {
                        'jurnal' => 'journal',
                        'izin' => 'izin',
                        default => 'attendance',
                    };

                    $pill = 'pill-info';
                    if (in_array($badge, ['Masuk', 'Pulang', 'Disetujui', 'Terkirim'], true)) {
                        $pill = 'pill-ok';
                    } elseif ($badge === 'Menunggu') {
                        $pill = 'pill-warn';
                    } elseif ($badge === 'Ditolak') {
                        $pill = 'pill-danger';
                    }

                    $title = (string) ($r['judul'] ?? '');
                    if ($type === 'izin' && !empty($r['keterangan'])) {
                        $title .= ': ' . $r['keterangan'];
                    }

                    $riwayat[] = [
                        'type'  => $historyType,
                        'date'  => (string) ($r['tanggal'] ?? ''),
                        'label' => (string) ($r['tanggal_label'] ?? ''),
                        'title' => $title,
                        'badge' => $badge,
                        'pill'  => $pill,
                        'jam'   => !empty($r['jam']) ? $r['jam'] . ' WIB' : '',
                    ];
                }

                $lastPage = (int) ($res['meta']['last_page'] ?? 1);
                $page++;
            } while ($page <= $lastPage && $page <= 10);

            // Urutkan berdasarkan tanggal terbaru (descending)
            usort($riwayat, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });
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