<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\Instansi;
use App\Models\KuotaInstansiPeriode;
use App\Models\PenempatanPkl;
use App\Models\PeriodePkl;
use App\Models\PicInstansi;
use App\Models\Siswa;
use App\Models\User;
use App\Services\PenempatanPklService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminPenempatanPklController extends Controller
{
    private const RELATIONS = [
        'siswa.user',
        'periode',
        'instansi',
        'guru.user',
        'pic',
        'riwayatStatus.admin.role',
        'riwayatGuru.guruLama',
        'riwayatGuru.guruBaru',
        'riwayatGuru.admin',
        'riwayatPerubahan.admin',
    ];

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'periode_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'status' => ['sometimes', 'nullable', Rule::in([
                PenempatanPklService::STATUS_BELUM_MULAI,
                PenempatanPklService::STATUS_AKTIF,
                PenempatanPklService::STATUS_SELESAI,
                PenempatanPklService::STATUS_DIBATALKAN,
            ])],
            'instansi_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'guru_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'periode_search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'instansi_search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'guru_search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);

        $query = PenempatanPkl::query()->with(self::RELATIONS);

        foreach (['periode_id', 'status', 'instansi_id', 'guru_id'] as $filter) {
            if (isset($filters[$filter]) && $filters[$filter] !== '') {
                $query->where($filter, $filters[$filter]);
            }
        }

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->whereHas('siswa', function ($studentQuery) use ($search): void {
                $studentQuery->where('nama_lengkap', 'like', '%'.$search.'%')
                    ->orWhere('nisn', 'like', '%'.$search.'%')
                    ->orWhere('nis', 'like', '%'.$search.'%');
            });
        }

        foreach ([
            'periode_search' => ['periode', 'nama'],
            'instansi_search' => ['instansi', 'nama'],
        ] as $filter => [$relation, $column]) {
            if (! empty($filters[$filter])) {
                $search = trim($filters[$filter]);
                $query->whereHas($relation, fn ($relatedQuery) => $relatedQuery
                    ->where($column, 'like', '%'.$search.'%'));
            }
        }
        if (! empty($filters['guru_search'])) {
            $search = trim($filters['guru_search']);
            $query->whereHas('guru', fn ($teacherQuery) => $teacherQuery
                ->where('nama_lengkap', 'like', '%'.$search.'%')
                ->orWhere('nip', 'like', '%'.$search.'%'));
        }

        $placements = $query
            ->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->through(fn (PenempatanPkl $placement): array => $this->placementData($placement));

        return response()->json([
            'success' => true,
            'message' => 'Daftar penempatan PKL berhasil diambil.',
            'data' => [
                'penempatan' => $placements->items(),
                'pagination' => [
                    'current_page' => $placements->currentPage(),
                    'per_page' => $placements->perPage(),
                    'total' => $placements->total(),
                    'last_page' => $placements->lastPage(),
                ],
            ],
        ]);
    }

    public function references(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'siswa_search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'guru_search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'siswa_page' => ['sometimes', 'integer', 'min:1'],
            'guru_page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'instansi_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'periode_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'siswa_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'guru_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 20);
        $students = $this->eligibleStudentQuery()
            ->when(! empty($filters['siswa_search']), function ($query) use ($filters): void {
                $search = trim($filters['siswa_search']);
                $query->where(function ($studentQuery) use ($search): void {
                    $studentQuery->where('nama_lengkap', 'like', '%'.$search.'%')
                        ->orWhere('nisn', 'like', '%'.$search.'%')
                        ->orWhere('nis', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('nama_lengkap')
            ->paginate($perPage, ['*'], 'siswa_page', (int) ($filters['siswa_page'] ?? 1));

        $teachers = $this->eligibleTeacherQuery()
            ->when(! empty($filters['guru_search']), function ($query) use ($filters): void {
                $search = trim($filters['guru_search']);
                $query->where(function ($teacherQuery) use ($search): void {
                    $teacherQuery->where('nama_lengkap', 'like', '%'.$search.'%')
                        ->orWhere('nip', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('nama_lengkap')
            ->paginate($perPage, ['*'], 'guru_page', (int) ($filters['guru_page'] ?? 1));

        $instanceId = $filters['instansi_id'] ?? null;
        $periodId = $filters['periode_id'] ?? null;
        $quota = null;
        if ($instanceId !== null && $periodId !== null) {
            $quotaRow = KuotaInstansiPeriode::query()
                ->where('instansi_id', $instanceId)
                ->where('periode_id', $periodId)
                ->first();
            $used = PenempatanPkl::query()
                ->where('instansi_id', $instanceId)
                ->where('periode_id', $periodId)
                ->whereIn('status', [
                    PenempatanPklService::STATUS_BELUM_MULAI,
                    PenempatanPklService::STATUS_AKTIF,
                ])
                ->count();
            $limit = $quotaRow?->kuota_siswa;

            $quota = [
                'instansi_id' => (int) $instanceId,
                'periode_id' => (int) $periodId,
                'configured' => $quotaRow !== null,
                'kuota_siswa' => $limit === null ? null : (int) $limit,
                'terpakai' => $used,
                'sisa' => $limit === null ? null : max(0, (int) $limit - $used),
                'tanpa_batas' => $limit === null,
            ];
        }

        $selectedStudent = isset($filters['siswa_id'])
            ? $this->eligibleStudentQuery()->whereKey($filters['siswa_id'])->first()
            : null;
        $selectedTeacher = isset($filters['guru_id'])
            ? $this->eligibleTeacherQuery()->whereKey($filters['guru_id'])->first()
            : null;

        return response()->json([
            'success' => true,
            'message' => 'Referensi penempatan PKL berhasil diambil.',
            'data' => [
                'siswa' => $students->getCollection()
                    ->map(fn (Siswa $student): array => $this->studentReference($student))
                    ->values()
                    ->all(),
                'guru' => $teachers->getCollection()
                    ->map(fn (Guru $teacher): array => $this->teacherReference($teacher))
                    ->values()
                    ->all(),
                'selected' => [
                    'siswa' => $selectedStudent ? $this->studentReference($selectedStudent) : null,
                    'guru' => $selectedTeacher ? $this->teacherReference($selectedTeacher) : null,
                ],
                'pagination' => [
                    'per_page' => $perPage,
                    'siswa' => $this->paginationData($students),
                    'guru' => $this->paginationData($teachers),
                ],
                'periode' => PeriodePkl::query()
                    ->whereIn('status', ['persiapan', 'berjalan'])
                    ->orderByDesc('tanggal_mulai')
                    ->get(['id', 'nama', 'tanggal_mulai', 'tanggal_selesai', 'status'])
                    ->map(fn (PeriodePkl $period): array => [
                        'id' => $period->id,
                        'nama' => $period->nama,
                        'tanggal_mulai' => $period->tanggal_mulai?->toDateString(),
                        'tanggal_selesai' => $period->tanggal_selesai?->toDateString(),
                        'status' => $period->status,
                    ])
                    ->all(),
                'instansi' => Instansi::query()
                    ->where('status', 'aktif')
                    ->orderBy('nama')
                    ->get(['id', 'nama'])
                    ->map(fn (Instansi $instance): array => [
                        'id' => $instance->id,
                        'nama' => $instance->nama,
                    ])
                    ->all(),
                'pic_instansi' => $instanceId === null
                    ? []
                    : PicInstansi::query()
                        ->where('instansi_id', $instanceId)
                        ->where('aktif', true)
                        ->orderBy('nama')
                        ->get(['id', 'instansi_id', 'nama', 'jabatan'])
                        ->map(fn (PicInstansi $pic): array => [
                            'id' => $pic->id,
                            'instansi_id' => $pic->instansi_id,
                            'nama' => $pic->nama,
                            'jabatan' => $pic->jabatan,
                        ])
                        ->all(),
                'kuota' => $quota,
            ],
        ]);
    }

    public function store(Request $request, PenempatanPklService $service): JsonResponse
    {
        $data = $request->validate($this->placementRules() + $this->prohibitedFields());
        $placement = $service->create($this->admin($request), $data);
        $placement->load(self::RELATIONS);

        return response()->json([
            'success' => true,
            'message' => 'Penempatan PKL berhasil dibuat.',
            'data' => ['penempatan' => $this->placementData($placement)],
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $placement = PenempatanPkl::query()
            ->with(self::RELATIONS)
            ->find($id);

        if (! $placement) {
            return $this->notFound();
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail penempatan PKL berhasil diambil.',
            'data' => ['penempatan' => $this->placementData($placement)],
        ]);
    }

    public function update(Request $request, int $id, PenempatanPklService $service): JsonResponse
    {
        $data = $request->validate([
            'pic_instansi_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'tanggal_mulai' => ['required', 'date_format:Y-m-d'],
            'tanggal_selesai' => ['required', 'date_format:Y-m-d'],
            'alasan' => ['required', 'string', 'max:5000'],
        ] + $this->prohibitedFields() + [
            'siswa_id' => ['prohibited'],
            'periode_id' => ['prohibited'],
            'instansi_id' => ['prohibited'],
        ]);

        try {
            $placement = $service->update(
                $this->admin($request),
                $id,
                array_intersect_key($data, array_flip([
                    'pic_instansi_id',
                    'tanggal_mulai',
                    'tanggal_selesai',
                ])),
                $data['alasan'],
            );
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        $placement->load(self::RELATIONS);

        return response()->json([
            'success' => true,
            'message' => 'Data penempatan PKL berhasil diperbarui.',
            'data' => ['penempatan' => $this->placementData($placement)],
        ]);
    }

    public function cancel(Request $request, int $id, PenempatanPklService $service): JsonResponse
    {
        $data = $request->validate([
            'alasan_kode' => ['required', 'string', Rule::in(array_keys(PenempatanPklService::ALASAN_PEMBATALAN))],
            'alasan_detail' => ['nullable', 'string', 'max:5000'],
        ] + $this->prohibitedFields());

        try {
            $placement = $service->cancel(
                $this->admin($request),
                $id,
                $data['alasan_kode'],
                $data['alasan_detail'] ?? null,
            );
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        $placement->load(self::RELATIONS);

        return response()->json([
            'success' => true,
            'message' => 'Penempatan PKL berhasil dibatalkan.',
            'data' => ['penempatan' => $this->placementData($placement)],
        ]);
    }

    public function reactivate(Request $request, int $id, PenempatanPklService $service): JsonResponse
    {
        $data = $request->validate([
            'alasan' => ['required', 'string', 'max:5000'],
        ] + $this->prohibitedFields());

        try {
            $placement = $service->reactivate($this->admin($request), $id, $data['alasan']);
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        $placement->load(self::RELATIONS);

        return response()->json([
            'success' => true,
            'message' => 'Penempatan PKL berhasil diaktifkan kembali.',
            'data' => ['penempatan' => $this->placementData($placement)],
        ]);
    }

    public function move(Request $request, int $id, PenempatanPklService $service): JsonResponse
    {
        $data = $request->validate([
            'instansi_id' => ['required', 'integer', 'min:1'],
            'guru_id' => ['required', 'integer', 'min:1'],
            'pic_instansi_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'tanggal_mulai' => ['required', 'date_format:Y-m-d'],
            'tanggal_selesai' => ['required', 'date_format:Y-m-d'],
            'alasan_kode' => ['required', 'string', Rule::in(array_keys(PenempatanPklService::ALASAN_PEMBATALAN))],
            'alasan_detail' => ['nullable', 'string', 'max:5000'],
        ] + $this->prohibitedFields() + [
            'siswa_id' => ['prohibited'],
            'periode_id' => ['prohibited'],
        ]);

        $placementData = array_intersect_key($data, array_flip([
            'instansi_id',
            'guru_id',
            'pic_instansi_id',
            'tanggal_mulai',
            'tanggal_selesai',
        ]));

        try {
            $placement = $service->move(
                $this->admin($request),
                $id,
                $placementData,
                $data['alasan_kode'],
                $data['alasan_detail'] ?? null,
            );
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        $placement->load(self::RELATIONS);

        return response()->json([
            'success' => true,
            'message' => 'Penempatan PKL berhasil dipindahkan.',
            'data' => ['penempatan' => $this->placementData($placement)],
        ], 201);
    }

    /**
     * @return array<string, array<int, string|\Illuminate\Validation\Rules\In>>
     */
    private function placementRules(): array
    {
        return [
            'siswa_id' => ['required', 'integer', 'min:1'],
            'periode_id' => ['required', 'integer', 'min:1'],
            'instansi_id' => ['required', 'integer', 'min:1'],
            'guru_id' => ['required', 'integer', 'min:1'],
            'pic_instansi_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'tanggal_mulai' => ['required', 'date_format:Y-m-d'],
            'tanggal_selesai' => ['required', 'date_format:Y-m-d'],
        ];
    }

    private function eligibleStudentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Siswa::query()
            ->with('user')
            ->where('status_siswa', 'aktif')
            ->whereHas('user', fn ($query) => $query
                ->where('status', 'aktif')
                ->whereHas('role', fn ($roleQuery) => $roleQuery->whereRaw('LOWER(nama) = ?', ['siswa'])));
    }

    private function eligibleTeacherQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Guru::query()
            ->with('user')
            ->where('status_bimbingan', 'aktif')
            ->whereHas('user', fn ($query) => $query
                ->where('status', 'aktif')
                ->whereHas('role', fn ($roleQuery) => $roleQuery->whereRaw('LOWER(nama) = ?', ['guru'])));
    }

    private function studentReference(Siswa $student): array
    {
        return [
            'id' => $student->id,
            'profile_id' => $student->id,
            'nama_lengkap' => $student->nama_lengkap,
            'nisn' => $student->nisn,
            'nis' => $student->nis,
            'username' => $student->user?->username,
        ];
    }

    private function teacherReference(Guru $teacher): array
    {
        return [
            'id' => $teacher->id,
            'profile_id' => $teacher->id,
            'nama_lengkap' => $teacher->nama_lengkap,
            'nip' => $teacher->nip,
            'username' => $teacher->user?->username,
        ];
    }

    private function paginationData(\Illuminate\Contracts\Pagination\LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    private function prohibitedFields(): array
    {
        return [
            'admin_user_id' => ['prohibited'],
            'status' => ['prohibited'],
            'status_lama' => ['prohibited'],
            'status_baru' => ['prohibited'],
            'riwayat_status' => ['prohibited'],
        ];
    }

    private function admin(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function placementData(PenempatanPkl $placement): array
    {
        return [
            'id' => $placement->id,
            'siswa' => $placement->siswa ? [
                'id' => $placement->siswa->id,
                'nisn' => $placement->siswa->nisn,
                'nis' => $placement->siswa->nis,
                'nama_lengkap' => $placement->siswa->nama_lengkap,
                'status_siswa' => $placement->siswa->status_siswa,
                'username' => $placement->siswa->user?->username,
            ] : null,
            'periode' => $placement->periode ? [
                'id' => $placement->periode->id,
                'nama' => $placement->periode->nama,
                'tanggal_mulai' => $placement->periode->tanggal_mulai?->toDateString(),
                'tanggal_selesai' => $placement->periode->tanggal_selesai?->toDateString(),
                'status' => $placement->periode->status,
            ] : null,
            'instansi' => $placement->instansi ? [
                'id' => $placement->instansi->id,
                'nama' => $placement->instansi->nama,
                'status' => $placement->instansi->status,
            ] : null,
            'guru' => $placement->guru ? [
                'id' => $placement->guru->id,
                'nip' => $placement->guru->nip,
                'nama_lengkap' => $placement->guru->nama_lengkap,
                'status_bimbingan' => $placement->guru->status_bimbingan,
            ] : null,
            'pic' => $placement->pic ? [
                'id' => $placement->pic->id,
                'nama' => $placement->pic->nama,
                'jabatan' => $placement->pic->jabatan,
                'no_hp' => $placement->pic->no_hp,
                'email' => $placement->pic->email,
            ] : null,
            'tanggal_mulai' => $placement->tanggal_mulai?->toDateString(),
            'tanggal_selesai' => $placement->tanggal_selesai?->toDateString(),
            'status' => $placement->status,
            'riwayat_status' => $placement->riwayatStatus
                ->sortBy('created_at')
                ->values()
                ->map(fn ($history): array => [
                    'id' => $history->id,
                    'status_lama' => $history->status_lama,
                    'status_baru' => $history->status_baru,
                    'alasan_kode' => $history->alasan_kode,
                    'alasan_detail' => $history->alasan_detail,
                    'admin' => $history->admin ? [
                        'id' => $history->admin->id,
                        'nama_tampilan' => $history->admin->nama_tampilan,
                        'username' => $history->admin->username,
                    ] : null,
                    'created_at' => $history->created_at?->toISOString(),
                ])
                ->all(),
            'riwayat_guru' => $placement->riwayatGuru
                ->sortBy('created_at')
                ->values()
                ->map(fn ($history): array => [
                    'id' => $history->id,
                    'guru_lama' => $history->guruLama ? [
                        'id' => $history->guruLama->id,
                        'nama_lengkap' => $history->guruLama->nama_lengkap,
                        'nip' => $history->guruLama->nip,
                    ] : null,
                    'guru_baru' => $history->guruBaru ? [
                        'id' => $history->guruBaru->id,
                        'nama_lengkap' => $history->guruBaru->nama_lengkap,
                        'nip' => $history->guruBaru->nip,
                    ] : null,
                    'alasan' => $history->alasan,
                    'admin' => $history->admin ? [
                        'id' => $history->admin->id,
                        'nama_tampilan' => $history->admin->nama_tampilan,
                        'username' => $history->admin->username,
                    ] : null,
                    'created_at' => $history->created_at?->toISOString(),
                ])
                ->all(),
            'riwayat_perubahan' => $placement->riwayatPerubahan
                ->sortBy('created_at')
                ->values()
                ->map(fn ($history): array => [
                    'id' => $history->id,
                    'perubahan' => $history->perubahan,
                    'alasan' => $history->alasan,
                    'admin' => $history->admin ? [
                        'id' => $history->admin->id,
                        'nama_tampilan' => $history->admin->nama_tampilan,
                        'username' => $history->admin->username,
                    ] : null,
                    'created_at' => $history->created_at?->toISOString(),
                ])
                ->all(),
        ];
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Penempatan PKL tidak ditemukan.',
        ], 404);
    }
}
