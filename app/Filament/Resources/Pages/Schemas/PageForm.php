<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Filament\Schemas\SeoFields;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Page Content')
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set, string $operation) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                        RichEditor::make('content')
                            ->extraAttributes([
                                'style' => 'min-height: 300px;',
                            ]),
                    ]),
                Section::make('Settings')
                    ->schema([
                        Select::make('parent_id')
                            ->relationship('parent', 'title', function ($query, $record) {
                                // A page cannot be its own parent, nor sit under one of its descendants.
                                if ($record) {
                                    $query->whereKeyNot([$record->id, ...$record->descendantIds()]);
                                }

                                // Editors pick from pages they can see, but always keep the current parent,
                                // even when another user owns it; otherwise the form could not be saved.
                                return $query->where(fn ($q) => $q
                                    ->visibleTo(auth()->user())
                                    ->when($record?->parent_id, fn ($q) => $q->orWhereKey($record->parent_id)));
                            })
                            ->searchable()
                            ->preload(),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                            ->validationMessages(['regex' => 'Use only lowercase letters, numbers and single hyphens, e.g. my-first-post.'])
                            ->unique(ignoreRecord: true),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0),
                        Textarea::make('excerpt')
                            ->rows(3),
                        Toggle::make('is_published')
                            ->default(false),
                    ]),
                Section::make('SEO')
                    ->description('Optional. Search engines and social previews use the title and excerpt when these are empty.')
                    ->collapsible()
                    ->collapsed()
                    ->schema(SeoFields::make()),
            ]);
    }
}
