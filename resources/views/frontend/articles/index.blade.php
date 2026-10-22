<x-layouts.app :title="__('frontend.articles.title')">
    <x-page-header :title="__('frontend.articles.title')" :description="__('frontend.articles.description')" />

    <section class="container mx-auto px-4 sm:px-6 py-12">
        @if($articles->count())
            @if($categories->count())
                <nav aria-label="{{ __('frontend.articles.filter_by_topic') }}" class="flex flex-wrap items-center gap-2 mb-10">
                    <span class="text-sm text-base-content/65 mr-1">{{ __('frontend.articles.filter_by_topic') }}</span>
                    @foreach($categories as $category)
                        <a href="{{ route('categories.show', $category->slug) }}" class="btn btn-sm btn-outline gap-2" style="--topic: {{ $category->cover_color }}">
                            <span class="topic-dot"></span>
                            {{ $category->title }}
                            <span class="text-base-content/65 font-normal">{{ $category->articles_count }}</span>
                        </a>
                    @endforeach
                </nav>
            @endif

            <!-- Articles Grid -->
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-10">
                @foreach($articles as $article)
                    <x-article-card :article="$article" heading="h2" />
                @endforeach
            </div>

            <x-pagination :paginator="$articles" />
        @else
            <!-- Empty State -->
            <div class="text-center py-16">
                <div class="w-20 h-20 rounded-full bg-base-200 flex items-center justify-center mx-auto mb-6">
                    <x-lucide-newspaper class="h-10 w-10 text-base-content/30" />
                </div>
                <h2 class="font-display text-2xl font-semibold mb-3">{{ __('frontend.articles.no_articles') }}</h2>
                <p class="text-base-content/65">{{ __('frontend.empty.check_back') }}</p>
            </div>
        @endif
    </section>
</x-layouts.app>
