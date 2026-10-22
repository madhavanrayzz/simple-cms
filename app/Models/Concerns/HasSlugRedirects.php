<?php

namespace App\Models\Concerns;

use App\Models\SlugRedirect;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Remembers every slug a record had before, so that old links (shares, search engine
 * indexes, bookmarks) can be answered with a 301 to the current URL instead of a 404.
 */
trait HasSlugRedirects
{
    public static function bootHasSlugRedirects(): void
    {
        static::updated(function ($model) {
            $oldSlug = $model->getOriginal('slug');

            if ($model->wasChanged('slug') && filled($oldSlug)) {
                // The newest owner of an old slug wins if two records used it in turn.
                SlugRedirect::updateOrCreate(
                    ['redirectable_type' => $model->getMorphClass(), 'old_slug' => $oldSlug],
                    ['redirectable_id' => $model->getKey()],
                );
            }
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function slugRedirects(): MorphMany
    {
        return $this->morphMany(SlugRedirect::class, 'redirectable');
    }

    /**
     * Narrow the query to the record that used to live at $slug. Combine it with the
     * visibility scope (e.g. published()) so a redirect never reveals a draft's new URL.
     */
    public function scopeWherePreviousSlug(Builder $query, string $slug): Builder
    {
        return $query->whereKey(
            SlugRedirect::where('redirectable_type', $this->getMorphClass())
                ->where('old_slug', $slug)
                ->select('redirectable_id')
        );
    }
}
