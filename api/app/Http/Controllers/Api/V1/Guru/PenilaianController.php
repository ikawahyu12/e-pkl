<?php

namespace App\Http\Controllers\Api\V1\Guru;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenilaianController extends Controller
{
    private const KOMPONEN = [
        'Sikap & Disiplin' => 'sikap_disiplin',
        'Keterampilan Kerja' => 'keterampilan_kerja',
        'Pengetahuan' => 'pengetahuan',
        'Inisiatif & Kreativitas' => 'inisiatif_kreativitas',
        'Kerjasama Tim' => 'kerjasama_tim',
    ];

    /**
     * Menampilkan penilaian siswa.
     * Siswa belum_mulai tetap dapat membuka halaman.
     */
    public function show(Request $request, int $siswaId): JsonResponse
    {
        $guru = $request->user()->guru;

        if (!$guru) {
            return $this->gagal('Data guru tidak ditemukan.', 403);
        }

        $penempatan = $this->cariPenempatan($siswaId, $guru->id);

        if (!$penempatan) {
            return $this->gagal(
                'Data penempatan siswa tidak ditemukan atau bukan siswa bimbingan Anda.',
                404
            );
        }

        $komponen = $this->ambilKomponen();

        if ($komponen->count() !== count(self::KOMPONEN)) {
            return $this->gagal(
                'Data komponen penilaian belum lengkap atau belum aktif.',
                422
            );
        }

        $details = DB::table('penilaian_detail as pd')
            ->join('komponen_penilaian as kp', 'kp.id', '=', 'pd.komponen_id')
            ->where('pd.penempatan_id', $penempatan->id)
            ->where('pd.guru_id', $guru->id)
            ->select(
                'pd.nilai',
                'pd.catatan',
                'pd.dinilai_at',
                'kp.nama as nama_komponen'
            )
            ->get();

        $nilai = [];

        foreach ($details as $detail) {
            $key = self::KOMPONEN[$detail->nama_komponen] ?? null;

            if ($key !== null) {
                $nilai[$key] = (int) round((float) $detail->nilai);
            }
        }

        $catatan = $details
            ->pluck('catatan')
            ->filter(fn ($item) => $item !== null && $item !== '')
            ->first() ?? '';

        $disimpanPada = $details
            ->pluck('dinilai_at')
            ->filter()
            ->sortDesc()
            ->first();

        return $this->berhasil([
            'siswa_id' => $siswaId,
            'status_penempatan' => $penempatan->status,
            'dapat_dinilai' => in_array(
                $penempatan->status,
                ['aktif', 'selesai'],
                true
            ),
            'nilai' => count($nilai) === count(self::KOMPONEN)
                ? $nilai
                : null,
            'catatan' => $catatan,
            'disimpan_pada' => $disimpanPada,
        ], 'Data penilaian berhasil diambil.');
    }

    /**
     * Menyimpan atau memperbarui penilaian.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'siswa_id' => [
                'required',
                'integer',
                'exists:siswa,id',
            ],

            'nilai' => [
                'required',
                'array',
            ],

            'nilai.sikap_disiplin' => [
                'required',
                'integer',
                'between:0,100',
            ],

            'nilai.keterampilan_kerja' => [
                'required',
                'integer',
                'between:0,100',
            ],

            'nilai.pengetahuan' => [
                'required',
                'integer',
                'between:0,100',
            ],

            'nilai.inisiatif_kreativitas' => [
                'required',
                'integer',
                'between:0,100',
            ],

            'nilai.kerjasama_tim' => [
                'required',
                'integer',
                'between:0,100',
            ],

            'catatan' => [
                'nullable',
                'string',
                'max:300',
            ],
        ]);

        $guru = $request->user()->guru;

        if (!$guru) {
            return $this->gagal('Data guru tidak ditemukan.', 403);
        }

        $siswaId = (int) $validated['siswa_id'];

        // Penyimpanan hanya diperbolehkan untuk PKL aktif atau selesai.
        $penempatan = $this->cariPenempatanAktif(
            $siswaId,
            $guru->id
        );

        if (!$penempatan) {
            return $this->gagal(
                'Penilaian hanya dapat disimpan untuk siswa dengan status PKL aktif atau selesai.',
                403
            );
        }

        $komponen = $this->ambilKomponen();

        if ($komponen->count() !== count(self::KOMPONEN)) {
            return $this->gagal(
                'Data komponen penilaian belum lengkap atau belum aktif.',
                422
            );
        }

        $catatanBaru = $validated['catatan'] ?? null;

        $waktuSimpan = now();

        DB::transaction(function () use (
            $validated,
            $penempatan,
            $guru,
            $komponen,
            $catatanBaru,
            $waktuSimpan
        ) {
            foreach (self::KOMPONEN as $nama => $key) {
                $komponenId = $komponen[$nama]->id;
                $nilaiBaru = $validated['nilai'][$key];

                $detail = DB::table('penilaian_detail')
                    ->where('penempatan_id', $penempatan->id)
                    ->where('komponen_id', $komponenId)
                    ->where('guru_id', $guru->id)
                    ->lockForUpdate()
                    ->first();

                if (!$detail) {
                    $detailId = DB::table('penilaian_detail')
                        ->insertGetId([
                            'penempatan_id' => $penempatan->id,
                            'komponen_id' => $komponenId,
                            'guru_id' => $guru->id,
                            'nilai' => $nilaiBaru,
                            'catatan' => $catatanBaru,
                            'dinilai_at' => $waktuSimpan,
                        ]);

                    DB::table('riwayat_penilaian')->insert([
                        'penilaian_detail_id' => $detailId,
                        'nilai_lama' => null,
                        'nilai_baru' => $nilaiBaru,
                        'catatan_lama' => null,
                        'catatan_baru' => $catatanBaru,
                        'guru_id' => $guru->id,
                        'diubah_at' => $waktuSimpan,
                        'created_at' => $waktuSimpan,
                        'updated_at' => $waktuSimpan,
                    ]);

                    continue;
                }

                $nilaiLama = (int) round((float) $detail->nilai);
                $catatanLama = $detail->catatan;

                $nilaiBerubah = $nilaiLama !== $nilaiBaru;

                $catatanBerubah =
                    (string) ($catatanLama ?? '') !==
                    (string) ($catatanBaru ?? '');

                if (!$nilaiBerubah && !$catatanBerubah) {
                    continue;
                }

                DB::table('penilaian_detail')
                    ->where('id', $detail->id)
                    ->update([
                        'nilai' => $nilaiBaru,
                        'catatan' => $catatanBaru,
                        'dinilai_at' => $waktuSimpan,
                    ]);

                DB::table('riwayat_penilaian')->insert([
                    'penilaian_detail_id' => $detail->id,
                    'nilai_lama' => $nilaiLama,
                    'nilai_baru' => $nilaiBaru,
                    'catatan_lama' => $catatanLama,
                    'catatan_baru' => $catatanBaru,
                    'guru_id' => $guru->id,
                    'diubah_at' => $waktuSimpan,
                    'created_at' => $waktuSimpan,
                    'updated_at' => $waktuSimpan,
                ]);
            }
        });

        $nilai = [];

        foreach (self::KOMPONEN as $nama => $key) {
            $nilai[$key] = (int) $validated['nilai'][$key];
        }

        return $this->berhasil([
            'siswa_id' => $siswaId,
            'nilai' => $nilai,
            'catatan' => $catatanBaru ?? '',
            'disimpan_pada' => $waktuSimpan->toDateTimeString(),
        ], 'Penilaian berhasil disimpan.');
    }

    /**
     * Mengambil riwayat penilaian.
     */
    public function riwayat(Request $request, int $siswaId): JsonResponse
    {
        $guru = $request->user()->guru;

        if (!$guru) {
            return $this->gagal('Data guru tidak ditemukan.', 403);
        }

        $penempatan = $this->cariPenempatan($siswaId, $guru->id);

        if (!$penempatan) {
            return $this->gagal(
                'Data penempatan siswa tidak ditemukan atau bukan siswa bimbingan Anda.',
                404
            );
        }

        $riwayat = DB::table('riwayat_penilaian as rp')
            ->join(
                'penilaian_detail as pd',
                'pd.id',
                '=',
                'rp.penilaian_detail_id'
            )
            ->join(
                'komponen_penilaian as kp',
                'kp.id',
                '=',
                'pd.komponen_id'
            )
            ->leftJoin('guru as g', 'g.id', '=', 'rp.guru_id')
            ->where('pd.penempatan_id', $penempatan->id)
            ->where('pd.guru_id', $guru->id)
            ->select(
                'rp.id',
                'rp.penilaian_detail_id',
                'kp.nama as nama_komponen',
                'rp.nilai_lama',
                'rp.nilai_baru',
                'rp.catatan_lama',
                'rp.catatan_baru',
                'rp.guru_id',
                'g.nama_lengkap as nama_guru',
                'rp.diubah_at'
            )
            ->orderByDesc('rp.diubah_at')
            ->orderByDesc('rp.id')
            ->get();

        return $this->berhasil(
            $riwayat,
            'Riwayat penilaian berhasil diambil.'
        );
    }

    /**
     * Mengambil komponen aktif.
     */
    private function ambilKomponen()
    {
        return DB::table('komponen_penilaian')
            ->where('aktif', 1)
            ->whereIn('nama', array_keys(self::KOMPONEN))
            ->get()
            ->keyBy('nama');
    }

    /**
     * Mencari penempatan untuk menampilkan halaman.
     */
    private function cariPenempatan(int $siswaId, int $guruId)
    {
        return DB::table('penempatan_pkl')
            ->where('siswa_id', $siswaId)
            ->where('guru_id', $guruId)
            ->whereIn('status', ['belum_mulai', 'aktif', 'selesai'])
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Mencari penempatan yang boleh dinilai.
     */
    private function cariPenempatanAktif(int $siswaId, int $guruId)
    {
        return DB::table('penempatan_pkl')
            ->where('siswa_id', $siswaId)
            ->where('guru_id', $guruId)
            ->whereIn('status', ['aktif', 'selesai'])
            ->orderByDesc('id')
            ->first();
    }

    private function berhasil(
        mixed $data,
        string $pesan = 'Berhasil.'
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $pesan,
            'data' => $data,
        ], 200);
    }

    private function gagal(
        string $pesan,
        int $status = 400
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $pesan,
            'data' => null,
        ], $status);
    }
}
