<?php

namespace App\Http\Controllers;

use App\Exceptions\TokenStorageUnavailable;
use App\Http\Requests\Siswa\LoginSiswaRequest;
use App\Models\User;
use App\Services\SiswaAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class SiswaAuthController extends Controller
{
    public function login(LoginSiswaRequest $request, SiswaAuthService $authService): JsonResponse
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

    public function me(Request $request, SiswaAuthService $authService): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json([
                'success' => false,
                'message' => 'Autentikasi diperlukan.',
            ], 401);
        }

        $siswaData = $authService->authenticatedSiswaData($user);

        if (! $siswaData) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Siswa tidak aktif atau profil Siswa tidak tersedia.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sesi Siswa aktif.',
            'data' => [
                'user' => $siswaData,
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
