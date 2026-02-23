<?php

return [

    /*
    |--------------------------------------------------------------------------
    | IMAP Connection
    |--------------------------------------------------------------------------
    */

    'host'     => env('IMAP_HOST', '{imap.hostinger.com:993/imap/ssl}INBOX'),
    'user'     => env('IMAP_USER'),
    'password' => env('IMAP_PASS'),

    /*
    |--------------------------------------------------------------------------
    | Catch-All Domain
    |--------------------------------------------------------------------------
    |
    | The domain suffix used to identify which recipient addresses belong to
    | your virtual inboxes. Only emails addressed to @<domain> will be treated
    | as a target mailbox by the importer.
    |
    */

    'domain' => env('IMAP_DOMAIN', 'evicio.site'),

];
