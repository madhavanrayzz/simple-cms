<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;

class HomeController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
     */
    public function index()
    {
        $articles = Article::published()->with('category')->latest('published_at')->take(11)->get();
        $categories = Category::where('is_active', true)->whereHas('articles', fn ($q) => $q->published())->withCount(['articles' => fn ($q) => $q->published()])
            ->with(['articles' => fn ($q) => $q->published()->latest('published_at')->limit(3)])
            ->orderByDesc('articles_count')
            ->get();
        return view('frontend.home', compact('articles', 'categories'));
    }
}
