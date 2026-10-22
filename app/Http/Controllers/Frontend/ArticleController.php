<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\RedirectsOldSlugs;
use App\Models\Article;
use App\Models\Category;

class ArticleController extends Controller
{
    use RedirectsOldSlugs;

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
     */
    public function index()
    {
        $articles = Article::published()->with('category')->latest('published_at')->paginate(12);
        $categories = Category::where('is_active', true)
            ->whereHas('articles', fn ($q) => $q->published())
            ->withCount(['articles' => fn ($q) => $q->published()])
            ->get();
        return view('frontend.articles.index', compact('articles', 'categories'));
    }

    /**
     * @param string $slug
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show(string $slug)
    {
        $article = Article::published()->with(['category', 'tags'])->withCount('views')->where('slug', $slug)->first();
        if (! $article) {
            return $this->redirectFromOldSlug(Article::published(), $slug, 'articles.show');
        }
        $others = $this->sidebarArticles($article);
        // Record the view with details (deduplicated per IP per 30 minutes)
        $ip = filter_var(request()->ip(), FILTER_VALIDATE_IP) ? request()->ip() : null;
        $cacheKey = 'article_view:'.$article->id.':'.md5($ip ?? 'unknown');
        if (cache()->add($cacheKey, true, now()->addMinutes(30))) {
            $article->recordView(
                $ip,
                request()->userAgent(),
                request()->header('referer')
            );
        }
        return view('frontend.articles.show', compact('article', 'others'));
    }

    /**
     * Render a draft or scheduled article for the editor who holds the signed link.
     * No view is recorded and search engines are told not to index it.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function preview(int $id)
    {
        $article = Article::with(['category', 'tags'])->withCount('views')->findOrFail($id);
        $others = $this->sidebarArticles($article);
        $preview = true;
        return response()
            ->view('frontend.articles.show', compact('article', 'others', 'preview'))
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Referrer-Policy', 'no-referrer');
    }

    /**
     * Related articles first, topped up with the latest ones.
     */
    protected function sidebarArticles(Article $article): \Illuminate\Support\Collection
    {
        $related = $article->related();
        if ($related->count() >= 5) {
            return $related;
        }
        return $related->concat(
            Article::published()
                ->with('category')
                ->whereKeyNot($related->modelKeys())
                ->whereKeyNot($article->id)
                ->latest('published_at')
                ->take(5 - $related->count())
                ->get()
        );
    }
}
