<?php

namespace App\Http\Controllers;

use App\Exceptions\TokenStorageUnavailable;
use App\Http\Requests\Admin\LoginAdminRequest;
use App\Models\User;
use App\Services\AdminAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class AdminAuthController extends Controller
{
    public function login(
        LoginAdminRequest $request,
        AdminAuthService $authService
    ): JsonResponse {
        try {
            $result = $authService->login(
                $request->validated('login'),
                $request->validated('password'),
            );
        } catch (TokenStorageUnavailable) {
            return response()->json([
                'success' => false,
                'message' => 'Autentikasi belum tersedia karena penyimpanan token belum disiapkan.',
            ], 503);
        }

        if (! $result) {
            return response()->json([
                'success' => false,
                'message' => 'Login atau password tidak valid.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data' => [
                'token' => $result['token'],
                'token_type' => 'Bearer',
                'user' => $result['user'],
            ],
        ]);
    }

    public function me(
        Request $request,
        AdminAuthService $authService
    ): JsonResponse {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json([
                'success' => false,
                'message' => 'Autentikasi diperlukan.',
            ], 401);
        }

        $adminData = $authService->authenticatedAdminData($user);

        if (! $adminData) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Admin tidak aktif atau tidak memiliki akses Admin.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sesi Admin aktif.',
            'data' => [
                'user' => $adminData,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json([
                'success' => false,
                'message' => 'Autentikasi diperlukan.',
            ], 401);
        }

        $token = $user->currentAccessToken();

        if (! $token instanceof PersonalAccessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token akses tidak valid.',
            ], 401);
        }

        $token->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
            'data' => null,
        ]);
    }
}
