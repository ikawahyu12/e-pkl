<?php

use App\Http\Controllers\GuruAuthController;
use App\Http\Controllers\SiswaAuthController;
use App\Http\Middleware\EnsureGuruRole;
use App\Http\Middleware\EnsureSiswaRole;
use App\Http\Middleware\EnsureSanctumTokenStorage;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/guru/auth')->group(function (): void {
    Route::post('/login', [GuruAuthController::class, 'login'])->middleware('throttle:5,1');

    Route::middleware([EnsureSanctumTokenStorage::class, 'auth:sanctum', EnsureGuruRole::class])->group(function (): void {
        Route::get('/me', [GuruAuthController::class, 'me']);
        Route::post('/logout', [GuruAuthController::class, 'logout']);
    });
});

Route::prefix('v1/siswa/auth')->group(function (): void {
    Route::post('/login', [SiswaAuthController::class, 'login'])->middleware('throttle:5,1');

    Route::middleware([EnsureSanctumTokenStorage::class, 'auth:sanctum', EnsureSiswaRole::class])->group(function (): void {
        Route::get('/me', [SiswaAuthController::class, 'me']);
        Route::post('/logout', [SiswaAuthController::class, 'logout']);
    });
});
