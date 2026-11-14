<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
use App\Http\Controllers\JurnalNativeController;

Route::get('/jurnal', [JurnalNativeController::class, 'index'])->name('jurnal.index');
Route::post('/jurnal', [JurnalNativeController::class, 'store'])->name('jurnal.store');