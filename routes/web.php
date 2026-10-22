<?php

use App\Http\Controllers\Frontend\ArticleController;
use App\Http\Controllers\Frontend\CategoryController;
use App\Http\Controllers\Frontend\FeedController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\PageController;
use App\Http\Controllers\Frontend\SearchController;
use App\Http\Controllers\Frontend\TagController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:60,1')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/articles', [ArticleController::class, 'index'])->name('articles.index');
    Route::get('/article/{slug}', [ArticleController::class, 'show'])->name('articles.show')->where('slug', '[a-z0-9\-]+');
    Route::get('/category/{slug}', [CategoryController::class, 'show'])->name('categories.show')->where('slug', '[a-z0-9\-]+');
    Route::get('/tag/{slug}', [TagController::class, 'show'])->name('tags.show')->where('slug', '[a-z0-9\-]+');
    Route::get('/page/{slug}', [PageController::class, 'show'])->name('pages.show')->where('slug', '[a-z0-9\-]+');
    Route::get('/search', [SearchController::class, 'index'])->name('search');
    Route::get('/feed', [FeedController::class, 'rss'])->name('feed');
    Route::get('/sitemap.xml', [FeedController::class, 'sitemap'])->name('sitemap');

    // Draft previews: only reachable through a temporary signed URL generated in the admin panel.
    Route::middleware('signed')->group(function () {
        Route::get('/preview/article/{id}', [ArticleController::class, 'preview'])->name('articles.preview')->whereNumber('id');
        Route::get('/preview/page/{id}', [PageController::class, 'preview'])->name('pages.preview')->whereNumber('id');
    });
});
