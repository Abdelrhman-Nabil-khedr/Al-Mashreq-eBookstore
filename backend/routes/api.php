<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\AuthorController;
use App\Http\Controllers\API\BlogController;
use App\Http\Controllers\API\BookController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\PageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'app' => config('app.name'),
    ]);
});

/* =========================================================================
 | Version 1 API Routes (/api/v1/...)
 | ========================================================================= */
Route::prefix('v1')->group(function () {
    // Authentication Routes
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('api.v1.auth.register');
        Route::post('/login', [AuthController::class, 'login'])->name('api.v1.auth.login');
    });
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum')->name('api.v1.auth.logout');
    Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum')->name('api.v1.auth.user');

    // Books Catalog Routes
    Route::get('/books', [BookController::class, 'index'])->name('api.v1.books.index');
    Route::get('/books/{isbn}', [BookController::class, 'show'])->name('api.v1.books.show');

    // Authors Routes
    Route::get('/authors', [AuthorController::class, 'index'])->name('api.v1.authors.index');
    Route::get('/authors/{id}', [AuthorController::class, 'show'])->name('api.v1.authors.show');

    // Categories Routes
    Route::get('/categories', [CategoryController::class, 'index'])->name('api.v1.categories.index');
    Route::get('/categories/{id}', [CategoryController::class, 'show'])->name('api.v1.categories.show');

    // Blog Routes
    Route::get('/blog', [BlogController::class, 'index'])->name('api.v1.blog.index');
    Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('api.v1.blog.show');

    // Static Pages Routes
    Route::get('/pages', [PageController::class, 'index'])->name('api.v1.pages.index');
    Route::get('/pages/{slug}', [PageController::class, 'show'])->name('api.v1.pages.show');
});
