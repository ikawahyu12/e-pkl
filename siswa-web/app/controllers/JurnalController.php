<?php
/** Jurnal - kerangka halaman (layout & navigasi). Isi fitur dikerjakan anggota yang bertanggung jawab. */
require_once APP_PATH . '/services/ApiClient.php';

class JurnalController extends Controller
{
    public function __construct()
    {
        $this->requireLogin();
    }

    public function index()
    {
        $this->render('siswa/jurnal/jurnal', [
            'title'    => 'Jurnal',
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

        foreach (['tanggal', 'judul', 'kegiatan', 'hasil'] as $field) {
            if (trim($_POST[$field] ?? '') === '') {
                $this->respondJson(['success' => false, 'message' => 'Tanggal, judul, kegiatan, dan hasil wajib diisi.'], 422);
                return;
            }
        }

        $fields = [
            'tanggal' => trim($_POST['tanggal']),
            'judul' => trim($_POST['judul']),
            'kegiatan' => trim($_POST['kegiatan']),
            'hasil' => trim($_POST['hasil']),
            'kendala' => trim($_POST['kendala'] ?? ''),
        ];

        $foto = $_FILES['foto'] ?? null;
        if (is_array($foto) && $foto['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($foto['error'] !== UPLOAD_ERR_OK) {
                $message = $foto['error'] === UPLOAD_ERR_INI_SIZE || $foto['error'] === UPLOAD_ERR_FORM_SIZE
                    ? 'Ukuran foto maksimal 5MB.'
                    : 'Foto gagal diterima. Silakan coba lagi.';
                $this->respondJson(['success' => false, 'message' => $message], 422);
                return;
            }

            if ($foto['size'] > 5 * 1024 * 1024) {
                $this->respondJson(['success' => false, 'message' => 'Ukuran foto maksimal 5MB.'], 422);
                return;
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = $finfo ? finfo_file($finfo, $foto['tmp_name']) : false;
            if ($finfo) {
                finfo_close($finfo);
            }

            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!in_array($mimeType, $allowedMimes, true)) {
                $this->respondJson(['success' => false, 'message' => 'Format foto harus JPG, PNG, atau WEBP.'], 422);
                return;
            }

            $fields['foto'] = new CURLFile($foto['tmp_name'], $mimeType, basename($foto['name']));
        }

        if (!function_exists('curl_init')) {
            $this->respondJson(['success' => false, 'message' => 'Server belum mendukung koneksi ke API jurnal.'], 500);
            return;
        }

        $api = new ApiClient();
        $response = $api->postForm('siswa/jurnal', $fields, $_SESSION['api_token'] ?? null);
        $status = (int) ($response['status'] ?? 502);
        if ($status < 200 || $status > 599) {
            $status = 502;
        }

        $this->respondJson([
            'success' => ($response['success'] ?? false) === true,
            'message' => $response['message'] ?? 'Jurnal gagal dikirim. Silakan coba lagi.',
        ], $status);
    }

    private function respondJson(array $payload, int $status): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
