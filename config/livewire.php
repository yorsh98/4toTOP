<?php

return [
    'pagination_theme' => 'bootstrap',

    'temporary_file_upload' => [
        // Must not be lower than the document validation limit.
        'rules' => ['required', 'file', 'max:20480'],
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
