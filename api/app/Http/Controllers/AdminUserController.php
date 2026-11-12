<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\AdminUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'nullable', Rule::in(['aktif', 'nonaktif', 'ditangguhkan', 'Aktif', 'Nonaktif', 'Ditangguhkan'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $role = Role::query()->where('nama', 'admin')->first();

        if (! $role) {
            return $this->adminRoleUnavailable();
        }

        $query = User::query()->where('role_id', $role->id);

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($userQuery) use ($search): void {
                $userQuery->where('nama_tampilan', 'like', '%'.$search.'%')
                    ->orWhere('username', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', strtolower($filters['status']));
        }

        $users = $query
            ->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->through(fn (User $user): array => $this->userData($user));

        return response()->json([
            'success' => true,
            'message' => 'Daftar akun Admin berhasil diambil.',
            'data' => [
                'users' => $users->items(),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'last_page' => $users->lastPage(),
                ],
            ],
        ]);
    }

    public function store(AdminUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        return DB::transaction(function () use ($data): JsonResponse {
            $role = Role::query()->where('nama', 'admin')->lockForUpdate()->first();

            if (! $role) {
                return $this->adminRoleUnavailable();
            }

            $user = new User;
            $user->role_id = $role->id;
            $user->nama_tampilan = $data['nama_tampilan'];
            $user->username = $data['username'];
            $user->email = $data['email'] ?? null;
            $user->password_hash = Hash::make($data['password']);
            $user->status = $data['status'] ?? 'aktif';
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Akun Admin berhasil dibuat.',
                'data' => $this->userData($user),
            ], 201);
        });
    }

    public function show(int $user): JsonResponse
    {
        $role = Role::query()->where('nama', 'admin')->first();

        if (! $role) {
            return $this->adminRoleUnavailable();
        }

        $admin = User::query()
            ->where('role_id', $role->id)
            ->find($user);

        if (! $admin) {
            return $this->adminNotFound();
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail akun Admin berhasil diambil.',
            'data' => $this->userData($admin),
        ]);
    }

    public function update(AdminUserRequest $request, int $user): JsonResponse
    {
        $data = $request->validated();

        return DB::transaction(function () use ($data, $user): JsonResponse {
            $role = Role::query()->where('nama', 'admin')->lockForUpdate()->first();

            if (! $role) {
                return $this->adminRoleUnavailable();
            }

            $admin = User::query()
                ->where('role_id', $role->id)
                ->lockForUpdate()
                ->find($user);

            if (! $admin) {
                return $this->adminNotFound();
            }

            $nextStatus = $data['status'] ?? $admin->status;

            if (
                $admin->status === 'aktif'
                && $nextStatus !== 'aktif'
                && ! User::query()
                    ->where('role_id', $role->id)
                    ->where('status', 'aktif')
                    ->where('id', '<>', $admin->id)
                    ->exists()
            ) {
                return $this->lastActiveAdminConflict();
            }

            if (isset($data['nama_tampilan'])) {
                $admin->nama_tampilan = $data['nama_tampilan'];
            }

            if (isset($data['username'])) {
                $admin->username = $data['username'];
            }

            if (array_key_exists('email', $data)) {
                $admin->email = $data['email'];
            }

            $credentialsChanged = false;

            if (isset($data['password'])) {
                $admin->password_hash = Hash::make($data['password']);
                $credentialsChanged = true;
            }

            if (isset($data['status'])) {
                $credentialsChanged = $credentialsChanged || $admin->status !== $nextStatus;
                $admin->status = $nextStatus;
            }

            $admin->save();

            if ($credentialsChanged) {
                $admin->tokens()->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Akun Admin berhasil diperbarui.',
                'data' => $this->userData($admin),
            ]);
        });
    }

    public function destroy(Request $request, int $user): JsonResponse
    {
        $currentUserId = $request->user()?->getKey();

        if ((int) $currentUserId === $user) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Admin yang sedang digunakan tidak dapat dihapus.',
            ], 409);
        }

        return DB::transaction(function () use ($user): JsonResponse {
            $role = Role::query()->where('nama', 'admin')->lockForUpdate()->first();

            if (! $role) {
                return $this->adminRoleUnavailable();
            }

            $admin = User::query()
                ->where('role_id', $role->id)
                ->lockForUpdate()
                ->find($user);

            if (! $admin) {
                return $this->adminNotFound();
            }

            if (
                $admin->status === 'aktif'
                && ! User::query()
                    ->where('role_id', $role->id)
                    ->where('status', 'aktif')
                    ->where('id', '<>', $admin->id)
                    ->exists()
            ) {
                return $this->lastActiveAdminConflict();
            }

            $admin->tokens()->delete();
            $admin->delete();

            return response()->json([
                'success' => true,
                'message' => 'Akun Admin berhasil dihapus.',
                'data' => null,
            ]);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function userData(User $user): array
    {
        return [
            'id' => $user->id,
            'nama' => $user->nama_tampilan,
            'nama_tampilan' => $user->nama_tampilan,
            'username' => $user->username,
            'email' => $user->email,
            'status' => $user->status,
            'terakhir_login_at' => $user->terakhir_login_at,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }

    private function adminRoleUnavailable(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Role Admin belum tersedia di database.',
        ], 503);
    }

    private function adminNotFound(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Akun Admin tidak ditemukan.',
        ], 404);
    }

    private function lastActiveAdminConflict(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Admin aktif terakhir tidak dapat dinonaktifkan atau dihapus.',
        ], 409);
    }
}
