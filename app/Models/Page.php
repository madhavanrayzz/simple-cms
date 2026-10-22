<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOwner;
use App\Models\Concerns\HasSlugRedirects;
use App\Models\Concerns\HasUniqueSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Stevebauman\Purify\Facades\Purify;

class Page extends Model
{
    use BelongsToOwner;
    use HasSlugRedirects;
    use HasUniqueSlug;
    use SoftDeletes;

    /**
     * @var string[]
     */
    protected $fillable = [
        'parent_id',
        'title',
        'slug',
        'excerpt',
        'meta_title',
        'meta_description',
        'content',
        'is_published',
        'sort_order',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'is_published' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * @return void
     */
    protected static function booted(): void
    {
        static::creating(function (Page $page) {
            // Auto-assign current user ID when creating
            if (empty($page->user_id) && auth()->check()) {
                $page->user_id = auth()->id();
            }

            if (empty($page->slug)) {
                $baseSlug = Str::slug($page->title) ?: Str::lower(Str::random(8));
                $slug = $baseSlug;
                $count = 1;
                while (Page::withTrashed()->where('slug', $slug)->exists()) {
                    $slug = $baseSlug.'-'.++$count;
                }
                $page->slug = $slug;
            }
        });

        static::saving(function (Page $page) {
            if ($page->isDirty('content') && $page->content) {
                $page->content = Purify::clean($page->content);
            }

            // title and excerpt are rendered as plain text on the frontend; strip any HTML
            // so they can never become an XSS vector if later output in a raw/JS context.
            if ($page->isDirty('title') && $page->title) {
                $page->title = strip_tags($page->title);
            }
            foreach (['excerpt', 'meta_title', 'meta_description'] as $field) {
                if ($page->isDirty($field) && $page->{$field}) {
                    $page->{$field} = strip_tags($page->{$field});
                }
            }
        });

        static::saved(function (Page $page) {
            cache()->forget('nav_pages');
            static::forgetDashboardStats($page);
        });

        static::deleted(function (Page $page) {
            cache()->forget('nav_pages');
            static::forgetDashboardStats($page);
        });
    }

    protected static function forgetDashboardStats(Page $page): void
    {
        if ($page->user_id) {
            cache()->forget('dashboard_stats:'.$page->user_id);
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'parent_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(Page::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * @param $query
     *
     * @return mixed
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * @param $query
     *
     * @return mixed
     */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * IDs of every page below this one, at any depth. Walks the tree level by level, and
     * stops on ids it has seen already, so a cycle that exists in old data cannot loop forever.
     *
     * @return int[]
     */
    public function descendantIds(): array
    {
        $ids = [];
        $level = [$this->getKey()];
        while ($level) {
            $level = array_values(array_diff(
                static::withTrashed()->whereIn('parent_id', $level)->pluck('id')->all(),
                $ids,
                [$this->getKey()]
            ));
            $ids = array_merge($ids, $level);
        }
        return $ids;
    }
}
