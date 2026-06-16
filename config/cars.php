<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */
    'default_per_page' => env('CARS_DEFAULT_PER_PAGE', 12),
    'max_per_page' => env('CARS_MAX_PER_PAGE', 60),

    /*
    |--------------------------------------------------------------------------
    | Filter Options Cache
    |--------------------------------------------------------------------------
    | TTL (seconds) for the /api/car-filters response cache. Cache is also
    | flushed automatically whenever a Car model is saved/deleted.
    */
    'filter_options_cache_ttl' => env('CARS_FILTER_CACHE_TTL', 600),
    'filter_options_cache_key' => 'cars:filter-options',

    /*
    |--------------------------------------------------------------------------
    | Image Storage
    |--------------------------------------------------------------------------
    |
    | All car image uploads go through these settings. To switch from the
    | local "public" disk to S3 / Cloudflare R2 / DigitalOcean Spaces, just
    | configure that disk in config/filesystems.php and set CARS_IMAGES_DISK
    | in your .env. No model or controller code needs to change.
    |
    | Resize values are applied client-side (browser canvas) before upload
    | so 12-megapixel phone photos are downscaled before they hit storage.
    |
    */
    'images' => [
        'disk' => env('CARS_IMAGES_DISK', 'public'),
        'directory' => env('CARS_IMAGES_DIRECTORY', 'cars'),
        'visibility' => 'public',

        'max_size_kb' => (int) env('CARS_IMAGES_MAX_SIZE_KB', 5120),
        'allowed_mimes' => [
            'image/jpeg',
            'image/png',
            'image/webp',
        ],

        'resize' => [
            'mode' => env('CARS_IMAGES_RESIZE_MODE', 'contain'),
            'width' => (int) env('CARS_IMAGES_RESIZE_WIDTH', 1600),
            'height' => (int) env('CARS_IMAGES_RESIZE_HEIGHT', 1067),
        ],
    ],
];

