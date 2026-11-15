<?php

// Simpan di: app/Http/Controllers/Api/V1/Guru/AuthController.php
// (menggantikan AuthController.php versi sebelumnya; file itu boleh dihapus)
//
// ASUMSI: tiap akun guru punya baris di tabel gurus dengan kolom user_id.
// Pengecekan role memakai keberadaan baris Guru, jadi tidak bergantung
// pada skema tabel roles.

namespace App\Http\Controllers\Api\V1\Guru;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        $guru = Guru::where('user_id', $user->id)->first();

        if (! $guru) {
            // Pesan sengaja dibuat sama agar tidak membocorkan role akun lain.
            throw ValidationException::withMessages([
                'email' => ['Akun ini tidak memiliki akses aplikasi guru.'],
            ]);
        }

        $token = $user->createToken($data['device_name'] ?? 'mobile-guru')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'nama' => $guru->nama,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $guru = Guru::where('user_id', $request->user()->id)->firstOrFail();

        return response()->json([
            'user' => [
                'id' => $request->user()->id,
                'email' => $request->user()->email,
                'nama' => $guru->nama,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Berhasil logout.']);
    }
}