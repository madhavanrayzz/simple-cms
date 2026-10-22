<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\RedirectsOldSlugs;
use App\Models\Page;

class PageController extends Controller
{
    use RedirectsOldSlugs;

    /**
     * @param string $slug
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show(string $slug)
    {
        $page = Page::published()->with([
            'parent' => fn ($q) => $q->published(),
            'children' => fn ($q) => $q->published(),
        ])->where('slug', $slug)->first();
        if (! $page) {
            return $this->redirectFromOldSlug(Page::published(), $slug, 'pages.show');
        }
        return view('frontend.pages.show', compact('page'));
    }

    /**
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function preview(int $id)
    {
        $page = Page::with([
            'parent' => fn ($q) => $q->published(),
            'children' => fn ($q) => $q->published(),
        ])->findOrFail($id);
        $preview = true;
        return response()
            ->view('frontend.pages.show', compact('page', 'preview'))
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Referrer-Policy', 'no-referrer');
    }
}
