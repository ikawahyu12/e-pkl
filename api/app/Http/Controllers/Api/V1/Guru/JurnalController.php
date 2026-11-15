<?php

namespace App\Http\Controllers\Api\V1\Guru;

use App\Http\Controllers\Controller;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class JurnalController extends Controller
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

        $jurnal = $this->jurnalQuery($guru->id)
            ->orderByDesc('j.tanggal')
            ->orderByDesc('j.id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar jurnal siswa berhasil dimuat.',
            'data' => $this->attachDocumentation($request, $jurnal),
        ]);
    }

    public function show(Request $request, int $jurnal): JsonResponse
    {
        $guru = $request->user()->guru;
        if (! $guru) {
            return response()->json([
                'success' => false,
                'message' => 'Profil Guru tidak ditemukan.',
            ], 403);
        }

        $entry = $this->jurnalQuery($guru->id)
            ->where('j.id', $jurnal)
            ->first();

        if (! $entry) {
            return response()->json([
                'success' => false,
                'message' => 'Jurnal tidak ditemukan.',
            ], 404);
        }

        $jurnalDenganDokumentasi = $this->attachDocumentation(
            $request,
            collect([$entry]),
        )->first();

        return response()->json([
            'success' => true,
            'message' => 'Detail jurnal berhasil dimuat.',
            'data' => $jurnalDenganDokumentasi,
        ]);
    }

    public function showDocumentation(Request $request, int $jurnal, int $lampiran): Response
    {
        $guru = $request->user()->guru;
        if (! $guru) {
            return response()->json([
                'success' => false,
                'message' => 'Profil Guru tidak ditemukan.',
            ], 403);
        }

        $ownsJournal = $this->jurnalQuery($guru->id)
            ->where('j.id', $jurnal)
            ->exists();
        if (! $ownsJournal) {
            return response()->json([
                'success' => false,
                'message' => 'Dokumentasi tidak ditemukan.',
            ], 404);
        }

        $file = DB::table('jurnal_lampiran')
            ->where('id', $lampiran)
            ->where('jurnal_id', $jurnal)
            ->first();

        if (! $file || ! Storage::disk('public')->exists($file->path_file)) {
            return response()->json([
                'success' => false,
                'message' => 'Dokumentasi tidak ditemukan.',
            ], 404);
        }

        return Storage::disk('public')->response(
            $file->path_file,
            $file->nama_file,
            [
                'Content-Type' => $file->mime_type ?: 'application/octet-stream',
                'Cache-Control' => 'private, max-age=300',
            ],
        );
    }

    private function jurnalQuery(int $guruId): Builder
    {
        return DB::table('jurnal as j')
            ->join('penempatan_pkl as pp', 'pp.id', '=', 'j.penempatan_id')
            ->join('siswa as s', 's.id', '=', 'pp.siswa_id')
            ->where('pp.guru_id', $guruId)
            ->select([
                'j.id',
                'j.penempatan_id',
                'j.tanggal',
                'j.judul',
                'j.kegiatan',
                'j.hasil',
                'j.kendala',
                's.id as siswa_id',
                's.nama_lengkap as siswa_nama',
                's.nisn as siswa_nisn',
            ]);
    }

    private function attachDocumentation(Request $request, Collection $jurnal): Collection
    {
        if ($jurnal->isEmpty()) {
            return $jurnal;
        }

        $lampiranByJurnal = DB::table('jurnal_lampiran')
            ->whereIn('jurnal_id', $jurnal->pluck('id'))
            ->orderBy('id')
            ->get()
            ->groupBy('jurnal_id');

        return $jurnal->map(function ($entry) use ($request, $lampiranByJurnal): array {
            $lampiran = $lampiranByJurnal->get($entry->id, collect())
                ->map(fn ($file): array => [
                    'id' => $file->id,
                    'nama_file' => $file->nama_file,
                    'mime_type' => $file->mime_type,
                    'ukuran_byte' => $file->ukuran_byte,
                    'url' => $this->documentationUrl($request, $entry->id, $file->id),
                ])->values();

            return [
                'id' => $entry->id,
                'penempatan_id' => $entry->penempatan_id,
                'tanggal' => $entry->tanggal,
                'judul' => $entry->judul,
                'kegiatan' => $entry->kegiatan,
                'hasil' => $entry->hasil,
                'kendala' => $entry->kendala,
                'siswa' => [
                    'id' => $entry->siswa_id,
                    'nama_lengkap' => $entry->siswa_nama,
                    'nisn' => $entry->siswa_nisn,
                ],
                'dokumentasi' => $lampiran,
            ];
        })->values();
    }

    private function documentationUrl(Request $request, int $jurnalId, int $lampiranId): string
    {
        return rtrim($request->getSchemeAndHttpHost(), '/')
            ."/api/v1/guru/jurnal/{$jurnalId}/lampiran/{$lampiranId}";
    }
}
