<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGuruRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $user?->loadMissing(['role', 'guru']);

        if (
            $user?->role?->nama !== 'guru' ||
            $user?->status !== 'aktif' ||
            $user?->guru === null ||
            $user?->guru?->status_bimbingan !== 'aktif'
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Akses hanya tersedia untuk akun Guru aktif dengan status bimbingan aktif.',
            ], 403);
        }

        return $next($request);
    }
}
