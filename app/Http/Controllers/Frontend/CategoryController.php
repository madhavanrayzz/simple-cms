<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\RedirectsOldSlugs;
use App\Models\Category;

class CategoryController extends Controller
{
    use RedirectsOldSlugs;

    /**
     * @param string $slug
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show(string $slug)
    {
        $category = Category::where('slug', $slug)->where('is_active', true)->first();
        if (! $category) {
            return $this->redirectFromOldSlug(Category::where('is_active', true), $slug, 'categories.show');
        }
        $articles = $category->articles()->published()->with('category')->latest('published_at')->paginate(12);
        $otherCategories = Category::where('is_active', true)
            ->where('id', '!=', $category->id)
            ->whereHas('articles', fn ($q) => $q->published())
            ->withCount(['articles' => fn ($q) => $q->published()])
            ->get();
        return view('frontend.categories.show', compact('category', 'articles', 'otherCategories'));
    }
}
