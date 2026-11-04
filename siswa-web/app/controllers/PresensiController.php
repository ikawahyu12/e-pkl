<?php
/** Presensi Siswa - mengelola halaman presensi, check-in, dan check-out. */
class PresensiController extends Controller
{
    public function __construct()
    {
        // Pastikan siswa sudah login menggunakan method bawaan framework kamu
        $this->requireLogin();
    }

    /** Halaman utama presensi: url('presensi') */
    public function index()
    {
        $this->render('siswa/presensi/index', [
            'title'    => 'Presensi Harian',
            'showBack' => true,
            'wide'     => true,
            // TODO: Ambil data status presensi hari ini dari database jika diperlukan
        ]);
    }

    /** Proses simpan presensi (POST via AJAX/Fetch): url('presensi/store') */
    public function store()
    {
        header('Content-Type: application/json');

        // Terima data JSON dari request JavaScript
        $input = json_decode(file_get_contents('php://input'), true);

        $type = $input['type'] ?? '';
        $lat  = $input['latitude'] ?? null;
        $lng  = $input['longitude'] ?? null;

        // Validasi input
        if (!$lat || !$lng || !in_array($type, ['datang', 'pulang'])) {
            echo json_encode([
                'status'  => 'error',
                'message' => 'Data lokasi atau jenis presensi tidak valid.'
            ]);
            return;
        }

        // Koordinat Mitra (Lokasi PKL) & Radius Maksimal (misal 100 Meter)
        $mitraLat = -8.1585;
        $mitraLng = 113.7226;
        $maxRadiusMeters = 100;

        $distance = $this->haversineDistance($lat, $lng, $mitraLat, $mitraLng);

        if ($distance > $maxRadiusMeters) {
            echo json_encode([
                'status'  => 'error',
                'message' => 'Posisi Anda berada di luar radius lokasi PKL (' . round($distance) . 'm).'
            ]);
            return;
        }

        // TODO: Simpan data presensi ($type, $lat, $lng) ke database melalui Model
        // Contoh: $this->model('PresensiModel')->savePresensi($type, $lat, $lng);

        echo json_encode([
            'status'  => 'success',
            'message' => 'Presensi ' . $type . ' berhasil dikonfirmasi server!'
        ]);
    }

    /** Algoritma menghitung jarak latitude & longitude dalam satuan meter (Haversine Formula) */
    private function haversineDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // Meter

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}