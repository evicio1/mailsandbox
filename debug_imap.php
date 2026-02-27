<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$mb = \App\Models\ExternalMailbox::find(1);
if (!$mb) {
    die("Mailbox not found.\n");
}

$conn = '{' . $mb->host . ':' . $mb->port . '/imap/ssl/novalidate-cert}INBOX';
try {
    $inbox = imap_open($conn, $mb->username, $mb->password);
    if (!$inbox) {
        die("Login Failed: " . imap_last_error() . "\n");
    }

    $uids = imap_search($inbox, 'ALL', SE_UID);
    echo "Total UIDs on Server: " . ($uids ? count($uids) : 0) . "\n";
    if ($uids) {
        echo "UID List: " . json_encode($uids) . "\n";
    }
    
    echo "Current ext_mb last_seen_uid: " . $mb->last_seen_uid . "\n";
    
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
