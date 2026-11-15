<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Membuat berkas laporan, menyimpannya di disk privat, dan mencatatnya
 * di tabel laporan_arsip (Riwayat Unduhan).
 */
class LaporanArsipService
{
    public const DISK = 'local';

    public function __construct(
        private readonly LaporanService $laporan,
        private readonly LaporanExporter $exporter,
    ) {}

    /**
     * Buat laporan dari data database, simpan, dan catat di arsip.
     *
     * @return array{arsip: array<string, mixed>, isi: string}|null  null bila belum ada periode PKL
     */
    public function buat(string $jenis, string $format, ?int $periodeId, ?string $jurusan, ?int $userId): ?array
    {
        $data = $jenis === 'jurnal'
            ? $this->laporan->jurnal($periodeId, $jurusan)
            : $this->laporan->presensi($periodeId, $jurusan);

        if ($data['periode'] === null) {
            return null;
        }

        $ext = $format === 'pdf' ? 'html' : 'xls';
        $isi = $this->exporter->render($jenis, $format, $data);
        $jurusanKode = $data['jurusan']['kode'] ?? null;
        $nama = $this->exporter->namaBerkas($jenis, $data['periode']['nama'], $jurusanKode, $format === 'pdf' ? 'pdf' : 'xls');
        $path = 'laporan/'.bin2hex(random_bytes(16)).'.'.$ext;

        Storage::disk(self::DISK)->put($path, $isi);

        $jurusanId = $jurusanKode === null
            ? null
            : DB::selectOne('SELECT id FROM jurusan WHERE kode = ?', [$jurusanKode])?->id;

        DB::insert(
            'INSERT INTO laporan_arsip (jenis, format, periode_id, jurusan_id, nama_berkas, path, ukuran, dibuat_oleh, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$jenis, $format, $data['periode']['id'], $jurusanId, $nama, $path, strlen($isi), $userId, gmdate('Y-m-d H:i:s')]
        );

        $id = (int) DB::getPdo()->lastInsertId();

        return ['arsip' => $this->tanpaPath($this->temukan($id)), 'isi' => $isi];
    }

    /**
     * Riwayat, terbaru dulu. Arsip "semua jurusan" ikut tampil di setiap filter jurusan.
     *
     * @return array<int, array<string, mixed>>
     */
    public function daftar(?int $periodeId, ?string $jurusan, int $batas = 50): array
    {
        $periodeId ??= $this->laporan->periodeDefaultId();
        $bindings = [$periodeId];
        $filterJurusan = '';

        if ($jurusan !== null && $jurusan !== '') {
            $filterJurusan = ' AND (a.jurusan_id IS NULL OR j.kode = ?)';
            $bindings[] = strtoupper($jurusan);
        }

        $batas = max(1, min($batas, 200));

        $rows = DB::select(
            $this->pilih().'
             WHERE a.periode_id = ?'.$filterJurusan.'
             ORDER BY a.created_at DESC, a.id DESC
             LIMIT '.$batas,
            $bindings
        );

        return array_map(fn (object $row): array => $this->tanpaPath($this->bentuk($row)), $rows);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function temukan(int $id): ?array
    {
        $row = DB::selectOne($this->pilih().' WHERE a.id = ?', [$id]);

        return $row ? $this->bentuk($row) : null;
    }

    public function baca(string $path): ?string
    {
        $disk = Storage::disk(self::DISK);

        return $disk->exists($path) ? $disk->get($path) : null;
    }

    /**
     * @param  array<string, mixed>  $arsip
     * @return array<string, mixed>
     */
    private function tanpaPath(array $arsip): array
    {
        unset($arsip['path']);

        return $arsip;
    }

    private function pilih(): string
    {
        return 'SELECT a.id, a.jenis, a.format, a.periode_id, p.nama AS periode_nama,
                       j.kode AS jurusan_kode, a.nama_berkas, a.path, a.ukuran, a.created_at
                FROM laporan_arsip a
                JOIN periode_pkl p ON p.id = a.periode_id
                LEFT JOIN jurusan j ON j.id = a.jurusan_id';
    }

    /**
     * @return array<string, mixed>
     */
    private function bentuk(object $row): array
    {
        $jurusan = $row->jurusan_kode === null ? null : strtoupper((string) $row->jurusan_kode);

        return [
            'id' => (int) $row->id,
            'jenis' => $row->jenis,
            'format' => $row->format,
            'periode_id' => (int) $row->periode_id,
            'periode_nama' => $row->periode_nama,
            'jurusan' => $jurusan,
            'nama_berkas' => $row->nama_berkas,
            'keterangan' => $this->exporter->keterangan($row->jenis, $jurusan),
            'ukuran' => (int) $row->ukuran,
            'path' => $row->path,
            'dibuat_pada' => gmdate('Y-m-d\TH:i:s\Z', (int) strtotime($row->created_at.' UTC')),
        ];
    }
}