<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class GuruController extends Controller
{
    private const ROLE_GURU = 2;

    private function dasar()
    {
        return DB::table('guru as g')
            ->join('users as u', 'u.id', '=', 'g.user_id')
            ->leftJoin('jurusan as j', 'j.id', '=', 'g.jurusan_id')
            ->select(
                'g.id', 'g.user_id', 'g.nip', 'g.nama_lengkap', 'g.jenis_kelamin',
                'g.no_hp', 'g.alamat', 'g.mata_pelajaran', 'g.jurusan_id',
                'g.status_bimbingan', 'g.kuota_bimbingan',
                'j.kode as jurusan_kode', 'j.nama as jurusan_nama',
                'u.username', 'u.email'
            )
            ->selectSub(function ($s) {
                $s->from('penempatan_pkl as p')
                    ->selectRaw('count(*)')
                    ->whereColumn('p.guru_id', 'g.id')
                    ->whereIn('p.status', ['belum_mulai', 'aktif']);
            }, 'kuota_terpakai');
    }

    private function aturan(?object $guru = null): array
    {
        return [
            'nama_lengkap'     => ['required', 'string', 'max:120'],
            'nip'              => ['nullable', 'string', 'max:30', Rule::unique('guru', 'nip')->ignore($guru?->id)],
            'username'         => ['required', 'string', 'max:60', Rule::unique('users', 'username')->ignore($guru?->user_id)],
            'email'            => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($guru?->user_id)],
            'password'         => [$guru ? 'nullable' : 'required', 'string', 'min:8'],
            'jenis_kelamin'    => ['nullable', Rule::in(['L', 'P'])],
            'no_hp'            => ['nullable', 'string', 'max:25'],
            'alamat'           => ['nullable', 'string'],
            'jurusan_id'       => ['nullable', 'integer', 'exists:jurusan,id'],
            'status_bimbingan' => ['nullable', Rule::in(['aktif', 'nonaktif'])],
            'kuota_bimbingan'  => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }

    public function index(Request $request)
    {
        $perPage = min(max((int) $request->query('per_page', 10), 1), 100);
        $q = $this->dasar();

        $cari = trim((string) $request->query('search', ''));
        if ($cari !== '') {
            $q->where(function ($w) use ($cari) {
                $w->where('g.nama_lengkap', 'like', "%{$cari}%")
                  ->orWhere('g.nip', 'like', "%{$cari}%");
            });
        }

        if ($request->filled('jurusan_id')) {
            $q->where('g.jurusan_id', (int) $request->query('jurusan_id'));
        }

        $status = $request->query('status');
        if (in_array($status, ['aktif', 'nonaktif'], true)) {
            $q->where('g.status_bimbingan', $status);
        }

        $p = $q->orderBy('g.nama_lengkap')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $p->items(),
            'meta'    => [
                'page'      => $p->currentPage(),
                'per_page'  => $p->perPage(),
                'total'     => $p->total(),
                'last_page' => $p->lastPage(),
            ],
            'stats'   => [
                'total_guru'       => DB::table('guru')->count(),
                'pembimbing_aktif' => DB::table('guru')->where('status_bimbingan', 'aktif')->count(),
                'total_kuota'      => (int) DB::table('guru')->where('status_bimbingan', 'aktif')->sum('kuota_bimbingan'),
            ],
        ]);
    }

    public function jurusan()
    {
        return response()->json([
            'success' => true,
            'data'    => DB::table('jurusan')->orderBy('kode')->get(['id', 'kode', 'nama']),
        ]);
    }

    public function show(int $id)
    {
        $guru = $this->dasar()->where('g.id', $id)->first();

        if (! $guru) {
            return response()->json(['success' => false, 'message' => 'Guru tidak ditemukan.'], 404);
        }

        $siswa = DB::table('penempatan_pkl as p')
            ->join('siswa as s', 's.id', '=', 'p.siswa_id')
            ->leftJoin('instansi as i', 'i.id', '=', 'p.instansi_id')
            ->where('p.guru_id', $id)
            ->whereIn('p.status', ['belum_mulai', 'aktif'])
            ->get(['s.id', 's.nisn', 's.nama_lengkap', 'i.nama as instansi', 'p.status', 'p.tanggal_mulai', 'p.tanggal_selesai']);

        return response()->json([
            'success' => true,
            'data'    => $guru,
            'siswa_bimbingan' => $siswa,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->aturan());

        $id = DB::transaction(function () use ($data) {
            $now = now();

            $userId = DB::table('users')->insertGetId([
                'role_id'              => self::ROLE_GURU,
                'username'             => $data['username'],
                'email'                => $data['email'],
                'password_hash'        => Hash::make($data['password']),
                'nama_tampilan'        => $data['nama_lengkap'],
                'status'               => 'aktif',
                'wajib_ganti_password' => 1,
                'created_at'           => $now,
                'updated_at'           => $now,
            ]);

            return DB::table('guru')->insertGetId([
                'user_id'          => $userId,
                'nip'              => $data['nip'] ?? null,
                'nama_lengkap'     => $data['nama_lengkap'],
                'jenis_kelamin'    => $data['jenis_kelamin'] ?? null,
                'no_hp'            => $data['no_hp'] ?? null,
                'alamat'           => $data['alamat'] ?? null,
                'jurusan_id'       => $data['jurusan_id'] ?? null,
                'status_bimbingan' => $data['status_bimbingan'] ?? 'aktif',
                'kuota_bimbingan'  => $data['kuota_bimbingan'] ?? 0,
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Guru berhasil ditambahkan.',
            'data'    => ['id' => $id],
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $guru = DB::table('guru')->where('id', $id)->first();

        if (! $guru) {
            return response()->json(['success' => false, 'message' => 'Guru tidak ditemukan.'], 404);
        }

        $data = $request->validate($this->aturan($guru));

        DB::transaction(function () use ($guru, $data) {
            $user = [
                'username'      => $data['username'],
                'email'         => $data['email'],
                'nama_tampilan' => $data['nama_lengkap'],
            ];

            if (! empty($data['password'])) {
                $user['password_hash'] = Hash::make($data['password']);
                $user['wajib_ganti_password'] = 1;
            }

            DB::table('users')->where('id', $guru->user_id)->update($user);

            DB::table('guru')->where('id', $guru->id)->update([
                'nip'              => $data['nip'] ?? null,
                'nama_lengkap'     => $data['nama_lengkap'],
                'jenis_kelamin'    => $data['jenis_kelamin'] ?? null,
                'no_hp'            => $data['no_hp'] ?? null,
                'alamat'           => $data['alamat'] ?? null,
                'jurusan_id'       => $data['jurusan_id'] ?? null,
                'status_bimbingan' => $data['status_bimbingan'] ?? 'aktif',
                'kuota_bimbingan'  => $data['kuota_bimbingan'] ?? 0,
                'updated_at'       => now(),
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Data guru berhasil diperbarui.']);
    }

    public function destroy(int $id)
    {
        $guru = DB::table('guru')->where('id', $id)->first();

        if (! $guru) {
            return response()->json(['success' => false, 'message' => 'Guru tidak ditemukan.'], 404);
        }

        $dipakai = DB::table('penempatan_pkl')->where('guru_id', $id)->count();
        if ($dipakai > 0) {
            return response()->json([
                'success' => false,
                'message' => "Guru masih tercatat di {$dipakai} penempatan PKL, jadi tidak bisa dihapus. Ubah statusnya menjadi Non-Aktif sebagai gantinya.",
            ], 409);
        }

        try {
            DB::transaction(function () use ($guru) {
                DB::table('personal_access_tokens')
                    ->where('tokenable_type', User::class)
                    ->where('tokenable_id', $guru->user_id)
                    ->delete();

                DB::table('guru')->where('id', $guru->id)->delete();
                DB::table('users')->where('id', $guru->user_id)->delete();
            });
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data guru masih dipakai data lain, jadi tidak bisa dihapus.',
            ], 409);
        }

        return response()->json(['success' => true, 'message' => 'Guru berhasil dihapus.']);
    }
}