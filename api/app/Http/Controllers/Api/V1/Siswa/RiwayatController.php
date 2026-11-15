<?php

namespace App\Http\Controllers\Api\V1\Siswa;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RiwayatController extends Controller
{
    /** Minggu ke-N dalam bulan, berdasarkan tanggal (1-7, 8-14, 15-21, 22-28, 29-31) */
    private const MINGGU = [
        1 => [1, 7],
        2 => [8, 14],
        3 => [15, 21],
        4 => [22, 28],
        5 => [29, 31],
    ];

    private const LABEL_STATUS_MASUK = [
        'tepat_waktu'         => 'Tepat waktu',
        'terlambat'           => 'Terlambat',
        'di_luar_radius'      => 'Di luar radius',
    ];

    private const LABEL_STATUS_PULANG = [
        'sesuai'              => 'Sesuai',
        'di_luar_radius'      => 'Di luar radius',
    ];

    /**
     * GET /v1/siswa/riwayat?tipe=semua|presensi|jurnal&minggu=1-5&bulan=1-12&tahun=&page=&per_page=
     */
    public function index(Request $request): JsonResponse
    {
        $siswaId = DB::table('siswa')->where('user_id', $request->user()->id)->value('id');
        if (!$siswaId) {
            return response()->json(['success' => false, 'message' => 'Data siswa untuk akun ini tidak ditemukan.'], 404);
        }

        $tipe  = $request->query('tipe');
        $tipe  = in_array($tipe, ['presensi', 'jurnal'], true) ? $tipe : 'semua';
        $bulan = (int) $request->query('bulan');
        $bulan = ($bulan >= 1 && $bulan <= 12) ? $bulan : null;
        $tahun = (int) $request->query('tahun') ?: ($bulan ? (int) now()->year : null);
        $minggu = (int) $request->query('minggu');
        $minggu = isset(self::MINGGU[$minggu]) ? $minggu : null;

        $filter = function ($q, string $kolom) use ($bulan, $tahun, $minggu) {
            if ($bulan) {
                $q->whereMonth($kolom, $bulan);
            }
            if ($tahun) {
                $q->whereYear($kolom, $tahun);
            }
            if ($minggu) {
                [$a, $b] = self::MINGGU[$minggu];
                $q->whereRaw("DAY($kolom) BETWEEN ? AND ?", [$a, $b]);
            }
            return $q;
        };

        $items = collect();

        if ($tipe !== 'jurnal') {
            $presensi = $filter(
                DB::table('presensi as pr')
                    ->join('penempatan_pkl as p', 'p.id', '=', 'pr.penempatan_id')
                    ->where('p.siswa_id', $siswaId)
                    ->select([
                        'pr.id', 'pr.tanggal', 'pr.waktu_masuk', 'pr.waktu_pulang',
                        'pr.status_masuk', 'pr.status_pulang', 'pr.status_harian', 'pr.catatan',
                    ]),
                'pr.tanggal'
            )->get();

            foreach ($presensi as $r) {
                if ($r->waktu_masuk) {
                    $items->push($this->item(
                        "presensi-{$r->id}-masuk", 'presensi', 'masuk', 'Presensi: Masuk',
                        $r->tanggal, $r->waktu_masuk, 'Masuk',
                        self::LABEL_STATUS_MASUK[$r->status_masuk] ?? null
                    ));
                }
                if ($r->waktu_pulang) {
                    $items->push($this->item(
                        "presensi-{$r->id}-pulang", 'presensi', 'pulang', 'Presensi: Pulang',
                        $r->tanggal, $r->waktu_pulang, 'Pulang',
                        self::LABEL_STATUS_PULANG[$r->status_pulang] ?? null
                    ));
                }
                // Izin / sakit / alpha tidak punya jam masuk-pulang
                if (!$r->waktu_masuk && !$r->waktu_pulang && in_array($r->status_harian, ['izin', 'sakit', 'alpha'], true)) {
                    $label = ucfirst($r->status_harian);
                    $items->push($this->item(
                        "presensi-{$r->id}-{$r->status_harian}", 'presensi', $r->status_harian, "Presensi: {$label}",
                        $r->tanggal, null, $label, $r->catatan
                    ));
                }
            }
        }

        if ($tipe !== 'presensi') {
            $jurnal = $filter(
                DB::table('jurnal as j')
                    ->join('penempatan_pkl as p', 'p.id', '=', 'j.penempatan_id')
                    ->where('p.siswa_id', $siswaId)
                    ->select(['j.id', 'j.tanggal', 'j.judul', 'j.created_at']),
                'j.tanggal'
            )->get();

            foreach ($jurnal as $r) {
                $items->push($this->item(
                    "jurnal-{$r->id}", 'jurnal', 'jurnal', 'Laporan Jurnal Harian',
                    $r->tanggal, $r->created_at, 'Terkirim',
                    $r->judul, $r->id
                ));
            }
        }

        // Terbaru dulu
        $items = $items->sortByDesc('urut')->values();

        $perPage = min(max((int) $request->query('per_page', 20), 1), 100);
        $page    = max((int) $request->query('page', 1), 1);
        $total   = $items->count();

        $data = $items->slice(($page - 1) * $perPage, $perPage)->map(function ($i) {
            unset($i['urut']);
            return $i;
        })->values();

        return response()->json([
            'success' => true,
            'data'    => $data,
            'meta'    => [
                'page'      => $page,
                'per_page'  => $perPage,
                'total'     => $total,
                'last_page' => max((int) ceil($total / $perPage), 1),
            ],
        ]);
    }

    /** Bentuk satu baris riwayat yang seragam untuk presensi maupun jurnal */
    private function item(
        string $key,
        string $tipe,
        string $jenis,
        string $judul,
        string $tanggal,
        ?string $waktu,
        string $badge,
        ?string $keterangan = null,
        ?int $refId = null
    ): array {
        $tgl  = Carbon::parse($tanggal);
        $jam  = $waktu ? Carbon::parse($waktu)->format('H:i') : null;

        return [
            'key'           => $key,
            'tipe'          => $tipe,           // presensi | jurnal
            'jenis'         => $jenis,          // masuk | pulang | izin | sakit | alpha | jurnal
            'judul'         => $judul,
            'tanggal'       => $tgl->toDateString(),
            'tanggal_label' => $tgl->locale('id')->translatedFormat('l, d M Y'),
            'jam'           => $jam,
            'badge'         => $badge,
            'keterangan'    => $keterangan,
            'ref_id'        => $refId,
            'urut'          => $tgl->toDateString() . ' ' . ($jam ?? '00:00'),
        ];
    }
}