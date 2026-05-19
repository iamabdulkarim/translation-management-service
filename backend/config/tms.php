<?php

return [
    'cdn' => [
        'enabled' => (bool) env('TMS_CDN_ENABLED', false),
        'asset_url' => env('TMS_CDN_ASSET_URL'),
    ],

    'headers' => [
        'api_cache_control' => env('TMS_API_CACHE_CONTROL', 'no-store, max-age=0'),
        'export_cache_control' => env('TMS_EXPORT_CACHE_CONTROL', 'no-store, max-age=0'),
    ],
];
