<?php

use App\Models\Article;
use App\Models\Tag;
use Database\Seeders\ContentSeeder;
use Database\Seeders\DatabaseSeeder;

it('seeds demo content with tags and can be re-run', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(ContentSeeder::class);
    $views = \App\Models\ArticleView::count();
    $this->seed(ContentSeeder::class);

    expect(\App\Models\ArticleView::count())->toBe($views);

    expect(Tag::where('name', 'UX')->first()->articles()->count())->toBe(3)
        ->and(Article::count())->toBe(14);
});
