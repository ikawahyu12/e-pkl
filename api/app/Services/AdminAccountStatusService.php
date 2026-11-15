<?php

namespace App\Services;

use App\Models\Guru;
use App\Models\PenempatanPkl;
use App\Models\RiwayatPergantianGuru;
use App\Models\RiwayatStatusPenempatan;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminAccountStatusService
{
    private const ACTIVE_PLACEMENT_STATUSES = [
        PenempatanPklService::STATUS_BELUM_MULAI,
        PenempatanPklService::STATUS_AKTIF,
    ];

    public function setStudentStatus(
        User $admin,
        int $userId,
        string $status,
        ?string $reasonCode = null,
        ?string $reasonDetail = null
    ): Siswa
    {
        $this->authorizeAdmin($admin);
        $this->assertStatus($status);
        if ($status === 'nonaktif') {
            $this->validateStudentDeactivationReason($reasonCode, $reasonDetail);
        }

        return DB::transaction(function () use (
            $admin,
            $userId,
            $status,
            $reasonCode,
            $reasonDetail
        ): Siswa {
            $student = Siswa::query()->where('user_id', $userId)->lockForUpdate()->first();
            $user = User::query()->with('role')->lockForUpdate()->find($userId);

            if (! $student || ! $user || $user->role?->nama !== 'siswa') {
                $this->fail('user', 'Akun Siswa tidak ditemukan.');
            }

            if ($status === 'nonaktif') {
                $placements = PenempatanPkl::query()
                    ->where('siswa_id', $student->id)
                    ->whereIn('status', self::ACTIVE_PLACEMENT_STATUSES)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                foreach ($placements as $placement) {
                    $oldStatus = $placement->status;
                    $placement->status = PenempatanPklService::STATUS_DIBATALKAN;
                    $placement->save();

                    RiwayatStatusPenempatan::query()->create([
                        'penempatan_id' => $placement->id,
                        'status_lama' => $oldStatus,
                        'status_baru' => PenempatanPklService::STATUS_DIBATALKAN,
                        'alasan_kode' => $reasonCode,
                        'alasan_detail' => $reasonDetail,
                        'admin_user_id' => $admin->id,
                        'created_at' => now(),
                    ]);
                }
            }

            $changed = $user->status !== $status;
            $user->status = $status;
            $student->status_siswa = $status;
            $user->save();
            $student->save();

            if ($status === 'nonaktif' || $changed) {
                $user->tokens()->delete();
            }

            return $student->load(['user', 'kelas']);
        }, attempts: 3);
    }

    private function validateStudentDeactivationReason(?string $reasonCode, ?string $reasonDetail): void
    {
        if (! is_string($reasonCode) || ! array_key_exists($reasonCode, PenempatanPklService::ALASAN_PEMBATALAN)) {
            $this->fail('alasan_kode', 'Alasan pembatalan wajib dipilih dan harus valid.');
        }

        if ($reasonCode === 'lainnya' && trim((string) $reasonDetail) === '') {
            $this->fail('alasan_detail', 'Penjelasan wajib diisi untuk alasan Lainnya.');
        }
    }

    /**
     * @return array{penempatan: Collection<int, PenempatanPkl>, guru_pengganti: Collection<int, Guru>}|null
     */
    public function teacherResponsibilities(int $userId): ?array
    {
        $teacher = Guru::query()->where('user_id', $userId)->first();
        if (! $teacher) {
            return null;
        }

        return [
            'penempatan' => PenempatanPkl::query()
                ->with(['siswa', 'instansi', 'periode'])
                ->where('guru_id', $teacher->id)
                ->whereIn('status', self::ACTIVE_PLACEMENT_STATUSES)
                ->orderBy('id')
                ->get(),
            'guru_pengganti' => Guru::query()
                ->with('user')
                ->where('id', '<>', $teacher->id)
                ->where('status_bimbingan', 'aktif')
                ->whereHas('user', fn ($query) => $query
                    ->where('status', 'aktif')
                    ->whereHas('role', fn ($roleQuery) => $roleQuery->where('nama', 'guru')))
                ->orderBy('nama_lengkap')
                ->get(),
        ];
    }

    /**
     * @param array<int, array{penempatan_id: int, guru_baru_id: int, alasan: string}> $reassignments
     */
    public function setTeacherStatus(
        User $admin,
        int $userId,
        string $status,
        array $reassignments = []
    ): Guru {
        $this->authorizeAdmin($admin);
        $this->assertStatus($status);

        return DB::transaction(function () use ($admin, $userId, $status, $reassignments): Guru {
            $teacherSnapshot = Guru::query()->where('user_id', $userId)->first();
            if (! $teacherSnapshot) {
                $this->fail('user', 'Akun Guru tidak ditemukan.');
            }

            if ($status === 'nonaktif') {
                $this->deactivateTeacher($admin, $userId, $teacherSnapshot, $reassignments);
            } else {
                $teachers = $this->lockTeachers([$teacherSnapshot->id]);
                $teacher = $teachers[$teacherSnapshot->id] ?? null;
                $users = $this->lockUsersForTeachers($teachers);
                $user = $users[$userId] ?? null;

                if (! $teacher || ! $user || $user->role?->nama !== 'guru') {
                    $this->fail('user', 'Akun Guru tidak ditemukan.');
                }

                $teacher->status_bimbingan = 'aktif';
                $user->status = 'aktif';
                $teacher->save();
                $user->save();
            }

            return Guru::query()->with('user')->findOrFail($teacherSnapshot->id);
        }, attempts: 3);
    }

    public function reassignPlacement(
        User $admin,
        int $placementId,
        int $expectedOldTeacherId,
        int $newTeacherId,
        string $reason
    ): PenempatanPkl {
        $this->authorizeAdmin($admin);
        $reason = trim($reason);
        if ($reason === '') {
            $this->fail('alasan', 'Alasan pergantian Guru wajib diisi.');
        }

        return DB::transaction(function () use (
            $admin,
            $placementId,
            $expectedOldTeacherId,
            $newTeacherId,
            $reason
        ): PenempatanPkl {
            $snapshot = PenempatanPkl::query()->findOrFail($placementId);
            if ((int) $snapshot->guru_id !== $expectedOldTeacherId) {
                $this->fail('penempatan_id', 'Penempatan tidak lagi menjadi tanggung jawab Guru tersebut.');
            }
            $teachers = $this->lockTeachers([$snapshot->guru_id, $newTeacherId]);
            $users = $this->lockUsersForTeachers($teachers);
            $newTeacher = $teachers[$newTeacherId] ?? null;
            $newUser = $newTeacher ? ($users[$newTeacher->user_id] ?? null) : null;

            $this->assertEligibleReplacement($newTeacher, $newUser, $snapshot->guru_id);

            $placement = PenempatanPkl::query()->lockForUpdate()->findOrFail($placementId);
            if (
                (int) $placement->guru_id !== (int) $snapshot->guru_id
                || (int) $placement->guru_id !== $expectedOldTeacherId
                || ! in_array($placement->status, self::ACTIVE_PLACEMENT_STATUSES, true)
            ) {
                $this->fail('penempatan_id', 'Penempatan berubah atau tidak lagi aktif untuk dialihkan.');
            }

            $this->recordTeacherChange($admin, $placement, (int) $placement->guru_id, $newTeacherId, $reason);
            $placement->guru_id = $newTeacherId;
            $placement->save();

            return $placement->load(['siswa', 'periode', 'instansi', 'guru.user', 'pic', 'riwayatGuru.guruLama', 'riwayatGuru.guruBaru']);
        }, attempts: 3);
    }

    /**
     * @param array<int, array{penempatan_id: int, guru_baru_id: int, alasan: string}> $reassignments
     */
    private function deactivateTeacher(
        User $admin,
        int $userId,
        Guru $teacherSnapshot,
        array $reassignments
    ): void {
        $replacementIds = array_column($reassignments, 'guru_baru_id');
        $teachers = $this->lockTeachers(array_merge([$teacherSnapshot->id], $replacementIds));
        $users = $this->lockUsersForTeachers($teachers);
        $teacher = $teachers[$teacherSnapshot->id] ?? null;
        $user = $users[$userId] ?? null;

        if (! $teacher || ! $user || $user->role?->nama !== 'guru') {
            $this->fail('user', 'Akun Guru tidak ditemukan.');
        }

        $placements = PenempatanPkl::query()
            ->where('guru_id', $teacher->id)
            ->whereIn('status', self::ACTIVE_PLACEMENT_STATUSES)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $assignments = [];
        foreach ($reassignments as $reassignment) {
            $placementId = (int) $reassignment['penempatan_id'];
            if (isset($assignments[$placementId])) {
                $this->fail('reassignments', 'Penempatan yang sama tidak boleh dialihkan lebih dari satu kali.');
            }
            $assignments[$placementId] = $reassignment;
        }

        $placementIds = $placements->modelKeys();
        $assignmentIds = array_keys($assignments);
        sort($placementIds, SORT_NUMERIC);
        sort($assignmentIds, SORT_NUMERIC);

        if ($placementIds !== $assignmentIds) {
            $this->fail('reassignments', 'Seluruh penempatan aktif atau belum mulai harus dialihkan sebelum Guru dinonaktifkan.');
        }

        foreach ($placements as $placement) {
            $assignment = $assignments[$placement->id];
            $newTeacherId = (int) $assignment['guru_baru_id'];
            $newTeacher = $teachers[$newTeacherId] ?? null;
            $newUser = $newTeacher ? ($users[$newTeacher->user_id] ?? null) : null;
            $this->assertEligibleReplacement($newTeacher, $newUser, $teacher->id);

            $this->recordTeacherChange(
                $admin,
                $placement,
                (int) $teacher->id,
                $newTeacherId,
                trim($assignment['alasan'])
            );
            $placement->guru_id = $newTeacherId;
            $placement->save();
        }

        $remaining = PenempatanPkl::query()
            ->where('guru_id', $teacher->id)
            ->whereIn('status', self::ACTIVE_PLACEMENT_STATUSES)
            ->exists();
        if ($remaining) {
            $this->fail('reassignments', 'Masih ada penempatan aktif yang belum dialihkan.');
        }

        $teacher->status_bimbingan = 'nonaktif';
        $user->status = 'nonaktif';
        $teacher->save();
        $user->save();
        $user->tokens()->delete();
    }

    /**
     * @param array<int, int|string> $ids
     * @return array<int, Guru>
     */
    private function lockTeachers(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        sort($ids, SORT_NUMERIC);

        return Guru::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id')
            ->all();
    }

    /**
     * @param array<int, Guru> $teachers
     * @return array<int, User>
     */
    private function lockUsersForTeachers(array $teachers): array
    {
        $userIds = array_values(array_unique(array_map(
            static fn (Guru $teacher): int => (int) $teacher->user_id,
            $teachers
        )));
        sort($userIds, SORT_NUMERIC);

        return User::query()
            ->with('role')
            ->whereIn('id', $userIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id')
            ->all();
    }

    private function assertEligibleReplacement(?Guru $teacher, ?User $user, int $oldTeacherId): void
    {
        if (
            ! $teacher
            || (int) $teacher->id === $oldTeacherId
            || $teacher->status_bimbingan !== 'aktif'
            || ! $user
            || $user->status !== 'aktif'
            || $user->role?->nama !== 'guru'
        ) {
            $this->fail('guru_baru_id', 'Guru pengganti harus memiliki akun aktif dan status bimbingan aktif.');
        }
    }

    private function recordTeacherChange(
        User $admin,
        PenempatanPkl $placement,
        int $oldTeacherId,
        int $newTeacherId,
        string $reason
    ): void {
        if ($reason === '') {
            $this->fail('alasan', 'Alasan pergantian Guru wajib diisi.');
        }

        RiwayatPergantianGuru::query()->create([
            'penempatan_id' => $placement->id,
            'guru_lama_id' => $oldTeacherId,
            'guru_baru_id' => $newTeacherId,
            'admin_user_id' => $admin->id,
            'alasan' => $reason,
            'created_at' => now(),
        ]);
    }

    private function assertStatus(string $status): void
    {
        if (! in_array($status, ['aktif', 'nonaktif'], true)) {
            $this->fail('status', 'Status akun harus aktif atau nonaktif.');
        }
    }

    private function authorizeAdmin(User $admin): void
    {
        $admin->loadMissing('role');
        if ($admin->role?->nama !== 'admin' || $admin->status !== 'aktif') {
            $this->fail('admin', 'Hanya Admin aktif yang dapat mengubah status akun.');
        }
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
