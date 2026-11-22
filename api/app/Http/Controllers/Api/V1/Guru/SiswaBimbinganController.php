<?php

namespace App\Http\Controllers\Api\V1\Guru;

use App\Http\Controllers\Controller;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SiswaBimbinganController extends Controller
{
    /**
     * Menampilkan daftar siswa bimbingan guru.
     */
    public function index(Request $request): JsonResponse
    {
        $guru = $request->user()->guru;

        if (! $guru || $guru->status_bimbingan !== 'aktif') {
            return response()->json([
                'success' => false,
                'message' => 'Data Guru tidak ditemukan atau tidak aktif.',
            ], 403);
        }

        $query = $this->siswaBimbinganQuery($guru->id);

        $search = trim((string) $request->query('search', ''));

        if ($search !== '') {
            $query->where(function ($where) use ($search) {
                $where->where('s.nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('s.nis', 'like', "%{$search}%")
                    ->orWhere('s.nisn', 'like', "%{$search}%")
                    ->orWhere('i.nama', 'like', "%{$search}%");
            });
        }

        $perPage = min(
            max((int) $request->query('per_page', 20), 1),
            100
        );

        $siswa = $query
            ->orderBy('s.nama_lengkap')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar siswa bimbingan berhasil dimuat.',
            'data' => $siswa->items(),
            'meta' => [
                'current_page' => $siswa->currentPage(),
                'per_page' => $siswa->perPage(),
                'total' => $siswa->total(),
                'last_page' => $siswa->lastPage(),
            ],
        ]);
    }

    /**
     * Menampilkan detail siswa bimbingan.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $guru = $request->user()->guru;

        if (! $guru || $guru->status_bimbingan !== 'aktif') {
            return response()->json([
                'success' => false,
                'message' => 'Data Guru tidak ditemukan atau tidak aktif.',
            ], 403);
        }

        $siswa = $this->siswaBimbinganQuery($guru->id)
            ->where('s.id', $id)
            ->first();

        if (! $siswa) {
            return response()->json([
                'success' => false,
                'message' => 'Siswa bimbingan tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail siswa bimbingan berhasil dimuat.',
            'data' => $siswa,
        ]);
    }

    /**
     * Mengambil siswa berdasarkan guru yang sedang login.
     */
    private function siswaBimbinganQuery(int $guruId): Builder
    {
        // Mengambil penempatan terbaru yang tidak dibatalkan.
        $latestPlacementIds = DB::table('penempatan_pkl')
            ->selectRaw('MAX(id)')
            ->where('guru_id', $guruId)
            ->whereIn('status', [
                'belum_mulai',
                'aktif',
                'selesai',
            ])
            ->groupBy('siswa_id');

        // Rekap kehadiran setiap penempatan.
        $rekapPresensi = DB::table('presensi')
            ->select('penempatan_id')
            ->selectRaw("
                SUM(CASE WHEN status_harian = 'hadir'
                    THEN 1 ELSE 0 END) AS jumlah_hadir
            ")
            ->selectRaw("
                COUNT(CASE WHEN status_harian IN
                    ('hadir', 'izin', 'sakit', 'alpha')
                    THEN 1 END) AS total_presensi
            ")
            ->groupBy('penempatan_id');

        // Status presensi terakhir siswa.
        $statusTerakhir = DB::table('presensi as p')
            ->select('p.status_harian')
            ->whereColumn('p.penempatan_id', 'pp.id')
            ->orderByDesc('p.tanggal')
            ->orderByDesc('p.id')
            ->limit(1);

        return DB::table('penempatan_pkl as pp')
            ->join('siswa as s', 's.id', '=', 'pp.siswa_id')
            ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
            ->leftJoin('instansi as i', 'i.id', '=', 'pp.instansi_id')
            ->leftJoin('pic_instansi as pic', 'pic.id', '=', 'pp.pic_instansi_id')
            ->leftJoin('periode_pkl as periode', 'periode.id', '=', 'pp.periode_id')
            ->leftJoinSub($rekapPresensi, 'rekap', function ($join) {
                $join->on('rekap.penempatan_id', '=', 'pp.id');
            })
            ->whereIn('pp.id', $latestPlacementIds)
            ->select([
                's.id as id',
                's.nama_lengkap as nama',
                's.nis',
                's.nisn',
                'k.nama as kelas',
                'i.nama as instansi',
                'pic.nama as pembimbing_industri',
                'periode.nama as periode_pkl',
                'pp.id as penempatan_id',
                'pp.status as status_pkl',
                'pp.tanggal_mulai',
                'pp.tanggal_selesai',
            ])
            ->selectRaw("
                COALESCE(
                    ({$statusTerakhir->toSql()}),
                    'belum_lengkap'
                ) AS status
            ", $statusTerakhir->getBindings())
            ->selectRaw("
                CASE
                    WHEN COALESCE(rekap.total_presensi, 0) = 0
                    THEN 0
                    ELSE ROUND(
                        rekap.jumlah_hadir * 100.0 /
                        rekap.total_presensi
                    )
                END AS persentase_kehadiran
            ");
    }
}
