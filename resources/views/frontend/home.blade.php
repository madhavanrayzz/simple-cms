<x-layouts.app :title="__('frontend.nav.home')">
    @php
        $lead = $articles->first();
        $next = $articles->slice(1, 4);
        $latest = $articles->slice(5);
    @endphp

    @if($lead)
        <!-- Lead story beside the next few headlines -->
        <section class="container mx-auto px-4 sm:px-6 pt-8 pb-14 md:pt-12">
            <h1 class="sr-only">{{ __('frontend.site_name') }}: {{ __('frontend.site_tagline') }}</h1>
            <div class="grid lg:grid-cols-12 gap-8 lg:gap-12">
                <a href="{{ route('articles.show', $lead->slug) }}" class="lg:col-span-7 group block rounded-box">
                    <x-cover :article="$lead" heading="h2" :eager="true" class="aspect-[4/3] md:aspect-[5/4] lg:aspect-[4/3]" />
                    <div class="mt-4 flex flex-wrap items-baseline gap-x-6 gap-y-2">
                        @if($lead->excerpt)
                            <p class="font-serif text-lg md:text-xl text-base-content/80 leading-snug max-w-[52ch]">{{ $lead->excerpt }}</p>
                        @endif
                        <p class="text-sm text-base-content/65 whitespace-nowrap">
                            @if($lead->published_at)
                                <time datetime="{{ $lead->published_at->toDateString() }}">{{ $lead->published_at->format('M j, Y') }}</time>
                                <span class="mx-1.5" aria-hidden="true">/</span>
                            @endif
                            {{ __('frontend.articles.min_read', ['minutes' => $lead->reading_time]) }}
                        </p>
                    </div>
                </a>

                @if($next->isNotEmpty())
                    <div class="lg:col-span-5">
                        <h2 class="text-sm font-semibold text-base-content/65 pb-3 border-b-2 border-base-content">{{ __('frontend.home.also_new') }}</h2>
                        <ul>
                            @foreach($next as $article)
                                <li class="border-b border-base-300">
                                    <a href="{{ route('articles.show', $article->slug) }}" class="group grid grid-cols-[auto_1fr] gap-x-3 py-5">
                                        <span class="topic-dot mt-2" style="--topic: {{ $article->public_category?->cover_color ?? 'var(--color-base-content)' }}"></span>
                                        <span>
                                            @if($article->public_category)
                                                <span class="block text-sm font-medium text-base-content/65">{{ $article->public_category->title }}</span>
                                            @endif
                                            <span class="block text-xl md:text-2xl font-semibold leading-tight tracking-tight group-hover:underline decoration-2 underline-offset-4">{{ $article->title }}</span>
                                            <span class="block mt-1.5 text-sm text-base-content/65">{{ __('frontend.articles.min_read', ['minutes' => $article->reading_time]) }}</span>
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                        <a href="{{ route('articles.index') }}" class="btn btn-primary mt-6">{{ __('frontend.nav.browse_articles') }}</a>
                    </div>
                @endif
            </div>
        </section>
    @else
        <!-- Empty site: tell the owner what to do next -->
        <section class="container mx-auto px-4 sm:px-6 py-20">
            <div class="cover aspect-[16/7] max-w-4xl" data-initial="{{ mb_substr(__('frontend.site_name'), 0, 1) }}">
                <span class="cover-topic">{{ __('frontend.site_name') }}</span>
                <h1 class="cover-title">{{ __('frontend.empty.no_content') }}</h1>
            </div>
            <p class="mt-6 text-lg text-base-content/70 max-w-xl">{{ __('frontend.empty.check_back') }}</p>
        </section>
    @endif

    @if($latest->isNotEmpty())
        <section id="latest" class="border-t border-base-300">
            <div class="container mx-auto px-4 sm:px-6 py-14">
                <div class="flex items-baseline justify-between gap-4 mb-8">
                    <h2 class="text-3xl md:text-4xl font-bold">{{ __('frontend.articles.latest') }}</h2>
                    <a href="{{ route('articles.index') }}" class="link link-hover font-medium text-primary">{{ __('frontend.articles.view_all_articles') }}</a>
                </div>
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-10">
                    @foreach($latest as $article)
                        <x-article-card :article="$article" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if($categories->isNotEmpty())
        <!-- Topics: one band per category, in its cover color -->
        <section class="border-t border-base-300 bg-base-200/60">
            <div class="container mx-auto px-4 sm:px-6 py-14">
                <h2 class="text-3xl md:text-4xl font-bold mb-8">{{ __('frontend.articles.browse_by_topic') }}</h2>
                <div class="grid md:grid-cols-2 gap-6">
                    @foreach($categories as $category)
                        <div class="rounded-box bg-base-100 border border-base-300 overflow-hidden" style="--topic: {{ $category->cover_color }}">
                            <a href="{{ route('categories.show', $category->slug) }}" class="flex items-center justify-between gap-4 px-6 py-5 border-t-8 border-[var(--topic)] group">
                                <span class="text-2xl font-bold tracking-tight group-hover:underline decoration-2 underline-offset-4">{{ $category->title }}</span>
                                <span class="text-sm text-base-content/65 whitespace-nowrap">{{ trans_choice('frontend.articles.article_count', $category->articles_count, ['count' => $category->articles_count]) }}</span>
                            </a>
                            <ul class="px-6 pb-4">
                                @foreach($category->articles as $article)
                                    <li class="border-t border-base-300 first:border-t-0">
                                        <a href="{{ route('articles.show', $article->slug) }}" class="block py-3 font-medium leading-snug hover:text-primary">{{ $article->title }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts.app>
