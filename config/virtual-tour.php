<?php

return [
    'disk' => env('VIRTUAL_TOUR_DISK', 'local'),
    'max_upload_mb' => (int) env('VIRTUAL_TOUR_MAX_UPLOAD_MB', 80),
    'async_processing' => (bool) env('VIRTUAL_TOUR_ASYNC', false),
    'jpeg_quality' => (int) env('VIRTUAL_TOUR_JPEG_QUALITY', 86),
    'webp_quality' => (int) env('VIRTUAL_TOUR_WEBP_QUALITY', 84),
    'variant_widths' => [
        'ultra' => 8192,
        'high' => 6144,
        'medium' => 4096,
        'preview' => 2048,
        'thumbnail' => 640,
    ],
    'ratio' => [
        'min' => 1.85,
        'max' => 2.15,
        'warning_min' => 1.95,
        'warning_max' => 2.05,
    ],
];
