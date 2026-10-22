{!! '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url><loc>{{ route('home') }}</loc></url>
    <url><loc>{{ route('articles.index') }}</loc></url>
    @foreach($articles as $article)
        <url><loc>{{ route('articles.show', $article->slug) }}</loc><lastmod>{{ $article->updated_at->toAtomString() }}</lastmod></url>
    @endforeach
    @foreach($pages as $page)
        <url><loc>{{ route('pages.show', $page->slug) }}</loc><lastmod>{{ $page->updated_at->toAtomString() }}</lastmod></url>
    @endforeach
    @foreach($categories as $category)
        <url><loc>{{ route('categories.show', $category->slug) }}</loc><lastmod>{{ $category->updated_at->toAtomString() }}</lastmod></url>
    @endforeach
    @foreach($tags as $tag)
        <url><loc>{{ route('tags.show', $tag->slug) }}</loc><lastmod>{{ $tag->updated_at->toAtomString() }}</lastmod></url>
    @endforeach
</urlset>
