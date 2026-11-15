<?php

namespace App\Http\Controllers\Api\V1\Siswa;

use App\Http\Controllers\Controller;
use App\Models\PenempatanPkl;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use League\Flysystem\UnableToWriteFile;
use Throwable;

class JurnalController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tanggal' => ['required', 'date_format:Y-m-d'],
            'judul' => ['required', 'string', 'max:160'],
            'kegiatan' => ['required', 'string'],
            'hasil' => ['required', 'string'],
            'kendala' => ['nullable', 'string'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $journalDate = CarbonImmutable::createFromFormat(
            '!Y-m-d',
            $data['tanggal'],
            (string) config('app.timezone')
        );
        if ($journalDate->gt(CarbonImmutable::today((string) config('app.timezone')))) {
            throw ValidationException::withMessages([
                'tanggal' => 'Tanggal jurnal tidak boleh melewati hari ini.',
            ]);
        }

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
            ->whereDate('tanggal_mulai', '<=', $data['tanggal'])
            ->whereDate('tanggal_selesai', '>=', $data['tanggal'])
            ->orderByDesc('tanggal_mulai')
            ->first();

        if (! $penempatan) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada penempatan aktif Siswa yang mencakup tanggal jurnal.',
            ], 422);
        }

        $foto = $request->file('foto');
        try {
            $fotoPath = $foto?->store('jurnal', 'local');
        } catch (UnableToWriteFile) {
            return response()->json([
                'success' => false,
                'message' => 'Foto gagal disimpan. Silakan coba lagi.',
            ], 500);
        }

        if ($foto && $fotoPath === false) {
            return response()->json([
                'success' => false,
                'message' => 'Foto gagal disimpan. Silakan coba lagi.',
            ], 500);
        }

        try {
            $jurnalId = DB::transaction(function () use ($data, $penempatan, $foto, $fotoPath): int {
                $now = now();
                $id = DB::table('jurnal')->insertGetId([
                    'penempatan_id' => $penempatan->id,
                    'tanggal' => $data['tanggal'],
                    'judul' => $data['judul'],
                    'kegiatan' => $data['kegiatan'],
                    'hasil' => $data['hasil'],
                    'kendala' => $data['kendala'] ?? null,
                    'status' => 'menunggu',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                if ($foto && is_string($fotoPath)) {
                    DB::table('jurnal_lampiran')->insert([
                        'jurnal_id' => $id,
                        'nama_file' => basename($fotoPath),
                        'path_file' => $fotoPath,
                        'mime_type' => $foto->getMimeType(),
                        'ukuran_byte' => $foto->getSize(),
                        'created_at' => $now,
                    ]);
                }

                return $id;
            });
        } catch (Throwable $exception) {
            if (is_string($fotoPath)) {
                Storage::disk('local')->delete($fotoPath);
            }

            throw $exception;
        }

        return response()->json([
            'success' => true,
            'message' => 'Jurnal berhasil dikirim.',
            'data' => ['id' => $jurnalId],
        ]);
    }
}