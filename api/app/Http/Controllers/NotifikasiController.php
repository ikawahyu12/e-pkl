<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotifikasiResource;
use App\Models\Notifikasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotifikasiController extends Controller
{
    // GET /api/v1/notifikasi
    public function index(Request $request)
    {
        // Hanya ambil notifikasi milik user yang sedang login
        $data = Notifikasi::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(100);

        return NotifikasiResource::collection($data);
    }

    // GET /api/v1/notifikasi/jumlah-belum-dibaca
    public function jumlahBelumDibaca(Request $request): JsonResponse
    {
        $jumlah = Notifikasi::where('user_id', $request->user()->id)
            ->belumDibaca()
            ->count();

        return response()->json(['data' => ['jumlah' => $jumlah]]);
    }

    // PATCH /api/v1/notifikasi/{id}/dibaca
    public function tandaiDibaca(Request $request, $id): JsonResponse
    {
        $notif = Notifikasi::findOrFail($id);

        // Cek keamanan: pastikan ini notif miliknya
        if ($notif->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($notif->dibaca_at === null) {
            $notif->update(['dibaca_at' => now()]);
        }

        return response()->json(['message' => 'Berhasil ditandai dibaca']);
    }

    // PATCH /api/v1/notifikasi/dibaca-semua
    public function tandaiSemuaDibaca(Request $request): JsonResponse
    {
        Notifikasi::where('user_id', $request->user()->id)
            ->belumDibaca()
            ->update(['dibaca_at' => now()]);

        return response()->json(['message' => 'Semua berhasil ditandai dibaca']);
    }
}