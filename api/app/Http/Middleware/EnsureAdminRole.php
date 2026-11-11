<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $user?->loadMissing('role');

        if (
            $user?->role?->nama !== 'admin'
            || $user?->status !== 'aktif'
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Akses hanya tersedia untuk akun Admin aktif.',
            ], 403);
        }

        return $next($request);
    }
}
