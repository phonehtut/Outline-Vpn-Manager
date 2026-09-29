<?php

use App\Http\Controllers\Admin\AccessKeyController as AdminAccessKeyController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\SellerController;
use App\Http\Controllers\Admin\ServerController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Seller\AccessKeyController;
use App\Http\Controllers\Seller\DashboardController as SellerDashboardController;
use Illuminate\Support\Facades\Route;

// Auth
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// Admin routes
Route::prefix('admin')
    ->middleware(['auth', 'role:admin'])
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('/keys', [AdminAccessKeyController::class, 'index'])->name('keys.index');
        Route::post('/keys', [AdminAccessKeyController::class, 'store'])->name('keys.store');
        Route::patch('/keys/{accessKey}', [AdminAccessKeyController::class, 'update'])->name('keys.update');
        Route::delete('/keys/{accessKey}', [AdminAccessKeyController::class, 'destroy'])->name('keys.destroy');

        Route::resource('sellers', SellerController::class)->except('show');

        Route::resource('servers', ServerController::class)->except('show');
    });

// Seller routes
Route::prefix('seller')
    ->middleware(['auth', 'role:seller'])
    ->name('seller.')
    ->group(function () {
        Route::get('/dashboard', [SellerDashboardController::class, 'index'])->name('dashboard');

        Route::prefix('servers/{server}')->name('servers.')->group(function () {
            Route::get('/keys', [AccessKeyController::class, 'index'])->name('keys.index');
            Route::post('/keys', [AccessKeyController::class, 'store'])->name('keys.store');
            Route::post('/keys/sync', [AccessKeyController::class, 'sync'])->name('keys.sync');
            Route::patch('/keys/{accessKey}', [AccessKeyController::class, 'update'])->name('keys.update');
            Route::delete('/keys/{accessKey}', [AccessKeyController::class, 'destroy'])->name('keys.destroy');
        });
    });

// Redirect root to login
Route::redirect('/', '/login');
