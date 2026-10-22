<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\RedirectsOldSlugs;
use App\Models\Tag;

class TagController extends Controller
{
    use RedirectsOldSlugs;

    /**
     * @param string $slug
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show(string $slug)
    {
        $tag = Tag::where('slug', $slug)->whereHas('articles', fn ($q) => $q->published())->first();
        if (! $tag) {
            return $this->redirectFromOldSlug(Tag::whereHas('articles', fn ($q) => $q->published()), $slug, 'tags.show');
        }
        $articles = $tag->articles()->published()->with('category')->latest('published_at')->paginate(12);
        return view('frontend.tags.show', compact('tag', 'articles'));
    }
}
