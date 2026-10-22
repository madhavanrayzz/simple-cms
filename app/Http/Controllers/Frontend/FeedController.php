<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Models\Page;
use App\Models\Tag;
use Illuminate\Http\Response;

class FeedController extends Controller
{
    public function rss(): Response
    {
        $articles = Article::published()->with('category')->latest('published_at')->take(20)->get();
        return response()
            ->view('feeds.rss', compact('articles'))
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }

    public function sitemap(): Response
    {
        $articles = Article::published()->latest('published_at')->get(['slug', 'updated_at']);
        $pages = Page::published()->get(['slug', 'updated_at']);
        $categories = Category::where('is_active', true)->whereHas('articles', fn ($q) => $q->published())->get(['slug', 'updated_at']);
        $tags = Tag::whereHas('articles', fn ($q) => $q->published())->get(['slug', 'updated_at']);
        return response()
            ->view('feeds.sitemap', compact('articles', 'pages', 'categories', 'tags'))
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
