<?php

return [

    /*
    | Editorial configuration for the news aggregation pipeline.
    | See MISSION.md for the source tier policy.
    */

    'user_agent' => 'TagaytayNewsBot/1.0 (+https://tagaytaynews.com/about)',

    // An RSS item is relevant when title+summary mentions any of these (lowercased).
    'relevance_keywords' => [
        'tagaytay',
        'taal',
        'cavite',
        'silang',
        'mendez',
        'amadeo',
        'alfonso',
        'magallanes',
        'picnic grove',
        'sky ranch',
        "people's park",
        'peoples park',
        'aguinaldo highway',
    ],

    // First matching category wins; falls back to 'News'.
    'category_keywords' => [
        'Taal Volcano' => ['taal', 'volcano', 'phivolcs', 'eruption', 'alert level', 'vog', 'volcanic'],
        'Weather' => ['weather', 'typhoon', 'storm', 'rain', 'pagasa', 'flood', 'fog', 'signal no', 'habagat', 'amihan'],
        'Traffic' => ['traffic', 'road', 'highway', 'slex', 'cavitex', 'calax', 'closure', 'detour'],
        'Tourism' => ['tourism', 'tourist', 'hotel', 'resort', 'staycation', 'travel', 'visitors'],
        'Food & Drink' => ['restaurant', 'food', 'cafe', 'café', 'bulalo', 'coffee', 'dining', 'eatery'],
        'Events' => ['festival', 'fiesta', 'event', 'concert', 'celebration'],
        'Business' => ['business', 'economy', 'investment', 'market', 'real estate', 'developer'],
    ],

    'fallback_category' => 'News',

];
