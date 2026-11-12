<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
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
            'data' => $this->studentData($student),
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
            'password' => ['required', 'string', 'min:12', 'max:255'],
            'kelas_id' => [$classIsRequired ? 'required' : 'nullable', 'integer', Rule::exists('kelas', 'id')],
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
            $user->nama_tampilan = $data['nama'];
            $user->password_hash = Hash::make($data['password']);
            $user->status = $data['status'];
            $user->wajib_ganti_password = true;
            $user->save();

            $student = new Siswa;
            $student->user_id = $user->id;
            $student->nisn = $data['username'];
            $student->nama_lengkap = $data['nama'];
            $student->kelas_id = $data['kelas_id'] ?? null;
            $student->status_siswa = 'aktif';
            $student->save();

            return response()->json([
                'success' => true,
                'message' => 'Akun siswa berhasil dibuat.',
                'data' => $this->studentData($student->load(['user', 'kelas'])),
            ], 201);
        });
    }

    public function update(Request $request, int $user): JsonResponse
    {
        $student = $this->findStudent($user);

        if (! $student) {
            return $this->notFound();
        }

        $status = $request->input('status');
        if (is_string($status)) {
            $request->merge(['status' => strtolower(trim($status))]);
        }

        $data = $request->validate([
            'nama' => ['sometimes', 'required', 'string', 'max:120'],
            'username' => [
                'sometimes', 'required', 'string', 'max:20',
                Rule::unique('users', 'username')->ignore($student->user_id),
                Rule::unique('siswa', 'nisn')->ignore($student->id),
            ],
            'password' => ['sometimes', 'nullable', 'string', 'min:12', 'max:255'],
            'kelas_id' => ['sometimes', 'nullable', 'integer', Rule::exists('kelas', 'id')],
            'status' => ['sometimes', 'required', Rule::in(['aktif', 'nonaktif'])],
        ]);

        return DB::transaction(function () use ($data, $student): JsonResponse {
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

            if (isset($data['password']) && $data['password'] !== '') {
                $user->password_hash = Hash::make($data['password']);
                $user->wajib_ganti_password = true;
                $credentialsChanged = true;
            }

            if (isset($data['status'])) {
                $credentialsChanged = $credentialsChanged || $user->status !== $data['status'];
                $user->status = $data['status'];
            }

            if (array_key_exists('kelas_id', $data)) {
                $student->kelas_id = $data['kelas_id'];
            }

            $user->save();
            $student->save();

            if ($credentialsChanged) {
                $user->tokens()->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Akun siswa berhasil diperbarui.',
                'data' => $this->studentData($student->load(['user', 'kelas'])),
            ]);
        });
    }

    public function destroy(int $user): JsonResponse
    {
        $student = $this->findStudent($user);

        if (! $student) {
            return $this->notFound();
        }

        if (DB::table('penempatan_pkl')->where('siswa_id', $student->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Akun siswa memiliki data penempatan PKL dan tidak dapat dihapus.',
            ], 409);
        }

        DB::transaction(function () use ($student): void {
            $account = User::query()->lockForUpdate()->findOrFail($student->user_id);
            $account->tokens()->delete();
            $student->delete();
            $account->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Akun siswa berhasil dihapus.',
            'data' => null,
        ]);
    }

    private function findStudent(int $user): ?Siswa
    {
        return Siswa::query()
            ->with(['user', 'kelas'])
            ->where('user_id', $user)
            ->whereHas('user.role', fn ($roleQuery) => $roleQuery->whereRaw('LOWER(nama) = ?', ['siswa']))
            ->first();
    }

    private function studentData(Siswa $student): array
    {
        return [
            'id' => $student->user_id,
            'profile_id' => $student->id,
            'nama' => $student->nama_lengkap,
            'username' => $student->user?->username ?? $student->nisn,
            'kelas_id' => $student->kelas_id,
            'kelas' => $student->kelas?->nama,
            'status' => $student->user?->status,
            'terakhir_login_at' => $student->user?->terakhir_login_at,
        ];
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
