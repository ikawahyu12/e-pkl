<?php

namespace App\Http\Controllers;

use App\Exceptions\SelfRegistrationUnavailable;
use App\Exceptions\VerificationEmailDeliveryFailed;
use App\Services\SelfRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class SelfRegistrationController extends Controller
{
    public function register(Request $request, string $role, SelfRegistrationService $service): JsonResponse
    {
        if (! in_array($role, ['siswa', 'guru'], true)) {
            abort(404);
        }

        $identity = $role === 'siswa' ? 'nisn' : 'nip';
        $maxIdentity = $role === 'siswa' ? 20 : 30;
        $input = $request->validate([
            $identity => ['required', 'string', 'max:'.$maxIdentity],
            'nama_lengkap' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150'],
            'password' => ['required', 'string', 'min:12', 'max:255', 'confirmed'],
        ]);

        try {
            $service->register($role, $input);
        } catch (InvalidArgumentException) {
            return response()->json([
                'success' => true,
                'message' => 'Jika data cocok dengan master dan belum terhubung ke akun lain, instruksi verifikasi akan dikirim ke email.',
                'data' => null,
            ], 202);
        } catch (SelfRegistrationUnavailable $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Registrasi sementara belum tersedia karena skema belum siap.',
            ], 503);
        } catch (VerificationEmailDeliveryFailed $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Akun menunggu verifikasi, tetapi email belum dapat dikirim. Coba kirim ulang.',
            ], 503);
        }

        return response()->json([
            'success' => true,
            'message' => 'Jika data cocok dengan master, instruksi verifikasi akan dikirim ke email.',
            'data' => null,
        ], 202);
    }

    public function resend(Request $request, string $role, SelfRegistrationService $service): JsonResponse
    {
        if (! in_array($role, ['siswa', 'guru'], true)) {
            abort(404);
        }

        $input = $request->validate(['email' => ['required', 'email', 'max:150']]);

        try {
            $service->resend($role, $input['email']);
        } catch (SelfRegistrationUnavailable) {
            return response()->json([
                'success' => false,
                'message' => 'Layanan verifikasi sementara belum tersedia.',
            ], 503);
        }

        return response()->json([
            'success' => true,
            'message' => 'Jika ada akun yang menunggu verifikasi, tautan baru akan dikirim.',
            'data' => null,
        ], 202);
    }

    public function verify(Request $request, string $role, SelfRegistrationService $service): JsonResponse|\Illuminate\View\View
    {
        $token = $request->input('token');
        if (! is_string($token) || $token === '') {
            $verified = false;
        } else {
            try {
                $verified = $service->verify($role, $token);
            } catch (SelfRegistrationUnavailable) {
                $verified = false;
            }
        }

        $message = $verified
            ? 'Email berhasil diverifikasi. Akun sudah aktif dan dapat digunakan untuk login.'
            : 'Tautan verifikasi tidak valid, kedaluwarsa, atau skema belum siap.';

        if (! $request->expectsJson()) {
            return view('auth.verification-result', compact('verified', 'message'));
        }

        return response()->json([
            'success' => $verified,
            'message' => $message,
            'data' => null,
        ], $verified ? 200 : 422);
    }
}
