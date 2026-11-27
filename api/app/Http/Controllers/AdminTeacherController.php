<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\Role;
use App\Models\User;
use App\Models\PenempatanPkl;
use App\Services\AdminAccountStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminTeacherController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'nullable', Rule::in(['aktif', 'nonaktif', 'Aktif', 'Nonaktif'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $role = Role::query()->whereRaw('LOWER(nama) = ?', ['guru'])->first();

        if (! $role) {
            return $this->roleUnavailable();
        }

        $query = Guru::query()
            ->with('user')
            ->whereHas('user', fn ($userQuery) => $userQuery->where('role_id', $role->id));

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($teacherQuery) use ($search): void {
                $teacherQuery->where('nama_lengkap', 'like', '%'.$search.'%')
                    ->orWhere('nip', 'like', '%'.$search.'%')
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('username', 'like', '%'.$search.'%'));
            });
        }

        if (! empty($filters['status'])) {
            $status = strtolower($filters['status']);
            $query->whereHas('user', fn ($userQuery) => $userQuery->where('status', $status));
        }

        $teachers = $query->orderByDesc('id')->paginate((int) ($filters['per_page'] ?? 15));

        return response()->json([
            'success' => true,
            'message' => 'Daftar akun guru berhasil diambil.',
            'data' => [
                'users' => array_map(fn (Guru $teacher): array => $this->teacherData($teacher), $teachers->items()),
                'pagination' => [
                    'current_page' => $teachers->currentPage(),
                    'per_page' => $teachers->perPage(),
                    'total' => $teachers->total(),
                    'last_page' => $teachers->lastPage(),
                ],
            ],
        ]);
    }

    public function show(int $user): JsonResponse
    {
        $teacher = $this->findTeacher($user);

        if (! $teacher) {
            return $this->notFound();
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail akun guru berhasil diambil.',
            'data' => $this->teacherData($teacher),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $status = $request->input('status', 'aktif');
        if (is_string($status)) {
            $request->merge(['status' => strtolower(trim($status))]);
        }
        $email = $request->input('email');
        if (is_string($email)) {
            $request->merge(['email' => mb_strtolower(trim($email))]);
        }
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'max:30', Rule::unique('users', 'username'), Rule::unique('guru', 'nip')],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'status' => ['required', Rule::in(['aktif', 'nonaktif'])],
        ]);

        return DB::transaction(function () use ($data): JsonResponse {
            $role = Role::query()->whereRaw('LOWER(nama) = ?', ['guru'])->lockForUpdate()->first();

            if (! $role) {
                return $this->roleUnavailable();
            }

            $user = new User;
            $user->role_id = $role->id;
            $user->username = $data['username'];
            $user->email = $data['email'];
            $user->nama_tampilan = $data['nama'];
            $user->password_hash = Hash::make($data['password']);
            $user->status = $data['status'];
            $user->wajib_ganti_password = true;
            $user->save();

            $teacher = new Guru;
            $teacher->user_id = $user->id;
            $teacher->nip = $data['username'];
            $teacher->nama_lengkap = $data['nama'];
            $teacher->status_bimbingan = $data['status'];
            $teacher->save();

            return response()->json([
                'success' => true,
                'message' => 'Akun guru berhasil dibuat.',
                'data' => $this->teacherData($teacher->load('user')),
            ], 201);
        });
    }

    public function update(Request $request, int $user, AdminAccountStatusService $statusService): JsonResponse
    {
        $teacher = $this->findTeacher($user);

        if (! $teacher) {
            return $this->notFound();
        }

        $status = $request->input('status');
        if (is_string($status)) {
            $request->merge(['status' => strtolower(trim($status))]);
        }

        $pendingAccountId = $teacher->user?->status === 'pending' ? $teacher->user->id : null;
        $email = $request->input('email_akun');
        if (is_string($email)) {
            $request->merge(['email_akun' => mb_strtolower(trim($email))]);
        }
        $data = $request->validate([
            'nama' => ['sometimes', 'required', 'string', 'max:120'],
            'username' => [
                'sometimes', 'required', 'string', 'max:30',
                Rule::unique('users', 'username')->ignore($pendingAccountId ?? $teacher->user_id),
                Rule::unique('guru', 'nip')->ignore($teacher->id),
            ],
            'email_akun' => [
                'sometimes',
                Rule::prohibitedIf($pendingAccountId !== null),
                'required',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($teacher->user_id),
            ],
            'password' => $pendingAccountId === null
                ? ['sometimes', 'nullable', 'string', 'min:8', 'max:255']
                : ['prohibited'],
            'status' => $pendingAccountId === null
                ? ['sometimes', 'required', Rule::in(['aktif', 'nonaktif'])]
                : ['prohibited'],
            'reassignments' => ['sometimes', 'array'],
            'reassignments.*' => ['array:penempatan_id,guru_baru_id,alasan'],
            'reassignments.*.penempatan_id' => ['required', 'integer', 'min:1', 'distinct'],
            'reassignments.*.guru_baru_id' => ['required', 'integer', 'min:1'],
            'reassignments.*.alasan' => ['required', 'string', 'not_regex:/^\s*$/', 'max:5000'],
        ]);
        if (
            $request->has('reassignments')
            && ($data['status'] ?? null) !== 'nonaktif'
        ) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'reassignments' => 'Pengalihan hanya dapat dikirim saat menonaktifkan Guru.',
            ]);
        }

        return DB::transaction(function () use ($data, $teacher, $pendingAccountId, $request, $statusService): JsonResponse {
            if ($pendingAccountId !== null) {
                $reservation = DB::table('pending_account_verifications')
                    ->where('user_id', $pendingAccountId)
                    ->where('guru_id', $teacher->id)
                    ->whereNull('siswa_id')
                    ->lockForUpdate()
                    ->first();
                $user = User::query()->with('role')->lockForUpdate()->find($pendingAccountId);
                $lockedTeacher = Guru::query()->lockForUpdate()->find($teacher->id);

                if (
                    ! $reservation
                    || ! $user
                    || $user->status !== 'pending'
                    || $user->email_terverifikasi_at !== null
                    || $user->role?->nama !== 'guru'
                    || (int) $reservation->role_id !== (int) $user->role_id
                    || ! $lockedTeacher
                    || (
                        $lockedTeacher->user_id !== null
                        && (int) $lockedTeacher->user_id !== (int) $user->getKey()
                    )
                ) {
                    return $this->notFound();
                }

                $identityChanged = array_key_exists('username', $data)
                    && $data['username'] !== $lockedTeacher->nip;
                $nameChanged = array_key_exists('nama', $data)
                    && $data['nama'] !== $lockedTeacher->nama_lengkap;

                if (isset($data['nama'])) {
                    $lockedTeacher->nama_lengkap = $data['nama'];
                }
                if (isset($data['username'])) {
                    $lockedTeacher->nip = $data['username'];
                }
                $lockedTeacher->save();

                if ($identityChanged || $nameChanged) {
                    if ((int) $lockedTeacher->user_id === (int) $user->getKey()) {
                        $lockedTeacher->user_id = null;
                        $lockedTeacher->save();
                    }

                    DB::table('pending_account_verifications')->where('id', $reservation->id)->delete();
                    $deleted = User::query()
                        ->whereKey($user->getKey())
                        ->where('status', 'pending')
                        ->whereNull('email_terverifikasi_at')
                        ->delete();

                    if ($deleted !== 1) {
                        throw new \RuntimeException('Akun pending guru berubah saat pembatalan registrasi.');
                    }

                    $lockedTeacher->setRelation('user', null);

                    return response()->json([
                        'success' => true,
                        'message' => 'Data master guru diperbarui dan registrasi pending dibatalkan.',
                        'data' => $this->teacherData($lockedTeacher),
                    ]);
                }

                $lockedTeacher->setRelation('user', $user);

                return response()->json([
                    'success' => true,
                    'message' => 'Data master guru berhasil diperbarui.',
                    'data' => $this->teacherData($lockedTeacher),
                ]);
            }

            $credentialsChanged = false;
            if (isset($data['status'])) {
                $credentialsChanged = $teacher->user?->status !== $data['status'];
                $statusService->setTeacherStatus(
                    $request->user(),
                    (int) $teacher->user_id,
                    $data['status'],
                    $data['reassignments'] ?? []
                );
            }

            $teacher = Guru::query()->lockForUpdate()->findOrFail($teacher->id);
            $user = User::query()->lockForUpdate()->findOrFail($teacher->user_id);

            if (isset($data['nama'])) {
                $user->nama_tampilan = $data['nama'];
                $teacher->nama_lengkap = $data['nama'];
            }

            if (isset($data['username'])) {
                $user->username = $data['username'];
                $teacher->nip = $data['username'];
                $credentialsChanged = true;
            }

            if (array_key_exists('email_akun', $data)) {
                $email = mb_strtolower(trim($data['email_akun']));
                if ($user->email !== $email) {
                    $user->email = $email;
                    $user->email_terverifikasi_at = null;
                }
            }

            if (isset($data['password']) && $data['password'] !== '') {
                $user->password_hash = Hash::make($data['password']);
                $user->wajib_ganti_password = true;
                $credentialsChanged = true;
            }

            $user->save();
            $teacher->save();

            if ($credentialsChanged) {
                $user->tokens()->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Akun guru berhasil diperbarui.',
                'data' => $this->teacherData($teacher->load('user')),
            ]);
        }, attempts: 3);
    }

    public function activePlacements(int $user, AdminAccountStatusService $statusService): JsonResponse
    {
        if (! $this->findTeacher($user)) {
            return $this->notFound();
        }

        $responsibilities = $statusService->teacherResponsibilities($user);
        if (! $responsibilities) {
            return $this->notFound();
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar tanggung jawab Guru berhasil diambil.',
            'data' => [
                'penempatan' => $responsibilities['penempatan']->map(fn (PenempatanPkl $placement): array => [
                    'id' => $placement->id,
                    'status' => $placement->status,
                    'siswa' => [
                        'id' => $placement->siswa?->id,
                        'nama' => $placement->siswa?->nama_lengkap,
                        'nisn' => $placement->siswa?->nisn,
                    ],
                    'instansi' => [
                        'id' => $placement->instansi?->id,
                        'nama' => $placement->instansi?->nama,
                    ],
                    'periode' => [
                        'id' => $placement->periode?->id,
                        'nama' => $placement->periode?->nama,
                    ],
                ])->values(),
                'guru_pengganti' => $responsibilities['guru_pengganti']->map(fn (Guru $teacher): array => [
                    'id' => $teacher->id,
                    'nama' => $teacher->nama_lengkap,
                    'nip' => $teacher->nip,
                ])->values(),
            ],
        ]);
    }

    public function reassignPlacement(
        Request $request,
        int $user,
        int $placement,
        AdminAccountStatusService $statusService
    ): JsonResponse {
        $data = $request->validate([
            'guru_baru_id' => ['required', 'integer', 'min:1'],
            'alasan' => ['required', 'string', 'not_regex:/^\s*$/', 'max:5000'],
            'admin_user_id' => ['prohibited'],
            'guru_lama_id' => ['prohibited'],
            'penempatan_id' => ['prohibited'],
        ]);

        $teacher = $this->findTeacher($user);
        if (! $teacher) {
            return $this->notFound();
        }
        $placementRecord = PenempatanPkl::query()
            ->where('guru_id', $teacher->id)
            ->find($placement);
        if (! $placementRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Penempatan tidak ditemukan pada Guru tersebut.',
            ], 404);
        }

        $updated = $statusService->reassignPlacement(
            $request->user(),
            $placement,
            (int) $teacher->id,
            (int) $data['guru_baru_id'],
            $data['alasan']
        );

        return response()->json([
            'success' => true,
            'message' => 'Guru pembimbing penempatan berhasil diganti.',
            'data' => [
                'penempatan_id' => $updated->id,
                'guru_id' => $updated->guru_id,
                'riwayat_guru' => $updated->riwayatGuru->map(fn ($history): array => [
                    'guru_lama' => $history->guruLama?->nama_lengkap,
                    'guru_baru' => $history->guruBaru?->nama_lengkap,
                    'alasan' => $history->alasan,
                    'admin_user_id' => $history->admin_user_id,
                    'created_at' => $history->created_at?->toISOString(),
                ]),
            ],
        ]);
    }

    public function destroy(
        Request $request,
        int $user,
        AdminAccountStatusService $statusService
    ): JsonResponse {
        $data = $request->validate([
            'reassignments' => ['sometimes', 'array'],
            'reassignments.*' => ['array:penempatan_id,guru_baru_id,alasan'],
            'reassignments.*.penempatan_id' => ['required', 'integer', 'min:1', 'distinct'],
            'reassignments.*.guru_baru_id' => ['required', 'integer', 'min:1'],
            'reassignments.*.alasan' => ['required', 'string', 'not_regex:/^\s*$/', 'max:5000'],
        ]);

        try {
            $statusService->setTeacherStatus(
                $request->user(),
                $user,
                'nonaktif',
                $data['reassignments'] ?? []
            );
        } catch (\Illuminate\Validation\ValidationException $exception) {
            if (isset($exception->errors()['user'])) {
                return $this->notFound();
            }

            throw $exception;
        }

        return response()->json([
            'success' => true,
            'message' => 'Akun Guru berhasil dinonaktifkan.',
            'data' => null,
        ]);
    }

    private function findTeacher(int $user): ?Guru
    {
        $teacher = Guru::query()
            ->with('user')
            ->where('user_id', $user)
            ->whereHas('user.role', fn ($roleQuery) => $roleQuery->whereRaw('LOWER(nama) = ?', ['guru']))
            ->first();

        if ($teacher) {
            return $teacher;
        }

        $account = User::query()->with('role')->where('status', 'pending')->find($user);
        if (! $account || $account->role?->nama !== 'guru') {
            return null;
        }

        $reservation = DB::table('pending_account_verifications')
            ->where('user_id', $account->getKey())
            ->where('role_id', $account->role_id)
            ->whereNotNull('guru_id')
            ->whereNull('siswa_id')
            ->first();
        if (! $reservation) {
            return null;
        }

        $teacher = Guru::query()
            ->whereNull('user_id')
            ->find($reservation->guru_id);
        if ($teacher) {
            $teacher->setRelation('user', $account);
        }

        return $teacher;
    }

    private function teacherData(Guru $teacher): array
    {
        return [
            'id' => $teacher->user?->id ?? $teacher->user_id,
            'profile_id' => $teacher->id,
            'nama' => $teacher->nama_lengkap,
            'username' => $teacher->user_id === null
                ? $teacher->nip
                : ($teacher->user?->username ?? $teacher->nip),
            'email_akun' => $teacher->user?->email,
            'status' => $teacher->user?->status,
            'status_bimbingan' => $teacher->status_bimbingan,
            'terakhir_login_at' => $teacher->user?->terakhir_login_at,
        ];
    }

    private function roleUnavailable(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Role guru belum tersedia di database.',
        ], 503);
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Akun guru tidak ditemukan.',
        ], 404);
    }
}
