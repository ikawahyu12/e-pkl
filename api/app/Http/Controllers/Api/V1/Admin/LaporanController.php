<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LaporanEksporRequest;
use App\Http\Requests\Admin\LaporanFilterRequest;
use App\Services\LaporanArsipService;
use App\Services\LaporanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class LaporanController extends Controller
{
    public function __construct(
        private readonly LaporanService $laporan,
        private readonly LaporanArsipService $arsip,
    ) {}

    /** Pilihan filter (periode PKL & jurusan) beserta periode bawaan. */
    public function filter(): JsonResponse
    {
        return $this->ok($this->laporan->filter());
    }

    /** Angka ringkas untuk kartu laporan. */
    public function ringkasan(LaporanFilterRequest $request): JsonResponse
    {
        return $this->ok($this->laporan->ringkasan(
            $request->periodeId(),
            $request->jurusanKode(),
        ));
    }

    /** Rekap presensi per siswa. */
    public function presensi(LaporanFilterRequest $request): JsonResponse
    {
        return $this->ok($this->laporan->presensi(
            $request->periodeId(),
            $request->jurusanKode(),
        ));
    }

    /** Rekap jurnal kegiatan per siswa. */
    public function jurnal(LaporanFilterRequest $request): JsonResponse
    {
        return $this->ok($this->laporan->jurnal(
            $request->periodeId(),
            $request->jurusanKode(),
        ));
    }

    /** Riwayat unduhan (arsip berkas) untuk periode & jurusan terpilih. */
    public function arsip(LaporanFilterRequest $request): JsonResponse
    {
        return $this->ok([
            'arsip' => $this->arsip->daftar($request->periodeId(), $request->jurusanKode()),
        ]);
    }

    /**
     * Buat berkas laporan dari database, arsipkan, lalu kembalikan isinya.
     * Metadata arsip ada di header X-Arsip-Id.
     */
    public function ekspor(LaporanEksporRequest $request): JsonResponse|Response
    {
        $hasil = $this->arsip->buat(
            $request->validated('jenis'),
            $request->validated('format'),
            $request->periodeId(),
            $request->jurusanKode(),
            $request->user()?->id,
        );

        if ($hasil === null) {
            return response()->json([
                'success' => false,
                'message' => 'Belum ada periode PKL untuk dibuatkan laporan.',
            ], 422);
        }

        $arsip = $hasil['arsip'];

        return response($hasil['isi'], 201, [
            'Content-Type' => $this->tipeKonten($arsip['format']),
            'Content-Disposition' => 'attachment; filename="'.$arsip['nama_berkas'].'"',
            'X-Arsip-Id' => (string) $arsip['id'],
        ]);
    }

    /** Unduh ulang berkas dari arsip. */
    public function unduh(int $id): JsonResponse|Response
    {
        $arsip = $this->arsip->temukan($id);
        $isi = $arsip ? $this->arsip->baca($arsip['path']) : null;

        if ($arsip === null || $isi === null) {
            return response()->json([
                'success' => false,
                'message' => 'Berkas arsip tidak ditemukan.',
            ], 404);
        }

        return response($isi, 200, [
            'Content-Type' => $this->tipeKonten($arsip['format']),
            'Content-Disposition' => 'attachment; filename="'.$arsip['nama_berkas'].'"',
        ]);
    }

    private function tipeKonten(string $format): string
    {
        return $format === 'pdf'
            ? 'text/html; charset=utf-8'
            : 'application/vnd.ms-excel; charset=utf-8';
    }

    /** @param array<string, mixed> $data */
    private function ok(array $data): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}