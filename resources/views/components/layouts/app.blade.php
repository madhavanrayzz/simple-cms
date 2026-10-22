<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#f5f6f8" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#12141b" media="(prefers-color-scheme: dark)">
    @php
        $metaTitle = ($title ?? __('frontend.nav.home')).' - '.__('frontend.site_name');
        $metaDescription = Str::limit(strip_tags($description ?? __('frontend.site_description')), 160);
        $metaImage = isset($image) && $image ? url(Storage::url($image)) : null;
        // Paginated listings are separate documents: keep ?page=N in the canonical URL.
        $canonicalUrl = ($canonical ?? url()->current()).(request()->integer('page') > 1 ? '?page='.request()->integer('page') : '');
    @endphp
    <title>{{ $metaTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    @if(isset($robots))
        <meta name="robots" content="{{ $robots }}">
    @endif
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <link rel="sitemap" type="application/xml" href="{{ route('sitemap') }}">
    <link rel="alternate" type="application/rss+xml" title="{{ __('frontend.site_name') }}" href="{{ route('feed') }}">
    <!-- Open Graph / Twitter -->
    <meta property="og:site_name" content="{{ __('frontend.site_name') }}">
    <meta property="og:title" content="{{ $title ?? __('frontend.site_name') }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:type" content="{{ $type ?? 'website' }}">
    <meta property="og:locale" content="{{ str_replace('-', '_', app()->getLocale()) }}">
    @if($metaImage)
        <meta property="og:image" content="{{ $metaImage }}">
        <meta name="twitter:image" content="{{ $metaImage }}">
    @endif
    <meta name="twitter:card" content="{{ $metaImage ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $title ?? __('frontend.site_name') }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    @stack('head')
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link rel="stylesheet" href="https://fonts.bunny.net/css?family=schibsted-grotesk:400,500,600,700,800|source-serif-4:400,400i,600,600i,700&display=swap">
    @vite('resources/css/app.css')
    @stack('styles')
</head>
<body class="min-h-screen flex flex-col bg-base-100 text-base-content">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[60] btn btn-primary btn-sm">{{ __('frontend.nav.skip') }}</a>
    <!-- Header -->
    <header class="sticky top-0 z-50 bg-base-100/90 backdrop-blur border-b border-base-300">
        <div class="navbar container mx-auto px-4 sm:px-6 justify-between min-h-16">
            <div class="navbar-start">
                <a href="{{ route('home') }}" class="text-xl font-extrabold tracking-tight">
                    {{ __('frontend.site_name') }}
                </a>
            </div>
            <div class="navbar-end hidden lg:flex gap-2">
                <ul class="menu menu-horizontal gap-1">
                    <li><a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">{{ __('frontend.nav.home') }}</a></li>
                    <li><a href="{{ route('articles.index') }}" class="{{ request()->routeIs('articles.*') ? 'active' : '' }}">{{ __('frontend.nav.articles') }}</a></li>
                    @foreach($navPages as $navPage)
                        <li><a href="{{ route('pages.show', $navPage->slug) }}" class="{{ request()->is('page/'.$navPage->slug) ? 'active' : '' }}">{{ $navPage->title }}</a></li>
                    @endforeach
                </ul>
                <form action="{{ route('search') }}" method="GET" role="search">
                    <label class="input input-sm w-48">
                        <x-lucide-search class="h-4 w-4 opacity-50" />
                        <input type="search" name="q" value="{{ request()->routeIs('search') && is_string(request()->query('q')) ? request()->query('q') : '' }}" placeholder="{{ __('frontend.search.placeholder') }}" aria-label="{{ __('frontend.search.placeholder') }}" maxlength="100" />
                    </label>
                </form>
            </div>
            <div class="navbar-end lg:hidden gap-1">
                <a href="{{ route('search') }}" class="btn btn-ghost btn-square" aria-label="{{ __('frontend.search.title') }}">
                    <x-lucide-search class="w-5 h-5" />
                </a>
                <div class="dropdown dropdown-end">
                    <div tabindex="0" role="button" aria-label="{{ __('frontend.nav.menu') }}" aria-haspopup="true" class="btn btn-ghost btn-square">
                        <x-lucide-menu class="w-5 h-5" />
                    </div>
                    <ul tabindex="0" class="dropdown-content menu bg-base-200 rounded-box z-50 w-52 p-2 shadow-lg mt-3">
                        <li><a href="{{ route('home') }}">{{ __('frontend.nav.home') }}</a></li>
                        <li><a href="{{ route('articles.index') }}">{{ __('frontend.nav.articles') }}</a></li>
                        @foreach($navPages as $navPage)
                            <li><a href="{{ route('pages.show', $navPage->slug) }}">{{ $navPage->title }}</a></li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </header>
    <!-- Main content -->
    <main id="main" class="flex-1">
        {{ $slot }}
    </main>
    <!-- Footer -->
    <footer class="bg-neutral text-neutral-content">
        <div class="container mx-auto px-6 py-12">
            <div class="flex flex-col md:flex-row justify-between gap-8">
                <div>
                    <span class="text-lg font-extrabold tracking-tight">{{ __('frontend.site_name') }}</span>
                    <p class="mt-3 text-neutral-content/75 text-sm max-w-xs">
                        {{ __('frontend.site_description') }}
                    </p>
                </div>
                <div class="flex gap-12">
                    <div>
                        <h2 class="font-semibold text-sm text-neutral-content/75 mb-4">{{ __('frontend.nav.navigate') }}</h2>
                        <ul class="space-y-2 text-sm">
                            <li><a href="{{ route('home') }}" class="text-neutral-content/80 hover:text-neutral-content hover:underline underline-offset-4">{{ __('frontend.nav.home') }}</a></li>
                            <li><a href="{{ route('articles.index') }}" class="text-neutral-content/80 hover:text-neutral-content hover:underline underline-offset-4">{{ __('frontend.nav.articles') }}</a></li>
                            <li><a href="{{ route('search') }}" class="text-neutral-content/80 hover:text-neutral-content hover:underline underline-offset-4">{{ __('frontend.search.title') }}</a></li>
                            <li><a href="{{ route('feed') }}" class="text-neutral-content/80 hover:text-neutral-content hover:underline underline-offset-4 inline-flex items-center gap-1"><x-lucide-rss class="h-3.5 w-3.5" />{{ __('frontend.nav.rss') }}</a></li>
                        </ul>
                    </div>
                    @if($footerPages->isNotEmpty())
                    <div>
                        <h2 class="font-semibold text-sm text-neutral-content/75 mb-4">{{ __('frontend.nav.info') }}</h2>
                        <ul class="space-y-2 text-sm">
                            @foreach($footerPages as $footerPage)
                                <li><a href="{{ route('pages.show', $footerPage->slug) }}" class="text-neutral-content/80 hover:text-neutral-content hover:underline underline-offset-4">{{ $footerPage->title }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </div>
            </div>
            <div class="divider my-8"></div>
            <div class="flex flex-col md:flex-row justify-between items-center gap-4 text-sm text-neutral-content/75">
                <p>{{ __('frontend.footer.copyright', ['year' => date('Y'), 'name' => __('frontend.site_name')]) }}</p>
                <p>{{ __('frontend.footer.built_with') }}</p>
            </div>
        </div>
    </footer>
    @stack('scripts')
</body>
</html>
