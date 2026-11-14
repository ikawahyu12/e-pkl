<?php

// Simpan di: app/Http/Controllers/Api/V1/Guru/AgendaController.php
//
// Route (routes/api.php), di dalam grup 'v1/guru' yang sudah ada:
//   use App\Http\Controllers\Api\V1\Guru\AgendaController;
//
//   Route::get('/agenda', [AgendaController::class, 'index']);
//   Route::get('/agenda/opsi', [AgendaController::class, 'opsi']);   // harus sebelum {id}
//   Route::post('/agenda', [AgendaController::class, 'store']);
//   Route::patch('/agenda/{id}/status', [AgendaController::class, 'updateStatus']);
//   Route::delete('/agenda/{id}', [AgendaController::class, 'destroy']);
//
// ASUMSI yang perlu kamu cek:
//   - model Instansi punya kolom nama dan alamat
//   - tabel penempatan_pkl punya kolom siswa_id dan guru_id, dan relasi instansi()
//   - model Siswa punya kolom nama_lengkap dan relasi kelas()

namespace App\Http\Controllers\Api\V1\Guru;

use App\Http\Controllers\Controller;
use App\Models\Agenda;
use App\Models\Guru;
use App\Models\PenempatanPkl;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AgendaController extends Controller
{
    /** GET /guru/agenda?dari=YYYY-MM-DD&sampai=YYYY-MM-DD */
    public function index(Request $request): JsonResponse
    {
        $guru = $this->guru($request);
        if (! $guru) {
            return $this->guruTidakDitemukan();
        }

        $data = $request->validate([
            'dari'   => ['required', 'date_format:Y-m-d'],
            'sampai' => ['required', 'date_format:Y-m-d', 'after_or_equal:dari'],
        ]);

        if (Carbon::parse($data['dari'])->diffInDays(Carbon::parse($data['sampai'])) > 62) {
            return response()->json([
                'success' => false,
                'message' => 'Rentang tanggal maksimal 62 hari.',
            ], 422);
        }

        $agenda = Agenda::with(['instansi', 'siswa.kelas'])
            ->where('guru_id', $guru->id)
            ->whereBetween('tanggal', [$data['dari'], $data['sampai']])
            ->orderBy('tanggal')
            ->orderBy('jam_mulai')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $agenda->map(fn (Agenda $a) => $this->bentuk($a))->values(),
        ]);
    }

    /** GET /guru/agenda/opsi : tempat PKL beserta siswa bimbingan di tiap tempat. */
    public function opsi(Request $request): JsonResponse
    {
        $guru = $this->guru($request);
        if (! $guru) {
            return $this->guruTidakDitemukan();
        }

        return response()->json([
            'success' => true,
            'data'    => $this->opsiGuru($guru),
        ]);
    }

    /** POST /guru/agenda */
    public function store(Request $request): JsonResponse
    {
        $guru = $this->guru($request);
        if (! $guru) {
            return $this->guruTidakDitemukan();
        }

        $data = $request->validate([
            'instansi_id'   => ['required', 'integer'],
            'siswa_ids'     => ['required', 'array', 'min:1'],
            'siswa_ids.*'   => ['integer', 'distinct'],
            'tanggal'       => ['required', 'date_format:Y-m-d'],
            'jam_mulai'     => ['required', 'date_format:H:i'],
            'jam_selesai'   => ['required', 'date_format:H:i', 'after:jam_mulai'],
            'catatan'       => ['nullable', 'string', 'max:300'],
        ]);

        // Pastikan tempat dan siswa memang bimbingan guru ini.
        $tempat = $this->opsiGuru($guru)->firstWhere('instansi_id', $data['instansi_id']);
        if (! $tempat) {
            return $this->tolak('Tempat PKL ini bukan bagian dari bimbingan Anda.');
        }

        $idSah = collect($tempat['siswa'])->pluck('id');
        if (collect($data['siswa_ids'])->diff($idSah)->isNotEmpty()) {
            return $this->tolak('Ada siswa yang bukan bimbingan Anda di tempat ini.');
        }

        $agenda = DB::transaction(function () use ($guru, $data) {
            $agenda = Agenda::create([
                'guru_id'     => $guru->id,
                'instansi_id' => $data['instansi_id'],
                'tanggal'     => $data['tanggal'],
                'jam_mulai'   => $data['jam_mulai'],
                'jam_selesai' => $data['jam_selesai'],
                'status'      => 'terjadwal',
                'catatan'     => $data['catatan'] ?? null,
            ]);
            $agenda->siswa()->sync($data['siswa_ids']);

            return $agenda;
        });

        $agenda->load(['instansi', 'siswa.kelas']);

        return response()->json([
            'success' => true,
            'message' => 'Jadwal berhasil ditambahkan',
            'data'    => $this->bentuk($agenda),
        ], 201);
    }

    /** PATCH /guru/agenda/{id}/status */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $guru = $this->guru($request);
        if (! $guru) {
            return $this->guruTidakDitemukan();
        }

        $data = $request->validate([
            'status' => ['required', 'in:terjadwal,selesai'],
        ]);

        $agenda = Agenda::with(['instansi', 'siswa.kelas'])
            ->where('guru_id', $guru->id)
            ->find($id);

        if (! $agenda) {
            return $this->agendaTidakDitemukan();
        }

        $agenda->update(['status' => $data['status']]);

        return response()->json([
            'success' => true,
            'message' => 'Status agenda diperbarui',
            'data'    => $this->bentuk($agenda->fresh(['instansi', 'siswa.kelas'])),
        ]);
    }

    /** DELETE /guru/agenda/{id} */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $guru = $this->guru($request);
        if (! $guru) {
            return $this->guruTidakDitemukan();
        }

        $agenda = Agenda::where('guru_id', $guru->id)->find($id);

        if (! $agenda) {
            return $this->agendaTidakDitemukan();
        }

        $agenda->siswa()->detach();
        $agenda->delete();

        return response()->json([
            'success' => true,
            'message' => 'Jadwal dihapus',
        ]);
    }

    // ---------- bantu ----------

    private function guru(Request $request): ?Guru
    {
        return Guru::where('user_id', $request->user()->id)->first();
    }

    /** Tempat PKL guru ini, dikelompokkan per instansi, lengkap dengan siswanya. */
    private function opsiGuru(Guru $guru): Collection
    {
        $penempatan = PenempatanPkl::with('instansi')
            ->where('guru_id', $guru->id)
            ->where('status', '!=', 'dibatalkan')
            ->get();

        $siswa = Siswa::with('kelas')
            ->whereIn('id', $penempatan->pluck('siswa_id')->unique())
            ->get()
            ->keyBy('id');

        return $penempatan
            ->filter(fn ($p) => $p->instansi && $siswa->has($p->siswa_id))
            ->groupBy(fn ($p) => $p->instansi->id)
            ->map(function (Collection $grup) use ($siswa) {
                $instansi = $grup->first()->instansi;

                return [
                    'instansi_id' => $instansi->id,
                    'nama'        => $instansi->nama,
                    'alamat'      => $instansi->alamat,
                    'siswa'       => $grup
                        ->map(fn ($p) => [
                            'id'    => $p->siswa_id,
                            'nama'  => $siswa[$p->siswa_id]->nama_lengkap,
                            'kelas' => $siswa[$p->siswa_id]->kelas?->nama,
                        ])
                        ->unique('id')
                        ->values(),
                ];
            })
            ->sortBy('nama')
            ->values();
    }

    /** Bentuk respons; key dibaca oleh AgendaItem.fromJson di Flutter. */
    private function bentuk(Agenda $a): array
    {
        return [
            'id'          => $a->id,
            'tanggal'     => $a->tanggal->toDateString(),
            'jam_mulai'   => substr((string) $a->jam_mulai, 0, 5),
            'jam_selesai' => substr((string) $a->jam_selesai, 0, 5),
            'status'      => $a->status,
            'catatan'     => $a->catatan,
            'perusahaan'  => $a->instansi?->nama,
            'alamat'      => $a->instansi?->alamat,
            'siswa'       => $a->siswa
                ->map(fn ($s) => [
                    'id'    => $s->id,
                    'nama'  => $s->nama_lengkap,
                    'kelas' => $s->kelas?->nama,
                ])
                ->values(),
        ];
    }

    private function tolak(string $pesan): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $pesan], 422);
    }

    private function guruTidakDitemukan(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Data guru tidak ditemukan',
        ], 404);
    }

    private function agendaTidakDitemukan(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Agenda tidak ditemukan',
        ], 404);
    }
}