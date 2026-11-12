<?php
/** Presensi Siswa - mengelola halaman presensi, check-in, dan check-out. */
require_once APP_PATH . '/services/ApiClient.php';

class PresensiController extends Controller
{
    public function __construct()
    {
        $this->requireLogin();
    }

    /** Halaman utama presensi: url('presensi') */
    public function index()
    {
        $this->render('siswa/presensi/index', [
            'title'    => 'Presensi Harian',
            'showBack' => true,
            'wide'     => true,
        ]);
    }

    /** Proses simpan presensi melalui API Laravel. */
    public function store()
    {
        header('Content-Type: application/json; charset=utf-8');

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->respond('error', 'Metode permintaan tidak diizinkan.', 405);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $this->respond('error', 'Data permintaan tidak valid.', 400);
            return;
        }

        $sessionToken = $_SESSION['csrf_token'] ?? '';
        $formToken = $input['csrf_token'] ?? '';
        if (
            !is_string($formToken)
            || $sessionToken === ''
            || !hash_equals($sessionToken, $formToken)
        ) {
            $this->respond('error', 'Sesi formulir tidak valid. Muat ulang halaman dan coba lagi.', 419);
            return;
        }

        $type = $input['type'] ?? null;
        $latitude = $input['latitude'] ?? null;
        $longitude = $input['longitude'] ?? null;
        if (
            !in_array($type, ['datang', 'pulang'], true)
            || !is_numeric($latitude)
            || !is_numeric($longitude)
            || !is_finite((float) $latitude)
            || !is_finite((float) $longitude)
            || (float) $latitude < -90
            || (float) $latitude > 90
            || (float) $longitude < -180
            || (float) $longitude > 180
        ) {
            $this->respond('error', 'Data lokasi atau jenis presensi tidak valid.', 422);
            return;
        }

        $api = new ApiClient();
        $response = $api->post('siswa/presensi', [
            'type' => $type,
            'latitude' => (float) $latitude,
            'longitude' => (float) $longitude,
        ], $_SESSION['api_token'] ?? null);

        $statusCode = (int) ($response['status'] ?? 502);
        if ($statusCode === 401) {
            unset($_SESSION['api_token'], $_SESSION['user']);
        }
        if ($statusCode < 100 || $statusCode > 599) {
            $statusCode = 502;
        }

        $this->respond(
            ($response['success'] ?? false) === true ? 'success' : 'error',
            $response['message'] ?? 'Server API tidak dapat memproses presensi.',
            $statusCode,
            $response['data'] ?? null,
        );
    }

    private function respond(string $status, string $message, int $statusCode, ?array $data = null): void
    {
        http_response_code($statusCode);
        echo json_encode([
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ], JSON_UNESCAPED_UNICODE);
    }
}
