{!! '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' !!}
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>{{ __('frontend.site_name') }}</title>
        <link>{{ route('home') }}</link>
        <description>{{ __('frontend.feed.description', ['name' => __('frontend.site_name')]) }}</description>
        <language>{{ str_replace('_', '-', app()->getLocale()) }}</language>
        <atom:link href="{{ route('feed') }}" rel="self" type="application/rss+xml" />
        @if($articles->isNotEmpty())
            <lastBuildDate>{{ $articles->first()->published_at->toRssString() }}</lastBuildDate>
        @endif
        @foreach($articles as $article)
            <item>
                <title>{{ $article->title }}</title>
                <link>{{ route('articles.show', $article->slug) }}</link>
                <guid isPermaLink="false">article-{{ $article->id }}</guid>
                <pubDate>{{ $article->published_at->toRssString() }}</pubDate>
                @if($article->public_category)
                    <category>{{ $article->public_category->title }}</category>
                @endif
                <description>{{ $article->meta_description ?: ($article->excerpt ?: Str::limit(strip_tags((string) $article->content), 300)) }}</description>
            </item>
        @endforeach
    </channel>
</rss>
