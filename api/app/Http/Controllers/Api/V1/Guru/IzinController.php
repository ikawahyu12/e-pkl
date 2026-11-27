<?php

namespace App\Http\Controllers\Api\V1\Guru;

use App\Http\Controllers\Controller;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class IzinController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $guru = $request->user()->guru;
        if (! $guru) {
            return response()->json([
                'success' => false,
                'message' => 'Profil Guru tidak ditemukan.',
            ], 403);
        }

        $pengajuan = $this->pengajuanQuery($guru->id)
            ->orderByDesc('i.tanggal_mulai')
            ->orderByDesc('i.id')
            ->get();

        $lampiranByPengajuan = $pengajuan->isEmpty()
            ? collect()
            : DB::table('pengajuan_izin_lampiran')
                ->whereIn('pengajuan_id', $pengajuan->pluck('id'))
                ->orderBy('id')
                ->get()
                ->groupBy('pengajuan_id');

        $data = $pengajuan->map(function ($item) use ($request, $lampiranByPengajuan): array {
            $lampiran = $lampiranByPengajuan
                ->get($item->id, collect())
                ->map(fn ($file): array => [
                    'id' => $file->id,
                    'nama_file' => $file->nama_file,
                    'mime_type' => $file->mime_type,
                    'ukuran_byte' => $file->ukuran_byte,
                    'url' => $this->lampiranUrl($request, $item->id, $file->id),
                ])
                ->values();

            return [
                'id' => $item->id,
                'penempatan_id' => $item->penempatan_id,
                'jenis' => $item->jenis,
                'tanggal_mulai' => $item->tanggal_mulai,
                'tanggal_selesai' => $item->tanggal_selesai,
                'alasan' => $item->alasan,
                'status' => $item->status,
                'created_at' => $item->created_at,
                'siswa' => [
                    'id' => $item->siswa_id,
                    'nama_lengkap' => $item->siswa_nama,
                    'nisn' => $item->siswa_nisn,
                ],
                'lampiran' => $lampiran,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'message' => 'Daftar pengajuan izin siswa berhasil dimuat.',
            'data' => $data,
        ]);
    }

    public function showAttachment(
        Request $request,
        int $izin,
        int $lampiran
    ): Response|JsonResponse {
        $guru = $request->user()->guru;
        if (! $guru) {
            return response()->json([
                'success' => false,
                'message' => 'Profil Guru tidak ditemukan.',
            ], 403);
        }

        $file = DB::table('pengajuan_izin_lampiran as l')
            ->join('pengajuan_izin as i', 'i.id', '=', 'l.pengajuan_id')
            ->join('penempatan_pkl as pp', 'pp.id', '=', 'i.penempatan_id')
            ->where('pp.guru_id', $guru->id)
            ->where('i.id', $izin)
            ->where('l.id', $lampiran)
            ->select(['l.path_file', 'l.mime_type'])
            ->first();

        if (! $file || ! Storage::disk('local')->exists($file->path_file)) {
            return response()->json([
                'success' => false,
                'message' => 'Lampiran izin tidak ditemukan.',
            ], 404);
        }

        return response()->make(
            Storage::disk('local')->get($file->path_file),
            200,
            [
                'Content-Type' => $file->mime_type ?: 'application/octet-stream',
                'Cache-Control' => 'private, max-age=300',
            ],
        );
    }

    private function pengajuanQuery(int $guruId): Builder
    {
        return DB::table('pengajuan_izin as i')
            ->join('penempatan_pkl as pp', 'pp.id', '=', 'i.penempatan_id')
            ->join('siswa as s', 's.id', '=', 'pp.siswa_id')
            ->where('pp.guru_id', $guruId)
            ->select([
                'i.id',
                'i.penempatan_id',
                'i.jenis',
                'i.tanggal_mulai',
                'i.tanggal_selesai',
                'i.alasan',
                'i.status',
                'i.created_at',
                's.id as siswa_id',
                's.nama_lengkap as siswa_nama',
                's.nisn as siswa_nisn',
            ]);
    }

    private function lampiranUrl(
        Request $request,
        int $izinId,
        int $lampiranId
    ): string {
        return rtrim($request->getSchemeAndHttpHost(), '/')
            ."/api/v1/guru/izin/{$izinId}/lampiran/{$lampiranId}";
    }
}
