<?php

use Webklex\IMAP\Events\FlagDeletedEvent;
use Webklex\IMAP\Events\FlagNewEvent;
use Webklex\IMAP\Events\FolderDeletedEvent;
use Webklex\IMAP\Events\FolderMovedEvent;
use Webklex\IMAP\Events\FolderNewEvent;
use Webklex\IMAP\Events\MessageCopiedEvent;
use Webklex\IMAP\Events\MessageDeletedEvent;
use Webklex\IMAP\Events\MessageMovedEvent;
use Webklex\IMAP\Events\MessageNewEvent;
use Webklex\IMAP\Events\MessageRestoredEvent;
use Webklex\PHPIMAP\Decoder\AttachmentDecoder;
use Webklex\PHPIMAP\Decoder\HeaderDecoder;
use Webklex\PHPIMAP\Decoder\MessageDecoder;
use Webklex\PHPIMAP\IMAP;
use Webklex\PHPIMAP\Support\Masks\AttachmentMask;
use Webklex\PHPIMAP\Support\Masks\MessageMask;

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
        'fetch' => IMAP::FT_PEEK,
        'sequence' => IMAP::ST_UID,
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
            'header' => HeaderDecoder::class,
            'message' => MessageDecoder::class,
            'attachment' => AttachmentDecoder::class,
        ],
    ],

    'flags' => ['recent', 'flagged', 'answered', 'deleted', 'seen', 'draft'],

    'events' => [
        'message' => [
            'new' => MessageNewEvent::class,
            'moved' => MessageMovedEvent::class,
            'copied' => MessageCopiedEvent::class,
            'deleted' => MessageDeletedEvent::class,
            'restored' => MessageRestoredEvent::class,
        ],
        'folder' => [
            'new' => FolderNewEvent::class,
            'moved' => FolderMovedEvent::class,
            'deleted' => FolderDeletedEvent::class,
        ],
        'flag' => [
            'new' => FlagNewEvent::class,
            'deleted' => FlagDeletedEvent::class,
        ],
    ],

    'masks' => [
        'message' => MessageMask::class,
        'attachment' => AttachmentMask::class,
    ],
];
