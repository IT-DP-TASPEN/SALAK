<?php

use App\Http\Controllers\LabaRugiPdfController;
use App\Http\Controllers\NeracaPdfController;
use App\Http\Controllers\SsoLoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/admin/neraca/pdf', NeracaPdfController::class)
    ->middleware(['auth', 'can:page_Neraca'])
    ->name('neraca.pdf');

Route::get('/admin/laba-rugi/pdf', LabaRugiPdfController::class)
    ->middleware(['auth', 'can:page_LabaRugi'])
    ->name('laba-rugi.pdf');

Route::get('/sso/login', [SsoLoginController::class, 'store'])->name('sso.login');
