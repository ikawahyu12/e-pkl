<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminStudentController;
use App\Http\Controllers\AdminTeacherController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\Api\V1\Admin\GuruController as AdminGuruController;
use App\Http\Controllers\Api\V1\Guru\AgendaController;
use App\Http\Controllers\Api\V1\Guru\ProfilController as GuruProfilController;
use App\Http\Controllers\Api\V1\Siswa\IzinController;
use App\Http\Controllers\Api\V1\Siswa\JurnalController;
use App\Http\Controllers\Api\V1\Siswa\PresensiController;
use App\Http\Controllers\Api\V1\Siswa\ProfilController;
use App\Http\Controllers\GuruAuthController;
use App\Http\Controllers\SiswaAuthController;
use App\Http\Middleware\EnsureAdminRole;
use App\Http\Middleware\EnsureGuruRole;
use App\Http\Middleware\EnsureSanctumTokenStorage;
use App\Http\Middleware\EnsureSiswaRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('v1/foto/{path}', function (string $path) {
    abort_if(str_contains($path, '..'), 404);
    abort_unless(Storage::disk('public')->exists($path), 404);
    return response()->file(Storage::disk('public')->path($path));
})->where('path', 'foto-guru/.*');

// Admin: Data Guru (dipakai web admin dan mobile)
Route::prefix('v1/admin')->middleware([
    EnsureSanctumTokenStorage::class,
    'auth:sanctum',
    EnsureAdminRole::class,
])->group(function (): void {
    Route::get('/jurusan', function () {
        return response()->json([
            'data' => DB::table('jurusan')
                ->orderBy('nama')->get(['id', 'kode', 'nama']),
        ]);
    });

    Route::get('/guru', [AdminGuruController::class, 'index']);
    Route::post('/guru', [AdminGuruController::class, 'store']);
    Route::get('/guru/{id}', [AdminGuruController::class, 'show']);
    Route::put('/guru/{id}', [AdminGuruController::class, 'update']);
    Route::delete('/guru/{id}', [AdminGuruController::class, 'destroy']);
});

// Autentikasi Guru
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
    });
});

// Autentikasi Siswa
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

// Profil, Presensi, Jurnal, dan Izin Siswa
Route::prefix('v1/siswa')->middleware([
    EnsureSanctumTokenStorage::class,
    'auth:sanctum',
    EnsureSiswaRole::class,
])->group(function (): void {
    Route::get('/profil', [ProfilController::class, 'show']);
    Route::post('/profil', [ProfilController::class, 'update']);
    Route::post('/profil/password', [ProfilController::class, 'updatePassword']);
    Route::post('/presensi', [PresensiController::class, 'store']);
    Route::post('/jurnal', [JurnalController::class, 'store']);
    Route::post('/izin', [IzinController::class, 'store']);
});

// Autentikasi Admin
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
    });
});

// Profil dan Agenda Guru
Route::prefix('v1/guru')->middleware([
    EnsureSanctumTokenStorage::class,
    'auth:sanctum',
    EnsureGuruRole::class,
])->group(function (): void {
    Route::get('/profil', [GuruProfilController::class, 'show']);
    Route::post('/profil', [GuruProfilController::class, 'update']);
    Route::post('/profil/password', [GuruProfilController::class, 'updatePassword']);

    Route::get('/agenda', [AgendaController::class, 'index']);
    Route::get('/agenda/opsi', [AgendaController::class, 'opsi']);
    Route::post('/agenda', [AgendaController::class, 'store']);
    Route::patch('/agenda/{id}/status', [AgendaController::class, 'updateStatus']);
    Route::delete('/agenda/{id}', [AgendaController::class, 'destroy']);
});

// Manajemen akun Admin
Route::prefix('v1/admin/users')->middleware([
    EnsureSanctumTokenStorage::class,
    'auth:sanctum',
    EnsureAdminRole::class,
])->group(function (): void {
    Route::get('/', [AdminUserController::class, 'index']);
    Route::post('/', [AdminUserController::class, 'store']);
    Route::get('/{user}', [AdminUserController::class, 'show']);
    Route::match(['put', 'patch'], '/{user}', [AdminUserController::class, 'update']);
    Route::delete('/{user}', [AdminUserController::class, 'destroy']);
});

// Manajemen akun Siswa
Route::prefix('v1/admin/students')->middleware([
    EnsureSanctumTokenStorage::class,
    'auth:sanctum',
    EnsureAdminRole::class,
])->group(function (): void {
    Route::get('/classes', [AdminStudentController::class, 'classes']);
    Route::get('/', [AdminStudentController::class, 'index']);
    Route::post('/', [AdminStudentController::class, 'store']);
    Route::get('/{user}', [AdminStudentController::class, 'show']);
    Route::patch('/{user}', [AdminStudentController::class, 'update']);
    Route::delete('/{user}', [AdminStudentController::class, 'destroy']);
});

// Manajemen akun Guru
Route::prefix('v1/admin/teachers')->middleware([
    EnsureSanctumTokenStorage::class,
    'auth:sanctum',
    EnsureAdminRole::class,
])->group(function (): void {
    Route::get('/', [AdminTeacherController::class, 'index']);
    Route::post('/', [AdminTeacherController::class, 'store']);
    Route::get('/{user}', [AdminTeacherController::class, 'show']);
    Route::patch('/{user}', [AdminTeacherController::class, 'update']);
    Route::delete('/{user}', [AdminTeacherController::class, 'destroy']);
});