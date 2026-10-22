@props(['paginator'])

@if($paginator->hasPages())
    <nav aria-label="{{ __('frontend.pagination.label') }}" class="mt-12 flex justify-center">
        <div class="join">
            @if($paginator->onFirstPage())
                <span class="join-item btn btn-disabled" aria-disabled="true" aria-label="{{ __('frontend.pagination.previous') }}">«</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="join-item btn" aria-label="{{ __('frontend.pagination.previous') }}">«</a>
            @endif

            @foreach($paginator->getUrlRange(max(1, $paginator->currentPage() - 3), min($paginator->lastPage(), $paginator->currentPage() + 3)) as $page => $url)
                @if($page == $paginator->currentPage())
                    <span class="join-item btn btn-primary" aria-current="page">{{ $page }}</span>
                @else
                    <a href="{{ $url }}" class="join-item btn" aria-label="{{ __('frontend.pagination.page', ['page' => $page]) }}">{{ $page }}</a>
                @endif
            @endforeach

            @if($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="join-item btn" aria-label="{{ __('frontend.pagination.next') }}">»</a>
            @else
                <span class="join-item btn btn-disabled" aria-disabled="true" aria-label="{{ __('frontend.pagination.next') }}">»</span>
            @endif
        </div>
    </nav>
@endif
