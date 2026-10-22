<?php

namespace App\Filament\Actions;

use Closure;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

/**
 * Opens the record on the public site. A live record links to its public URL; a draft or
 * scheduled one gets a temporary signed preview link that works without a login.
 */
class ViewOnSiteAction
{
    public static function make(string $showRoute, string $previewRoute, Closure $isLive): Action
    {
        return Action::make('viewOnSite')
            ->label(fn (Model $record): string => $isLive($record) ? 'View on site' : 'Preview')
            ->icon(fn (Model $record) => $isLive($record) ? Heroicon::OutlinedArrowTopRightOnSquare : Heroicon::OutlinedEye)
            ->color('gray')
            ->url(fn (Model $record): string => $isLive($record)
                ? route($showRoute, $record->slug)
                : URL::temporarySignedRoute($previewRoute, now()->addHour(), ['id' => $record->getKey()]))
            ->openUrlInNewTab()
            ->hidden(fn (Model $record): bool => $record->trashed());
    }

    public static function forArticle(): Action
    {
        return static::make('articles.show', 'articles.preview', fn ($record) => $record->status === 'published');
    }

    public static function forPage(): Action
    {
        return static::make('pages.show', 'pages.preview', fn ($record) => $record->is_published);
    }
}
