<?php

use App\Http\Controllers\NeracaPdfController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/admin/neraca/pdf', NeracaPdfController::class)
    ->middleware(['auth', 'can:page_Neraca'])
    ->name('neraca.pdf');
