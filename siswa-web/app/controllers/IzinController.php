<?php
/** Izin - kerangka halaman (layout & navigasi). Isi fitur dikerjakan anggota yang bertanggung jawab. */
require_once APP_PATH . '/services/ApiClient.php';

class IzinController extends Controller
{
    public function __construct()
    {
        $this->requireLogin();
    }

    public function index()
    {
        $this->render('siswa/izin/izin', [
            'title'    => 'Izin',
            'showBack' => true,
            'wide'     => true,
        ]);
    }

    public function kirim()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->respondJson(['success' => false, 'message' => 'Metode permintaan tidak diizinkan.'], 405);
            return;
        }

        if (!$this->verifyCsrfToken()) {
            $this->respondJson(['success' => false, 'message' => 'Sesi formulir tidak valid. Muat ulang halaman.'], 419);
            return;
        }

        $jenis = $_POST['jenis'] ?? '';
        $tanggalMulai = $_POST['tanggal_mulai'] ?? '';
        $tanggalSelesai = $_POST['tanggal_selesai'] ?? '';
        $alasan = trim($_POST['alasan'] ?? '');

        if (
            !in_array($jenis, ['izin', 'sakit'], true)
            || !$this->isValidDate($tanggalMulai)
            || !$this->isValidDate($tanggalSelesai)
            || $tanggalSelesai < $tanggalMulai
            || $alasan === ''
        ) {
            $this->respondJson(['success' => false, 'message' => 'Lengkapi jenis, tanggal yang valid, dan alasan pengajuan. Pastikan tanggal selesai tidak sebelum tanggal mulai.'], 422);
            return;
        }

        $fields = [
            'jenis' => $jenis,
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai,
            'alasan' => $alasan,
        ];

        $lampiran = $_FILES['lampiran'] ?? null;
        if (is_array($lampiran) && $lampiran['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($lampiran['error'] !== UPLOAD_ERR_OK) {
                $message = $lampiran['error'] === UPLOAD_ERR_INI_SIZE || $lampiran['error'] === UPLOAD_ERR_FORM_SIZE
                    ? 'Ukuran lampiran maksimal 5MB.'
                    : 'Lampiran gagal diterima. Silakan coba lagi.';
                $this->respondJson(['success' => false, 'message' => $message], 422);
                return;
            }

            if ($lampiran['size'] > 5 * 1024 * 1024) {
                $this->respondJson(['success' => false, 'message' => 'Ukuran lampiran maksimal 5MB.'], 422);
                return;
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = $finfo ? finfo_file($finfo, $lampiran['tmp_name']) : false;
            if ($finfo) {
                finfo_close($finfo);
            }

            $allowedMimes = ['image/jpeg', 'image/png', 'application/pdf'];
            if (!in_array($mimeType, $allowedMimes, true)) {
                $this->respondJson(['success' => false, 'message' => 'Format lampiran harus JPG, PNG, atau PDF.'], 422);
                return;
            }

            if (!function_exists('curl_init')) {
                $this->respondJson(['success' => false, 'message' => 'Server belum mendukung koneksi ke API pengajuan izin.'], 500);
                return;
            }

            $fields['lampiran'] = new CURLFile($lampiran['tmp_name'], $mimeType, basename($lampiran['name']));
        }

        $api = new ApiClient();
        $response = $api->postForm('siswa/izin', $fields, $_SESSION['api_token'] ?? null);
        $status = (int) ($response['status'] ?? 502);
        if ($status < 200 || $status > 599) {
            $status = 502;
        }

        $this->respondJson([
            'success' => ($response['success'] ?? false) === true,
            'message' => $response['message'] ?? 'Pengajuan gagal dikirim. Silakan coba lagi.',
        ], $status);
    }

    private function isValidDate(string $date): bool
    {
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }

    private function respondJson(array $payload, int $status): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
