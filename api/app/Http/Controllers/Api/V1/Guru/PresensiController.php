<?php

namespace App\Http\Controllers\Api\V1\Guru;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\PenempatanPkl;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PresensiController extends Controller
{
    private const SESSION_NAME = 'Reguler';

    private const PLACEMENT_STATUSES = ['belum_mulai', 'aktif'];

    public function store(Request $request): JsonResponse
    {
        $guru = $this->authenticatedGuru($request);
        $data = $request->validate([
            'instansi_id' => ['required', 'integer', 'min:1'],
            'siswa_ids' => ['required', 'array', 'min:1', 'max:100'],
            'siswa_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
            'tanggal' => ['required', 'date_format:Y-m-d'],
            'jam_masuk_mulai' => ['required', 'date_format:H:i'],
            'jam_masuk_selesai' => ['required', 'date_format:H:i', 'after:jam_masuk_mulai'],
            'jam_pulang_mulai' => [
                'required',
                'date_format:H:i',
                'after:jam_masuk_selesai',
            ],
            'jam_pulang_selesai' => [
                'required',
                'date_format:H:i',
                'after:jam_pulang_mulai',
            ],
        ]);
        $date = CarbonImmutable::createFromFormat('!Y-m-d', $data['tanggal'], 'Asia/Jakarta');
        if ($date->lt(CarbonImmutable::today('Asia/Jakarta'))) {
            $this->fail('tanggal', 'Sesi presensi tidak dapat dibuat untuk tanggal lampau.');
        }

        $studentIds = array_values(array_unique(array_map('intval', $data['siswa_ids'])));
        sort($studentIds, SORT_NUMERIC);

        $session = DB::transaction(function () use ($guru, $data, $studentIds): array {
            $lockedUser = User::query()
                ->with('role')
                ->lockForUpdate()
                ->findOrFail($guru->user_id);
            $lockedGuru = Guru::query()->lockForUpdate()->findOrFail($guru->id);
            if (
                $lockedUser->role?->nama !== 'guru'
                || $lockedUser->status !== 'aktif'
                || (int) $lockedGuru->user_id !== (int) $lockedUser->id
                || $lockedGuru->status_bimbingan !== 'aktif'
            ) {
                $this->fail('guru', 'Akun Guru tidak aktif atau tidak lagi terhubung ke profil.');
            }

            $instance = DB::table('instansi')
                ->where('id', $data['instansi_id'])
                ->where('status', 'aktif')
                ->lockForUpdate()
                ->first();
            if (! $instance) {
                $this->fail('instansi_id', 'Instansi tidak ditemukan atau tidak aktif.');
            }

            $settings = DB::table('pengaturan_presensi')
                ->where('instansi_id', $instance->id)
                ->where('aktif', true)
                ->orderBy('id')
                ->lockForUpdate()
                ->first();
            if (! $settings) {
                $this->fail(
                    'instansi_id',
                    'Pengaturan presensi aktif untuk instansi ini belum dibuat oleh Admin.'
                );
            }

            $placements = PenempatanPkl::query()
                ->where('guru_id', $lockedGuru->id)
                ->where('instansi_id', $instance->id)
                ->whereIn('siswa_id', $studentIds)
                ->whereIn('status', self::PLACEMENT_STATUSES)
                ->whereDate('tanggal_mulai', '<=', $data['tanggal'])
                ->whereDate('tanggal_selesai', '>=', $data['tanggal'])
                ->whereHas('siswa', fn ($query) => $query->where('status_siswa', 'aktif'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id', 'siswa_id']);
            $placementStudentIds = $placements->pluck('siswa_id')
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->sort()
                ->values()
                ->all();
            if ($placementStudentIds !== $studentIds) {
                $this->fail(
                    'siswa_ids',
                    'Semua siswa harus memiliki penempatan aktif pada Guru, Instansi, dan tanggal yang dipilih.'
                );
            }

            $sessionId = DB::table('sesi_presensi')->insertGetId([
                'pengaturan_id' => $settings->id,
                'tanggal' => $data['tanggal'],
                'nama_shift' => self::SESSION_NAME,
                'jam_masuk_mulai' => $data['jam_masuk_mulai'],
                'jam_masuk_selesai' => $data['jam_masuk_selesai'],
                'batas_toleransi' => $data['jam_masuk_selesai'],
                'jam_pulang_mulai' => $data['jam_pulang_mulai'],
                'jam_pulang_selesai' => $data['jam_pulang_selesai'],
                'status' => 'dibuka',
                'dibuat_oleh' => $lockedUser->id,
                'created_at' => now(),
            ]);
            DB::table('sesi_presensi_penempatan')->insert(
                $placements->map(fn (PenempatanPkl $placement): array => [
                    'sesi_id' => $sessionId,
                    'penempatan_id' => $placement->id,
                ])->all()
            );

            return [
                'id' => $sessionId,
                'tanggal' => $data['tanggal'],
                'nama_shift' => self::SESSION_NAME,
                'status' => 'dibuka',
                'jumlah_siswa' => count($studentIds),
            ];
        }, attempts: 3);

        return response()->json([
            'success' => true,
            'message' => 'Sesi presensi berhasil dibuka.',
            'data' => $session,
        ], 201);
    }

    private function authenticatedGuru(Request $request): Guru
    {
        $user = $request->user()->loadMissing('role');
        $guru = Guru::query()->where('user_id', $user->id)->first();
        if (
            $user->role?->nama !== 'guru'
            || $user->status !== 'aktif'
            || ! $guru
            || $guru->status_bimbingan !== 'aktif'
        ) {
            abort(404);
        }

        return $guru;
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
