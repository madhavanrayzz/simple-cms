<?php

return [
    // Site
    'site_name' => 'Simple CMS',
    'site_tagline' => 'Stories & insights that matter.',
    'site_description' => 'A curated collection of thoughtful articles exploring technology, design, lifestyle, and business.',

    // Navigation
    'nav' => [
        'home' => 'Home',
        'articles' => 'Articles',
        'browse_articles' => 'Browse Articles',
        'navigate' => 'Navigate',
        'info' => 'Info',
        'rss' => 'RSS Feed',
        'skip' => 'Skip to content',
        'menu' => 'Menu',
    ],

    // Home
    'home' => [
        'also_new' => 'Also new',
    ],

    // Articles
    'articles' => [
        'title' => 'All Articles',
        'description' => 'Explore our collection of articles on technology, design, lifestyle, and business.',
        'latest' => 'Latest Articles',
        'browse_by_topic' => 'Browse by Topic',
        'min_read' => ':minutes min read',
        'article_count' => ':count article|:count articles',
        'no_articles' => 'No articles found.',
        'no_articles_in_category' => 'No articles yet',
        'no_articles_in_category_desc' => 'There are no published articles in this category.',
        'browse_all_articles' => 'Browse all articles',
        'view_all_articles' => 'View All Articles',
        'share' => 'Share this article',
        'view_count' => ':count view|:count views',
        'related' => 'Related Articles',
        'filter_by_topic' => 'Filter by topic:',
        'tagged' => 'Tagged',
        'preview_notice' => 'Preview — this is not visible to the public yet.',
    ],

    // Categories
    'categories' => [
        'explore_other' => 'Explore Other Topics',
    ],

    // Tags
    'tags' => [
        'title' => 'Tag: :tag',
        'description' => 'Articles tagged with :tag.',
    ],

    // Search
    'search' => [
        'title' => 'Search',
        'placeholder' => 'Search articles…',
        'button' => 'Search',
        'results_for' => 'Results for “:query”',
        'result_count' => ':count result|:count results',
        'no_results' => 'No articles match your search.',
        'no_results_desc' => 'Try different or fewer words.',
        'too_short' => 'Type at least two characters to search.',
    ],

    // Feed
    'feed' => [
        'description' => 'The latest articles from :name.',
    ],

    // Pages
    'pages' => [
        'last_updated' => 'Last updated: :date',
        'related_pages' => 'Related Pages',
        'quick_contact' => 'Quick Contact',
        'contact_address' => '123 Demo Street, City, Country',
        'contact_email' => 'hello@example.com',
        'contact_hours' => 'Mon - Fri: 9:00 - 17:00',
    ],

    // Pagination
    'pagination' => [
        'label' => 'Pages',
        'previous' => 'Previous page',
        'next' => 'Next page',
        'page' => 'Page :page',
    ],

    // Social Share
    'share' => [
        'x' => 'Post on X',
        'facebook' => 'Share on Facebook',
        'linkedin' => 'Share on LinkedIn',
    ],

    // Footer
    'footer' => [
        'copyright' => '© :year :name',
        'built_with' => 'Built with Laravel, Filament & DaisyUI.',
    ],

    // Empty States
    'empty' => [
        'no_content' => 'No content yet',
        'check_back' => 'Check back soon for articles.',
    ],

    // Errors
    'errors' => [
        '404' => [
            'title' => 'Page Not Found',
            'message' => 'This page does not exist or has moved. Search the articles or go back to the home page.',
            'back_home' => 'Back to Home',
        ],
    ],
];
