<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Category Information')
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set, string $operation) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                            ->validationMessages(['regex' => 'Use only lowercase letters, numbers and single hyphens, e.g. my-first-post.'])
                            ->unique(ignoreRecord: true),
                        ColorPicker::make('color')
                            ->label('Cover color')
                            ->regex('/^#[0-9a-fA-F]{6}$/')
                            ->helperText('Used for article covers in this category when an article has no image. Leave empty for an automatic color.'),
                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->default(true),
                    ])
                    ->columns(2),
            ])->columns(1);
    }
}
