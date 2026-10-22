<?php

namespace App\Filament\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class SeoFields
{
    /**
     * @return array<\Filament\Forms\Components\Field>
     */
    public static function make(): array
    {
        return [
            TextInput::make('meta_title')
                ->label('Meta title')
                ->maxLength(255)
                ->helperText('Shown in search results and browser tabs. About 60 characters works best.'),
            Textarea::make('meta_description')
                ->label('Meta description')
                ->rows(3)
                ->maxLength(320)
                ->helperText('Shown under the title in search results. About 155 characters works best.'),
        ];
    }
}
