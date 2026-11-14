<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\Role;
use App\Models\User;
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
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'max:30', Rule::unique('users', 'username'), Rule::unique('guru', 'nip')],
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
            $user->nama_tampilan = $data['nama'];
            $user->password_hash = Hash::make($data['password']);
            $user->status = $data['status'];
            $user->wajib_ganti_password = true;
            $user->save();

            $teacher = new Guru;
            $teacher->user_id = $user->id;
            $teacher->nip = $data['username'];
            $teacher->nama_lengkap = $data['nama'];
            $teacher->status_bimbingan = 'aktif';
            $teacher->save();

            return response()->json([
                'success' => true,
                'message' => 'Akun guru berhasil dibuat.',
                'data' => $this->teacherData($teacher->load('user')),
            ], 201);
        });
    }

    public function update(Request $request, int $user): JsonResponse
    {
        $teacher = $this->findTeacher($user);

        if (! $teacher) {
            return $this->notFound();
        }

        $status = $request->input('status');
        if (is_string($status)) {
            $request->merge(['status' => strtolower(trim($status))]);
        }

        $data = $request->validate([
            'nama' => ['sometimes', 'required', 'string', 'max:120'],
            'username' => [
                'sometimes', 'required', 'string', 'max:30',
                Rule::unique('users', 'username')->ignore($teacher->user_id),
                Rule::unique('guru', 'nip')->ignore($teacher->id),
            ],
            'password' => ['sometimes', 'nullable', 'string', 'min:8', 'max:255'],
            'status' => ['sometimes', 'required', Rule::in(['aktif', 'nonaktif'])],
        ]);

        return DB::transaction(function () use ($data, $teacher): JsonResponse {
            $user = User::query()->lockForUpdate()->findOrFail($teacher->user_id);
            $credentialsChanged = false;

            if (isset($data['nama'])) {
                $user->nama_tampilan = $data['nama'];
                $teacher->nama_lengkap = $data['nama'];
            }

            if (isset($data['username'])) {
                $user->username = $data['username'];
                $teacher->nip = $data['username'];
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
        });
    }

    public function destroy(int $user): JsonResponse
    {
        $teacher = $this->findTeacher($user);

        if (! $teacher) {
            return $this->notFound();
        }

        if (DB::table('penempatan_pkl')->where('guru_id', $teacher->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Akun guru memiliki data penempatan PKL dan tidak dapat dihapus.',
            ], 409);
        }

        DB::transaction(function () use ($teacher): void {
            $account = User::query()->lockForUpdate()->findOrFail($teacher->user_id);
            $account->tokens()->delete();
            $teacher->delete();
            $account->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Akun guru berhasil dihapus.',
            'data' => null,
        ]);
    }

    private function findTeacher(int $user): ?Guru
    {
        return Guru::query()
            ->with('user')
            ->where('user_id', $user)
            ->whereHas('user.role', fn ($roleQuery) => $roleQuery->whereRaw('LOWER(nama) = ?', ['guru']))
            ->first();
    }

    private function teacherData(Guru $teacher): array
    {
        return [
            'id' => $teacher->user_id,
            'profile_id' => $teacher->id,
            'nama' => $teacher->nama_lengkap,
            'username' => $teacher->user?->username ?? $teacher->nip,
            'status' => $teacher->user?->status,
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
