<?php

namespace App\Http\Controllers;

use App\Models\Instansi;
use App\Models\KuotaInstansiPeriode;
use App\Models\PenempatanPkl;
use App\Models\PeriodePkl;
use App\Models\PicInstansi;
use App\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminMasterDataController extends Controller
{
    private const ACTIVE_PLACEMENT_STATUSES = ['belum_mulai', 'aktif'];

    public function instansiIndex(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'nullable', Rule::in(['aktif', 'nonaktif'])],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $instances = Instansi::query()
            ->when(! empty($filters['search']), function (Builder $query) use ($filters): void {
                $search = trim($filters['search']);
                $query->where(function (Builder $instanceQuery) use ($search): void {
                    $instanceQuery->where('nama', 'like', '%'.$search.'%')
                        ->orWhere('alamat', 'like', '%'.$search.'%')
                        ->orWhere('nama_penanggung_jawab', 'like', '%'.$search.'%');
                });
            })
            ->when(isset($filters['status']), fn (Builder $query) => $query->where('status', $filters['status']))
            ->orderBy('nama')
            ->paginate((int) ($filters['per_page'] ?? 15));

        return $this->paginated('instansi', $instances);
    }

    public function instansiStore(Request $request): JsonResponse
    {
        $data = $this->validateInstansi($request);
        $instance = new Instansi();
        $instance->forceFill($data)->save();

        return $this->success(['instansi' => $instance->fresh()], 'Instansi berhasil ditambahkan.', 201);
    }

    public function instansiShow(int $instansi): JsonResponse
    {
        $instance = Instansi::query()
            ->with([
                'picInstansi' => fn (Builder $query) => $query->orderBy('nama'),
                'kuotaPeriode.periode',
            ])
            ->findOrFail($instansi);

        return $this->success(['instansi' => $instance]);
    }

    public function instansiUpdate(Request $request, int $instansi): JsonResponse
    {
        $instance = Instansi::query()->findOrFail($instansi);
        $data = $this->validateInstansi($request, $instance->id);

        DB::transaction(function () use ($instance, $data): void {
            $locked = Instansi::query()->lockForUpdate()->findOrFail($instance->id);
            if (($data['status'] ?? $locked->status) === 'nonaktif') {
                $this->assertInstanceCanBeDisabled($locked->id);
            }
            $locked->forceFill($data)->save();
        });

        return $this->success(
            ['instansi' => $instance->fresh()],
            'Instansi berhasil diperbarui.'
        );
    }

    public function instansiUpdateStatus(Request $request, int $instansi): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['aktif', 'nonaktif'])],
        ]);

        DB::transaction(function () use ($instansi, $data): void {
            $instance = Instansi::query()->lockForUpdate()->findOrFail($instansi);
            if ($data['status'] === 'nonaktif') {
                $this->assertInstanceCanBeDisabled($instance->id);
            }

            $instance->status = $data['status'];
            $instance->save();
        });

        return $this->success(
            ['instansi' => Instansi::query()->findOrFail($instansi)],
            'Status Instansi berhasil diperbarui.'
        );
    }

    public function tahunAjaranIndex(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['sometimes', 'nullable', 'string', 'max:20'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $years = TahunAjaran::query()
            ->when(! empty($filters['search']), fn (Builder $query) => $query->where('nama', 'like', '%'.trim($filters['search']).'%'))
            ->orderByDesc('tanggal_mulai')
            ->paginate((int) ($filters['per_page'] ?? 15));

        return $this->paginated('tahun_ajaran', $years);
    }

    public function tahunAjaranStore(Request $request): JsonResponse
    {
        $data = $this->validateTahunAjaran($request);
        $year = new TahunAjaran();
        $year->forceFill($data)->save();

        return $this->success(['tahun_ajaran' => $year->fresh()], 'Tahun ajaran berhasil ditambahkan.', 201);
    }

    public function tahunAjaranShow(int $tahunAjaran): JsonResponse
    {
        return $this->success([
            'tahun_ajaran' => TahunAjaran::query()->findOrFail($tahunAjaran),
        ]);
    }

    public function tahunAjaranUpdate(Request $request, int $tahunAjaran): JsonResponse
    {
        $year = TahunAjaran::query()->findOrFail($tahunAjaran);
        $data = $this->validateTahunAjaran($request, $year->id);

        DB::transaction(function () use ($year, $data): void {
            $locked = TahunAjaran::query()->lockForUpdate()->findOrFail($year->id);
            $this->assertYearContainsPeriods($year->id, $data['tanggal_mulai'], $data['tanggal_selesai']);
            $locked->forceFill($data)->save();
        });

        return $this->success(
            ['tahun_ajaran' => $year->fresh()],
            'Tahun ajaran berhasil diperbarui.'
        );
    }

    public function periodeIndex(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'nullable', Rule::in(['persiapan', 'berjalan', 'selesai'])],
            'tahun_ajaran_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $periods = PeriodePkl::query()
            ->with('tahunAjaran')
            ->when(! empty($filters['search']), fn (Builder $query) => $query->where('nama', 'like', '%'.trim($filters['search']).'%'))
            ->when(isset($filters['status']), fn (Builder $query) => $query->where('status', $filters['status']))
            ->when(isset($filters['tahun_ajaran_id']), fn (Builder $query) => $query->where('tahun_ajaran_id', $filters['tahun_ajaran_id']))
            ->orderByDesc('tanggal_mulai')
            ->paginate((int) ($filters['per_page'] ?? 15));

        return $this->paginated('periode_pkl', $periods);
    }

    public function periodeStore(Request $request): JsonResponse
    {
        $data = $this->validatePeriode($request);
        $period = DB::transaction(function () use ($data): PeriodePkl {
            $yearId = $data['tahun_ajaran_id'] ?? null;
            if ($yearId !== null) {
                TahunAjaran::query()->lockForUpdate()->findOrFail($yearId);
            }
            $this->assertPeriodWithinYear($yearId, $data['tanggal_mulai'], $data['tanggal_selesai']);
            $period = new PeriodePkl();
            $period->forceFill($data)->save();

            return $period;
        });

        return $this->success(['periode_pkl' => $period->fresh('tahunAjaran')], 'Periode PKL berhasil ditambahkan.', 201);
    }

    public function periodeShow(int $periode): JsonResponse
    {
        return $this->success([
            'periode_pkl' => PeriodePkl::query()->with('tahunAjaran')->findOrFail($periode),
        ]);
    }

    public function periodeUpdate(Request $request, int $periode): JsonResponse
    {
        $period = PeriodePkl::query()->findOrFail($periode);
        $data = $this->validatePeriode($request);

        DB::transaction(function () use ($period, $data): void {
            $locked = PeriodePkl::query()->lockForUpdate()->findOrFail($period->id);
            $yearId = $data['tahun_ajaran_id'] ?? null;
            if ($yearId !== null) {
                TahunAjaran::query()->lockForUpdate()->findOrFail($yearId);
            }
            $this->assertPeriodWithinYear($yearId, $data['tanggal_mulai'], $data['tanggal_selesai']);
            $this->assertPeriodChangePreservesPlacements($locked->id, $data);
            $locked->forceFill($data)->save();
        });

        return $this->success(
            ['periode_pkl' => $period->fresh('tahunAjaran')],
            'Periode PKL berhasil diperbarui.'
        );
    }

    public function picIndex(int $instansi): JsonResponse
    {
        Instansi::query()->findOrFail($instansi);
        $pics = PicInstansi::query()
            ->where('instansi_id', $instansi)
            ->orderBy('nama')
            ->paginate(15);

        return $this->paginated('pic_instansi', $pics);
    }

    public function picStore(Request $request, int $instansi): JsonResponse
    {
        Instansi::query()->findOrFail($instansi);
        $data = $this->validatePic($request);
        $pic = new PicInstansi();
        $pic->forceFill($data + ['instansi_id' => $instansi, 'aktif' => true])->save();

        return $this->success(['pic_instansi' => $pic->fresh()], 'PIC Instansi berhasil ditambahkan.', 201);
    }

    public function picUpdate(Request $request, int $instansi, int $pic): JsonResponse
    {
        Instansi::query()->findOrFail($instansi);
        $contact = PicInstansi::query()
            ->where('instansi_id', $instansi)
            ->findOrFail($pic);
        $data = $this->validatePic($request);
        $contact->forceFill($data)->save();

        return $this->success(['pic_instansi' => $contact->fresh()], 'PIC Instansi berhasil diperbarui.');
    }

    public function picUpdateStatus(Request $request, int $instansi, int $pic): JsonResponse
    {
        Instansi::query()->findOrFail($instansi);
        $data = $request->validate(['aktif' => ['required', 'boolean']]);
        $contact = PicInstansi::query()
            ->where('instansi_id', $instansi)
            ->findOrFail($pic);
        $contact->aktif = (bool) $data['aktif'];
        $contact->save();

        return $this->success(['pic_instansi' => $contact->fresh()], 'Status PIC Instansi berhasil diperbarui.');
    }

    public function kuotaIndex(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'instansi_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'periode_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $quotas = KuotaInstansiPeriode::query()
            ->with(['instansi', 'periode'])
            ->when(isset($filters['instansi_id']), fn (Builder $query) => $query->where('instansi_id', $filters['instansi_id']))
            ->when(isset($filters['periode_id']), fn (Builder $query) => $query->where('periode_id', $filters['periode_id']))
            ->orderBy('instansi_id')
            ->orderBy('periode_id')
            ->paginate((int) ($filters['per_page'] ?? 15));

        return $this->paginated('kuota', $quotas);
    }

    public function kuotaUpsert(Request $request, int $instansi, int $periode): JsonResponse
    {
        $data = $request->validate([
            'kuota_siswa' => ['present', 'nullable', 'integer', 'min:0', 'max:4294967295'],
        ]);
        $quota = DB::transaction(function () use ($instansi, $periode, $data): KuotaInstansiPeriode {
            PeriodePkl::query()->lockForUpdate()->findOrFail($periode);
            Instansi::query()->lockForUpdate()->findOrFail($instansi);
            $quota = KuotaInstansiPeriode::query()
                ->where('instansi_id', $instansi)
                ->where('periode_id', $periode)
                ->lockForUpdate()
                ->first();
            $used = PenempatanPkl::query()
                ->where('instansi_id', $instansi)
                ->where('periode_id', $periode)
                ->whereIn('status', self::ACTIVE_PLACEMENT_STATUSES)
                ->count();

            if ($data['kuota_siswa'] !== null && (int) $data['kuota_siswa'] < $used) {
                throw ValidationException::withMessages([
                    'kuota_siswa' => "Kuota tidak dapat ditetapkan di bawah jumlah penempatan aktif saat ini ($used).",
                ]);
            }

            if ($quota === null) {
                $quota = new KuotaInstansiPeriode([
                    'instansi_id' => $instansi,
                    'periode_id' => $periode,
                ]);
            }
            $quota->kuota_siswa = $data['kuota_siswa'];
            $quota->save();

            return $quota->fresh(['instansi', 'periode']);
        });

        return $this->success(['kuota' => $quota], 'Kuota Instansi per Periode berhasil disimpan.');
    }

    private function validateInstansi(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:160'],
            'jenis' => ['sometimes', 'nullable', 'string', 'max:80'],
            'alamat' => ['required', 'string'],
            'kota' => ['sometimes', 'nullable', 'string', 'max:100'],
            'provinsi' => ['sometimes', 'nullable', 'string', 'max:100'],
            'telepon' => ['sometimes', 'nullable', 'string', 'max:25'],
            'email' => ['sometimes', 'nullable', 'email', 'max:150'],
            'nama_penanggung_jawab' => ['sometimes', 'nullable', 'string', 'max:120'],
            'status' => ['sometimes', 'required', Rule::in(['aktif', 'nonaktif'])],
        ]);
    }

    private function validateTahunAjaran(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:20', Rule::unique('tahun_ajaran', 'nama')->ignore($id)],
            'tanggal_mulai' => ['required', 'date_format:Y-m-d'],
            'tanggal_selesai' => ['required', 'date_format:Y-m-d', 'after_or_equal:tanggal_mulai'],
            'aktif' => ['required', 'boolean'],
        ]);
    }

    private function validatePeriode(Request $request): array
    {
        return $request->validate([
            'tahun_ajaran_id' => ['present', 'nullable', 'integer', 'min:1', 'exists:tahun_ajaran,id'],
            'nama' => ['required', 'string', 'max:100'],
            'tanggal_mulai' => ['required', 'date_format:Y-m-d'],
            'tanggal_selesai' => ['required', 'date_format:Y-m-d', 'after_or_equal:tanggal_mulai'],
            'status' => ['required', Rule::in(['persiapan', 'berjalan', 'selesai'])],
        ]);
    }

    private function validatePic(Request $request): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:120'],
            'jabatan' => ['sometimes', 'nullable', 'string', 'max:100'],
            'no_hp' => ['sometimes', 'nullable', 'string', 'max:25'],
            'email' => ['sometimes', 'nullable', 'email', 'max:150'],
        ]);
    }

    private function assertYearContainsPeriods(int $yearId, string $start, string $end): void
    {
        $outside = PeriodePkl::query()
            ->where('tahun_ajaran_id', $yearId)
            ->where(fn (Builder $query) => $query->where('tanggal_mulai', '<', $start)->orWhere('tanggal_selesai', '>', $end))
            ->exists();

        if ($outside) {
            throw ValidationException::withMessages([
                'tanggal_mulai' => 'Rentang Tahun Ajaran tidak dapat diubah karena akan berada di luar tanggal Periode PKL yang terhubung.',
            ]);
        }
    }

    private function assertInstanceCanBeDisabled(int $instanceId): void
    {
        if (PenempatanPkl::query()
            ->where('instansi_id', $instanceId)
            ->whereIn('status', self::ACTIVE_PLACEMENT_STATUSES)
            ->exists()) {
            throw ValidationException::withMessages([
                'status' => 'Instansi tidak dapat dinonaktifkan karena masih memiliki penempatan PKL yang belum selesai.',
            ]);
        }
    }

    private function assertPeriodWithinYear(?int $yearId, string $start, string $end): void
    {
        if ($yearId === null) {
            return;
        }

        $year = TahunAjaran::query()->findOrFail($yearId);
        if ($start < $year->tanggal_mulai->toDateString() || $end > $year->tanggal_selesai->toDateString()) {
            throw ValidationException::withMessages([
                'tanggal_mulai' => 'Tanggal Periode PKL harus berada di dalam rentang Tahun Ajaran yang dipilih.',
            ]);
        }
    }

    private function assertPeriodChangePreservesPlacements(int $periodId, array $data): void
    {
        $placements = PenempatanPkl::query()->where('periode_id', $periodId);
        if ($placements->where(function (Builder $query) use ($data): void {
            $query->where('tanggal_mulai', '<', $data['tanggal_mulai'])
                ->orWhere('tanggal_selesai', '>', $data['tanggal_selesai']);
        })->exists()) {
            throw ValidationException::withMessages([
                'tanggal_mulai' => 'Rentang Periode PKL tidak dapat diubah karena akan berada di luar tanggal penempatan yang sudah tercatat.',
            ]);
        }

        if (
            $data['status'] === 'selesai'
            && PenempatanPkl::query()
                ->where('periode_id', $periodId)
                ->whereIn('status', self::ACTIVE_PLACEMENT_STATUSES)
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'status' => 'Periode tidak dapat ditandai selesai selama masih ada penempatan aktif atau belum mulai.',
            ]);
        }
    }

    private function paginated(string $key, $paginator): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Data master berhasil diambil.',
            'data' => [
                $key => $paginator->items(),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
            ],
        ]);
    }

    private function success(array $data, string $message, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }
}
