<?php

namespace App\Services;

class MailboxService
{
    public static function normalizeMailboxKey(string $email): string
    {
        return strtolower(trim($email));
    }

    public static function generateDedupeKey(string $mailbox_key, ?string $message_id, ?string $from_email, ?string $subject, ?string $received_at, int $size_bytes): string
    {
        if (!empty($message_id)) {
            return hash('sha256', $mailbox_key . "|" . $message_id);
        } else {
            return hash('sha256', $mailbox_key . "|" . $from_email . "|" . $subject . "|" . $received_at . "|" . $size_bytes);
        }
    }

    public static function formatBytes(int $bytes, int $precision = 2): string

    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
