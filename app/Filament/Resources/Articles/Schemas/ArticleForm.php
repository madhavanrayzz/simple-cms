<?php

namespace App\Filament\Resources\Articles\Schemas;

use App\Filament\Schemas\SeoFields;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Content')
                    ->schema([
                        FileUpload::make('featured_image')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('articles')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
                            ->maxSize(5120)
                            // Derive the stored extension from the server-detected MIME type rather than
                            // trusting the client-supplied filename. Prevents a polyglot/double-extension
                            // upload (e.g. shell.php) from landing as an executable file under public/storage.
                            ->getUploadedFileNameForStorageUsing(
                                fn (TemporaryUploadedFile $file): string => Str::ulid().'.'.($file->guessExtension() ?? 'bin')
                            ),
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set, string $operation) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                        RichEditor::make('content')
                            ->columnSpanFull()
                            ->extraAttributes([
                                'style' => 'min-height: 300px;',
                            ]),
                    ]),
                Section::make('Settings')
                    ->schema([
                        Select::make('category_id')
                            ->relationship('category', 'title')
                            ->searchable()
                            ->preload(),
                        Select::make('tags')
                            ->relationship('tags', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(50),
                            ]),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                            ->validationMessages(['regex' => 'Use only lowercase letters, numbers and single hyphens, e.g. my-first-post.'])
                            ->unique(ignoreRecord: true),
                        Textarea::make('excerpt')
                            ->rows(6),
                        DateTimePicker::make('published_at')
                            ->helperText(fn (): string => 'Leave empty to publish now. A future date schedules the article. Times are in '.config('app.timezone').'.'),
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
