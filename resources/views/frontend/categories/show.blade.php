<x-layouts.app :title="$category->title" :description="$category->description" :canonical="route('categories.show', $category->slug)">
    <x-page-header :title="$category->title" :description="$category->description" :topic="$category->cover_color"
                   :kicker="trans_choice('frontend.articles.article_count', $articles->total(), ['count' => $articles->total()])">
        <x-slot:breadcrumbs>
            <nav aria-label="Breadcrumb" class="text-sm mb-6">
                <ol class="flex flex-wrap items-center gap-2 text-base-content/65">
                    <li><a href="{{ route('home') }}" class="hover:text-primary hover:underline underline-offset-4">{{ __('frontend.nav.home') }}</a></li>
                    <li aria-hidden="true">/</li>
                    <li><a href="{{ route('articles.index') }}" class="hover:text-primary hover:underline underline-offset-4">{{ __('frontend.nav.articles') }}</a></li>
                </ol>
            </nav>
        </x-slot:breadcrumbs>
    </x-page-header>

    <section class="container mx-auto px-4 sm:px-6 py-12">
        @if($articles->count())
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-10">
                @foreach($articles as $article)
                    <x-article-card :article="$article" heading="h2" />
                @endforeach
            </div>
            <x-pagination :paginator="$articles" />
        @else
            <div class="max-w-xl py-10">
                <h2 class="text-2xl font-bold mb-2">{{ __('frontend.articles.no_articles_in_category') }}</h2>
                <p class="text-base-content/70 mb-6">{{ __('frontend.articles.no_articles_in_category_desc') }}</p>
                <a href="{{ route('articles.index') }}" class="btn btn-primary">{{ __('frontend.articles.browse_all_articles') }}</a>
            </div>
        @endif
    </section>

    @if($otherCategories->count())
        <section class="border-t border-base-300 bg-base-200/60">
            <div class="container mx-auto px-4 sm:px-6 py-12">
                <h2 class="text-2xl font-bold mb-6">{{ __('frontend.categories.explore_other') }}</h2>
                <ul class="flex flex-wrap gap-3">
                    @foreach($otherCategories as $otherCategory)
                        <li>
                            <a href="{{ route('categories.show', $otherCategory->slug) }}" class="btn btn-outline gap-2" style="--topic: {{ $otherCategory->cover_color }}">
                                <span class="topic-dot"></span>
                                {{ $otherCategory->title }}
                                <span class="text-base-content/65 font-normal">{{ $otherCategory->articles_count }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif
</x-layouts.app>
