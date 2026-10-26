<?php

return [
    'paths' => ['api/*', 'up'],
    'allowed_methods' => ['*'],
    'allowed_origins' => array_values(array_unique(array_filter(array_merge(
        [
            'http://localhost:5173',
            'http://127.0.0.1:5173',
            'https://gradingsystem05.vercel.app',
            'https://grading-system-gamma-six.vercel.app',
            'https://grading-system-cdm-kleins-projects-246b3510.vercel.app',
        ],
        array_map(
            'trim',
            explode(',', (string) env('CORS_ALLOWED_ORIGINS', '')),
        ),
    )))),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
