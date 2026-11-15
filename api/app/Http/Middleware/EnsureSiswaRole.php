<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSiswaRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $user?->loadMissing(['role', 'siswa']);

        if (
            $user?->role?->nama !== 'siswa' ||
            $user?->status !== 'aktif' ||
            $user?->siswa === null ||
            $user?->siswa?->status_siswa !== 'aktif'
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Akses hanya tersedia untuk akun Siswa aktif dengan profil Siswa aktif.',
            ], 403);
        }

        return $next($request);
    }
}