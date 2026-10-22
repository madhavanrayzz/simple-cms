@props(['article', 'heading' => 'h3', 'eager' => false])

@php
    $category = $article->public_category;
    $hasImage = filled($article->featured_image);
@endphp
<div {{ $attributes->merge(['class' => 'cover']) }}
     style="--topic: {{ $category?->cover_color ?? '#151821' }}; --topic-ink: {{ $hasImage ? '#ffffff' : ($category?->cover_ink ?? '#ffffff') }}"
     data-initial="{{ mb_substr($category?->title ?? $article->title, 0, 1) }}">
    <span class="cover-topic">{{ $category?->title }}</span>
    <{{ $heading }} class="cover-title">{{ $article->title }}</{{ $heading }}>
    @if($hasImage)
        <img src="{{ Storage::url($article->featured_image) }}" alt="" @unless($eager) loading="lazy" @endunless />
    @endif
</div>
