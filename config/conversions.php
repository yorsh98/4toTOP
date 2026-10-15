<?php

return [
    'disk' => env('CONVERSIONS_DISK', 'local'),
    'public_limit' => 2,
    'pdf_max_kb' => 20 * 1024,
    'audio_max_kb' => 50 * 1024,
    'download_hours' => 24,
    'ffmpeg' => env('CONVERSIONS_FFMPEG', 'ffmpeg'),
    'libreoffice' => env('CONVERSIONS_LIBREOFFICE', 'libreoffice'),
];
