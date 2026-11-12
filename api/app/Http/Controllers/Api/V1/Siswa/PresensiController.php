<?php

namespace App\Http\Controllers\Api\V1\Siswa;

use App\Http\Controllers\Controller;
use App\Models\PenempatanPkl;
use App\Models\Presensi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PresensiController extends Controller
{
    private const RADIUS_METERS = 100;

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:datang,pulang'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $siswa = $request->user()->siswa;
        $now = now('Asia/Jakarta');
        $today = $now->toDateString();

        $penempatan = PenempatanPkl::with('instansi')
            ->where('siswa_id', $siswa->id)
            ->where('status', 'aktif')
            ->whereDate('tanggal_mulai', '<=', $today)
            ->whereDate('tanggal_selesai', '>=', $today)
            ->orderByDesc('tanggal_mulai')
            ->first();

        if (! $penempatan || ! $penempatan->instansi) {
            return response()->json([
                'success' => false,
                'message' => 'Penempatan PKL aktif beserta data instansi tidak ditemukan.',
            ], 422);
        }

        $instansi = $penempatan->instansi;
        if (
            ! is_numeric($instansi->latitude)
            || ! is_numeric($instansi->longitude)
            || ! is_finite((float) $instansi->latitude)
            || ! is_finite((float) $instansi->longitude)
            || $instansi->latitude < -90
            || $instansi->latitude > 90
            || $instansi->longitude < -180
            || $instansi->longitude > 180
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Koordinat lokasi instansi belum tersedia atau tidak valid.',
            ], 422);
        }

        $distance = self::haversineDistance(
            (float) $data['latitude'],
            (float) $data['longitude'],
            (float) $instansi->latitude,
            (float) $instansi->longitude,
        );

        if ($distance > self::RADIUS_METERS) {
            return response()->json([
                'success' => false,
                'message' => 'Anda berada di luar radius 100 meter dari lokasi instansi PKL.',
                'data' => [
                    'distance_meters' => round($distance, 2),
                    'radius_meters' => self::RADIUS_METERS,
                ],
            ], 422);
        }

        $session = DB::table('sesi_presensi')
            ->join('pengaturan_presensi', 'pengaturan_presensi.id', '=', 'sesi_presensi.pengaturan_id')
            ->where('pengaturan_presensi.instansi_id', $instansi->id)
            ->where('pengaturan_presensi.aktif', true)
            ->where('sesi_presensi.tanggal', $today)
            ->where('sesi_presensi.status', 'dibuka')
            ->orderBy('sesi_presensi.id')
            ->select('sesi_presensi.*')
            ->first();

        if (! $session) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada sesi presensi yang dibuka untuk instansi Anda hari ini.',
            ], 422);
        }

        $attendance = DB::transaction(function () use ($data, $distance, $penempatan, $session, $today, $now): ?Presensi {
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
                return null;
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

            return $attendance;
        });

        if (! $attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Presensi '.$data['type'].' sudah tercatat pada sesi ini.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Presensi '.$data['type'].' berhasil disimpan.',
            'data' => [
                'distance_meters' => round($distance, 2),
                'radius_meters' => self::RADIUS_METERS,
                'presensi' => $attendance,
            ],
        ]);
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
