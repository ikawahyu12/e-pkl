<?php

namespace App\Http\Controllers\Api\V1\Siswa;

use App\Http\Controllers\Controller;
use App\Models\PenempatanPkl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToWriteFile;
use Throwable;

class IzinController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'jenis' => ['required', 'in:izin,sakit'],
            'tanggal_mulai' => ['required', 'date_format:Y-m-d'],
            'tanggal_selesai' => ['required', 'date_format:Y-m-d', 'after_or_equal:tanggal_mulai'],
            'alasan' => ['required', 'string'],
            'lampiran' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $siswa = $request->user()->siswa;
        if (! $siswa) {
            return response()->json([
                'success' => false,
                'message' => 'Data siswa tidak ditemukan.',
            ], 404);
        }

        $penempatan = PenempatanPkl::query()
            ->where('siswa_id', $siswa->id)
            ->where('status', 'aktif')
            ->whereDate('tanggal_mulai', '<=', $data['tanggal_mulai'])
            ->whereDate('tanggal_selesai', '>=', $data['tanggal_selesai'])
            ->orderByDesc('tanggal_mulai')
            ->first();

        if (! $penempatan) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada penempatan aktif Siswa yang mencakup tanggal pengajuan.',
            ], 422);
        }

        $file = $request->file('lampiran');
        try {
            $filePath = $file?->store('izin', 'local');
        } catch (UnableToWriteFile) {
            return response()->json([
                'success' => false,
                'message' => 'Lampiran gagal disimpan. Silakan coba lagi.',
            ], 500);
        }

        if ($file && $filePath === false) {
            return response()->json([
                'success' => false,
                'message' => 'Lampiran gagal disimpan. Silakan coba lagi.',
            ], 500);
        }

        try {
            $pengajuanId = DB::transaction(function () use ($data, $penempatan, $file, $filePath): int {
                $now = now();
                $id = DB::table('pengajuan_izin')->insertGetId([
                    'penempatan_id' => $penempatan->id,
                    'jenis' => $data['jenis'],
                    'tanggal_mulai' => $data['tanggal_mulai'],
                    'tanggal_selesai' => $data['tanggal_selesai'],
                    'alasan' => $data['alasan'],
                    'status' => 'menunggu',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                if ($file && is_string($filePath)) {
                    DB::table('pengajuan_izin_lampiran')->insert([
                        'pengajuan_id' => $id,
                        'nama_file' => basename($filePath),
                        'path_file' => $filePath,
                        'mime_type' => $file->getMimeType(),
                        'ukuran_byte' => $file->getSize(),
                        'created_at' => $now,
                    ]);
                }

                return $id;
            });
        } catch (Throwable $exception) {
            if (is_string($filePath)) {
                Storage::disk('local')->delete($filePath);
            }

            throw $exception;
        }

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan izin berhasil dikirim.',
            'data' => ['id' => $pengajuanId],
        ]);
    }
}
