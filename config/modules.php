<?php

return [
    'catalog_url' => env(
        'MODULES_CATALOG_URL',
        'https://raw.githubusercontent.com/zentra-team/catalog/main/catalog.json',
    ),

    'catalog_cache_ttl' => (int) env('MODULES_CATALOG_TTL', 3600),
];
