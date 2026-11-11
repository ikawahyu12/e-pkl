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
