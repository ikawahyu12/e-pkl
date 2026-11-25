<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'app' => 'E-PKL API',
        'status' => 'running',
        'api_base' => url('/api/v1'),
    ]);
});

Route::get('/verify-account', function () {
    $role = request()->query('role');
    $token = request()->query('token');
    abort_unless(in_array($role, ['guru', 'siswa'], true) && is_string($token), 404);

    return view('auth.verify-account', [
        'role' => $role,
        'token' => $token,
        'verifyUrl' => url('/api/v1/'.$role.'/auth/verify-email'),
    ]);
});

Route::get('/password-reset/guru', function () {
    return view('auth.reset-password-guru', [
        'apiUrl' => url('/api/v1/guru/auth/reset-password'),
        'email' => request()->query('email', ''),
        'token' => request()->query('token', ''),
    ]);
});

// Dimatikan: JurnalNativeController butuh public/koneksi.php & view jurnal.index yang tidak ada.
// Jurnal siswa sudah lewat API: /api/v1/siswa/jurnal
// Route::get('/jurnal', [JurnalNativeController::class, 'index'])->name('jurnal.index');
// Route::post('/jurnal', [JurnalNativeController::class, 'store'])->name('jurnal.store');