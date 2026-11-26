<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminMasterDataController;
use App\Http\Controllers\AdminPenempatanPklController;
use App\Http\Controllers\AdminStudentController;
use App\Http\Controllers\AdminTeacherController;
use App\Http\Controllers\AdminUserController;

use App\Http\Controllers\Api\V1\Admin\GuruController as AdminGuruController;
use App\Http\Controllers\Api\V1\Admin\LaporanController;
use App\Http\Controllers\Api\V1\Admin\DashboardMonitoringController;

use App\Http\Controllers\Api\V1\Guru\AgendaController;
use App\Http\Controllers\Api\V1\Guru\EmailChangeController;
use App\Http\Controllers\Api\V1\Guru\InstansiController as GuruInstansiController;
use App\Http\Controllers\Api\V1\Guru\PresensiController as GuruPresensiController;
use App\Http\Controllers\Api\V1\Guru\JurnalController as GuruJurnalController;
use App\Http\Controllers\Api\V1\Guru\ProfilController as GuruProfilController;
use App\Http\Controllers\Api\V1\Guru\SiswaBimbinganController;
use App\Http\Controllers\Api\V1\Guru\PenilaianController;

use App\Http\Controllers\Api\V1\Guru\NotifikasiController;

use App\Http\Controllers\Api\V1\Siswa\IzinController;
use App\Http\Controllers\Api\V1\Siswa\JurnalController;
use App\Http\Controllers\Api\V1\Siswa\PresensiController;
use App\Http\Controllers\Api\V1\Siswa\ProfilController;
use App\Http\Controllers\Api\V1\Siswa\RiwayatController;

use App\Http\Controllers\GuruAuthController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\SelfRegistrationController;
use App\Http\Controllers\SiswaAuthController;

use App\Http\Middleware\EnsureAdminRole;
use App\Http\Middleware\EnsureGuruRole;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\EnsureSanctumTokenStorage;
use App\Http\Middleware\EnsureSiswaRole;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;


// ======================================================
// FOTO GURU
// ======================================================

Route::get('v1/foto/{path}', function (string $path) {
    abort_if(str_contains($path, '..'), 404);
    abort_unless(Storage::disk('public')->exists($path), 404);

    return response()->file(
        Storage::disk('public')->path($path)
    );
})->where('path', 'foto-guru/.*');


// ======================================================
// ADMIN: DATA GURU
// ======================================================

Route::prefix('v1/admin')->middleware([
    EnsureSanctumTokenStorage::class,
    'auth:sanctum',
    EnsureAdminRole::class,
])->group(function (): void {

    Route::get('/jurusan', function () {
        return response()->json([
            'data' => DB::table('jurusan')
                ->orderBy('nama')
                ->get(['id', 'kode', 'nama']),
        ]);
    });

    Route::get('/guru', [AdminGuruController::class, 'index']);
    Route::post('/guru', [AdminGuruController::class, 'store']);
    Route::get('/guru/{id}', [AdminGuruController::class, 'show']);
    Route::put('/guru/{id}', [AdminGuruController::class, 'update']);
    Route::delete('/guru/{id}', [AdminGuruController::class, 'destroy']);
});


// ======================================================
// RESET PASSWORD
// ======================================================

Route::prefix('v1/{role}/auth')
    ->whereIn('role', ['admin', 'guru', 'siswa'])
    ->group(function (): void {

        Route::post(
            '/forgot-password',
            [PasswordResetController::class, 'send']
        )->middleware('throttle:5,1');

        Route::post(
            '/reset-password',
            [PasswordResetController::class, 'reset']
        )->middleware('throttle:10,1');
    });


// ======================================================
// REGISTRASI DAN VERIFIKASI
// ======================================================

Route::prefix('v1/{role}/auth')->group(function (): void {

    Route::post(
        '/register',
        [SelfRegistrationController::class, 'register']
    )->middleware('throttle:self-registration-register');

    Route::post(
        '/resend-verification',
        [SelfRegistrationController::class, 'resend']
    )->middleware('throttle:self-registration-resend');

    Route::post(
        '/verify-email',
        [SelfRegistrationController::class, 'verify']
    )->middleware('throttle:self-registration-verify');
});


// ======================================================
// PERUBAHAN EMAIL GURU
// ======================================================

Route::get(
    '/v1/guru/email/verify-change',
    [EmailChangeController::class, 'verify']
)->middleware(['signed:relative', 'throttle:10,1'])
    ->name('guru.email-change.verify');


// ======================================================
// AUTENTIKASI GURU
// ======================================================

Route::prefix('v1/guru/auth')->group(function (): void {

    Route::post('/login', [GuruAuthController::class, 'login'])
        ->middleware('throttle:5,1');

    Route::middleware([
        EnsureSanctumTokenStorage::class,
        'auth:sanctum',
        EnsureGuruRole::class,
    ])->group(function (): void {

        Route::get('/me', [GuruAuthController::class, 'me']);
        Route::post('/logout', [GuruAuthController::class, 'logout']);

        Route::post(
            '/password',
            [PasswordController::class, 'update']
        )->middleware('throttle:5,1');
    });
});


// ======================================================
// GURU: PROFIL, AGENDA, SISWA, PENILAIAN, JURNAL
// ======================================================

Route::prefix('v1/guru')->middleware([
    EnsureSanctumTokenStorage::class,
    'auth:sanctum',
    EnsureGuruRole::class,
    EnsurePasswordChanged::class,
])->group(function (): void {

    // ----------------------------------------------
    // PROFIL
    // ----------------------------------------------

    Route::get('/profil', [GuruProfilController::class, 'show']);
    Route::post('/profil', [GuruProfilController::class, 'update']);
    Route::patch('/profil', [GuruProfilController::class, 'update']);

    Route::post(
        '/profil/email-change',
        [EmailChangeController::class, 'request']
    );


    // ----------------------------------------------
    // AGENDA
    // ----------------------------------------------

    Route::get('/instansi', [GuruInstansiController::class, 'index']);
    Route::get('/agenda', [AgendaController::class, 'index']);
    Route::get('/agenda/opsi', [AgendaController::class, 'options']);
    Route::post('/agenda', [AgendaController::class, 'store']);

    Route::patch(
        '/agenda/{id}/status',
        [AgendaController::class, 'updateStatus']
    )->whereNumber('id');

    Route::delete(
        '/agenda/{id}',
        [AgendaController::class, 'destroy']
    )->whereNumber('id');


    // ----------------------------------------------
    // SISWA BIMBINGAN
    // ----------------------------------------------

    Route::get(
        '/siswa-bimbingan',
        [SiswaBimbinganController::class, 'index']
    );

    Route::get(
        '/siswa-bimbingan/{id}',
        [SiswaBimbinganController::class, 'show']
    )->whereNumber('id');


    // ----------------------------------------------
    // PENILAIAN SISWA
    // ----------------------------------------------

    Route::get(
        '/penilaian/{siswaId}/riwayat',
        [PenilaianController::class, 'riwayat']
    )->whereNumber('siswaId');

    Route::get(
        '/penilaian/{siswaId}',
        [PenilaianController::class, 'show']
    )->whereNumber('siswaId');

    Route::post(
        '/penilaian',
        [PenilaianController::class, 'store']
    );


    // ----------------------------------------------
    // JURNAL
    // ----------------------------------------------

    Route::post('/presensi/sesi', [GuruPresensiController::class, 'store']);

    Route::get('/jurnal', [GuruJurnalController::class, 'index']);

    Route::get(
        '/jurnal/{jurnal}',
        [GuruJurnalController::class, 'show']
    )->whereNumber('jurnal');

    Route::get(
        '/jurnal/{jurnal}/lampiran/{lampiran}',
        [GuruJurnalController::class, 'showDocumentation']
    )->whereNumber('jurnal')
        ->whereNumber('lampiran');
});


// ======================================================
// AUTENTIKASI SISWA
// ======================================================

Route::prefix('v1/siswa/auth')->group(function (): void {

    Route::post('/login', [SiswaAuthController::class, 'login'])
        ->middleware('throttle:5,1');

    Route::middleware([
        EnsureSanctumTokenStorage::class,
        'auth:sanctum',
        EnsureSiswaRole::class,
    ])->group(function (): void {

        Route::get('/me', [SiswaAuthController::class, 'me']);
        Route::post('/logout', [SiswaAuthController::class, 'logout']);
    });
});


// ======================================================
// SISWA: PROFIL, PRESENSI, JURNAL, IZIN, RIWAYAT
// ======================================================

Route::prefix('v1/siswa')->middleware([
    EnsureSanctumTokenStorage::class,
    'auth:sanctum',
    EnsureSiswaRole::class,
])->group(function (): void {

    Route::post(
        '/profil/password',
        [PasswordController::class, 'update']
    )->middleware('throttle:5,1');

    Route::middleware(EnsurePasswordChanged::class)
        ->group(function (): void {

            Route::get('/profil', [ProfilController::class, 'show']);
            Route::post('/profil', [ProfilController::class, 'update']);

            Route::get('/presensi', [PresensiController::class, 'index']);
            Route::post('/presensi', [PresensiController::class, 'store']);

            Route::post('/jurnal', [JurnalController::class, 'store']);
            Route::post('/izin', [IzinController::class, 'store']);

            Route::get('/riwayat', [RiwayatController::class, 'index']);
        });
});


// ======================================================
// AUTENTIKASI ADMIN
// ======================================================

Route::prefix('v1/admin/auth')->group(function (): void {

    Route::post('/login', [AdminAuthController::class, 'login'])
        ->middleware('throttle:5,1');

    Route::middleware([
        EnsureSanctumTokenStorage::class,
        'auth:sanctum',
        EnsureAdminRole::class,
    ])->group(function (): void {

        Route::get('/me', [AdminAuthController::class, 'me']);
        Route::post('/logout', [AdminAuthController::class, 'logout']);

        Route::post(
            '/password',
            [PasswordController::class, 'update']
        )->middleware('throttle:5,1');
    });
});


// ======================================================
// ADMIN: MANAJEMEN AKUN
// ======================================================

// Manajemen akun Admin

Route::prefix('v1/admin/users')->middleware([
    EnsureSanctumTokenStorage::class,
    'auth:sanctum',
    EnsureAdminRole::class,
    EnsurePasswordChanged::class,
])->group(function (): void {

    Route::get('/', [AdminUserController::class, 'index']);
    Route::post('/', [AdminUserController::class, 'store']);
    Route::get('/{user}', [AdminUserController::class, 'show']);

    Route::match(
        ['put', 'patch'],
        '/{user}',
        [AdminUserController::class, 'update']
    );

    Route::delete('/{user}', [AdminUserController::class, 'destroy']);
});


// Manajemen akun Siswa

Route::prefix('v1/admin/students')->middleware([
    EnsureSanctumTokenStorage::class,
    'auth:sanctum',
    EnsureAdminRole::class,
    EnsurePasswordChanged::class,
])->group(function (): void {

    Route::get('/classes', [AdminStudentController::class, 'classes']);
    Route::get('/', [AdminStudentController::class, 'index']);
    Route::post('/', [AdminStudentController::class, 'store']);

    Route::get('/{user}', [AdminStudentController::class, 'show']);
    Route::patch('/{user}/status', [AdminStudentController::class, 'updateStatus']);
    Route::patch('/{user}', [AdminStudentController::class, 'update']);
    Route::delete('/{user}', [AdminStudentController::class, 'destroy']);
});


// Manajemen akun Guru

Route::prefix('v1/admin/teachers')->middleware([
    EnsureSanctumTokenStorage::class,
    'auth:sanctum',
    EnsureAdminRole::class,
    EnsurePasswordChanged::class,
])->group(function (): void {

    Route::get('/', [AdminTeacherController::class, 'index']);
    Route::post('/', [AdminTeacherController::class, 'store']);

    Route::get(
        '/{user}/active-placements',
        [AdminTeacherController::class, 'activePlacements']
    );

    Route::post(
        '/{user}/placements/{placement}/reassign',
        [AdminTeacherController::class, 'reassignPlacement']
    );

    Route::get('/{user}', [AdminTeacherController::class, 'show']);
    Route::patch('/{user}', [AdminTeacherController::class, 'update']);
    Route::delete('/{user}', [AdminTeacherController::class, 'destroy']);
});


// ======================================================
// ADMIN: PENEMPATAN PKL
// ======================================================

Route::prefix('v1/admin/penempatan-pkl')->middleware([
    EnsureSanctumTokenStorage::class,
    'auth:sanctum',
    EnsureAdminRole::class,
    EnsurePasswordChanged::class,
])->group(function (): void {

    Route::get(
        '/referensi',
        [AdminPenempatanPklController::class, 'references']
    );

    Route::get('/', [AdminPenempatanPklController::class, 'index']);
    Route::post('/', [AdminPenempatanPklController::class, 'store']);
    Route::get('/{id}', [AdminPenempatanPklController::class, 'show'])->whereNumber('id');
    Route::patch('/{id}', [AdminPenempatanPklController::class, 'update'])->whereNumber('id');
    Route::post('/{id}/batalkan', [AdminPenempatanPklController::class, 'cancel'])->whereNumber('id');
    Route::post('/{id}/aktifkan-kembali', [AdminPenempatanPklController::class, 'reactivate'])->whereNumber('id');
    Route::post('/{id}/pindahkan', [AdminPenempatanPklController::class, 'move'])->whereNumber('id');
});


// ======================================================
// ADMIN: MASTER DATA
// ======================================================

Route::prefix('v1/admin/master-data')->middleware([
    EnsureSanctumTokenStorage::class,
    'auth:sanctum',
    EnsureAdminRole::class,
    EnsurePasswordChanged::class,
])->group(function (): void {

    // Instansi

    Route::get('/instansi', [AdminMasterDataController::class, 'instansiIndex']);
    Route::post('/instansi', [AdminMasterDataController::class, 'instansiStore']);

    Route::get(
        '/instansi/{instansi}',
        [AdminMasterDataController::class, 'instansiShow']
    )->whereNumber('instansi');

    Route::patch(
        '/instansi/{instansi}',
        [AdminMasterDataController::class, 'instansiUpdate']
    )->whereNumber('instansi');

    Route::patch(
        '/instansi/{instansi}/status',
        [AdminMasterDataController::class, 'instansiUpdateStatus']
    )->whereNumber('instansi');

    // PIC Instansi

    Route::get(
        '/instansi/{instansi}/pic',
        [AdminMasterDataController::class, 'picIndex']
    )->whereNumber('instansi');

    Route::post(
        '/instansi/{instansi}/pic',
        [AdminMasterDataController::class, 'picStore']
    )->whereNumber('instansi');

    Route::patch(
        '/instansi/{instansi}/pic/{pic}',
        [AdminMasterDataController::class, 'picUpdate']
    )->whereNumber('instansi')
        ->whereNumber('pic');

    Route::patch(
        '/instansi/{instansi}/pic/{pic}/status',
        [AdminMasterDataController::class, 'picUpdateStatus']
    )->whereNumber('instansi')
        ->whereNumber('pic');

    // Tahun Ajaran

    Route::get(
        '/tahun-ajaran',
        [AdminMasterDataController::class, 'tahunAjaranIndex']
    );

    Route::post(
        '/tahun-ajaran',
        [AdminMasterDataController::class, 'tahunAjaranStore']
    );

    Route::get(
        '/tahun-ajaran/{tahunAjaran}',
        [AdminMasterDataController::class, 'tahunAjaranShow']
    )->whereNumber('tahunAjaran');

    Route::patch(
        '/tahun-ajaran/{tahunAjaran}',
        [AdminMasterDataController::class, 'tahunAjaranUpdate']
    )->whereNumber('tahunAjaran');

    // Periode PKL

    Route::get(
        '/periode-pkl',
        [AdminMasterDataController::class, 'periodeIndex']
    );

    Route::post(
        '/periode-pkl',
        [AdminMasterDataController::class, 'periodeStore']
    );

    Route::get(
        '/periode-pkl/{periode}',
        [AdminMasterDataController::class, 'periodeShow']
    )->whereNumber('periode');

    Route::patch(
        '/periode-pkl/{periode}',
        [AdminMasterDataController::class, 'periodeUpdate']
    )->whereNumber('periode');

    // Kuota

    Route::get('/kuota', [AdminMasterDataController::class, 'kuotaIndex']);

    Route::put(
        '/kuota/{instansi}/{periode}',
        [AdminMasterDataController::class, 'kuotaUpsert']
    )->whereNumber('instansi')
        ->whereNumber('periode');
});


// ======================================================
// ADMIN: LAPORAN DAN REKAPITULASI
// ======================================================

Route::prefix('v1/admin/laporan')->middleware([
    EnsureSanctumTokenStorage::class,
    'auth:sanctum',
    EnsureAdminRole::class,
    EnsurePasswordChanged::class,
    'throttle:60,1',
])->group(function (): void {

    Route::get('/filter', [LaporanController::class, 'filter']);
    Route::get('/ringkasan', [LaporanController::class, 'ringkasan']);
    Route::get('/presensi', [LaporanController::class, 'presensi']);
    Route::get('/jurnal', [LaporanController::class, 'jurnal']);

    Route::get('/arsip', [LaporanController::class, 'arsip']);
    Route::post('/arsip', [LaporanController::class, 'ekspor']);

    Route::get(
        '/arsip/{id}/unduh',
        [LaporanController::class, 'unduh']
    )->whereNumber('id');
});

Route::prefix('v1/admin')->middleware([
    EnsureSanctumTokenStorage::class,
    'auth:sanctum',
    EnsureAdminRole::class,
    EnsurePasswordChanged::class,
    'throttle:60,1',
])->group(function (): void {
    Route::get('/dashboard-monitoring', [DashboardMonitoringController::class, 'index']);
});


// ======================================================
// NOTIFIKASI
// ======================================================

Route::prefix('v1')->middleware([
    EnsureSanctumTokenStorage::class,
    'auth:sanctum',
])->group(function (): void {

    Route::get('/notifikasi', [NotifikasiController::class, 'index']);
    Route::get('/notifikasi/jumlah-belum-dibaca', [NotifikasiController::class, 'jumlahBelumDibaca']);
    Route::patch('/notifikasi/dibaca-semua', [NotifikasiController::class, 'tandaiSemuaDibaca']);
    Route::patch('/notifikasi/{id}/dibaca', [NotifikasiController::class, 'tandaiDibaca']);
});