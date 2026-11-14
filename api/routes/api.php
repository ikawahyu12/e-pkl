<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\GuruAuthController;
use App\Http\Controllers\SiswaAuthController;
use App\Http\Middleware\EnsureAdminRole;
use App\Http\Middleware\EnsureGuruRole;
use App\Http\Middleware\EnsureSiswaRole;
use App\Http\Middleware\EnsureSanctumTokenStorage;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Siswa\ProfilController;
use App\Http\Controllers\Api\V1\Admin\GuruController;
use App\Http\Controllers\Api\V1\Admin\GuruController as AdminGuruController;
use App\Http\Controllers\Api\V1\Guru\ProfilController as GuruProfilController;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Api\V1\Guru\AgendaController;

Route::get('v1/foto/{path}', function (string $path) {
    abort_if(str_contains($path, '..'), 404);
    abort_unless(Storage::disk('public')->exists($path), 404);
    return response()->file(Storage::disk('public')->path($path));
})->where('path', 'foto-guru/.*');

Route::prefix('v1/admin')->middleware([
    EnsureSanctumTokenStorage::class,
    'auth:sanctum',
    EnsureAdminRole::class,
])->group(function (): void {
    Route::get('/guru', [GuruController::class, 'index']);

    Route::get('/jurusan', function () {
        return response()->json([
            'data' => \Illuminate\Support\Facades\DB::table('jurusan')
                ->orderBy('nama')->get(['id', 'kode', 'nama']),
        ]);
    });
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
// Profil Siswa
Route::prefix('v1/siswa')->middleware([
    EnsureSanctumTokenStorage::class,
    'auth:sanctum',
    EnsureSiswaRole::class,
])->group(function (): void {
    Route::get('/profil', [ProfilController::class, 'show']);
    Route::post('/profil', [ProfilController::class, 'update']);
    Route::post('/profil/password', [ProfilController::class, 'updatePassword']);
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

// Admin: Guru Pembimbing
Route::prefix('v1/admin')->middleware([
    EnsureSanctumTokenStorage::class,
    'auth:sanctum',
    EnsureAdminRole::class,
])->group(function (): void {
    Route::get('/jurusan', [AdminGuruController::class, 'jurusan']);
    Route::get('/guru', [AdminGuruController::class, 'index']);
    Route::post('/guru', [AdminGuruController::class, 'store']);
    Route::get('/guru/{id}', [AdminGuruController::class, 'show']);
    Route::put('/guru/{id}', [AdminGuruController::class, 'update']);
    Route::delete('/guru/{id}', [AdminGuruController::class, 'destroy']);
});

  // Profil Guru
Route::prefix('v1/guru')->middleware([
    EnsureSanctumTokenStorage::class,
    'auth:sanctum',
    EnsureGuruRole::class,
])->group(function (): void {
    Route::get('/profil', [GuruProfilController::class, 'show']);
    Route::post('/profil', [GuruProfilController::class, 'update']);
    Route::post('/profil/password', [GuruProfilController::class, 'updatePassword']);

        // Agenda
    Route::get('/agenda', [AgendaController::class, 'index']);
    Route::get('/agenda/opsi', [AgendaController::class, 'opsi']);
    Route::post('/agenda', [AgendaController::class, 'store']);
    Route::patch('/agenda/{id}/status', [AgendaController::class, 'updateStatus']);
    Route::delete('/agenda/{id}', [AgendaController::class, 'destroy']);
});