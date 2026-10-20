<?php

use App\Http\Controllers\Blog\BlogController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LlmsTxtController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

/*
| Public site. Loaded in the `public` middleware group (no session, CSRF or
| Inertia) so responses are cacheable to static files by CachePublicPage.
*/

Route::get('/', LandingController::class)->name('home');
foreach (array_keys(PageController::PAGES) as $page) {
    Route::get($page, PageController::class)->defaults('page', $page)->name($page);
}
Route::get('robots.txt', RobotsController::class)->name('robots');
Route::get('sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('llms.txt', LlmsTxtController::class)->name('llms');

Route::prefix('blog')->group(function () {
    Route::get('/', [BlogController::class, 'index'])->name('blog.index');
    Route::get('page/{page}', [BlogController::class, 'index'])->whereNumber('page')->name('blog.page');
    Route::get('feed.xml', FeedController::class)->name('blog.feed');
    Route::get('search', [BlogController::class, 'search'])->middleware('throttle:60,1')->name('blog.search');

    Route::get('category/{category}', [BlogController::class, 'category'])->name('blog.category');
    Route::get('category/{category}/page/{page}', [BlogController::class, 'category'])->whereNumber('page')->name('blog.category.page');

    Route::get('tag/{tag}', [BlogController::class, 'tag'])->name('blog.tag');
    Route::get('tag/{tag}/page/{page}', [BlogController::class, 'tag'])->whereNumber('page')->name('blog.tag.page');

    Route::get('author/{author}', [BlogController::class, 'author'])->name('blog.author');
    Route::get('author/{author}/page/{page}', [BlogController::class, 'author'])->whereNumber('page')->name('blog.author.page');

    // Keep these last: they match any single segment.
    Route::get('{article}.md', [BlogController::class, 'markdown'])->where('article', '[^/.]+')->name('blog.markdown');
    Route::get('{article}', [BlogController::class, 'show'])->where('article', '[^/.]+')->name('blog.show');
});
