<?php

use App\Http\Controllers\Admin\ApiKeyController;
use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\UploadSignatureController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Blog\BlogController;
use Illuminate\Support\Facades\Route;

// The public site (/, /blog, ...) lives in routes/blog.php.

Route::middleware(['auth', 'verified'])->group(function () {
    // Fortify's post-login home; the admin landing page is the article list.
    Route::get('dashboard', fn () => redirect()->route('admin.articles.index'))->name('dashboard');

    Route::get('admin', fn () => redirect()->route('admin.articles.index'));

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::post('uploads/sign', UploadSignatureController::class)->middleware('throttle:60,1')->name('uploads.sign');

        // Articles: authors manage their own, admins everything (ArticlePolicy).
        Route::get('articles/tags', [ArticleController::class, 'tags'])->name('articles.tags');
        Route::get('articles/{article}/preview', [BlogController::class, 'preview'])->name('articles.preview');
        Route::resource('articles', ArticleController::class)->except('show');

        // Admins manage everyone; authors may edit only their own profile (checked in the controller).
        Route::resource('users', UserController::class)->only(['edit', 'update']);

        Route::middleware('admin')->group(function () {
            Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
            Route::resource('tags', TagController::class)->only(['index', 'store', 'update', 'destroy']);
            Route::resource('users', UserController::class)->only(['index', 'store', 'destroy']);
            Route::resource('api-keys', ApiKeyController::class)->parameters(['api-keys' => 'apiKey'])->only(['index', 'store', 'destroy']);
        });
    });
});

require __DIR__.'/settings.php';
