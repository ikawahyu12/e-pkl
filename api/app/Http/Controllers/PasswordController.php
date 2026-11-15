<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class PasswordController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json([
                'success' => false,
                'message' => 'Autentikasi diperlukan.',
            ], 401);
        }

        $validated = $request->validate([
            'password_lama' => ['required', 'string'],
            'password_baru' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'different:password_lama',
                'regex:/[A-Za-z]/',
                'regex:/[0-9]/',
            ],
        ], [
            'password_lama.required' => 'Kata sandi lama wajib diisi.',
            'password_baru.required' => 'Kata sandi baru wajib diisi.',
            'password_baru.min' => 'Kata sandi baru minimal 8 karakter.',
            'password_baru.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
            'password_baru.different' => 'Kata sandi baru harus berbeda dari kata sandi lama.',
            'password_baru.regex' => 'Kata sandi baru harus mengandung huruf dan angka.',
        ]);

        if (
            ! $user->password_hash ||
            ! Hash::check($validated['password_lama'], $user->password_hash)
        ) {
            throw ValidationException::withMessages([
                'password_lama' => ['Kata sandi lama tidak sesuai.'],
            ]);
        }

        $currentToken = $user->currentAccessToken();

        if (! $currentToken instanceof PersonalAccessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token akses tidak valid.',
            ], 401);
        }

        $user->password_hash = Hash::make($validated['password_baru']);
        $user->wajib_ganti_password = false;
        $user->save();

        $user->tokens()->where('id', '!=', $currentToken->getKey())->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kata sandi berhasil diubah.',
            'data' => null,
        ]);
    }
}
