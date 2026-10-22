@props(['article', 'heading' => 'h3'])

<article class="group">
    <a href="{{ route('articles.show', $article->slug) }}" class="block rounded-box focus-visible:outline-offset-4">
        <x-cover :article="$article" :heading="$heading" class="aspect-[4/3] transition-transform duration-300 group-hover:-translate-y-1 motion-reduce:transition-none motion-reduce:group-hover:translate-y-0" />
        <div class="pt-4">
            @if($article->excerpt)
                <p class="text-base-content/75 leading-relaxed line-clamp-2">{{ $article->excerpt }}</p>
            @endif
            <p class="mt-2 text-sm text-base-content/65">
                @if($article->published_at)
                    <time datetime="{{ $article->published_at->toDateString() }}">{{ $article->published_at->format('M j, Y') }}</time>
                    <span class="mx-1.5" aria-hidden="true">/</span>
                @endif
                {{ __('frontend.articles.min_read', ['minutes' => $article->reading_time]) }}
            </p>
        </div>
    </a>
</article>
