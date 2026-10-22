<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOwner;
use App\Models\Concerns\HasSlugRedirects;
use App\Models\Concerns\HasUniqueSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Stevebauman\Purify\Facades\Purify;

class Article extends Model
{
    use BelongsToOwner;
    use HasSlugRedirects;
    use HasUniqueSlug;
    use SoftDeletes;

    /**
     * @var string[]
     */
    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'excerpt',
        'meta_title',
        'meta_description',
        'content',
        'featured_image',
        'is_published',
        'published_at',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    /**
     * @return void
     */
    protected static function booted(): void
    {
        static::creating(function (Article $article) {
            // Auto-assign current user ID when creating
            if (empty($article->user_id) && auth()->check()) {
                $article->user_id = auth()->id();
            }

            if (empty($article->slug)) {
                $baseSlug = Str::slug($article->title) ?: Str::lower(Str::random(8));
                $slug = $baseSlug;
                $count = 1;
                while (Article::withTrashed()->where('slug', $slug)->exists()) {
                    $slug = $baseSlug.'-'.++$count;
                }
                $article->slug = $slug;
            }
        });

        static::saving(function (Article $article) {
            if ($article->isDirty('content') && $article->content) {
                $article->content = Purify::clean($article->content);
            }

            // title and excerpt are rendered as plain text on the frontend; strip any HTML
            // so they can never become an XSS vector if later output in a raw/JS context.
            if ($article->isDirty('title') && $article->title) {
                $article->title = strip_tags($article->title);
            }
            foreach (['excerpt', 'meta_title', 'meta_description'] as $field) {
                if ($article->isDirty($field) && $article->{$field}) {
                    $article->{$field} = strip_tags($article->{$field});
                }
            }

            // Publishing without a date used to leave the article invisible forever,
            // because the published scope requires published_at. Treat it as "publish now".
            if ($article->is_published && $article->published_at === null) {
                $article->published_at = now();
            }
        });

        static::saved(fn (Article $article) => static::forgetDashboardStats($article));
        static::deleted(fn (Article $article) => static::forgetDashboardStats($article));
    }

    protected static function forgetDashboardStats(Article $article): void
    {
        if ($article->user_id) {
            cache()->forget('dashboard_stats:'.$article->user_id);
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function views(): HasMany
    {
        return $this->hasMany(ArticleView::class);
    }

    /**
     * @param string|null $ipAddress
     * @param string|null $userAgent
     * @param string|null $referer
     *
     * @return \App\Models\ArticleView
     */
    public function recordView(?string $ipAddress = null, ?string $userAgent = null, ?string $referer = null): ArticleView
    {
        return $this->views()->create([
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent ? mb_substr($userAgent, 0, 255) : null,
            'referer' => $referer ? mb_substr($referer, 0, 255) : null,
            'viewed_at' => now(),
        ]);
    }

    /**
     * @param $query
     *
     * @return mixed
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Published articles that share this article's category or one of its tags, newest first.
     */
    public function related(int $limit = 5): \Illuminate\Support\Collection
    {
        $tagIds = $this->tags()->pluck('tags.id');

        return static::published()
            ->with('category')
            ->whereKeyNot($this->getKey())
            ->where(fn (Builder $q) => $q
                ->when($this->category_id, fn (Builder $q) => $q->where('category_id', $this->category_id))
                ->when($tagIds->isNotEmpty(), fn (Builder $q) => $q->orWhereHas('tags', fn (Builder $q) => $q->whereKey($tagIds)))
                ->when(! $this->category_id && $tagIds->isEmpty(), fn (Builder $q) => $q->whereRaw('1 = 0')))
            ->latest('published_at')
            ->take($limit)
            ->get();
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string                                $term
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        // Escape LIKE wildcards with an explicit ESCAPE character: SQLite has no default one,
        // so a backslash escape would silently break searches for "100%" or "snake_case".
        $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';

        // Title matches first, then excerpt matches, then body-only matches; newest first within each.
        return $query->where(fn (Builder $q) => $q
            ->whereRaw("title LIKE ? ESCAPE '!'", [$like])
            ->orWhereRaw("excerpt LIKE ? ESCAPE '!'", [$like])
            ->orWhereRaw("content LIKE ? ESCAPE '!'", [$like]))
            ->orderByRaw("CASE WHEN title LIKE ? ESCAPE '!' THEN 0 WHEN excerpt LIKE ? ESCAPE '!' THEN 1 ELSE 2 END", [$like, $like])
            ->latest('published_at');
    }

    /**
     * The category as visitors may see it: null when it is inactive, because its page returns 404.
     */
    public function getPublicCategoryAttribute(): ?Category
    {
        return $this->category?->is_active ? $this->category : null;
    }

    public function getReadingTimeAttribute(): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags((string) $this->content)) / 200));
    }

    /**
     * draft, scheduled or published — what a visitor would see right now.
     */
    public function getStatusAttribute(): string
    {
        if (! $this->is_published || $this->published_at === null) {
            return 'draft';
        }

        return $this->published_at->isFuture() ? 'scheduled' : 'published';
    }
}
