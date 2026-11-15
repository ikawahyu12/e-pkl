<?php

namespace App\Services;

use App\Models\Guru;
use App\Models\Instansi;
use App\Models\KuotaInstansiPeriode;
use App\Models\PenempatanPkl;
use App\Models\PeriodePkl;
use App\Models\PicInstansi;
use App\Models\RiwayatStatusPenempatan;
use App\Models\Siswa;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PenempatanPklService
{
    public const STATUS_BELUM_MULAI = 'belum_mulai';
    public const STATUS_AKTIF = 'aktif';
    public const STATUS_SELESAI = 'selesai';
    public const STATUS_DIBATALKAN = 'dibatalkan';

    /**
     * Keys are persisted as alasan_kode; values are labels used by the Admin UI.
     */
    public const ALASAN_PEMBATALAN = [
        'siswa_mengundurkan_diri' => 'Siswa mengundurkan diri.',
        'perubahan_penempatan_dari_sekolah' => 'Perubahan penempatan dari sekolah.',
        'instansi_tidak_dapat_menerima_siswa' => 'Instansi tidak dapat menerima siswa.',
        'ketidaksesuaian_data_penempatan' => 'Ketidaksesuaian data penempatan.',
        'pelanggaran_atau_ketidakdisiplinan' => 'Pelanggaran atau ketidakdisiplinan.',
        'kondisi_operasional_tertentu' => 'Kondisi operasional tertentu.',
        'lainnya' => 'Lainnya.',
    ];

    /**
     * @param array{siswa_id: int|string, periode_id: int|string, instansi_id: int|string, guru_id: int|string, pic_instansi_id?: int|string|null, tanggal_mulai: string, tanggal_selesai: string} $data
     */
    public function create(User $admin, array $data): PenempatanPkl
    {
        $this->authorizeAdmin($admin);
        $data = $this->validatePlacementData($data);

        return DB::transaction(function () use ($admin, $data): PenempatanPkl {
            $siswa = Siswa::query()->lockForUpdate()->find($data['siswa_id']);
            if (! $siswa || $siswa->status_siswa !== 'aktif') {
                $this->fail('siswa_id', 'Siswa tidak ditemukan atau tidak aktif.');
            }
            $this->lockActiveSiswaAccount($siswa);

            $periode = PeriodePkl::query()->lockForUpdate()->find($data['periode_id']);
            if (! $periode || ! in_array($periode->status, ['persiapan', 'berjalan'], true)) {
                $this->fail('periode_id', 'Periode PKL tidak ditemukan atau tidak menerima penempatan baru.');
            }

            $instansi = $this->lockActiveInstansi($data['instansi_id']);
            $this->lockActiveGuru($data['guru_id']);
            $this->lockPic($data['pic_instansi_id'] ?? null, $instansi->id);

            $status = $this->initialStatus($data['tanggal_mulai'], $data['tanggal_selesai']);
            $this->assertDatesWithinPeriod($data, $periode);

            if ($this->usesQuota($status)) {
                $this->assertNoActivePlacement($siswa->id, $periode->id);
                $this->assertQuotaAvailable($instansi->id, $periode->id);
            }

            $placement = $this->newPlacement($data, $status);
            $this->recordStatus($placement, null, $status, 'penempatan_dibuat', null, $admin);

            return $placement;
        }, 3);
    }

    public function cancel(
        User $admin,
        int|PenempatanPkl $placement,
        string $reasonCode,
        ?string $reasonDetail = null
    ): PenempatanPkl {
        $this->authorizeAdmin($admin);
        $this->validateCancellationReason($reasonCode, $reasonDetail);
        $snapshot = $this->placementSnapshot($placement);

        return DB::transaction(function () use ($admin, $snapshot, $reasonCode, $reasonDetail): PenempatanPkl {
            $this->lockSiswa($snapshot->siswa_id);
            $this->lockPeriode($snapshot->periode_id);
            $this->lockInstansis([$snapshot->instansi_id]);
            $locked = $this->lockPlacement($snapshot);

            if (! $this->usesQuota($locked->status)) {
                $this->fail('penempatan', 'Hanya penempatan yang belum dimulai atau aktif yang dapat dibatalkan.');
            }

            $oldStatus = $locked->status;
            $locked->status = self::STATUS_DIBATALKAN;
            $locked->save();
            $this->recordStatus($locked, $oldStatus, self::STATUS_DIBATALKAN, $reasonCode, $reasonDetail, $admin);

            return $locked;
        }, 3);
    }

    public function reactivate(
        User $admin,
        int|PenempatanPkl $placement,
        string $reason
    ): PenempatanPkl {
        $this->authorizeAdmin($admin);
        $reason = trim($reason);
        if ($reason === '') {
            $this->fail('alasan', 'Alasan reaktivasi wajib diisi.');
        }

        $snapshot = $this->placementSnapshot($placement);

        return DB::transaction(function () use ($admin, $snapshot, $reason): PenempatanPkl {
            $siswa = $this->lockSiswa($snapshot->siswa_id);
            $periode = $this->lockPeriode($snapshot->periode_id);
            $instansi = $this->lockActiveInstansi($snapshot->instansi_id);
            $this->lockActiveGuru($snapshot->guru_id);
            $this->lockPic($snapshot->pic_instansi_id, $instansi->id);
            $locked = $this->lockPlacement($snapshot);

            if ($locked->status !== self::STATUS_DIBATALKAN) {
                $this->fail('penempatan', 'Hanya penempatan yang dibatalkan yang dapat diaktifkan kembali.');
            }

            if ($siswa->status_siswa !== 'aktif') {
                $this->fail('siswa_id', 'Siswa tidak ditemukan atau tidak aktif.');
            }
            $this->lockActiveSiswaAccount($siswa);

            if (! in_array($periode->status, ['persiapan', 'berjalan'], true)) {
                $this->fail('periode_id', 'Periode PKL tidak menerima penempatan aktif.');
            }

            $this->assertDatesWithinPeriod([
                'tanggal_mulai' => CarbonImmutable::parse($locked->tanggal_mulai)->toDateString(),
                'tanggal_selesai' => CarbonImmutable::parse($locked->tanggal_selesai)->toDateString(),
            ], $periode);

            $today = CarbonImmutable::today();
            $start = CarbonImmutable::parse($locked->tanggal_mulai)->startOfDay();
            $end = CarbonImmutable::parse($locked->tanggal_selesai)->startOfDay();
            if ($today->gt($end)) {
                $this->fail('penempatan', 'Penempatan tidak dapat diaktifkan kembali setelah tanggal selesai.');
            }

            $newStatus = $today->lt($start) ? self::STATUS_BELUM_MULAI : self::STATUS_AKTIF;
            $this->assertNoActivePlacement($siswa->id, $periode->id, $locked->id);
            $this->assertQuotaAvailable($instansi->id, $periode->id);

            $oldStatus = $locked->status;
            $locked->status = $newStatus;
            $locked->save();
            $this->recordStatus($locked, $oldStatus, $newStatus, 'reaktivasi', $reason, $admin);

            return $locked;
        }, 3);
    }

    /**
     * The old placement is canceled and the replacement is created atomically.
     *
     * @param array{instansi_id: int|string, guru_id: int|string, pic_instansi_id?: int|string|null, tanggal_mulai: string, tanggal_selesai: string} $newPlacement
     */
    public function move(
        User $admin,
        int|PenempatanPkl $placement,
        array $newPlacement,
        string $reasonCode,
        ?string $reasonDetail = null
    ): PenempatanPkl {
        $this->authorizeAdmin($admin);
        $this->validateCancellationReason($reasonCode, $reasonDetail);
        $snapshot = $this->placementSnapshot($placement);
        $data = $this->validatePlacementData(array_merge($newPlacement, [
            'siswa_id' => $snapshot->siswa_id,
            'periode_id' => $snapshot->periode_id,
        ]));

        if ((int) $data['instansi_id'] === (int) $snapshot->instansi_id) {
            $this->fail('instansi_id', 'Instansi tujuan harus berbeda dari instansi penempatan saat ini.');
        }

        return DB::transaction(function () use ($admin, $snapshot, $data, $reasonCode, $reasonDetail): PenempatanPkl {
            $siswa = $this->lockSiswa($snapshot->siswa_id);
            $periode = $this->lockPeriode($snapshot->periode_id);
            $instansis = $this->lockInstansis([$snapshot->instansi_id, $data['instansi_id']]);
            $targetInstansi = $instansis[$data['instansi_id']];
            if ($targetInstansi->status !== 'aktif') {
                $this->fail('instansi_id', 'Instansi tujuan tidak ditemukan atau tidak aktif.');
            }
            $this->lockActiveGuru($data['guru_id']);
            $this->lockPic($data['pic_instansi_id'] ?? null, $targetInstansi->id);
            $locked = $this->lockPlacement($snapshot);

            if (! $this->usesQuota($locked->status)) {
                $this->fail('penempatan', 'Hanya penempatan yang belum dimulai atau aktif yang dapat dipindahkan.');
            }
            if ($siswa->status_siswa !== 'aktif') {
                $this->fail('siswa_id', 'Siswa tidak aktif.');
            }
            $this->lockActiveSiswaAccount($siswa);
            if (! in_array($periode->status, ['persiapan', 'berjalan'], true)) {
                $this->fail('periode_id', 'Periode PKL tidak menerima penempatan baru.');
            }

            $this->assertDatesWithinPeriod($data, $periode);
            $newStatus = $this->initialStatus($data['tanggal_mulai'], $data['tanggal_selesai']);
            if ($this->usesQuota($newStatus)) {
                $this->assertNoActivePlacement($siswa->id, $periode->id, $locked->id);
            }

            $oldStatus = $locked->status;
            $locked->status = self::STATUS_DIBATALKAN;
            $locked->save();
            $this->recordStatus($locked, $oldStatus, self::STATUS_DIBATALKAN, $reasonCode, $reasonDetail, $admin);

            if ($this->usesQuota($newStatus)) {
                $this->assertQuotaAvailable($targetInstansi->id, $periode->id);
            }

            $replacement = $this->newPlacement($data, $newStatus);
            $this->recordStatus($replacement, null, $newStatus, 'penempatan_dibuat', null, $admin);

            return $replacement;
        }, 3);
    }

    /**
     * @param array<string, mixed> $data
     * @return array{siswa_id: int, periode_id: int, instansi_id: int, guru_id: int, pic_instansi_id: int|null, tanggal_mulai: string, tanggal_selesai: string}
     */
    private function validatePlacementData(array $data): array
    {
        $validated = Validator::make($data, [
            'siswa_id' => ['required', 'integer', 'min:1'],
            'periode_id' => ['required', 'integer', 'min:1'],
            'instansi_id' => ['required', 'integer', 'min:1'],
            'guru_id' => ['required', 'integer', 'min:1'],
            'pic_instansi_id' => ['nullable', 'integer', 'min:1'],
            'tanggal_mulai' => ['required', 'date_format:Y-m-d'],
            'tanggal_selesai' => ['required', 'date_format:Y-m-d'],
        ])->validate();

        if ($validated['tanggal_mulai'] > $validated['tanggal_selesai']) {
            $this->fail('tanggal_selesai', 'Tanggal selesai harus sama atau setelah tanggal mulai.');
        }

        return [
            'siswa_id' => (int) $validated['siswa_id'],
            'periode_id' => (int) $validated['periode_id'],
            'instansi_id' => (int) $validated['instansi_id'],
            'guru_id' => (int) $validated['guru_id'],
            'pic_instansi_id' => isset($validated['pic_instansi_id']) ? (int) $validated['pic_instansi_id'] : null,
            'tanggal_mulai' => $validated['tanggal_mulai'],
            'tanggal_selesai' => $validated['tanggal_selesai'],
        ];
    }

    private function validateCancellationReason(string $reasonCode, ?string $reasonDetail): void
    {
        if (! array_key_exists($reasonCode, self::ALASAN_PEMBATALAN)) {
            $this->fail('alasan_kode', 'Alasan pembatalan tidak valid.');
        }

        if ($reasonCode === 'lainnya' && trim((string) $reasonDetail) === '') {
            $this->fail('alasan_detail', 'Penjelasan wajib diisi untuk alasan Lainnya.');
        }
    }

    private function authorizeAdmin(User $admin): void
    {
        $admin->loadMissing('role');
        if ($admin->role?->nama !== 'admin' || $admin->status !== 'aktif') {
            $this->fail('admin', 'Hanya Admin aktif yang dapat mengelola penempatan PKL.');
        }
    }

    private function lockSiswa(int $id): Siswa
    {
        $siswa = Siswa::query()->lockForUpdate()->find($id);
        if (! $siswa) {
            $this->fail('siswa_id', 'Siswa tidak ditemukan.');
        }

        return $siswa;
    }

    private function lockPeriode(int $id): PeriodePkl
    {
        $periode = PeriodePkl::query()->lockForUpdate()->find($id);
        if (! $periode) {
            $this->fail('periode_id', 'Periode PKL tidak ditemukan.');
        }

        return $periode;
    }

    private function lockActiveInstansi(int $id): Instansi
    {
        $instansi = Instansi::query()->lockForUpdate()->find($id);
        if (! $instansi || $instansi->status !== 'aktif') {
            $this->fail('instansi_id', 'Instansi tidak ditemukan atau tidak aktif.');
        }

        return $instansi;
    }

    private function lockActiveGuru(int $id): Guru
    {
        $guru = Guru::query()->lockForUpdate()->find($id);
        if (! $guru || $guru->status_bimbingan !== 'aktif') {
            $this->fail('guru_id', 'Guru tidak ditemukan atau tidak aktif.');
        }

        $user = User::query()->with('role')->lockForUpdate()->find($guru->user_id);
        if (! $user || $user->status !== 'aktif' || $user->role?->nama !== 'guru') {
            $this->fail('guru_id', 'Akun Guru tidak ditemukan atau tidak aktif.');
        }

        return $guru;
    }

    private function lockActiveSiswaAccount(Siswa $siswa): User
    {
        $user = User::query()->with('role')->lockForUpdate()->find($siswa->user_id);
        if (! $user || $user->status !== 'aktif' || $user->role?->nama !== 'siswa') {
            $this->fail('siswa_id', 'Akun Siswa tidak ditemukan atau tidak aktif.');
        }

        return $user;
    }

    private function lockPic(?int $id, int $instansiId): ?PicInstansi
    {
        if ($id === null) {
            return null;
        }

        $pic = PicInstansi::query()->lockForUpdate()->find($id);
        if (! $pic || ! $pic->aktif || (int) $pic->instansi_id !== $instansiId) {
            $this->fail('pic_instansi_id', 'PIC harus aktif dan berasal dari instansi yang dipilih.');
        }

        return $pic;
    }

    /**
     * @param array<int, int|string> $ids
     * @return array<int, Instansi>
     */
    private function lockInstansis(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        sort($ids, SORT_NUMERIC);

        $instansis = [];
        foreach ($ids as $id) {
            $instansis[$id] = Instansi::query()->lockForUpdate()->find($id);
            if (! $instansis[$id]) {
                $this->fail('instansi_id', 'Instansi tidak ditemukan.');
            }
        }

        return $instansis;
    }

    private function assertDatesWithinPeriod(array $data, PeriodePkl $periode): void
    {
        $start = CarbonImmutable::createFromFormat('!Y-m-d', $data['tanggal_mulai']);
        $end = CarbonImmutable::createFromFormat('!Y-m-d', $data['tanggal_selesai']);
        $periodStart = CarbonImmutable::parse($periode->tanggal_mulai)->startOfDay();
        $periodEnd = CarbonImmutable::parse($periode->tanggal_selesai)->startOfDay();

        if ($start->lt($periodStart) || $end->gt($periodEnd)) {
            $this->fail('tanggal_mulai', 'Tanggal penempatan harus berada dalam rentang periode PKL.');
        }
    }

    private function assertNoActivePlacement(int $siswaId, int $periodeId, ?int $exceptId = null): void
    {
        $query = PenempatanPkl::query()
            ->where('siswa_id', $siswaId)
            ->where('periode_id', $periodeId)
            ->whereIn('status', [self::STATUS_BELUM_MULAI, self::STATUS_AKTIF]);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        if ($query->exists()) {
            $this->fail('siswa_id', 'Siswa sudah memiliki penempatan aktif dalam periode PKL ini.');
        }
    }

    private function assertQuotaAvailable(int $instansiId, int $periodeId): void
    {
        $quota = KuotaInstansiPeriode::query()
            ->where('instansi_id', $instansiId)
            ->where('periode_id', $periodeId)
            ->lockForUpdate()
            ->first();

        if ($quota?->kuota_siswa === null) {
            return;
        }

        $used = PenempatanPkl::query()
            ->where('instansi_id', $instansiId)
            ->where('periode_id', $periodeId)
            ->whereIn('status', [self::STATUS_BELUM_MULAI, self::STATUS_AKTIF])
            ->lockForUpdate()
            ->get(['id'])
            ->count();

        if ($used >= (int) $quota->kuota_siswa) {
            $this->fail('instansi_id', 'Kuota instansi untuk periode PKL ini sudah penuh.');
        }
    }

    private function initialStatus(string $startDate, string $endDate): string
    {
        $today = CarbonImmutable::today();
        if ($today->lt(CarbonImmutable::createFromFormat('!Y-m-d', $startDate))) {
            return self::STATUS_BELUM_MULAI;
        }
        if ($today->lte(CarbonImmutable::createFromFormat('!Y-m-d', $endDate))) {
            return self::STATUS_AKTIF;
        }

        return self::STATUS_SELESAI;
    }

    private function usesQuota(string $status): bool
    {
        return in_array($status, [self::STATUS_BELUM_MULAI, self::STATUS_AKTIF], true);
    }

    private function placementSnapshot(int|PenempatanPkl $placement): PenempatanPkl
    {
        if ($placement instanceof PenempatanPkl) {
            return PenempatanPkl::query()->findOrFail($placement->id);
        }

        return PenempatanPkl::query()->findOrFail($placement);
    }

    private function lockPlacement(PenempatanPkl $snapshot): PenempatanPkl
    {
        $locked = PenempatanPkl::query()->lockForUpdate()->findOrFail($snapshot->id);
        if (
            (int) $locked->siswa_id !== (int) $snapshot->siswa_id
            || (int) $locked->periode_id !== (int) $snapshot->periode_id
            || (int) $locked->instansi_id !== (int) $snapshot->instansi_id
            || (int) $locked->guru_id !== (int) $snapshot->guru_id
            || $this->nullableId($locked->pic_instansi_id) !== $this->nullableId($snapshot->pic_instansi_id)
            || CarbonImmutable::parse($locked->tanggal_mulai)->toDateString()
                !== CarbonImmutable::parse($snapshot->tanggal_mulai)->toDateString()
            || CarbonImmutable::parse($locked->tanggal_selesai)->toDateString()
                !== CarbonImmutable::parse($snapshot->tanggal_selesai)->toDateString()
        ) {
            $this->fail('penempatan', 'Data penempatan berubah. Muat ulang sebelum mencoba kembali.');
        }

        return $locked;
    }

    private function nullableId(mixed $id): ?int
    {
        return $id === null ? null : (int) $id;
    }

    private function newPlacement(array $data, string $status): PenempatanPkl
    {
        return PenempatanPkl::query()->forceCreate([
            'siswa_id' => $data['siswa_id'],
            'periode_id' => $data['periode_id'],
            'instansi_id' => $data['instansi_id'],
            'guru_id' => $data['guru_id'],
            'pic_instansi_id' => $data['pic_instansi_id'],
            'tanggal_mulai' => $data['tanggal_mulai'],
            'tanggal_selesai' => $data['tanggal_selesai'],
            'status' => $status,
        ]);
    }

    private function recordStatus(
        PenempatanPkl $placement,
        ?string $oldStatus,
        string $newStatus,
        string $reasonCode,
        ?string $reasonDetail,
        User $admin
    ): void {
        RiwayatStatusPenempatan::query()->create([
            'penempatan_id' => $placement->id,
            'status_lama' => $oldStatus,
            'status_baru' => $newStatus,
            'alasan_kode' => $reasonCode,
            'alasan_detail' => $reasonDetail,
            'admin_user_id' => $admin->id,
            'created_at' => now(),
        ]);
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
