<x-layouts.app
    :title="$page->meta_title ?: $page->title"
    :description="$page->meta_description ?: ($page->excerpt ?: Str::limit(strip_tags((string) $page->content), 160))"
    :canonical="route('pages.show', $page->slug)"
    :robots="isset($preview) ? 'noindex, nofollow' : null">
    @isset($preview)
        <div role="alert" class="alert alert-warning rounded-none justify-center">
            <x-lucide-eye class="h-5 w-5" />
            <span>{{ __('frontend.articles.preview_notice') }}</span>
        </div>
    @endisset
    @php
        $isContactPage = $page->slug === 'contact';
    @endphp
    <x-page-header :title="$page->title" :description="$page->excerpt">
        <x-slot:breadcrumbs>
            <nav aria-label="Breadcrumb" class="text-sm mb-6">
                <ol class="flex flex-wrap items-center gap-2 text-base-content/65">
                    <li><a href="{{ route('home') }}" class="hover:text-primary hover:underline underline-offset-4">{{ __('frontend.nav.home') }}</a></li>
                    @if($page->parent)
                        <li aria-hidden="true">/</li>
                        <li><a href="{{ route('pages.show', $page->parent->slug) }}" class="hover:text-primary hover:underline underline-offset-4">{{ $page->parent->title }}</a></li>
                    @endif
                </ol>
            </nav>
        </x-slot:breadcrumbs>
    </x-page-header>
    <!-- Page Content -->
    <div class="container mx-auto px-4 sm:px-6 py-12">
        @if($isContactPage)
            <!-- Contact Page Layout with Sidebar -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
                <!-- Main Content -->
                <article class="lg:col-span-7">
                    <div class="reading prose prose-lg font-serif prose-headings:font-sans prose-headings:tracking-tight prose-a:text-primary">{!! $page->content !!}</div>
                </article>
                <!-- Sidebar -->
                <aside class="lg:col-span-5">
                    <div class="lg:sticky lg:top-24 space-y-6">
                        <!-- Location -->
                        <div class="border border-base-300 rounded-lg overflow-hidden">
                            <div class="aspect-[4/3] bg-base-200 flex items-center justify-center">
                                <x-lucide-map class="w-24 h-24 text-base-content/20" />
                            </div>
                            <div class="p-4 bg-base-100">
                                <p class="text-sm text-base-content/65">
                                    <x-lucide-map-pin class="h-4 w-4 inline-block mr-1 -mt-0.5" />
                                    {{ __('frontend.pages.contact_address') }}
                                </p>
                            </div>
                        </div>
                        <!-- Quick Contact Info -->
                        <div class="border border-base-300 rounded-lg p-6">
                            <h3 class="font-display font-semibold mb-4 pb-4 border-b border-base-300">{{ __('frontend.pages.quick_contact') }}</h3>
                            <ul class="space-y-4 text-sm">
                                <li class="flex items-start gap-3">
                                    <x-lucide-mail class="h-5 w-5 text-primary shrink-0 mt-0.5" />
                                    <span class="text-base-content/70">{{ __('frontend.pages.contact_email') }}</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <x-lucide-clock class="h-5 w-5 text-primary shrink-0 mt-0.5" />
                                    <span class="text-base-content/70">{{ __('frontend.pages.contact_hours') }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </aside>
            </div>
        @else
            <!-- Default Page Layout (centered) -->
            <div class="max-w-3xl">
                <article>
                    <div class="reading prose prose-lg font-serif prose-headings:font-sans prose-headings:tracking-tight prose-a:text-primary">{!! $page->content !!}</div>
                    <!-- Last Updated -->
                    <div class="mt-12 pt-8 border-t border-base-300">
                        <p class="text-sm text-base-content/65">
                            {{ __('frontend.pages.last_updated', ['date' => $page->updated_at->format('F d, Y')]) }}
                        </p>
                    </div>
                </article>
            </div>
        @endif
    </div>
    <!-- Subpages -->
    @if($page->children->count())
        <section class="bg-base-200/30 border-t border-base-300 py-12">
            <div class="container mx-auto px-6">
                <div class="max-w-3xl">
                    <h2 class="font-display text-xl font-semibold mb-8">{{ __('frontend.pages.related_pages') }}</h2>

                    <div class="grid md:grid-cols-2 gap-4">
                        @foreach($page->children as $child)
                            <a href="{{ route('pages.show', $child->slug) }}" class="group p-6 bg-base-100 rounded-lg border border-base-300 hover:border-primary transition-colors">
                                <div class="flex items-start gap-4">
                                    <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
                                        <x-lucide-file-text class="h-5 w-5 text-primary" />
                                    </div>
                                    <div>
                                        <h3 class="font-display text-lg font-medium group-hover:text-primary transition-colors">{{ $child->title }}</h3>
                                        @if($child->excerpt)
                                            <p class="text-base-content/65 text-sm mt-1 line-clamp-2">{{ $child->excerpt }}</p>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif
</x-layouts.app>
