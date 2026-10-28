<?php

declare(strict_types=1);

return [
    'enabled' => (bool) env('CACHE_ENABLED', true),
    'ttl'     => (int) env('CACHE_TTL', 600),
    'path'    => base_path('storage/cache'),
];
