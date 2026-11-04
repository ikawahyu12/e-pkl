<?php

namespace App\Http\Controllers;

use App\Exceptions\TokenStorageUnavailable;
use App\Http\Requests\Guru\LoginGuruRequest;
use App\Models\User;
use App\Services\GuruAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class GuruAuthController extends Controller
{
    public function login(LoginGuruRequest $request, GuruAuthService $authService): JsonResponse
    {
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

    public function me(Request $request, GuruAuthService $authService): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json([
                'success' => false,
                'message' => 'Autentikasi diperlukan.',
            ], 401);
        }

        $guruData = $authService->authenticatedGuruData($user);

        if (! $guruData) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Guru tidak aktif atau profil Guru tidak tersedia.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sesi Guru aktif.',
            'data' => [
                'user' => $guruData,
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
