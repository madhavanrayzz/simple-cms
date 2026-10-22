<?php

namespace App\Http\Controllers\Frontend\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;

trait RedirectsOldSlugs
{
    /**
     * 301 to the current URL of the record that used to live at $slug, or 404.
     * $query carries the visibility rules (published, active) the target must pass.
     */
    protected function redirectFromOldSlug(Builder $query, string $slug, string $route): RedirectResponse
    {
        $record = $query->wherePreviousSlug($slug)->firstOrFail();

        return redirect()->route($route, $record->slug, 301);
    }
}
