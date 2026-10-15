<?php

return [
    'pagination_theme' => 'bootstrap',

    'temporary_file_upload' => [
        // The converter accepts up to 50 MB of audio; each component applies its own stricter rule.
        'rules' => ['required', 'file', 'max:51200'],
        'disk' => null,
        'directory' => null,
        'middleware' => null,
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4', 'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma',
        ],
        'max_upload_time' => 5,
        'cleanup' => true,
    ],
];
