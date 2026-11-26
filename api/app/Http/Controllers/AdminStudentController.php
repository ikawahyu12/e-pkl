<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use App\Services\AdminAccountStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminStudentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'nullable', Rule::in(['aktif', 'nonaktif', 'Aktif', 'Nonaktif'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $role = Role::query()->whereRaw('LOWER(nama) = ?', ['siswa'])->first();

        if (! $role) {
            return $this->roleUnavailable();
        }

        $query = Siswa::query()
            ->with(['user', 'kelas'])
            ->whereHas('user', fn ($userQuery) => $userQuery->where('role_id', $role->id));

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($studentQuery) use ($search): void {
                $studentQuery->where('nama_lengkap', 'like', '%'.$search.'%')
                    ->orWhere('nisn', 'like', '%'.$search.'%')
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('username', 'like', '%'.$search.'%'));
            });
        }

        if (! empty($filters['status'])) {
            $status = strtolower($filters['status']);
            $query->whereHas('user', fn ($userQuery) => $userQuery->where('status', $status));
        }

        $students = $query->orderByDesc('id')->paginate((int) ($filters['per_page'] ?? 15));

        return response()->json([
            'success' => true,
            'message' => 'Daftar akun siswa berhasil diambil.',
            'data' => [
                'users' => array_map(fn (Siswa $student): array => $this->studentData($student), $students->items()),
                'pagination' => [
                    'current_page' => $students->currentPage(),
                    'per_page' => $students->perPage(),
                    'total' => $students->total(),
                    'last_page' => $students->lastPage(),
                ],
                'classes' => Kelas::query()->orderBy('nama')->get(['id', 'nama']),
            ],
        ]);
    }

    public function classes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Kelas::query()->orderBy('nama')->get(['id', 'nama']),
        ]);
    }

    public function show(int $user): JsonResponse
    {
        $student = $this->findStudent($user);

        if (! $student) {
            return $this->notFound();
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail akun siswa berhasil diambil.',
            'data' => $this->studentData($student, true),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $status = $request->input('status', 'aktif');
        if (is_string($status)) {
            $request->merge(['status' => strtolower(trim($status))]);
        }
        $classIsRequired = Kelas::query()->exists();
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'max:20', Rule::unique('users', 'username'), Rule::unique('siswa', 'nisn')],
            'nisn' => ['sometimes', 'required', 'string', 'max:20', Rule::unique('siswa', 'nisn')],
            'nis' => ['sometimes', 'nullable', 'string', 'max:30', Rule::unique('siswa', 'nis')],
            'email' => ['sometimes', 'nullable', 'email', 'max:150', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'kelas_id' => [$classIsRequired ? 'required' : 'nullable', 'integer', Rule::exists('kelas', 'id')],
            'jenis_kelamin' => ['sometimes', 'nullable', Rule::in(['L', 'P'])],
            'no_hp' => ['sometimes', 'nullable', 'string', 'max:25'],
            'alamat' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'nama_orang_tua' => ['sometimes', 'nullable', 'string', 'max:120'],
            'no_hp_orang_tua' => ['sometimes', 'nullable', 'string', 'max:25'],
            'status' => ['required', Rule::in(['aktif', 'nonaktif'])],
        ]);

        return DB::transaction(function () use ($data): JsonResponse {
            $role = Role::query()->whereRaw('LOWER(nama) = ?', ['siswa'])->lockForUpdate()->first();

            if (! $role) {
                return $this->roleUnavailable();
            }

            $user = new User;
            $user->role_id = $role->id;
            $user->username = $data['username'];
            $user->email = $data['email'] ?? null;
            $user->nama_tampilan = $data['nama'];
            $user->password_hash = Hash::make($data['password']);
            $user->status = $data['status'];
            $user->wajib_ganti_password = true;
            $user->save();

            $student = new Siswa;
            $student->user_id = $user->id;
            $student->nisn = $data['nisn'] ?? $data['username'];
            $student->nis = $data['nis'] ?? null;
            $student->nama_lengkap = $data['nama'];
            $student->kelas_id = $data['kelas_id'] ?? null;
            $student->jenis_kelamin = $data['jenis_kelamin'] ?? null;
            $student->no_hp = $data['no_hp'] ?? null;
            $student->alamat = $data['alamat'] ?? null;
            $student->nama_orang_tua = $data['nama_orang_tua'] ?? null;
            $student->no_hp_orang_tua = $data['no_hp_orang_tua'] ?? null;
            $student->status_siswa = $data['status'];
            $student->save();

            return response()->json([
                'success' => true,
                'message' => 'Akun siswa berhasil dibuat.',
                'data' => $this->studentData($student->load(['user', 'kelas']), true),
            ], 201);
        });
    }

    public function update(Request $request, int $user): JsonResponse
    {
        $student = $this->findStudent($user);

        if (! $student) {
            return $this->notFound();
        }

        $pendingAccountId = $student->user?->status === 'pending' ? $student->user->id : null;
        $data = $request->validate([
            'nama' => ['sometimes', 'required', 'string', 'max:120'],
            'username' => [
                'sometimes', 'required', 'string', 'max:20',
                Rule::unique('users', 'username')->ignore($pendingAccountId ?? $student->user_id),
                Rule::unique('siswa', 'nisn')->ignore($student->id),
            ],
            'nisn' => [
                'sometimes', 'required', 'string', 'max:20',
                Rule::unique('siswa', 'nisn')->ignore($student->id),
            ],
            'nis' => [
                'sometimes', 'nullable', 'string', 'max:30',
                Rule::unique('siswa', 'nis')->ignore($student->id),
            ],
            'password' => $pendingAccountId === null
                ? ['sometimes', 'nullable', 'string', 'min:8', 'max:255']
                : ['prohibited'],
            'kelas_id' => ['sometimes', 'nullable', 'integer', Rule::exists('kelas', 'id')],
            'jenis_kelamin' => ['sometimes', 'nullable', Rule::in(['L', 'P'])],
            'no_hp' => ['sometimes', 'nullable', 'string', 'max:25'],
            'email_sekolah' => ['sometimes', 'nullable', 'email', 'max:150'],
            'alamat' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'nama_orang_tua' => ['sometimes', 'nullable', 'string', 'max:120'],
            'no_hp_orang_tua' => ['sometimes', 'nullable', 'string', 'max:25'],
            'status' => ['prohibited'],
            'ttl' => ['prohibited'],
        ]);

        return DB::transaction(function () use (
            $data,
            $student,
            $pendingAccountId
        ): JsonResponse {
            if ($pendingAccountId !== null) {
                $reservation = DB::table('pending_account_verifications')
                    ->where('user_id', $pendingAccountId)
                    ->where('siswa_id', $student->id)
                    ->whereNull('guru_id')
                    ->lockForUpdate()
                    ->first();
                $user = User::query()->with('role')->lockForUpdate()->find($pendingAccountId);
                $lockedStudent = Siswa::query()->lockForUpdate()->find($student->id);

                if (
                    ! $reservation
                    || ! $user
                    || $user->status !== 'pending'
                    || $user->email_terverifikasi_at !== null
                    || $user->role?->nama !== 'siswa'
                    || (int) $reservation->role_id !== (int) $user->role_id
                    || ! $lockedStudent
                    || (
                        $lockedStudent->user_id !== null
                        && (int) $lockedStudent->user_id !== (int) $user->getKey()
                    )
                ) {
                    return $this->notFound();
                }

                $identityChanged = array_key_exists('username', $data)
                    && $data['username'] !== $lockedStudent->nisn;
                $identityChanged = $identityChanged || (
                    array_key_exists('nisn', $data)
                    && $data['nisn'] !== $lockedStudent->nisn
                );
                $nameChanged = array_key_exists('nama', $data)
                    && $data['nama'] !== $lockedStudent->nama_lengkap;

                if (isset($data['nama'])) {
                    $lockedStudent->nama_lengkap = $data['nama'];
                }
                if (isset($data['username'])) {
                    $lockedStudent->nisn = $data['username'];
                }
                if (array_key_exists('nisn', $data)) {
                    $lockedStudent->nisn = $data['nisn'];
                }
                foreach ([
                    'nis',
                    'jenis_kelamin',
                    'no_hp',
                    'email_sekolah',
                    'alamat',
                    'nama_orang_tua',
                    'no_hp_orang_tua',
                ] as $field) {
                    if (array_key_exists($field, $data)) {
                        $lockedStudent->{$field} = $data[$field];
                    }
                }
                if (array_key_exists('kelas_id', $data)) {
                    $lockedStudent->kelas_id = $data['kelas_id'];
                }
                $lockedStudent->save();

                if ($identityChanged || $nameChanged) {
                    if ((int) $lockedStudent->user_id === (int) $user->getKey()) {
                        $lockedStudent->user_id = null;
                        $lockedStudent->save();
                    }

                    DB::table('pending_account_verifications')->where('id', $reservation->id)->delete();
                    $deleted = User::query()
                        ->whereKey($user->getKey())
                        ->where('status', 'pending')
                        ->whereNull('email_terverifikasi_at')
                        ->delete();

                    if ($deleted !== 1) {
                        throw new \RuntimeException('Akun pending siswa berubah saat pembatalan registrasi.');
                    }

                    $lockedStudent->setRelation('user', null);

                    return response()->json([
                        'success' => true,
                        'message' => 'Data master siswa diperbarui dan registrasi pending dibatalkan.',
                        'data' => $this->studentData($lockedStudent->load('kelas'), true),
                    ]);
                }

                $lockedStudent->setRelation('user', $user);

                return response()->json([
                    'success' => true,
                    'message' => 'Data master siswa berhasil diperbarui.',
                    'data' => $this->studentData($lockedStudent->load('kelas'), true),
                ]);
            }

            $student = Siswa::query()->lockForUpdate()->findOrFail($student->id);
            $user = User::query()->lockForUpdate()->findOrFail($student->user_id);
            $credentialsChanged = false;

            if (isset($data['nama'])) {
                $user->nama_tampilan = $data['nama'];
                $student->nama_lengkap = $data['nama'];
            }

            if (isset($data['username'])) {
                $user->username = $data['username'];
                $student->nisn = $data['username'];
                $credentialsChanged = true;
            }
            if (array_key_exists('nisn', $data)) {
                $student->nisn = $data['nisn'];
            }

            if (isset($data['password']) && $data['password'] !== '') {
                $user->password_hash = Hash::make($data['password']);
                $user->wajib_ganti_password = true;
                $credentialsChanged = true;
            }

            if (array_key_exists('kelas_id', $data)) {
                $student->kelas_id = $data['kelas_id'];
            }
            foreach ([
                'nis',
                'jenis_kelamin',
                'no_hp',
                'email_sekolah',
                'alamat',
                'nama_orang_tua',
                'no_hp_orang_tua',
            ] as $field) {
                if (array_key_exists($field, $data)) {
                    $student->{$field} = $data[$field];
                }
            }
            $user->save();
            $student->save();

            if ($credentialsChanged) {
                $user->tokens()->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Akun siswa berhasil diperbarui.',
                'data' => $this->studentData($student->load(['user', 'kelas']), true),
            ]);
        }, attempts: 3);
    }

    public function updateStatus(
        Request $request,
        int $user,
        AdminAccountStatusService $statusService
    ): JsonResponse {
        $status = $request->input('status');
        $data = $request->validate([
            'status' => ['required', Rule::in(['aktif', 'nonaktif'])],
            'alasan_kode' => [
                Rule::requiredIf($status === 'nonaktif'),
                Rule::prohibitedIf($status === 'aktif'),
                Rule::in(array_keys(\App\Services\PenempatanPklService::ALASAN_PEMBATALAN)),
            ],
            'alasan_detail' => [
                Rule::requiredIf($status === 'nonaktif' && $request->input('alasan_kode') === 'lainnya'),
                Rule::prohibitedIf($status === 'aktif'),
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        try {
            $student = $statusService->setStudentStatus(
                $request->user(),
                $user,
                $data['status'],
                $data['alasan_kode'] ?? null,
                $data['alasan_detail'] ?? null
            );
        } catch (\Illuminate\Validation\ValidationException $exception) {
            if (isset($exception->errors()['user'])) {
                return $this->notFound();
            }

            throw $exception;
        }

        return response()->json([
            'success' => true,
            'message' => 'Status akun dan profil Siswa berhasil diperbarui.',
            'data' => $this->studentData($student, true),
        ]);
    }

    public function destroy(
        Request $request,
        int $user,
        AdminAccountStatusService $statusService
    ): JsonResponse
    {
        $data = $request->validate([
            'alasan_kode' => [
                'sometimes',
                'nullable',
                Rule::in(array_keys(\App\Services\PenempatanPklService::ALASAN_PEMBATALAN)),
            ],
            'alasan_detail' => [
                'sometimes',
                'nullable',
                'string',
                'max:2000',
            ],
        ]);
        $reasonCode = $data['alasan_kode'] ?? 'lainnya';
        $reasonDetail = $data['alasan_detail'] ?? null;
        if (! isset($data['alasan_kode'])) {
            $reasonDetail = 'Akun dinonaktifkan melalui aksi Hapus pada Manajemen Siswa.';
        }

        try {
            $statusService->setStudentStatus(
                $request->user(),
                $user,
                'nonaktif',
                $reasonCode,
                $reasonDetail
            );
        } catch (\Illuminate\Validation\ValidationException $exception) {
            if (isset($exception->errors()['user'])) {
                return $this->notFound();
            }

            throw $exception;
        }

        return response()->json([
            'success' => true,
            'message' => 'Akun siswa berhasil dinonaktifkan.',
            'data' => null,
        ]);
    }

    private function findStudent(int $user): ?Siswa
    {
        $student = Siswa::query()
            ->with(['user', 'kelas'])
            ->where('user_id', $user)
            ->whereHas('user.role', fn ($roleQuery) => $roleQuery->whereRaw('LOWER(nama) = ?', ['siswa']))
            ->first();

        if ($student) {
            return $student;
        }

        $account = User::query()->with('role')->where('status', 'pending')->find($user);
        if (! $account || $account->role?->nama !== 'siswa') {
            return null;
        }

        $reservation = DB::table('pending_account_verifications')
            ->where('user_id', $account->getKey())
            ->where('role_id', $account->role_id)
            ->whereNotNull('siswa_id')
            ->whereNull('guru_id')
            ->first();
        if (! $reservation) {
            return null;
        }

        $student = Siswa::query()
            ->with('kelas')
            ->whereNull('user_id')
            ->find($reservation->siswa_id);
        if ($student) {
            $student->setRelation('user', $account);
        }

        return $student;
    }

    private function studentData(Siswa $student, bool $includeProfile = false): array
    {
        $data = [
            'id' => $student->user?->id ?? $student->user_id,
            'profile_id' => $student->id,
            'nama' => $student->nama_lengkap,
            'username' => $student->user_id === null
                ? $student->nisn
                : ($student->user?->username ?? $student->nisn),
            'kelas_id' => $student->kelas_id,
            'kelas' => $student->kelas?->nama,
            'status' => $student->user?->status,
            'status_siswa' => $student->status_siswa,
            'terakhir_login_at' => $student->user?->terakhir_login_at,
        ];

        if ($includeProfile) {
            $data += [
                'nisn' => $student->nisn,
                'nis' => $student->nis,
                'jenis_kelamin' => $student->jenis_kelamin,
                'no_hp' => $student->no_hp,
                'email_sekolah' => $student->email_sekolah,
                'alamat' => $student->alamat,
                'nama_orang_tua' => $student->nama_orang_tua,
                'no_hp_orang_tua' => $student->no_hp_orang_tua,
            ];
        }

        return $data;
    }

    private function roleUnavailable(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Role siswa belum tersedia di database.',
        ], 503);
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Akun siswa tidak ditemukan.',
        ], 404);
    }
}
