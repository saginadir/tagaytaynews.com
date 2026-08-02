<?php

use App\Http\Controllers\AdminArticleController;
use App\Http\Controllers\AdminCategoryController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminMediaController;
use App\Http\Controllers\AdminSourceController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PollController;
use App\Http\Controllers\SeoFilesController;
use App\Support\IndexNow;
use Illuminate\Support\Facades\Route;

// Public
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('/polls/{poll}/vote', [PollController::class, 'vote'])->name('polls.vote');
Route::get('/sitemap.xml', [SeoFilesController::class, 'sitemap'])->name('sitemap');
Route::get('/feed.xml', [SeoFilesController::class, 'feed'])->name('feed');
Route::get('/robots.txt', [SeoFilesController::class, 'robots'])->name('robots');
Route::get('/llms.txt', [SeoFilesController::class, 'llms'])->name('llms');

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
        Route::resource('articles', AdminArticleController::class)->except(['show']);
        Route::resource('categories', AdminCategoryController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('sources', AdminSourceController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('media', AdminMediaController::class)->only(['index', 'store', 'update', 'destroy']);
    });
});

// Public pages — wildcard routes stay last so static paths above win by order
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::get('/work-with-us', [PageController::class, 'workWithUs'])->name('work-with-us');
Route::get('/quiz', [PageController::class, 'quiz'])->name('quiz');
Route::get('/map', [PageController::class, 'map'])->name('map');

// IndexNow key verification file
Route::get('/{key}.txt', function (string $key) {
    abort_unless(hash_equals(IndexNow::key(), $key), 404);

    return response($key, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
})->where('key', '[a-z0-9]{32}')->name('indexnow.key');
Route::get('/{category:slug}/{article:slug}', [ArticleController::class, 'show'])
    ->where(['category' => '[a-z0-9-]+', 'article' => '[a-z0-9-]+'])
    ->name('article.show');
Route::get('/{category:slug}', [CategoryController::class, 'show'])
    ->where('category', '[a-z0-9-]+')
    ->name('category.show');
