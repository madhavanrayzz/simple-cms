<x-layouts.app
    :title="$article->meta_title ?: $article->title"
    :description="$article->meta_description ?: ($article->excerpt ?: Str::limit(strip_tags((string) $article->content), 160))"
    :image="$article->featured_image"
    :canonical="route('articles.show', $article->slug)"
    :robots="isset($preview) ? 'noindex, nofollow' : null"
    type="article">
    @push('head')
        @if($article->published_at)
            <meta property="article:published_time" content="{{ $article->published_at->toIso8601String() }}">
        @endif
        <meta property="article:modified_time" content="{{ $article->updated_at?->toIso8601String() }}">
        @if($article->public_category)
            <meta property="article:section" content="{{ $article->public_category->title }}">
        @endif
        @foreach($article->tags as $tag)
            <meta property="article:tag" content="{{ $tag->name }}">
        @endforeach
        @php
            $jsonLd = array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'BlogPosting',
                'headline' => $article->title,
                'description' => $article->meta_description ?: $article->excerpt,
                'image' => $article->featured_image ? url(Storage::url($article->featured_image)) : null,
                'datePublished' => $article->published_at?->toIso8601String(),
                'dateModified' => $article->updated_at?->toIso8601String(),
                'articleSection' => $article->public_category?->title,
                'keywords' => $article->tags->pluck('name')->implode(', ') ?: null,
                'wordCount' => str_word_count(strip_tags((string) $article->content)),
                'mainEntityOfPage' => route('articles.show', $article->slug),
                'publisher' => ['@type' => 'Organization', 'name' => __('frontend.site_name')],
            ]);
        @endphp
        <script type="application/ld+json">@json($jsonLd)</script>
    @endpush
    @isset($preview)
        <div role="alert" class="alert alert-warning rounded-none justify-center">
            <x-lucide-eye class="h-5 w-5" />
            <span>{{ __('frontend.articles.preview_notice') }}</span>
        </div>
    @endisset
    <article>
        <header class="container mx-auto px-4 sm:px-6 pt-6 md:pt-10">
            <nav aria-label="Breadcrumb" class="text-sm mb-5">
                <ol class="flex flex-wrap items-center gap-2 text-base-content/65">
                    <li><a href="{{ route('home') }}" class="hover:text-primary hover:underline underline-offset-4">{{ __('frontend.nav.home') }}</a></li>
                    <li aria-hidden="true">/</li>
                    <li><a href="{{ route('articles.index') }}" class="hover:text-primary hover:underline underline-offset-4">{{ __('frontend.nav.articles') }}</a></li>
                    @if($article->category?->is_active)
                        <li aria-hidden="true">/</li>
                        <li><a href="{{ route('categories.show', $article->category->slug) }}" class="hover:text-primary hover:underline underline-offset-4">{{ $article->category->title }}</a></li>
                    @endif
                </ol>
            </nav>
            <x-cover :article="$article" heading="h1" :eager="true" class="min-h-[22rem] md:min-h-[30rem] [&_.cover-title]:max-w-[20ch] [&_.cover-title]:text-[clamp(2.25rem,6.5cqi,5.5rem)]" />
        </header>

        <div class="container mx-auto px-4 sm:px-6 py-10 md:py-14">
            <div class="grid lg:grid-cols-12 gap-12">
                <div class="lg:col-span-8">
                    <div class="reading mx-auto lg:mx-0">
                        @if($article->excerpt)
                            <p class="text-2xl leading-snug text-base-content/85 italic mb-6">{{ $article->excerpt }}</p>
                        @endif
                        <p class="font-sans text-sm text-base-content/65 pb-6 mb-8 border-b border-base-300 flex flex-wrap gap-x-5 gap-y-1">
                            @if($article->published_at)
                                <time datetime="{{ $article->published_at->toDateString() }}">{{ $article->published_at->format('F j, Y') }}</time>
                            @endif
                            <span>{{ __('frontend.articles.min_read', ['minutes' => $article->reading_time]) }}</span>
                            <span class="inline-flex items-center gap-1"><x-lucide-eye class="h-4 w-4" />{{ trans_choice('frontend.articles.view_count', $article->views_count, ['count' => number_format($article->views_count)]) }}</span>
                        </p>

                        <div class="prose prose-lg max-w-none font-serif prose-headings:font-sans prose-headings:tracking-tight prose-a:text-primary prose-a:decoration-2 prose-a:underline-offset-2">{!! $article->content !!}</div>

                        @if($article->tags->isNotEmpty())
                            <ul class="font-sans flex flex-wrap gap-2 mt-10" aria-label="{{ __('frontend.articles.tagged') }}">
                                @foreach($article->tags as $tag)
                                    <li><a href="{{ route('tags.show', $tag->slug) }}" class="badge badge-outline hover:badge-primary">#{{ $tag->name }}</a></li>
                                @endforeach
                            </ul>
                        @endif

                        @unless(isset($preview))
                            <!-- Share section (not on previews: it would share the secret signed URL) -->
                            <div class="font-sans mt-10 pt-8 border-t border-base-300">
                                <h2 class="text-sm font-semibold text-base-content/65 mb-3">{{ __('frontend.articles.share') }}</h2>
                                <div id="share-buttons" data-labels='@json(__('frontend.share'))'></div>
                            </div>
                        @endunless
                    </div>
                </div>

                @if($others->count())
                    <aside class="lg:col-span-4">
                        <div class="lg:sticky lg:top-24">
                            <h2 class="text-sm font-semibold text-base-content/65 pb-3 border-b-2 border-base-content">{{ __('frontend.articles.related') }}</h2>
                            <ul>
                                @foreach($others as $other)
                                    <li class="border-b border-base-300">
                                        <a href="{{ route('articles.show', $other->slug) }}" class="group grid grid-cols-[auto_1fr] gap-x-3 py-4">
                                            <span class="topic-dot mt-1.5" style="--topic: {{ $other->public_category?->cover_color ?? 'var(--color-base-content)' }}"></span>
                                            <span>
                                                <span class="block font-semibold leading-snug group-hover:underline decoration-2 underline-offset-4">{{ $other->title }}</span>
                                                <span class="block mt-1 text-sm text-base-content/65">{{ $other->published_at?->format('M j, Y') }}</span>
                                            </span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                            <a href="{{ route('articles.index') }}" class="link link-hover font-medium text-primary inline-block mt-5">{{ __('frontend.articles.view_all_articles') }}</a>
                        </div>
                    </aside>
                @endif
            </div>
        </div>
    </article>

    @push('scripts')
        @vite('resources/js/article.js')
    @endpush
</x-layouts.app>
