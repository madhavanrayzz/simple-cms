<x-layouts.app :title="$query !== '' ? __('frontend.search.results_for', ['query' => $query]) : __('frontend.search.title')" robots="noindex, follow">
    <x-page-header :title="__('frontend.search.title')">
        <form action="{{ route('search') }}" method="GET" role="search" class="join w-full max-w-2xl mt-6">
            <label class="input input-lg join-item w-full">
                <x-lucide-search class="h-5 w-5 opacity-50" />
                <input type="search" name="q" value="{{ $query }}" placeholder="{{ __('frontend.search.placeholder') }}" aria-label="{{ __('frontend.search.placeholder') }}" maxlength="100" />
            </label>
            <button type="submit" class="btn btn-lg btn-primary join-item">{{ __('frontend.search.button') }}</button>
        </form>
    </x-page-header>

    <section class="container mx-auto px-4 sm:px-6 py-12">
        @if($articles === null)
            @if($query !== '')
                <p class="text-base-content/65">{{ __('frontend.search.too_short') }}</p>
            @endif
        @elseif($articles->count())
            <p class="text-sm text-base-content/65 mb-8">
                {{ __('frontend.search.results_for', ['query' => $query]) }} · {{ trans_choice('frontend.search.result_count', $articles->total(), ['count' => $articles->total()]) }}
            </p>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-10">
                @foreach($articles as $article)
                    <x-article-card :article="$article" heading="h2" />
                @endforeach
            </div>
            <x-pagination :paginator="$articles" />
        @else
            <div class="text-center py-16">
                <div class="w-20 h-20 rounded-full bg-base-200 flex items-center justify-center mx-auto mb-6">
                    <x-lucide-search-x class="h-10 w-10 text-base-content/30" />
                </div>
                <h2 class="font-display text-2xl font-semibold mb-3">{{ __('frontend.search.no_results') }}</h2>
                <p class="text-base-content/65">{{ __('frontend.search.no_results_desc') }}</p>
            </div>
        @endif
    </section>
</x-layouts.app>
