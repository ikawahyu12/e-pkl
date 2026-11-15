<?php

namespace App\Http\Controllers\Api\V1\Guru;

use App\Http\Controllers\Controller;
use App\Models\Agenda;
use App\Models\Guru;
use App\Models\Instansi;
use App\Models\PenempatanPkl;
use App\Models\Siswa;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AgendaController extends Controller
{
    private const PLACEMENT_STATUSES = ['belum_mulai', 'aktif'];

    private const RETROSPECTIVE_PLACEMENT_STATUSES = ['aktif', 'selesai'];

    public function index(Request $request): JsonResponse
    {
        $guru = $this->authenticatedGuru($request);
        $filters = $request->validate([
            'dari' => ['required', 'date_format:Y-m-d'],
            'sampai' => ['required', 'date_format:Y-m-d', 'after_or_equal:dari'],
        ]);

        if (CarbonImmutable::parse($filters['dari'])->diffInDays(
            CarbonImmutable::parse($filters['sampai'])
        ) > 62) {
            throw ValidationException::withMessages([
                'sampai' => 'Rentang tanggal maksimal 62 hari.',
            ]);
        }

        $agendas = Agenda::query()
            ->with(['instansi', 'siswa.kelas'])
            ->where('guru_id', $guru->id)
            ->whereBetween('tanggal', [$filters['dari'], $filters['sampai']])
            ->orderBy('tanggal')
            ->orderBy('jam_mulai')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $agendas->map(fn (Agenda $agenda): array => $this->present($agenda))->values(),
        ]);
    }

    public function options(Request $request): JsonResponse
    {
        $guru = $this->authenticatedGuru($request);
        $filters = $request->validate([
            'tanggal' => ['sometimes', 'date_format:Y-m-d'],
        ]);

        $placements = PenempatanPkl::query()
            ->with(['instansi', 'siswa.kelas'])
            ->where('guru_id', $guru->id)
            ->whereIn('status', self::PLACEMENT_STATUSES)
            ->whereHas('instansi', fn (Builder $query) => $query->where('status', 'aktif'))
            ->whereHas('siswa', fn (Builder $query) => $query->where('status_siswa', 'aktif'))
            ->when(isset($filters['tanggal']), fn (Builder $query) => $query
                ->whereDate('tanggal_mulai', '<=', $filters['tanggal'])
                ->whereDate('tanggal_selesai', '>=', $filters['tanggal']))
            ->get();

        return response()->json([
            'success' => true,
            'data' => $this->placementOptions($placements),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $guru = $this->authenticatedGuru($request);
        $data = $request->validate([
            'instansi_id' => ['required', 'integer', 'min:1'],
            'siswa_ids' => ['required', 'array', 'min:1', 'max:100'],
            'siswa_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
            'tanggal' => ['required', 'date_format:Y-m-d'],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai'],
            'jenis_pencatatan' => ['sometimes', Rule::in(['terencana', 'susulan'])],
            'konfirmasi_sudah_dilaksanakan' => [
                'exclude_unless:jenis_pencatatan,susulan',
                Rule::requiredIf($request->input('jenis_pencatatan') === 'susulan'),
                'accepted',
            ],
            'catatan' => ['nullable', 'string', 'max:300'],
        ]);
        $recordingType = $data['jenis_pencatatan'] ?? 'terencana';
        $now = CarbonImmutable::now('Asia/Jakarta');
        $today = $now->startOfDay();
        $visitDate = CarbonImmutable::createFromFormat('!Y-m-d', $data['tanggal'], 'Asia/Jakarta');

        if ($recordingType === 'terencana' && $visitDate->lt($today)) {
            $this->fail('tanggal', 'Agenda terencana tidak boleh menggunakan tanggal lampau.');
        }

        if ($recordingType === 'susulan') {
            $visitEnd = CarbonImmutable::createFromFormat(
                '!Y-m-d H:i',
                $data['tanggal'].' '.$data['jam_selesai'],
                'Asia/Jakarta'
            );
            if ($visitEnd->gte($now)) {
                $this->fail(
                    'tanggal',
                    'Pencatatan susulan hanya dapat dilakukan setelah waktu kunjungan berlalu.'
                );
            }
        } elseif ($request->exists('konfirmasi_sudah_dilaksanakan')) {
            $this->fail(
                'konfirmasi_sudah_dilaksanakan',
                'Konfirmasi pelaksanaan hanya berlaku untuk pencatatan susulan.'
            );
        }

        $studentIds = array_values(array_unique(array_map('intval', $data['siswa_ids'])));
        sort($studentIds, SORT_NUMERIC);

        $agenda = DB::transaction(function () use ($guru, $data, $studentIds, $recordingType): Agenda {
            $lockedGuru = $this->lockActiveGuru($guru);

            $students = Siswa::query()
                ->whereIn('id', $studentIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            if (
                $students->count() !== count($studentIds)
                || $students->contains(fn (Siswa $student): bool => $student->status_siswa !== 'aktif')
            ) {
                $this->fail('siswa_ids', 'Semua siswa harus aktif dan tersedia.');
            }

            $instance = Instansi::query()->lockForUpdate()->find($data['instansi_id']);
            if (! $instance || $instance->status !== 'aktif') {
                $this->fail('instansi_id', 'Instansi tidak ditemukan atau tidak aktif.');
            }

            $placements = PenempatanPkl::query()
                ->where('guru_id', $lockedGuru->id)
                ->where('instansi_id', $instance->id)
                ->whereIn('siswa_id', $studentIds)
                ->whereIn(
                    'status',
                    $recordingType === 'susulan'
                        ? self::RETROSPECTIVE_PLACEMENT_STATUSES
                        : self::PLACEMENT_STATUSES
                )
                ->whereDate('tanggal_mulai', '<=', $data['tanggal'])
                ->whereDate('tanggal_selesai', '>=', $data['tanggal'])
                ->orderBy('siswa_id')
                ->lockForUpdate()
                ->get(['id', 'siswa_id']);

            $placedStudentIds = $placements->pluck('siswa_id')
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->sort()
                ->values()
                ->all();
            if ($placedStudentIds !== $studentIds) {
                $this->fail(
                    'siswa_ids',
                    'Setiap siswa harus memiliki penempatan aktif atau belum mulai pada Guru, Instansi, dan tanggal yang dipilih.'
                );
            }

            $this->assertNoScheduleConflict(
                $lockedGuru->id,
                $studentIds,
                $data['tanggal'],
                $data['jam_mulai'],
                $data['jam_selesai']
            );

            $agenda = Agenda::query()->create([
                'guru_id' => $lockedGuru->id,
                'instansi_id' => $instance->id,
                'tanggal' => $data['tanggal'],
                'jam_mulai' => $data['jam_mulai'],
                'jam_selesai' => $data['jam_selesai'],
                'jenis_pencatatan' => $recordingType,
                'status' => $recordingType === 'susulan' ? 'selesai' : 'terjadwal',
                'catatan' => $data['catatan'] ?? null,
            ]);
            $agenda->siswa()->sync($studentIds);

            return $agenda->load(['instansi', 'siswa.kelas']);
        }, attempts: 3);

        return response()->json([
            'success' => true,
            'message' => 'Agenda kunjungan berhasil ditambahkan.',
            'data' => $this->present($agenda),
        ], 201);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $guru = $this->authenticatedGuru($request);
        $data = $request->validate([
            'status' => ['required', Rule::in(['selesai', 'dibatalkan'])],
            'alasan_pembatalan' => [
                Rule::requiredIf($request->input('status') === 'dibatalkan'),
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $agenda = DB::transaction(function () use ($guru, $id, $data): Agenda {
            $lockedGuru = $this->lockActiveGuru($guru);
            $agenda = Agenda::query()
                ->where('guru_id', $lockedGuru->id)
                ->lockForUpdate()
                ->findOrFail($id);

            if ($agenda->status !== 'terjadwal') {
                $this->fail('status', 'Agenda yang selesai atau dibatalkan tidak dapat diubah kembali.');
            }

            if ($data['status'] === 'selesai') {
                $now = CarbonImmutable::now('Asia/Jakarta');
                $scheduledEnd = CarbonImmutable::parse(
                    $agenda->tanggal->toDateString().' '.$agenda->jam_selesai,
                    'Asia/Jakarta'
                );
                if ($scheduledEnd->gt($now)) {
                    $this->fail('status', 'Agenda hanya dapat ditandai selesai setelah waktu kunjungan berlalu.');
                }
            }

            $agenda->status = $data['status'];
            if ($data['status'] === 'dibatalkan') {
                $reason = trim((string) ($data['alasan_pembatalan'] ?? ''));
                if ($reason === '') {
                    $this->fail('alasan_pembatalan', 'Alasan pembatalan wajib diisi.');
                }
                $agenda->alasan_pembatalan = $reason;
                $agenda->dibatalkan_at = now();
                $agenda->dibatalkan_oleh_user_id = (int) $lockedGuru->user_id;
            }
            $agenda->save();

            return $agenda->load(['instansi', 'siswa.kelas']);
        }, attempts: 3);

        return response()->json([
            'success' => true,
            'message' => $agenda->status === 'dibatalkan'
                ? 'Agenda dibatalkan dan disimpan dalam histori.'
                : 'Agenda ditandai selesai.',
            'data' => $this->present($agenda),
        ]);
    }

    private function authenticatedGuru(Request $request): Guru
    {
        $user = $request->user()->loadMissing('role');
        $guru = Guru::query()->where('user_id', $user->id)->first();
        if (
            $user->role?->nama !== 'guru'
            || $user->status !== 'aktif'
            || ! $guru
            || $guru->status_bimbingan !== 'aktif'
        ) {
            abort(404);
        }

        return $guru;
    }

    private function lockActiveGuru(Guru $guru): Guru
    {
        $user = User::query()
            ->with('role')
            ->lockForUpdate()
            ->findOrFail($guru->user_id);
        $lockedGuru = Guru::query()->lockForUpdate()->findOrFail($guru->id);

        if (
            $user->role?->nama !== 'guru'
            || $user->status !== 'aktif'
            || (int) $lockedGuru->user_id !== (int) $user->id
            || $lockedGuru->status_bimbingan !== 'aktif'
        ) {
            $this->fail('guru', 'Akun Guru tidak aktif atau tidak lagi terhubung ke profil.');
        }

        return $lockedGuru;
    }

    private function assertNoScheduleConflict(
        int $guruId,
        array $studentIds,
        string $date,
        string $start,
        string $end
    ): void {
        $conflict = Agenda::query()
            ->whereDate('tanggal', $date)
            ->whereIn('status', ['terjadwal', 'selesai'])
            ->where('jam_mulai', '<', $end)
            ->where('jam_selesai', '>', $start)
            ->where(function (Builder $query) use ($guruId, $studentIds): void {
                $query->where('guru_id', $guruId)
                    ->orWhereHas('siswa', fn (Builder $studentQuery) => $studentQuery
                        ->whereIn('siswa.id', $studentIds));
            })
            ->exists();

        if ($conflict) {
            $this->fail('jam_mulai', 'Jadwal bertabrakan dengan agenda Guru atau siswa yang sudah ada.');
        }
    }

    private function placementOptions(Collection $placements): array
    {
        return $placements
            ->filter(fn (PenempatanPkl $placement): bool => $placement->instansi !== null
                && $placement->siswa !== null)
            ->groupBy(fn (PenempatanPkl $placement): int => (int) $placement->instansi_id)
            ->map(function (Collection $group): array {
                $instance = $group->first()->instansi;

                return [
                    'instansi_id' => (int) $instance->id,
                    'nama' => $instance->nama,
                    'alamat' => $instance->alamat,
                    'siswa' => $group
                        ->map(fn (PenempatanPkl $placement): array => [
                            'id' => (int) $placement->siswa->id,
                            'nama' => $placement->siswa->nama_lengkap,
                            'kelas' => $placement->siswa->kelas?->nama,
                        ])
                        ->unique('id')
                        ->values()
                        ->all(),
                ];
            })
            ->sortBy('nama')
            ->values()
            ->all();
    }

    private function present(Agenda $agenda): array
    {
        return [
            'id' => (int) $agenda->id,
            'jenis_pencatatan' => $agenda->jenis_pencatatan,
            'tanggal' => $agenda->tanggal->toDateString(),
            'jam_mulai' => substr((string) $agenda->jam_mulai, 0, 5),
            'jam_selesai' => substr((string) $agenda->jam_selesai, 0, 5),
            'status' => $agenda->status,
            'catatan' => $agenda->catatan,
            'alasan_pembatalan' => $agenda->alasan_pembatalan,
            'dibatalkan_at' => $agenda->dibatalkan_at?->toISOString(),
            'perusahaan' => $agenda->instansi?->nama,
            'alamat' => $agenda->instansi?->alamat,
            'siswa' => $agenda->siswa
                ->map(fn (Siswa $student): array => [
                    'id' => (int) $student->id,
                    'nama' => $student->nama_lengkap,
                    'kelas' => $student->kelas?->nama,
                ])
                ->values()
                ->all(),
        ];
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
