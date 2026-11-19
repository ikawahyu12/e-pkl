<?php

namespace App\Http\Controllers\Api\V1\Guru;

use App\Http\Controllers\Controller;
use App\Models\Instansi;
use Illuminate\Http\JsonResponse;

class InstansiController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Instansi::query()
                ->where('status', 'aktif')
                ->orderBy('nama')
                ->get(['id', 'nama']),
        ]);
    }
}
