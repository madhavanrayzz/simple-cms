@props(['title', 'description' => null, 'topic' => null, 'kicker' => null])

{{-- A topic page gets a band in its cover color; other listing pages stay plain. --}}
<header @if($topic) style="--topic: {{ $topic }}" @endif class="border-b border-base-300 {{ $topic ? 'border-t-[12px] border-t-[var(--topic)]' : '' }}">
    <div class="container mx-auto px-4 sm:px-6 py-10 md:py-14">
        {{ $breadcrumbs ?? '' }}
        @if($kicker)
            <p class="text-base font-medium text-base-content/65 mb-2">{{ $kicker }}</p>
        @endif
        <h1 class="text-4xl md:text-6xl font-extrabold tracking-tight leading-[1.02]">{{ $title }}</h1>
        @if($description)
            <p class="mt-4 font-serif text-lg md:text-xl text-base-content/75 leading-snug max-w-[56ch]">{{ $description }}</p>
        @endif
        {{ $slot }}
    </div>
</header>
