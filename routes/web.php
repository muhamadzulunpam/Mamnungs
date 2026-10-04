<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Kasir\PosController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Admin\DashboardController;


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
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

        Route::resource('categories', CategoryController::class)->except('show');

        Route::patch('products/{product}/toggle', [ProductController::class, 'toggle'])
            ->name('products.toggle');
        Route::resource('products', ProductController::class)->except('show');
    });

    Route::middleware('role:kasir, admin')->prefix('kasir')->group(function () {
        Route::get('/', [PosController::class, 'index'])->name('kasir.pos');
        Route::post('/checkout', [PosController::class, 'checkout'])->name('kasir.checkout');
    });

    Route::middleware('role:admin,kasir')->group(function () {
        Route::get('/transaksi', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/transaksi/{order}/struk', [OrderController::class, 'receipt'])->name('orders.receipt');
    });
});