<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$mb = \App\Models\ExternalMailbox::find(1);
if ($mb) {
    $mb->last_seen_uid = 0;
    $mb->save();
    echo "Reset last_seen_uid to 0\n";
} else {
    echo "Mailbox not found.\n";
}
