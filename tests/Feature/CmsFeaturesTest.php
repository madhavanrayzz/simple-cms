<?php

use App\Enums\UserRole;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Filament\Resources\Articles\Pages\ListArticles;
use App\Filament\Resources\Tags\Pages\CreateTag;
use App\Models\Article;
use App\Models\ArticleView;
use App\Models\Category;
use App\Models\Page;
use App\Models\Tag;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

function publishedArticle(array $attributes = []): Article
{
    return Article::create(array_merge([
        'title' => 'Published Story',
        'slug' => 'published-story',
        'content' => '<p>Some words about the topic.</p>',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ], $attributes));
}

function draftArticle(array $attributes = []): Article
{
    return Article::create(array_merge([
        'title' => 'Secret Draft',
        'slug' => 'secret-draft',
        'content' => '<p>Not ready.</p>',
        'is_published' => false,
    ], $attributes));
}

describe('publishing', function () {
    it('sets published_at to now when published without a date', function () {
        $article = Article::create(['title' => 'No Date', 'content' => 'C', 'is_published' => true]);

        expect($article->published_at)->not->toBeNull()
            ->and($article->status)->toBe('published');
        $this->get('/article/no-date')->assertOk();
    });

    it('reports draft, scheduled and published status', function () {
        expect(draftArticle()->status)->toBe('draft')
            ->and(publishedArticle(['slug' => 'later', 'published_at' => now()->addWeek()])->status)->toBe('scheduled')
            ->and(publishedArticle()->status)->toBe('published');
    });
});

describe('seo', function () {
    it('renders meta, open graph and json-ld for an article', function () {
        publishedArticle(['meta_title' => 'Custom SEO Title', 'meta_description' => 'Custom SEO description.']);

        $html = $this->get('/article/published-story')->assertOk()->getContent();

        expect($html)
            ->toContain('<title>Custom SEO Title - ')
            ->toContain('<meta name="description" content="Custom SEO description.">')
            ->toContain('<meta property="og:type" content="article">')
            ->toContain('<link rel="canonical" href="'.route('articles.show', 'published-story').'">');

        preg_match('#<script type="application/ld\+json">(.+?)</script>#s', $html, $m);
        $data = json_decode(html_entity_decode($m[1]), true);
        expect($data['@type'])->toBe('BlogPosting')
            ->and($data['headline'])->toBe('Published Story');
    });

    it('strips html from meta fields', function () {
        $article = publishedArticle(['meta_title' => '<b>Bold</b>', 'meta_description' => '<script>x</script>Desc']);

        expect($article->meta_title)->toBe('Bold')
            ->and($article->meta_description)->toBe('xDesc');
    });

    it('lists only public content in the sitemap', function () {
        publishedArticle();
        draftArticle();
        Page::create(['title' => 'About', 'slug' => 'about', 'content' => 'C', 'is_published' => true]);

        $response = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        expect(simplexml_load_string($response->getContent()))->not->toBeFalse();
        $response->assertSee(route('articles.show', 'published-story'), false)
            ->assertSee(route('pages.show', 'about'), false)
            ->assertDontSee('secret-draft');
    });

    it('keeps robots.txt static so web servers can serve it directly', function () {
        expect(file_get_contents(public_path('robots.txt')))->toContain('Disallow: /admin');
        $this->get('/')->assertSee('<link rel="sitemap" type="application/xml" href="'.route('sitemap').'">', false);
    });

    it('serves a valid rss feed of published articles', function () {
        publishedArticle(['title' => 'Feed & Friends']);
        draftArticle();

        $response = $this->get('/feed')->assertOk()->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8');

        $xml = simplexml_load_string($response->getContent());
        expect($xml)->not->toBeFalse()
            ->and((string) $xml->channel->item[0]->title)->toBe('Feed & Friends')
            ->and(count($xml->channel->item))->toBe(1);
    });
});

describe('search', function () {
    it('finds published articles by title or content', function () {
        publishedArticle(['title' => 'Laravel Tips', 'slug' => 'laravel-tips']);
        publishedArticle(['title' => 'Other', 'slug' => 'other', 'content' => '<p>Deep dive into Laravel queues.</p>']);
        draftArticle(['title' => 'Laravel Draft']);

        $this->get('/search?q=laravel')->assertOk()
            ->assertSee('Laravel Tips')
            ->assertSee('Other')
            ->assertDontSee('Laravel Draft');
    });

    it('ranks title matches above body matches', function () {
        publishedArticle(['title' => 'Body only', 'slug' => 'body-only', 'content' => '<p>about kittens</p>', 'published_at' => now()]);
        publishedArticle(['title' => 'Kittens 101', 'slug' => 'kittens-101', 'published_at' => now()->subYear()]);

        $titles = $this->get('/search?q=kittens')->viewData('articles')->pluck('title')->all();

        expect($titles)->toBe(['Kittens 101', 'Body only']);
    });

    it('treats like wildcards as plain text', function () {
        publishedArticle();

        $this->get('/search?q=%25%25')->assertOk()->assertDontSee('Published Story');
    });

    it('matches literal percent and underscore characters', function () {
        publishedArticle(['title' => 'Reach 100% coverage', 'slug' => 'full-coverage']);
        publishedArticle(['title' => 'Use snake_case names', 'slug' => 'snake-case']);
        publishedArticle(['title' => 'Plain 100 items', 'slug' => 'plain-items']);

        $this->get('/search?q='.urlencode('100%'))->assertSee('Reach 100% coverage')->assertDontSee('Plain 100 items');
        $this->get('/search?q=snake_case')->assertSee('Use snake_case names');
    });

    it('ignores a non-string query', function () {
        $this->get('/search?q[]=x')->assertOk();
    });

    it('asks for a longer query', function () {
        $this->get('/search?q=a')->assertOk()->assertSee(__('frontend.search.too_short'));
    });

    it('is not indexed by search engines', function () {
        $this->get('/search?q=test')->assertSee('<meta name="robots" content="noindex, follow">', false);
    });
});

describe('tags', function () {
    it('shows tagged published articles on the tag page and links tags from the article', function () {
        $tag = Tag::create(['name' => 'PHP']);
        publishedArticle()->tags()->attach($tag);
        draftArticle()->tags()->attach($tag);

        expect($tag->slug)->toBe('php');
        $this->get('/tag/php')->assertOk()->assertSee('Published Story')->assertDontSee('Secret Draft');
        $this->get('/article/published-story')->assertSee(route('tags.show', 'php'), false);
    });

    it('can be created in the admin', function () {
        $this->actingAs(createUser(UserRole::Editor));

        Livewire::test(CreateTag::class)
            ->fillForm(['name' => 'Open Source', 'slug' => 'open-source'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('tags', ['name' => 'Open Source', 'slug' => 'open-source']);
    });

    it('lists related articles by shared tag first', function () {
        $tag = Tag::create(['name' => 'Rust']);
        $main = publishedArticle();
        $main->tags()->attach($tag);
        publishedArticle(['title' => 'Rust Sibling', 'slug' => 'rust-sibling', 'published_at' => now()->subWeek()])->tags()->attach($tag);
        foreach (range(1, 5) as $i) {
            publishedArticle(['title' => "Newer $i", 'slug' => "newer-$i", 'published_at' => now()->subHours($i)]);
        }

        $others = $this->get('/article/published-story')->viewData('others');

        expect($others->first()->slug)->toBe('rust-sibling')
            ->and($others)->toHaveCount(5);
    });
});

describe('old slug redirects', function () {
    it('redirects an old article slug to the new one', function () {
        $article = publishedArticle();
        $article->update(['slug' => 'renamed-story']);

        $this->get('/article/published-story')->assertStatus(301)->assertRedirect(route('articles.show', 'renamed-story'));
    });

    it('redirects old page and category slugs', function () {
        Page::create(['title' => 'About', 'slug' => 'about', 'content' => 'C', 'is_published' => true])->update(['slug' => 'about-us']);
        Category::create(['title' => 'Tech', 'slug' => 'tech', 'is_active' => true])->update(['slug' => 'technology']);

        $this->get('/page/about')->assertStatus(301)->assertRedirect(route('pages.show', 'about-us'));
        $this->get('/category/tech')->assertStatus(301)->assertRedirect(route('categories.show', 'technology'));
    });

    it('does not reveal the new slug of an unpublished article', function () {
        $article = publishedArticle();
        $article->update(['slug' => 'now-hidden', 'is_published' => false]);

        $this->get('/article/published-story')->assertNotFound();
    });

    it('prefers a live article over an old redirect with the same slug', function () {
        publishedArticle()->update(['slug' => 'moved']);
        publishedArticle(['title' => 'Newcomer', 'slug' => 'published-story']);

        $this->get('/article/published-story')->assertOk()->assertSee('Newcomer');
    });
});

describe('draft preview', function () {
    it('rejects an unsigned preview url', function () {
        $article = draftArticle();

        $this->get('/preview/article/'.$article->id)->assertForbidden();
    });

    it('renders a draft through a signed url without counting a view', function () {
        $article = draftArticle();

        $this->get(URL::temporarySignedRoute('articles.preview', now()->addHour(), ['id' => $article->id]))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('Secret Draft')
            ->assertSee(__('frontend.articles.preview_notice'))
            ->assertDontSee('id="share-buttons"', false);

        expect(ArticleView::count())->toBe(0);
    });

    it('rejects an expired preview url', function () {
        $article = draftArticle();
        $url = URL::temporarySignedRoute('articles.preview', now()->addHour(), ['id' => $article->id]);

        $this->travel(2)->hours();

        $this->get($url)->assertForbidden();
    });

    it('renders an unpublished page through a signed url', function () {
        $page = Page::create(['title' => 'Hidden Page', 'slug' => 'hidden-page', 'content' => 'C', 'is_published' => false]);

        $this->get(URL::temporarySignedRoute('pages.preview', now()->addHour(), ['id' => $page->id]))
            ->assertOk()
            ->assertSee('Hidden Page');
    });
});

describe('trash', function () {
    it('hides a trashed article from the frontend and keeps its slug reserved', function () {
        $article = publishedArticle();
        $article->delete();

        $this->get('/article/published-story')->assertNotFound();
        expect(Article::create(['title' => 'Published Story', 'content' => 'C'])->slug)->toBe('published-story-2');
    });

    it('lets an editor restore their own trashed article', function () {
        $editor = createUser(UserRole::Editor);
        $this->actingAs($editor);
        $article = publishedArticle();
        $article->delete();

        Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
            ->callAction('restore');

        expect($article->fresh()->trashed())->toBeFalse();
    });

    it('does not let an editor permanently delete an article', function () {
        $editor = createUser(UserRole::Editor);
        $this->actingAs($editor);
        $article = publishedArticle();
        $article->delete();

        Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
            ->assertActionHidden('forceDelete');
    });

    it('lets an admin permanently delete a trashed article', function () {
        $this->actingAs(createUser(UserRole::Admin));
        $article = publishedArticle();
        $article->delete();

        Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
            ->callAction('forceDelete');

        $this->assertDatabaseMissing('articles', ['id' => $article->id]);
    });

    it('shows trashed articles only through the trashed filter', function () {
        $this->actingAs(createUser(UserRole::Admin));
        $live = publishedArticle();
        $trashed = draftArticle();
        $trashed->delete();

        Livewire::test(ListArticles::class)
            ->assertCanSeeTableRecords([$live])
            ->assertCanNotSeeTableRecords([$trashed])
            ->filterTable('trashed', false)
            ->assertCanSeeTableRecords([$trashed])
            ->assertCanNotSeeTableRecords([$live]);
    });
});

describe('admin links', function () {
    it('links a published article to its public url and a draft to a signed preview', function () {
        $this->actingAs(createUser(UserRole::Admin));
        $live = publishedArticle();
        $draft = draftArticle();

        Livewire::test(EditArticle::class, ['record' => $live->getRouteKey()])
            ->assertActionHasUrl('viewOnSite', route('articles.show', 'published-story'));

        $url = Livewire::test(EditArticle::class, ['record' => $draft->getRouteKey()])
            ->instance()->getAction('viewOnSite')->getUrl();
        expect($url)->toContain('/preview/article/'.$draft->id)->toContain('signature=');
    });
});

describe('page tree', function () {
    it('collects all descendants so the parent select can exclude them', function () {
        $root = Page::create(['title' => 'Root', 'content' => 'C']);
        $child = Page::create(['title' => 'Child', 'content' => 'C', 'parent_id' => $root->id]);
        $grandchild = Page::create(['title' => 'Grandchild', 'content' => 'C', 'parent_id' => $child->id]);

        expect($root->descendantIds())->toEqualCanonicalizing([$child->id, $grandchild->id])
            ->and($grandchild->descendantIds())->toBe([]);
    });
});

describe('dashboard', function () {
    it('shows the real status of recent articles', function () {
        $this->actingAs(createUser(UserRole::Admin));
        $live = publishedArticle();
        $scheduled = publishedArticle(['title' => 'Later', 'slug' => 'later', 'published_at' => now()->addWeek()]);
        $draft = draftArticle();

        Livewire::test(\App\Filament\Widgets\RecentActivityTable::class)
            ->assertTableColumnFormattedStateSet('status', 'Published', $live)
            ->assertTableColumnFormattedStateSet('status', 'Scheduled', $scheduled)
            ->assertTableColumnFormattedStateSet('status', 'Draft', $draft);
    });
});

describe('topic covers', function () {
    it('stores only a strict hex color for a category', function () {
        $category = Category::create(['title' => 'Bad', 'color' => 'red; background: url(x)', 'is_active' => true]);
        expect($category->color)->toBeNull();

        $category->update(['color' => '#2A44D4']);
        expect($category->color)->toBe('#2a44d4');
    });

    it('falls back to a palette color and picks readable cover text', function () {
        $category = Category::create(['title' => 'Auto', 'is_active' => true]);
        expect(Category::PALETTE)->toContain($category->cover_color);

        expect((new Category(['color' => '#2a44d4']))->cover_ink)->toBe('#ffffff')
            ->and((new Category(['color' => '#f2c14e']))->cover_ink)->toBe('#151821');
    });

    it('renders article covers in the category color on the home page', function () {
        $category = Category::create(['title' => 'Tech', 'slug' => 'tech', 'color' => '#3f7d4e', 'is_active' => true]);
        publishedArticle(['category_id' => $category->id]);

        $this->get('/')->assertOk()->assertSee('--topic: #3f7d4e', false)->assertSee('Published Story');
    });
});

describe('inactive categories', function () {
    it('does not show an inactive category on covers or in structured data', function () {
        $hidden = Category::create(['title' => 'Hidden Topic', 'slug' => 'hidden-topic', 'is_active' => false]);
        publishedArticle(['category_id' => $hidden->id]);

        $this->get('/')->assertOk()->assertDontSee('Hidden Topic');
        $this->get('/article/published-story')->assertOk()->assertDontSee('Hidden Topic');
    });

    it('picks the ink with the higher contrast for the cover', function () {
        expect((new Category(['color' => '#e0533a']))->cover_ink)->toBe('#151821')
            ->and((new Category(['color' => '#151821']))->cover_ink)->toBe('#ffffff');
    });
});

describe('deep review fixes', function () {
    it('sends no referrer from a preview page so the signed url cannot leak', function () {
        $article = draftArticle();

        $this->get(URL::temporarySignedRoute('articles.preview', now()->addHour(), ['id' => $article->id]))
            ->assertHeader('Referrer-Policy', 'no-referrer');
        $this->get('/')->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    });

    it('returns 404 for a tag that only has drafts', function () {
        $tag = Tag::create(['name' => 'Secret Product']);
        draftArticle()->tags()->attach($tag);

        $this->get('/tag/secret-product')->assertNotFound();
    });

    it('does not count views of trashed articles on the dashboard', function () {
        $this->actingAs(createUser(UserRole::Admin));
        $live = publishedArticle();
        $trashed = publishedArticle(['title' => 'Gone', 'slug' => 'gone']);
        $live->recordView('1.1.1.1');
        $trashed->recordView('2.2.2.2');
        $trashed->delete();

        $counts = (fn () => $this->collectCounts(auth()->user(), true))->call(new \App\Filament\Widgets\StatsOverview);

        expect(ArticleView::count())->toBe(2)
            ->and($counts['totalViews'])->toBe(1);
    });

    it('lets an editor save their page under a parent owned by someone else', function () {
        $admin = createUser(UserRole::Admin);
        $this->actingAs($admin);
        $parent = Page::create(['title' => 'Admin Parent', 'content' => 'C', 'is_published' => true]);

        $editor = createUser(UserRole::Editor);
        $this->actingAs($editor);
        $page = Page::create(['title' => 'Editor Child', 'content' => 'C', 'parent_id' => $parent->id]);

        Livewire::test(\App\Filament\Resources\Pages\Pages\EditPage::class, ['record' => $page->getRouteKey()])
            ->fillForm(['title' => 'Editor Child Updated'])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($page->fresh()->parent_id)->toBe($parent->id);
    });

    it('does not count scheduled articles as published on the dashboard', function () {
        $user = createUser(UserRole::Admin);
        publishedArticle();
        publishedArticle(['title' => 'Later', 'slug' => 'later', 'published_at' => now()->addWeek()]);

        $counts = (fn () => $this->collectCounts($user, true))->call(new \App\Filament\Widgets\StatsOverview);

        expect($counts['totalArticles'])->toBe(2)->and($counts['publishedArticles'])->toBe(1);
    });
});

it('redirects an old tag slug to the new one', function () {
    $tag = Tag::create(['name' => 'Laravel']);
    publishedArticle()->tags()->attach($tag);
    $tag->update(['slug' => 'laravel-php']);

    $this->get('/tag/laravel')->assertStatus(301)->assertRedirect(route('tags.show', 'laravel-php'));
});

it('keeps the article name on views of a trashed article', function () {
    $article = publishedArticle();
    $view = $article->recordView('1.1.1.1');
    $article->delete();

    expect($view->fresh()->article?->title)->toBe('Published Story');
});

it('strips html from category title and description', function () {
    $category = Category::create(['title' => '<b>Bold</b> Topic', 'description' => '<i>Hi</i>', 'is_active' => true]);

    expect($category->title)->toBe('Bold Topic')->and($category->description)->toBe('Hi');
});
