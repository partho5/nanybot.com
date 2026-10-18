<?php

use App\Http\Controllers\Api\V1\ArticleWebhookController;
use Illuminate\Support\Facades\Route;

// Mounted under /api by bootstrap/app.php.
Route::prefix('v1')->middleware(['api.key', 'throttle:webhook'])->group(function () {
    Route::post('articles', [ArticleWebhookController::class, 'store'])->name('api.articles.store');
    Route::put('articles/{externalId}', [ArticleWebhookController::class, 'update'])->name('api.articles.update');
    Route::delete('articles/{externalId}', [ArticleWebhookController::class, 'destroy'])->name('api.articles.destroy');
});
