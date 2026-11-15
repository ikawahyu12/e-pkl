<?php

namespace App\Http\Controllers\Api\V1\Siswa;

use App\Http\Controllers\Controller;
use App\Models\PenempatanPkl;
use App\Models\Presensi;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PresensiController extends Controller
{
    public function index(Request $request)
    {
        $now = now('Asia/Jakarta');
        $penempatan = $this->activePlacement($request, $now->toDateString());

        if (! $penempatan || ! $penempatan->instansi) {
            return response()->json([
                'success' => false,
                'message' => 'Penempatan PKL aktif beserta data instansi tidak ditemukan.',
            ], 422);
        }

        $instansi = $penempatan->instansi;
        $sessions = $this->sessionsForPlacement($penempatan, $now->toDateString());
        $attendanceBySession = Presensi::query()
            ->whereIn('sesi_id', $sessions->pluck('id'))
            ->where('penempatan_id', $penempatan->id)
            ->get()
            ->keyBy('sesi_id');

        return response()->json([
            'success' => true,
            'message' => 'Jadwal presensi berhasil dimuat.',
            'data' => [
                'tanggal' => $now->toDateString(),
                'instansi' => [
                    'nama' => $instansi->nama,
                    'alamat' => $instansi->alamat,
                    'latitude' => $instansi->latitude,
                    'longitude' => $instansi->longitude,
                    'radius_meters' => $instansi->radius_geofence_meter,
                    'geofence_configured' => $this->hasValidGeofence($instansi),
                ],
                'sesi' => $sessions->map(function ($session) use ($attendanceBySession): array {
                    $attendance = $attendanceBySession->get($session->id);

                    return [
                        'id' => $session->id,
                        'tanggal' => $session->tanggal,
                        'nama_shift' => $session->nama_shift,
                        'jam_masuk_mulai' => $session->jam_masuk_mulai,
                        'jam_masuk_selesai' => $session->jam_masuk_selesai,
                        'jam_pulang_mulai' => $session->jam_pulang_mulai,
                        'jam_pulang_selesai' => $session->jam_pulang_selesai,
                        'status' => $session->status,
                        'waktu_masuk' => $attendance?->waktu_masuk,
                        'waktu_pulang' => $attendance?->waktu_pulang,
                    ];
                })->values(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'session_id' => ['required', 'integer', 'min:1'],
            'type' => ['required', 'in:datang,pulang'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $now = now('Asia/Jakarta');
        $today = $now->toDateString();
        $penempatan = $this->activePlacement($request, $today);

        if (! $penempatan || ! $penempatan->instansi) {
            return response()->json([
                'success' => false,
                'message' => 'Penempatan PKL aktif beserta data instansi tidak ditemukan.',
            ], 422);
        }

        $instansi = $penempatan->instansi;
        if (! $this->hasValidGeofence($instansi)) {
            return response()->json([
                'success' => false,
                'message' => 'Koordinat atau radius geofencing instansi belum diatur Admin atau tidak valid.',
            ], 422);
        }

        $session = $this->sessionsForPlacement($penempatan, $today, true)
            ->firstWhere('id', (int) $data['session_id']);

        if (! $session) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi presensi tidak ditemukan, bukan milik Guru pembimbing Anda, atau sudah ditutup.',
            ], 422);
        }

        $time = $now->format('H:i:s');
        $startColumn = $data['type'] === 'datang' ? 'jam_masuk_mulai' : 'jam_pulang_mulai';
        $endColumn = $data['type'] === 'datang' ? 'jam_masuk_selesai' : 'jam_pulang_selesai';
        if ($time < $session->{$startColumn} || $time > $session->{$endColumn}) {
            return response()->json([
                'success' => false,
                'message' => sprintf(
                    'Presensi %s hanya dapat dilakukan pukul %s sampai %s WIB.',
                    $data['type'],
                    substr($session->{$startColumn}, 0, 5),
                    substr($session->{$endColumn}, 0, 5),
                ),
            ], 422);
        }

        $distance = self::haversineDistance(
            (float) $data['latitude'],
            (float) $data['longitude'],
            (float) $instansi->latitude,
            (float) $instansi->longitude,
        );
        $radius = (int) $instansi->radius_geofence_meter;

        if ($distance > $radius) {
            return response()->json([
                'success' => false,
                'message' => "Anda berada di luar radius {$radius} meter dari lokasi instansi PKL.",
                'data' => [
                    'distance_meters' => round($distance, 2),
                    'radius_meters' => $radius,
                ],
            ], 422);
        }

        $result = DB::transaction(function () use ($data, $distance, $penempatan, $session, $today, $now): array {
            $attendance = Presensi::query()
                ->where('sesi_id', $session->id)
                ->where('penempatan_id', $penempatan->id)
                ->lockForUpdate()
                ->first();

            if (! $attendance) {
                $attendance = new Presensi([
                    'sesi_id' => $session->id,
                    'penempatan_id' => $penempatan->id,
                    'tanggal' => $today,
                    'status_harian' => 'belum_lengkap',
                ]);
            }

            $type = $data['type'];
            $timeColumn = $type === 'datang' ? 'waktu_masuk' : 'waktu_pulang';

            if ($attendance->{$timeColumn} !== null) {
                return ['error' => 'duplicate'];
            }

            if ($type === 'pulang' && $attendance->waktu_masuk === null) {
                return ['error' => 'checkin_required'];
            }

            if ($type === 'datang') {
                $attendance->waktu_masuk = $now;
                $attendance->latitude_masuk = $data['latitude'];
                $attendance->longitude_masuk = $data['longitude'];
                $attendance->jarak_masuk_meter = round($distance, 2);
                $toleransi = $session->batas_toleransi ?: $session->jam_masuk_selesai;
                $attendance->status_masuk = $now->format('H:i:s') > $toleransi
                    ? 'terlambat'
                    : 'tepat_waktu';
                $attendance->status_harian = 'hadir';
            } else {
                $attendance->waktu_pulang = $now;
                $attendance->latitude_pulang = $data['latitude'];
                $attendance->longitude_pulang = $data['longitude'];
                $attendance->jarak_pulang_meter = round($distance, 2);
                $attendance->status_pulang = 'sesuai';
                $attendance->status_harian = 'hadir';
            }

            $attendance->save();

            return ['attendance' => $attendance];
        });

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'] === 'checkin_required'
                    ? 'Presensi datang harus dilakukan sebelum presensi pulang.'
                    : 'Presensi '.$data['type'].' sudah tercatat pada sesi ini.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Presensi '.$data['type'].' berhasil disimpan.',
            'data' => [
                'distance_meters' => round($distance, 2),
                'radius_meters' => $radius,
                'presensi' => $result['attendance'],
            ],
        ]);
    }

    private function activePlacement(Request $request, string $today): ?PenempatanPkl
    {
        $siswa = $request->user()->siswa;
        if (! $siswa) {
            return null;
        }

        return PenempatanPkl::with(['instansi', 'guru'])
            ->where('siswa_id', $siswa->id)
            ->where('status', 'aktif')
            ->whereDate('tanggal_mulai', '<=', $today)
            ->whereDate('tanggal_selesai', '>=', $today)
            ->orderByDesc('tanggal_mulai')
            ->first();
    }

    private function sessionsForPlacement(
        PenempatanPkl $penempatan,
        string $today,
        bool $openOnly = false,
    ): Collection {
        if (! $penempatan->guru) {
            return collect();
        }

        $query = DB::table('sesi_presensi')
            ->join('pengaturan_presensi', 'pengaturan_presensi.id', '=', 'sesi_presensi.pengaturan_id')
            ->where('pengaturan_presensi.instansi_id', $penempatan->instansi_id)
            ->where('pengaturan_presensi.aktif', true)
            ->where('sesi_presensi.dibuat_oleh', $penempatan->guru->user_id)
            ->where('sesi_presensi.tanggal', $today)
            ->orderBy('sesi_presensi.id')
            ->select('sesi_presensi.*');

        if ($openOnly) {
            $query->where('sesi_presensi.status', 'dibuka');
        }

        return $query->get();
    }

    private function hasValidGeofence($instansi): bool
    {
        return is_numeric($instansi->latitude)
            && is_numeric($instansi->longitude)
            && is_finite((float) $instansi->latitude)
            && is_finite((float) $instansi->longitude)
            && (float) $instansi->latitude >= -90
            && (float) $instansi->latitude <= 90
            && (float) $instansi->longitude >= -180
            && (float) $instansi->longitude <= 180
            && is_numeric($instansi->radius_geofence_meter)
            && (int) $instansi->radius_geofence_meter > 0;
    }

    public static function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusMeters = 6371000;
        $deltaLatitude = deg2rad($lat2 - $lat1);
        $deltaLongitude = deg2rad($lon2 - $lon1);

        $a = sin($deltaLatitude / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($deltaLongitude / 2) ** 2;
        $a = max(0, min(1, $a));

        return $earthRadiusMeters * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
