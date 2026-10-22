<?php

namespace App\Models;

use App\Models\Concerns\HasSlugRedirects;
use App\Models\Concerns\HasUniqueSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasSlugRedirects;
    use HasUniqueSlug;

    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * @return void
     */
    protected static function booted(): void
    {
        static::creating(function (Tag $tag) {
            if (empty($tag->slug)) {
                $baseSlug = Str::slug($tag->name) ?: Str::lower(Str::random(8));
                $slug = $baseSlug;
                $count = 1;
                while (Tag::where('slug', $slug)->exists()) {
                    $slug = $baseSlug.'-'.++$count;
                }
                $tag->slug = $slug;
            }
        });

        static::saving(function (Tag $tag) {
            if ($tag->isDirty('name') && $tag->name) {
                $tag->name = strip_tags($tag->name);
            }
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class);
    }
}
