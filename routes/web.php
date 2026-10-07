<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Kasir\PosController;
use App\Http\Controllers\Kasir\QrisController;
use App\Http\Controllers\MidtransWebhookController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\ProfileController;


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

        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');

        Route::patch('users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
        Route::resource('users', UserController::class)->except('show');

        Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');

    });

    Route::middleware('role:kasir,admin')->prefix('kasir')->group(function () {
        Route::get('/', [PosController::class, 'index'])->name('kasir.pos');
        Route::post('/checkout', [PosController::class, 'checkout'])->name('kasir.checkout');

        Route::get('/pembayaran/{order}', [QrisController::class, 'show'])->name('kasir.qris');
        Route::get('/pembayaran/{order}/status', [QrisController::class, 'status']);
        Route::post('/pembayaran/{order}/batal', [QrisController::class, 'cancel']);
    });

    Route::middleware('role:admin,kasir')->group(function () {
        Route::get('/transaksi', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/transaksi/{order}/struk', [OrderController::class, 'receipt'])->name('orders.receipt');
        Route::get('/profil', [ProfileController::class, 'show'])->name('profile.show');
        Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('/profil/password', [ProfileController::class, 'password'])->name('profile.password');
    });
});

// Webhook dari Midtrans: tanpa login, diamankan lewat signature
Route::post('/midtrans/notification', MidtransWebhookController::class);