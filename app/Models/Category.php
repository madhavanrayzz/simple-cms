<?php

namespace App\Models;

use App\Models\Concerns\HasSlugRedirects;
use App\Models\Concerns\HasUniqueSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasSlugRedirects;
    use HasUniqueSlug;

    /**
     * @var string[]
     */
    protected $fillable = [
        'title',
        'slug',
        'description',
        'color',
        'is_active',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * @return void
     */
    protected static function booted(): void
    {
        // The color is printed into an inline style attribute, so only a strict #rrggbb value may be stored.
        static::saving(function (Category $category) {
            // Plain-text fields, rendered escaped; stripped as defense in depth like Article, Page and Tag.
            foreach (['title', 'description'] as $field) {
                if ($category->isDirty($field) && $category->{$field}) {
                    $category->{$field} = strip_tags($category->{$field});
                }
            }

            if ($category->isDirty('color')) {
                $category->color = preg_match('/^#[0-9a-f]{6}$/i', (string) $category->color) ? strtolower($category->color) : null;
            }
        });

        static::creating(function (Category $category) {
            if (empty($category->slug)) {
                $baseSlug = Str::slug($category->title) ?: Str::lower(Str::random(8));
                $slug = $baseSlug;
                $count = 1;
                while (Category::where('slug', $slug)->exists()) {
                    $slug = $baseSlug.'-'.++$count;
                }
                $category->slug = $slug;
            }
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    /**
     * Cover colors used when an editor has not picked one. Chosen by id, so a category
     * keeps the same color across pages and requests.
     *
     * @var string[]
     */
    public const PALETTE = ['#2a44d4', '#e0533a', '#3f7d4e', '#c9931a', '#6b3fa0', '#1f7a8c'];

    public function getCoverColorAttribute(): string
    {
        $color = preg_match('/^#[0-9a-f]{6}$/i', (string) $this->color) ? $this->color : null;

        return $color ?: self::PALETTE[max(0, ($this->id ?? 1) - 1) % count(self::PALETTE)];
    }

    /**
     * White or ink, whichever has the higher contrast on the cover color (WCAG relative
     * luminance), so the small topic label stays readable too, not only the large title.
     */
    public function getCoverInkAttribute(): string
    {
        [$r, $g, $b] = array_map(function (string $hex): float {
            $c = hexdec($hex) / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, str_split(substr($this->cover_color, 1), 2));
        $luminance = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;

        $onWhite = 1.05 / ($luminance + 0.05);
        $onInk = ($luminance + 0.05) / (0.0091 + 0.05); // #151821 has a luminance of about 0.0091

        return $onWhite >= $onInk ? '#ffffff' : '#151821';
    }
}
