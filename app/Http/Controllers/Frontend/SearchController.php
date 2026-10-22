<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $raw = $request->query('q');
        $query = is_string($raw) ? trim(mb_substr($raw, 0, 100)) : '';
        $articles = mb_strlen($query) >= 2
            ? Article::published()->search($query)->with('category')->paginate(12)->withQueryString()
            : null;
        return view('frontend.search.index', compact('query', 'articles'));
    }
}
