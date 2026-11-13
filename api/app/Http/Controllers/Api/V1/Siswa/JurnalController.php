<?php

namespace App\Http\Controllers\Api\V1\Siswa;

use App\Http\Controllers\Controller;
use App\Models\PenempatanPkl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class JurnalController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'tanggal'  => 'required|date',
            'judul'    => 'required|string|max:160',
            'kegiatan' => 'required|string',
            'hasil'    => 'required|string',
            'kendala'  => 'nullable|string',
            'foto'     => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $siswa = $request->user()->siswa;
        if (! $siswa) {
            return response()->json([
                'success' => false,
                'message' => 'Data siswa tidak ditemukan.',
            ], 404);
        }

        $penempatan = PenempatanPkl::where('siswa_id', $siswa->id)
            ->where('status', '!=', 'dibatalkan')
            ->orderByDesc('tanggal_mulai')
            ->first();

        if (! $penempatan) {
            return response()->json([
                'success' => false,
                'message' => 'Penempatan PKL untuk akun ini belum tersedia. Minta admin membuat periode PKL dan penempatan siswa terlebih dahulu.',
            ], 422);
        }

        $fotoPath = null;
        $foto = $request->file('foto');
        if ($foto) {
            $fotoPath = $foto->store('jurnal', 'public');
            if ($fotoPath === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Foto gagal disimpan. Silakan coba lagi.',
                ], 500);
            }
        }

        try {
            DB::transaction(function () use ($data, $penempatan, $foto, $fotoPath): void {
                $now = now();
                $jurnalId = DB::table('jurnal')->insertGetId([
                    'penempatan_id' => $penempatan->id,
                    'tanggal'       => $data['tanggal'],
                    'judul'         => $data['judul'],
                    'kegiatan'      => $data['kegiatan'],
                    'hasil'         => $data['hasil'],
                    'kendala'       => $data['kendala'] ?? '',
                    'status'        => 'menunggu',
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);

                if ($foto && $fotoPath) {
                    DB::table('jurnal_lampiran')->insert([
                        'jurnal_id'   => $jurnalId,
                        'nama_file'   => basename($fotoPath),
                        'path_file'   => $fotoPath,
                        'mime_type'   => $foto->getMimeType(),
                        'ukuran_byte' => $foto->getSize(),
                        'created_at'  => $now,
                    ]);
                }
            });
        } catch (Throwable $exception) {
            if ($fotoPath) {
                Storage::disk('public')->delete($fotoPath);
            }

            throw $exception;
        }

        return response()->json([
            'success' => true,
            'message' => 'Jurnal dan foto berhasil dikirim.',
        ]);
    }
}
