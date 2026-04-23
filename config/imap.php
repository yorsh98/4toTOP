<?php

return [
    'default' => env('IMAP_DEFAULT_ACCOUNT', 'default'),

    'date_format' => 'd-M-Y',

    'accounts' => [
        'default' => [
            'host' => env('IMAP_HOST'),
            'port' => (int) env('IMAP_PORT', 995),
            'encryption' => env('IMAP_ENCRYPTION', 'ssl'),
            'validate_cert' => filter_var(env('IMAP_VALIDATE_CERT', true), FILTER_VALIDATE_BOOL),
            'username' => env('IMAP_USERNAME'),
            'password' => env('IMAP_PASSWORD'),
            'protocol' => env('IMAP_PROTOCOL', 'pop3'),
            'options' => [
                'DISABLE_AUTHENTICATOR' => ['GSSAPI'],
            ],
        ],
    ],

    'options' => [
        'delimiter' => '/',
        'fetch' => \Webklex\PHPIMAP\IMAP::FT_PEEK,
        'sequence' => \Webklex\PHPIMAP\IMAP::ST_UID,
        'fetch_body' => true,
        'fetch_flags' => true,
        'soft_fail' => false,
        'rfc822' => true,
        'debug' => false,
        'uid_cache' => true,
        'boundary' => '/boundary=(.*?(?=;)|(.*))/i',
        'message_key' => 'list',
        'fetch_order' => 'asc',
        'dispositions' => ['attachment', 'inline'],
        'common_folders' => [
            'root' => 'INBOX',
            'junk' => 'INBOX/Junk',
            'draft' => 'INBOX/Drafts',
            'sent' => 'INBOX/Sent',
            'trash' => 'INBOX/Trash',
        ],
        'open' => [],
    ],

    'decoding' => [
        'options' => [
            'header' => 'utf-8',
            'message' => 'utf-8',
            'attachment' => 'utf-8',
        ],
        'decoder' => [
            'header' => \Webklex\PHPIMAP\Decoder\HeaderDecoder::class,
            'message' => \Webklex\PHPIMAP\Decoder\MessageDecoder::class,
            'attachment' => \Webklex\PHPIMAP\Decoder\AttachmentDecoder::class,
        ],
    ],

    'flags' => ['recent', 'flagged', 'answered', 'deleted', 'seen', 'draft'],

    'events' => [
        'message' => [
            'new' => \Webklex\IMAP\Events\MessageNewEvent::class,
            'moved' => \Webklex\IMAP\Events\MessageMovedEvent::class,
            'copied' => \Webklex\IMAP\Events\MessageCopiedEvent::class,
            'deleted' => \Webklex\IMAP\Events\MessageDeletedEvent::class,
            'restored' => \Webklex\IMAP\Events\MessageRestoredEvent::class,
        ],
        'folder' => [
            'new' => \Webklex\IMAP\Events\FolderNewEvent::class,
            'moved' => \Webklex\IMAP\Events\FolderMovedEvent::class,
            'deleted' => \Webklex\IMAP\Events\FolderDeletedEvent::class,
        ],
        'flag' => [
            'new' => \Webklex\IMAP\Events\FlagNewEvent::class,
            'deleted' => \Webklex\IMAP\Events\FlagDeletedEvent::class,
        ],
    ],

    'masks' => [
        'message' => \Webklex\PHPIMAP\Support\Masks\MessageMask::class,
        'attachment' => \Webklex\PHPIMAP\Support\Masks\AttachmentMask::class,
    ],
];
