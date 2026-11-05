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
        $user?->loadMissing('role');

        if ($user?->role?->nama !== 'siswa') {
            return response()->json([
                'success' => false,
                'message' => 'Akses hanya tersedia untuk akun Siswa.',
            ], 403);
        }

        return $next($request);
    }
}
