<x-layouts.app :title="__('frontend.tags.title', ['tag' => $tag->name])" :description="__('frontend.tags.description', ['tag' => $tag->name])">
    <x-page-header :title="'#'.$tag->name" :kicker="trans_choice('frontend.articles.article_count', $articles->total(), ['count' => $articles->total()])" />

    <section class="container mx-auto px-4 sm:px-6 py-12">
        @if($articles->count())
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-10">
                @foreach($articles as $article)
                    <x-article-card :article="$article" heading="h2" />
                @endforeach
            </div>
            <x-pagination :paginator="$articles" />
        @else
            <div class="text-center py-16">
                <div class="w-20 h-20 rounded-full bg-base-200 flex items-center justify-center mx-auto mb-6">
                    <x-lucide-newspaper class="h-10 w-10 text-base-content/30" />
                </div>
                <h2 class="font-display text-2xl font-semibold mb-3">{{ __('frontend.articles.no_articles') }}</h2>
                <a href="{{ route('articles.index') }}" class="btn btn-outline btn-sm mt-4">{{ __('frontend.articles.browse_all_articles') }}</a>
            </div>
        @endif
    </section>
</x-layouts.app>
