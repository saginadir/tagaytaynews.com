<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminMediaController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Public
Route::get('/', fn () => inertia('Welcome'))->name('home');

// Guest-only routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});

// Admin back-office
$adminPath = config('admin.path') ?: 'x-ops';
Route::prefix($adminPath)->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'showLogin'])->name('login');
    Route::post('/', [AdminController::class, 'login'])->name('authenticate');
    Route::post('/logout', [AdminController::class, 'logout'])->name('logout')->middleware('admin.auth');
    Route::middleware('admin.auth')->group(function () {
        Route::resource('media', AdminMediaController::class)->only(['index', 'store', 'update', 'destroy']);
    });
});
