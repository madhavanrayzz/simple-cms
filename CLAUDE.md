# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

A simple content management system built with Laravel 13, Filament PHP 5.10 (admin panel), DaisyUI 5 (frontend), and Lucide Icons 2. Manages Pages, Articles, Categories, Tags, and Media uploads with Spatie Media Library. Has SEO fields with Open Graph/JSON-LD, `sitemap.xml`/`robots.txt`/RSS, search (LIKE-based, titles rank first), 301 redirects from old slugs, signed draft previews, and a trash (soft deletes) for articles and pages. Frontend is fully internationalized. Content is sanitized with stevebauman/purify to prevent XSS. Tests run on Pest 5 / PHPUnit 13.

## Common Commands

```bash
# Development
composer install          # Install PHP dependencies
npm install               # Install Node dependencies
npm run dev               # Start Vite dev server with HMR
npm run build             # Build production assets
php artisan serve         # Start Laravel dev server at localhost:8000

# Database
php artisan migrate       # Run migrations
php artisan migrate:fresh --seed  # Reset DB and seed
php artisan db:seed       # Seed database with admin and editor users

# Testing
./vendor/bin/pest      # Run all tests (Pest 5; no asset build needed, TestCase calls withoutVite())
./vendor/bin/pest --filter=TestName  # Run specific test

# Filament
php artisan make:filament-resource ModelName --generate  # Create CRUD resource
php artisan filament:upgrade  # Upgrade Filament assets

# Cache
php artisan optimize:clear  # Clear all caches
```

## Architecture

### Admin Panel (Filament v5)
- **Location**: `app/Filament/Resources/`
- **Panel Provider**: `app/Providers/Filament/AdminPanelProvider.php`
- **Access**: `/admin` (admin: admin@admin.com / password, editor: editor@editor.com / password)
- Resources follow Filament v5 structure with separate Schemas/ and Tables/ directories
- `canAccessPanel()` restricts access to admin and editor roles only

### Filament 5 Resource Property Types
When creating Filament resources, use these property type declarations:
```php
protected static ?string $model = Model::class;
protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;
protected static ?int $navigationSort = 1;
```

Note: In Filament 5, `$navigationIcon` uses `BackedEnum` (not `UnitEnum`). Import with `use BackedEnum;`.
Note: `$navigationGroup` still uses `\UnitEnum|string|null` type (PHP property variance requirement).

### Filament 5 Component Namespaces
**IMPORTANT**: In Filament 5, layout/structural components are in `Filament\Schemas\Components`:
```php
// Layout components - in Schemas\Components (NOT Forms\Components!)
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\RenderHook;

// Form input components - still in Forms\Components
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;

// Schema class
use Filament\Schemas\Schema;

// Utilities
use Filament\Schemas\Components\Utilities\Get;
```

### Filament 5 Resource Directory Structure
When creating a resource, Filament 5 generates this structure:
```
app/Filament/Resources/
└── Customers/
    ├── CustomerResource.php
    ├── Pages/
    │   ├── CreateCustomer.php
    │   ├── EditCustomer.php
    │   └── ListCustomers.php
    ├── Schemas/
    │   └── CustomerForm.php
    └── Tables/
        └── CustomersTable.php
```

### Filament 5 Form Schema Pattern
```php
// In Schemas/CustomerForm.php
namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->required(),
                TextInput::make('email')->email()->required(),
            ]);
    }
}
```

### Filament 5 Widget Properties
Widget class properties are **instance properties**, NOT static:
```php
// CORRECT - instance properties
protected ?string $heading = 'Chart Title';
protected ?string $description = 'Chart description';
protected ?string $maxHeight = '300px';
protected int|string|array $columnSpan = 'full';

// WRONG - do NOT use static
// protected static ?string $heading = 'Title';  // This will error!
```

### Filament 5 Panel Configuration
```php
use Filament\Support\Enums\Width;

public function panel(Panel $panel): Panel
{
    return $panel
        ->maxContentWidth(Width::Full)  // Full width content
        ->spa()  // Single-page application mode
        ->unsavedChangesAlerts()  // Warn before leaving unsaved forms
        ->databaseTransactions();  // Wrap operations in transactions
}
```

### Filament 5 Hiding Fields by Operation
```php
use Filament\Support\Enums\Operation;

TextInput::make('password')
    ->password()
    ->required()
    ->hiddenOn(Operation::Edit)  // Hide on edit page
    ->visibleOn(Operation::Create);  // Only show on create
```

### Frontend (DaisyUI 5 + Lucide Icons)
- **Controllers**: `app/Http/Controllers/Frontend/`
- **Views**: `resources/views/frontend/`
- **Layout**: `resources/views/components/layouts/app.blade.php`
- **Language**: `lang/en/frontend.php` (all text is internationalized)
- Uses DaisyUI component classes and Lucide icons (`<x-lucide-*>`)

### Models
- `Category` - hasMany Articles, keeps old slugs for redirects
- `Article` - belongsTo Category, belongsToMany Tags, soft deletes, has published + `search()` scopes, `status` (draft/scheduled/published) and `reading_time` accessors, `related()` (same category or shared tag), auto-generates slug, has views tracking, `meta_title`/`meta_description`
- `Tag` - belongsToMany Articles, auto-generates slug, managed in `TagResource` (no owner, like Category)
- `ArticleView` - tracks article views with IP, user agent, referer
- `Page` - self-referential (parent/children), soft deletes, has published scope, auto-generates slug, `meta_title`/`meta_description`, `descendantIds()` (the parent select excludes them so no cycles)
- `SlugRedirect` - old slug → record (morph), written by the `HasSlugRedirects` trait on Article, Page, Category, Tag
- `User` - implements FilamentUser, has role (admin/editor)
- `MediaItem` - implements HasMedia for standalone media uploads

### User Roles
Uses simple role field with `App\Enums\UserRole` enum:
- **Admin**: Full access, can manage users
- **Editor**: Can manage articles, categories, pages (no user management)
- `role` is NOT mass-assignable. Set it explicitly: `$user->role = UserRole::Admin; $user->save();`

```php
// Check role
$user->isAdmin();  // true if admin
$user->isEditor(); // true if editor
```

### Security Patterns
- **HTML Sanitization**: Article and Page `content` is sanitized via `stevebauman/purify` on save to prevent stored XSS. `title` and `excerpt` are plain-text fields and get `strip_tags()` in the same `saving()` hook (defense-in-depth, since they are rendered with escaped `{{ }}` on the frontend)
- **View Deduplication**: Article views are deduplicated per IP per 30 minutes via cache
- **Route Constraints**: All slug routes have `[a-z0-9\-]+` regex constraints
- **Rate Limiting**: All frontend routes are wrapped in `throttle:60,1` (`routes/web.php`) so the per-request `article_views` write path can't be used to flood the DB
- **Security Headers**: `app/Http/Middleware/SecurityHeaders.php` (appended globally in `bootstrap/app.php`, covers `/admin` too) sets `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, a `Content-Security-Policy` (`fonts.bunny.net` is allowed in `style-src`/`font-src`; the only difference between panels is `script-src`: `/admin` adds `'unsafe-inline' 'unsafe-eval'` for Alpine, the frontend stays `'self'`), and `Strict-Transport-Security` (only emitted over HTTPS via `$request->isSecure()`). CSP is skipped entirely while the Vite `hot` file exists (i.e. during `npm run dev`) so HMR is not blocked; it always applies to built/production responses
- **Trusted Proxies**: none are trusted, so `X-Forwarded-*` headers are ignored and `request()->ip()` (view tracking, dedup, throttling) can't be spoofed. CAVEAT: behind a load balancer/CDN all visitors would share the proxy's IP (collapsing the throttle and view dedup) — in that case add a `config/trustedproxy.php` with a `'proxies'` key, which the framework's `TrustProxies` middleware reads at request time. Never configure it via `env()` in the `bootstrap/app.php` middleware closure: that closure runs before `.env` is loaded, so it would silently never apply
- **Password Policy**: The Filament `UserForm` password field uses `Password::min(8)->mixedCase()->numbers()->uncompromised()` (Have-I-Been-Pwned check + complexity)
- **File Upload Validation**: Featured images require an explicit MIME type allowlist AND the stored filename's extension is derived from the server-detected MIME via `getUploadedFileNameForStorageUsing(... $file->guessExtension() ...)`, never from the client filename — prevents a polyglot/double-extension upload landing as an executable file under `public/storage`
- **CSRF**: `Illuminate\Foundation\Http\Middleware\PreventRequestForgery` (Laravel 13's renamed CSRF middleware; `VerifyCsrfToken` is now a deprecated alias) is wired in `AdminPanelProvider`
- **Mass Assignment**: `role` field is excluded from `$fillable` on User model. `user_id` is excluded from `$fillable` on Article, Page, Media, and MediaItem — it is auto-assigned in `booted()` hooks, never user-editable
- **Ownership Authorization**: Articles, Pages, Media, and MediaItems track `user_id` ownership (auto-assigned via model `booted()` hooks). Editors can update/delete their own records OR orphan records (records whose `user_id` was nulled by `nullOnDelete` after the original owner was deleted), so content does not get permanently locked from anyone but admins. `forceDelete` stays admin-only
- **Policy Enforcement**: A single `OwnerablePolicy` handles authorization for Article, Page, Media, and MediaItem — see `app/Policies/OwnerablePolicy.php`. All models are mapped to it via `Gate::policy()` in `AppServiceProvider`. The user_id comparison casts both sides to `(int)` to remain correct on MySQL where some PDO configurations return BIGINT FK columns as strings. `viewAny` returns `true` unconditionally (visibility is enforced at the query layer — see `BelongsToOwner` trait below); `view` mirrors `update`/`delete` (admin or owner/orphan) as defense-in-depth for any future record View page
- **Query Scoping**: All ownership-aware queries use the `App\Models\Concerns\BelongsToOwner` trait's `visibleTo(?User $user)` scope. Used in: every `getEloquentQuery()` override, every dashboard widget (`StatsOverview`, `RecentActivityTable`, `TopArticlesTable`, `ArticleViewsChart`), the `PageForm` parent_id selector, and the MediaTable filter scopes — single source of truth so policy and listings can never drift apart
- **Bulk Delete Authorization**: `DeleteBulkAction` uses `authorizeIndividualRecords('delete')` to check per-record policies. NOTE: this hits the raw `Gate` (not Filament's "no policy = allow" convention), so every model using it MUST have a registered policy — `User` has `UserPolicy` (admin-only, `delete` denies self) for exactly this reason; without it bulk user delete silently deletes nothing
- **Last-Admin Guard**: `EditUser::handleRecordUpdate` blocks demoting the final remaining admin out of the Admin role (throws a `ValidationException` on `data.role`), preventing a lockout of user management with no in-app recovery
- **Cascade Protection**: `user_id` foreign keys use `nullOnDelete()` to preserve content when a user is deleted (content becomes orphaned and accessible to other editors)

### Database
- SQLite by default (`database/database.sqlite`)
- Migrations: `database/migrations/`

## Key Patterns

### Slug Generation
Article and Page models auto-generate slugs from title on creation via `booted()` hooks, with deduplication (appends `-2`, `-3`, etc.). The `HasUniqueSlug` trait (`app/Models/Concerns/`) catches `UniqueConstraintViolationException` on insert and retries with a random suffix to handle race conditions. The trait verifies the violation is actually about the slug column (via an existence check) before mutating the slug — so a violation on a different unique column (e.g., a future email constraint) won't silently corrupt the slug.
```php
// Auto-generation in booted()
static::creating(function ($model) {
    if (empty($model->slug)) {
        $model->slug = Str::slug($model->title);
    }
});

// Race condition safety via HasUniqueSlug trait on save()
```

### Cache Patterns
The app caches in three places and follows two rules: **cache primitives only** (no Eloquent collections, no Filament `Stat` objects — keeps the payload safe regardless of Laravel 13's `serializable_classes` setting), and **invalidate via model `booted()` hooks**, not TTL alone.

| Cache key                        | Where                                   | TTL    | Invalidated by                                                                                               |
|----------------------------------|-----------------------------------------|--------|--------------------------------------------------------------------------------------------------------------|
| `nav_pages`                      | `App\View\Composers\NavigationComposer` | 300s   | `Page::saved` (also fires on restore), `Page::deleted` (also fires on soft delete and force delete)          |
| `dashboard_stats:{user_id}`      | `App\Filament\Widgets\StatsOverview`    | 60s    | `Article::saved/deleted`, `Page::saved/deleted` (owner's key), `User::saved` on role change, `User::deleted` |
| `article_view:{article_id}:{md5(ip)}` | `ArticleController` view dedup          | 30 min | TTL only                                                                                                     |

Cache key for `dashboard_stats` is per-user because admins get extra `totalUsers`/`totalAdmins`/`totalEditors` keys that editors don't — without per-user keys an editor promoted to admin would hit a stale payload and crash with an undefined-key error. The `User::booted()` hook (`if ($user->wasChanged('role'))`) busts the cache on role change to make this safe.

### Adding Ownership to a New Model
If you add a new model that should follow the same ownership rules as Article/Page/Media:

1. Add a nullable `user_id` foreign key with `nullOnDelete()` in the migration
2. `use App\Models\Concerns\BelongsToOwner;` on the model
3. Add the auto-assign hook in `booted()`: `static::creating(fn ($m) => empty($m->user_id) && auth()->check() ? $m->user_id = auth()->id() : null);`
4. Map the model to `OwnerablePolicy` in `AppServiceProvider::boot()` via `Gate::policy()`
5. In any Filament resource for it, override `getEloquentQuery()` to call `->visibleTo(auth()->user())`
6. Exclude `user_id` from `$fillable` so it can't be mass-assigned

### Published Scopes
Articles and Pages have `published` scopes for filtering:
```php
Article::published()->get();  // is_published=true, published_at <= now
Page::published()->get();     // is_published=true
```

### Frontend Routes
```
/                    - Home (latest articles)
/articles            - Article listing
/article/{slug}      - Article detail (301 if {slug} is an old slug of a published article)
/category/{slug}     - Category articles (301 from old slugs)
/tag/{slug}          - Tag articles (301 from old slugs; 404 when the tag has no published articles)
/page/{slug}         - Page detail (301 from old slugs)
/search?q=           - Search published articles (title > excerpt > content ranking, LIKE wildcards escaped with ESCAPE '!', noindex)
/feed                - RSS 2.0 of the latest 20 published articles
/sitemap.xml         - Published articles/pages, active categories and tags with published articles
/preview/article/{id}, /preview/page/{id} - `signed` middleware only; no view tracking, X-Robots-Tag noindex
```

### Publishing, Trash and Slugs
- `is_published` with an empty `published_at` is saved as "publish now" (the `saving` hook sets `published_at`). A future date means scheduled.
- Article/Page delete is a soft delete. Restore uses the `restore` policy (admin or owner/orphan). `forceDelete` stays admin-only. Resources override `getRecordRouteBindingEloquentQuery()` so a trashed record can still open its edit page. A trashed row keeps its slug in the unique index, so slug dedup loops use `withTrashed()` and `HasUniqueSlug` uses `withoutGlobalScopes()`.
- The slug auto-fills from the title only on create. Editing the title never changes a live URL. A slug change on update writes a `slug_redirects` row. The redirect lookup uses `wherePreviousSlug()` combined with the visibility scope, so it never reveals the new URL of a draft. A live record with the slug always wins over a redirect.
- Slug inputs have the regex `^[a-z0-9]+(?:-[a-z0-9]+)*$` so they always match the route constraints.
- `ViewOnSiteAction` (`app/Filament/Actions/`) links live records to the public URL and drafts to a 1-hour `URL::temporarySignedRoute` preview. Preview responses send `Referrer-Policy: no-referrer` (the middleware only sets its default when a response has none), so the signature cannot leak through links on the preview page.
- Dashboard view counts are always scoped through `whereIn('article_id', Article::visibleTo(...))`, for admins too, so views of trashed articles are kept but not counted. "Published" counts use the `published()` scope (scheduled articles are not counted).
- A tag page returns 404 unless the tag has at least one published article.
- `ContentSeeder` is safe to re-run: `withTrashed()->firstOrCreate()` and demo views are only generated when `article_views` is empty.

### SEO
`public/robots.txt` stays a static file: the standard Laravel nginx config (Herd, Valet, Forge) has a `location = /robots.txt` block, so a Laravel route for it returns 404. The layout advertises the sitemap with `<link rel="sitemap">`; add a `Sitemap:` line with the full domain to robots.txt per deployment.
The layout (`components/layouts/app.blade.php`) takes optional `title`, `description`, `image` (storage path), `canonical`, `robots`, `type` props and renders the description, canonical, OG and Twitter tags. Pages push extra head tags with `@push('head')`. JSON-LD is built in `@php` and printed with single-line `@json(...)` (a multi-line `@json` does not compile). In XML Blade views, print the prolog as `'<'.'?xml ...?'.'>'`, because a literal `?>` closes PHP.

## DaisyUI 5 Guidelines

### Project design: "topic covers"
- Themes: `paper` (default, light) and `ink` (`prefersdark: true`) in `resources/css/app.css`. There is no `data-theme` attribute on `<html>`, so the OS setting picks the theme.
- Fonts come from Bunny Fonts, an independent GDPR-friendly drop-in replacement for Google Fonts (same open-source library, compatible URLs; `<link>` in the layout; CSP allows `https://fonts.bunny.net` in `style-src` and `font-src`): Schibsted Grotesk for headings and UI, Source Serif 4 for long-form text (`font-serif`, `.reading`). The admin panel uses Filament's local Inter.
- `<x-cover :article>` renders the article's typographic cover: the title on the category's `cover_color` (or the featured image with a scrim). `Category::cover_color` falls back to `Category::PALETTE` by id; `cover_ink` picks whichever of white or `#151821` has the higher contrast on the cover color (WCAG luminance). `Category::color` is sanitized to `#rrggbb` on save, because it is printed into inline `style` attributes.
- `<x-article-card>`, `<x-page-header>` (optional `topic` band) and `<x-pagination>` are the shared listing building blocks.
- Overrides for third-party CSS (share buttons, Tailwind Typography quotes) must stay OUTSIDE `@layer`: unlayered library CSS beats any layered rule.

### Configuration (resources/css/app.css)
DaisyUI 5 uses CSS-based configuration with Tailwind CSS 4 (generic example; this project's real themes are `paper` and `ink`):
```css
@plugin "daisyui" {
    themes: false;
    exclude: properties;
}

/* Custom theme */
@plugin "daisyui/theme" {
    name: "editorial";
    default: true;
    color-scheme: light;
    --color-base-100: oklch(98% 0.006 90);
    --color-primary: oklch(40% 0.15 15);
    /* ... other colors */
}
```

### Key DaisyUI 5 Components

**Layout:**
- **navbar**: `navbar`, `navbar-start`, `navbar-center`, `navbar-end`
- **hero**: `hero`, `hero-content`, `hero-overlay`
- **footer**: `footer`, `footer-title`, `footer-center`
- **drawer**: `drawer`, `drawer-toggle`, `drawer-content`, `drawer-side`, `drawer-overlay`

**Display:**
- **card**: `card`, `card-body`, `card-title`, `card-actions` (sizes: `card-xs` to `card-xl`)
- **badge**: `badge`, colors + styles: `badge-primary`, `badge-soft`, `badge-ghost`, `badge-outline`
- **alert**: `alert`, `alert-info`, `alert-success`, `alert-warning`, `alert-error`
- **stat**: `stats`, `stat`, `stat-title`, `stat-value`, `stat-desc`

**Navigation:**
- **menu**: `menu`, `menu-horizontal`, `menu-title`, sizes: `menu-xs` to `menu-xl`
- **breadcrumbs**: `breadcrumbs` with `<ul><li><a>` structure
- **tabs**: `tabs`, `tab`, `tab-content`, styles: `tabs-box`, `tabs-border`, `tabs-lift`

**Actions:**
- **btn**: Colors + styles: `btn-primary`, `btn-ghost`, `btn-outline`, `btn-soft`, `btn-link`
  Sizes: `btn-xs`, `btn-sm`, `btn-md`, `btn-lg`, `btn-xl`
  Modifiers: `btn-wide`, `btn-block`, `btn-square`, `btn-circle`
- **dropdown**: `dropdown`, `dropdown-content`, placement: `dropdown-end`, `dropdown-top`

**Form:**
- **input**: `input`, `input-primary`, `input-ghost`, sizes: `input-xs` to `input-xl`
- **select**: `select`, colors and sizes like input
- **textarea**: `textarea`, colors and sizes like input
- **checkbox**: `checkbox`, `checkbox-primary`, sizes: `checkbox-xs` to `checkbox-xl`
- **toggle**: `toggle`, `toggle-primary`, sizes: `toggle-xs` to `toggle-xl`
- **label**: `label` for descriptions, `floating-label` for floating labels

**Utility:**
- **divider**: `divider`, `divider-horizontal`, `divider-vertical`
- **loading**: `loading`, styles: `loading-spinner`, `loading-dots`, `loading-ring`
- **modal**: `modal`, `modal-box`, `modal-action`, `modal-backdrop`

### DaisyUI 5 New Features
- **Soft style**: `badge-soft`, `btn-soft` for softer appearance
- **Dash style**: `badge-dash`, `card-dash` for dashed borders
- **XL size**: `btn-xl`, `badge-xl` now available
- **Effects**: `--depth` and `--noise` theme variables
- Colors use oklch() format for better customization

## Lucide Icons

The frontend uses [Blade Lucide Icons](https://github.com/mallardduck/blade-lucide-icons). Use the component syntax:

```blade
<x-lucide-arrow-right class="w-4 h-4" />
<x-lucide-newspaper class="w-10 h-10 text-base-content/30" />
<x-lucide-menu class="w-5 h-5" />
<x-lucide-eye class="h-5 w-5" />
<x-lucide-map-pin class="h-4 w-4" />
<x-lucide-mail class="h-5 w-5 text-primary" />
<x-lucide-clock class="h-5 w-5 text-primary" />
<x-lucide-file-text class="h-5 w-5 text-primary" />
<x-lucide-chevron-left class="h-4 w-4" />
<x-lucide-chevron-right class="h-4 w-4" />
```

Browse all icons at [lucide.dev/icons](https://lucide.dev/icons/).

### Theme Colors
Use semantic color classes:
- `bg-base-100/200/300` - Background levels
- `text-base-content` - Main text color
- `bg-primary`, `text-primary` - Primary accent
- `bg-neutral`, `text-neutral-content` - Footer/dark sections

## File Upload & Spatie Media Library

### Storage Setup
Run `php artisan storage:link` to create the public symlink for uploaded files.

### Spatie Media Library File Upload
Use `SpatieMediaLibraryFileUpload` for models that implement `HasMedia`:

```php
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

// Basic usage
SpatieMediaLibraryFileUpload::make('avatar')
    ->collection('avatars')  // Optional: group files into categories

// Full featured upload
SpatieMediaLibraryFileUpload::make('images')
    ->collection('images')
    ->multiple()
    ->reorderable()
    ->image()
    ->imageEditor()  // Enable crop/rotate
    ->maxSize(5120)
    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
    ->responsiveImages()  // Generate responsive images
    ->conversion('thumb')  // Use specific conversion for preview
```

### Model Setup for Media Library
```php
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Article extends Model implements HasMedia
{
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('featured')
            ->singleFile()
            ->useDisk('public');
    }
}
```

### Table Column for Media
```php
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;

SpatieMediaLibraryImageColumn::make('avatar')
    ->collection('avatars')
    ->conversion('thumb')  // Use thumbnail conversion
    ->allCollections()  // Show from all collections
```

### Infolist Entry for Media
```php
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;

SpatieMediaLibraryImageEntry::make('avatar')
    ->collection('avatars')
```

### Action Modal with File Upload
For uploading media in action modals, use `storeFiles(false)` to get temporary upload objects, then attach via Spatie:
```php
use Filament\Forms\Components\FileUpload;

Action::make('upload')
    ->form([
        FileUpload::make('images')
            ->multiple()
            ->image()
            ->imageEditor()
            ->storeFiles(false),
    ])
    ->action(function (array $data): void {
        $mediaItem = MediaItem::create(['name' => 'Upload']);
        foreach ($data['images'] as $file) {
            $mediaItem->addMedia($file->getRealPath())
                ->usingFileName($file->getClientOriginalName())
                ->toMediaCollection('images');
        }
    })
```

### Infolist in Action Modal
Use Filament's native infolist for previews with copyable URLs:
```php
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;

Action::make('view')
    ->infolist([
        ImageEntry::make('preview')
            ->hiddenLabel()
            ->state(fn ($record) => $record->getUrl())
            ->height(300),
        TextEntry::make('url')
            ->state(fn ($record) => $record->getUrl())
            ->copyable()
            ->copyMessage('Copied!'),
        Section::make('Details')
            ->schema([
                TextEntry::make('size'),
                TextEntry::make('type'),
            ])
            ->columns(2)
            ->compact(),
    ])
    ->modalSubmitAction(false)
```
