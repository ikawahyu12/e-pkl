<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnsureSanctumTokenStorage
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->bearerToken() && ! Schema::hasTable('personal_access_tokens')) {
            return response()->json([
                'success' => false,
                'message' => 'Penyimpanan token belum disiapkan.',
            ], 503);
        }

        return $next($request);
    }
}
