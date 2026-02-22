<?php
// app/utils.php

function normalizeMailboxKey($email) {
    return strtolower(trim($email));
}

function generateDedupeKey($mailbox_key, $message_id, $from_email, $subject, $received_at, $size_bytes) {
    $mailbox_key = (string)$mailbox_key;
    if (!empty($message_id)) {
        return hash('sha256', $mailbox_key . "|" . $message_id);
    } else {
        return hash('sha256', $mailbox_key . "|" . $from_email . "|" . $subject . "|" . $received_at . "|" . $size_bytes);
    }
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    $bytes /= pow(1024, $pow);

    return round($bytes, $precision) . ' ' . $units[$pow];
}
