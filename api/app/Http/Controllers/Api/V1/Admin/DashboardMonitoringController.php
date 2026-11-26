<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardMonitoringController extends Controller
{
    public function index(): JsonResponse
    {
        $now = CarbonImmutable::now('Asia/Jakarta');
        $today = $now->toDateString();

        $totalStudents = DB::table('siswa as s')
            ->join('users as u', 'u.id', '=', 's.user_id')
            ->join('roles as r', 'r.id', '=', 'u.role_id')
            ->where('s.status_siswa', 'aktif')
            ->where('u.status', 'aktif')
            ->whereRaw('LOWER(r.nama) = ?', ['siswa'])
            ->count('s.id');

        $activePlacementIds = DB::table('penempatan_pkl as current')
            ->select('current.id')
            ->where('current.status', 'aktif')
            ->whereDate('current.tanggal_mulai', '<=', $today)
            ->whereDate('current.tanggal_selesai', '>=', $today)
            ->whereNotExists(function ($query) use ($today): void {
                $query->selectRaw('1')
                    ->from('penempatan_pkl as newer')
                    ->whereColumn('newer.siswa_id', 'current.siswa_id')
                    ->where('newer.status', 'aktif')
                    ->whereDate('newer.tanggal_mulai', '<=', $today)
                    ->whereDate('newer.tanggal_selesai', '>=', $today)
                    ->where(function ($query): void {
                        $query->whereColumn('newer.tanggal_mulai', '>', 'current.tanggal_mulai')
                            ->orWhere(function ($query): void {
                                $query->whereColumn('newer.tanggal_mulai', '=', 'current.tanggal_mulai')
                                    ->whereColumn('newer.id', '>', 'current.id');
                            });
                    });
            });

        $attendanceByPlacement = DB::table('presensi')
            ->select('penempatan_id')
            ->selectRaw("MAX(CASE WHEN status_harian = 'hadir' THEN 1 ELSE 0 END) AS hadir")
            ->selectRaw("MAX(CASE WHEN status_harian = 'izin' THEN 1 ELSE 0 END) AS izin")
            ->selectRaw("MAX(CASE WHEN status_harian = 'sakit' THEN 1 ELSE 0 END) AS sakit")
            ->selectRaw("MAX(CASE WHEN status_harian = 'alpha' THEN 1 ELSE 0 END) AS alpha")
            ->selectRaw("MIN(CASE WHEN status_harian = 'hadir' THEN waktu_masuk END) AS jam_masuk")
            ->where('tanggal', $today)
            ->groupBy('penempatan_id')
            ->get()
            ->keyBy('penempatan_id');

        $leaveByPlacement = DB::table('pengajuan_izin')
            ->select('penempatan_id')
            ->selectRaw("MAX(CASE WHEN jenis = 'izin' THEN 1 ELSE 0 END) AS izin")
            ->selectRaw("MAX(CASE WHEN jenis = 'sakit' THEN 1 ELSE 0 END) AS sakit")
            ->whereIn('status', ['menunggu', 'disetujui'])
            ->whereDate('tanggal_mulai', '<=', $today)
            ->whereDate('tanggal_selesai', '>=', $today)
            ->groupBy('penempatan_id')
            ->get()
            ->keyBy('penempatan_id');

        $placements = DB::table('penempatan_pkl as pp')
            ->join('siswa as s', 's.id', '=', 'pp.siswa_id')
            ->join('users as u', 'u.id', '=', 's.user_id')
            ->join('roles as r', 'r.id', '=', 'u.role_id')
            ->join('guru as g', 'g.id', '=', 'pp.guru_id')
            ->join('instansi as i', 'i.id', '=', 'pp.instansi_id')
            ->whereIn('pp.id', $activePlacementIds)
            ->where('s.status_siswa', 'aktif')
            ->where('u.status', 'aktif')
            ->whereRaw('LOWER(r.nama) = ?', ['siswa'])
            ->orderBy('s.nama_lengkap')
            ->get([
                'pp.id as penempatan_id',
                'pp.instansi_id',
                'g.user_id as guru_user_id',
                's.nama_lengkap as nama',
                's.nis',
                'i.nama as instansi',
            ]);

        $sessionsByInstance = $this->sessionsByInstance(
            $today,
            $placements->pluck('instansi_id')->unique()->values()
        );

        $rows = $placements->map(function (object $placement) use (
            $attendanceByPlacement,
            $leaveByPlacement,
            $sessionsByInstance
        ): array {
            $placementId = (int) $placement->penempatan_id;
            $attendance = $attendanceByPlacement->get($placementId);
            $leave = $leaveByPlacement->get($placementId);
            $isPresent = (int) ($attendance->hadir ?? 0) === 1;
            $hasLeave = (int) ($attendance->izin ?? 0) === 1
                || (int) ($leave->izin ?? 0) === 1;
            $hasSickLeave = (int) ($attendance->sakit ?? 0) === 1
                || (int) ($leave->sakit ?? 0) === 1;
            $isExplicitlyAbsent = (int) ($attendance->alpha ?? 0) === 1;
            $sessionState = $this->sessionStateForPlacement(
                $sessionsByInstance->get((int) $placement->instansi_id, collect()),
                $placementId,
                (int) $placement->guru_user_id
            );

            if ($isPresent) {
                $status = 'Hadir';
            } elseif ($hasLeave) {
                $status = 'Izin';
            } elseif ($hasSickLeave) {
                $status = 'Sakit';
            } elseif ($isExplicitlyAbsent || ($sessionState['closed'] && ! $sessionState['open'])) {
                $status = 'Tidak Hadir';
            } else {
                $status = 'Belum Presensi';
            }

            $time = $attendance->jam_masuk ?? null;

            return [
                'name' => (string) $placement->nama,
                'nis' => (string) ($placement->nis ?? ''),
                'company' => (string) $placement->instansi,
                'time' => is_string($time) && $time !== ''
                    ? CarbonImmutable::parse($time, 'Asia/Jakarta')->format('H:i')
                    : '—',
                'status' => $status,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'tanggal' => $today,
                'generated_at' => $now->toIso8601String(),
                'statistics' => [
                    'total_siswa' => (int) $totalStudents,
                    'hadir' => $rows->where('status', 'Hadir')->count(),
                    'izin' => $rows->where('status', 'Izin')->count(),
                    'tidak_hadir' => $rows->where('status', 'Tidak Hadir')->count(),
                ],
                'rows' => $rows->values(),
            ],
        ])->header('Cache-Control', 'no-store');
    }

    /**
     * @param Collection<int, int> $instanceIds
     * @return Collection<int, Collection<int, array{status: string, creator_id: int|null, placements: array<int, int>}>>
     */
    private function sessionsByInstance(string $today, Collection $instanceIds): Collection
    {
        if ($instanceIds->isEmpty()) {
            return collect();
        }

        $sessions = DB::table('sesi_presensi as sp')
            ->join('pengaturan_presensi as pengaturan', 'pengaturan.id', '=', 'sp.pengaturan_id')
            ->leftJoin('sesi_presensi_penempatan as assigned', 'assigned.sesi_id', '=', 'sp.id')
            ->where('sp.tanggal', $today)
            ->where('pengaturan.aktif', true)
            ->whereIn('pengaturan.instansi_id', $instanceIds)
            ->orderBy('sp.id')
            ->get([
                'sp.id',
                'sp.status',
                'sp.dibuat_oleh',
                'pengaturan.instansi_id',
                'assigned.penempatan_id',
            ]);

        $groupedSessions = $sessions
            ->groupBy('id')
            ->map(function (Collection $sessionRows): array {
                return [
                    'instance_id' => (int) $sessionRows->first()->instansi_id,
                    'status' => (string) $sessionRows->first()->status,
                    'creator_id' => $sessionRows->first()->dibuat_oleh === null
                        ? null
                        : (int) $sessionRows->first()->dibuat_oleh,
                    'placements' => $sessionRows
                        ->pluck('penempatan_id')
                        ->filter(fn ($id): bool => $id !== null)
                        ->map(fn ($id): int => (int) $id)
                        ->all(),
                ];
            });

        return $groupedSessions
            ->groupBy('instance_id')
            ->map(fn (Collection $items): Collection => $items
                ->map(fn (array $session): array => [
                    'status' => $session['status'],
                    'creator_id' => $session['creator_id'],
                    'placements' => $session['placements'],
                ]));
    }

    /**
     * @param Collection<int, array{status: string, creator_id: int|null, placements: array<int, int>}> $sessions
     * @return array{closed: bool, open: bool}
     */
    private function sessionStateForPlacement(Collection $sessions, int $placementId, int $teacherUserId): array
    {
        $closed = false;
        $open = false;

        foreach ($sessions as $session) {
            if ($session['placements'] !== []) {
                if (!in_array($placementId, $session['placements'], true)) {
                    continue;
                }
            } elseif ($session['creator_id'] !== $teacherUserId) {
                continue;
            }

            $closed = $closed || $session['status'] === 'ditutup';
            $open = $open || $session['status'] === 'dibuka';
        }

        return ['closed' => $closed, 'open' => $open];
    }
}
