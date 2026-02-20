<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Blacklisted Domains
    |--------------------------------------------------------------------------
    |
    | URLs from these domains will be skipped during scraping. Use for sites
    | that block scraping, inject anti-copy scripts, or return broken content.
    |
    */
    'blacklist' => [
        'linkedin.com',
        'facebook.com',
        'twitter.com',
        'instagram.com',
        'youtube.com',
        'tiktok.com',
        'paywall',
        'login.',
        'signin.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Trusted Source Patterns
    |--------------------------------------------------------------------------
    |
    | Optional: prefer URLs matching these patterns when selecting sources.
    | Used for source prioritization, not filtering.
    |
    */
    'trusted_patterns' => [
        'grants.gov',
        'fundsforngos',
        'devfunding',
        'africangrants',
        'opportunitydesk',
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Extraction
    |--------------------------------------------------------------------------
    */
    'min_description_length' => 500,
    'min_content_length' => 100,

];
