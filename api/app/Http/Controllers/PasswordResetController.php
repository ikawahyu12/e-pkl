<?php

namespace App\Http\Controllers;

use App\Services\PasswordResetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PasswordResetController extends Controller
{
    public function send(Request $request, PasswordResetService $service): JsonResponse
    {
        $role = (string) $request->route('role');
        $validated = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
        ])->validate();

        $service->sendResetLink($role, $validated['email']);

        return response()->json([
            'success' => true,
            'message' => 'Jika alamat email terdaftar, instruksi reset kata sandi akan dikirim.',
            'data' => null,
        ], 202);
    }

    public function reset(Request $request, PasswordResetService $service): JsonResponse
    {
        $role = (string) $request->route('role');
        $validated = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'token' => ['required', 'string', 'size:64', 'regex:/\A[a-f0-9]{64}\z/'],
            'password_baru' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/[A-Za-z]/',
                'regex:/[0-9]/',
            ],
        ], [
            'password_baru.min' => 'Kata sandi baru minimal 8 karakter.',
            'password_baru.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
            'password_baru.regex' => 'Kata sandi baru harus mengandung huruf dan angka.',
        ])->validate();

        if (! $service->resetPassword(
            $role,
            $validated['email'],
            $validated['token'],
            $validated['password_baru'],
        )) {
            return response()->json([
                'success' => false,
                'message' => 'Tautan reset kata sandi tidak valid atau telah kedaluwarsa.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Kata sandi berhasil diubah. Silakan masuk menggunakan kata sandi baru.',
            'data' => null,
        ]);
    }
}
