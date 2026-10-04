<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\CategoryController;


Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect(AuthController::homeFor(auth()->user()->role)));

    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', fn () => Inertia::render('Admin/Dashboard'))
            ->name('admin.dashboard');

        Route::resource('categories', CategoryController::class)->except('show');
    });

    Route::middleware('role:kasir')->prefix('kasir')->group(function () {
        Route::get('/', fn () => Inertia::render('Kasir/Pos'))
            ->name('kasir.pos');
    });
});