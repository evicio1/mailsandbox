<?php
// app/config.php

// Application Settings
define('APP_PASSWORD', 'evicio_qa_2026!'); // Set generic password for now
define('RETENTION_DAYS', 14);

// Database Settings
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'mailsandbox');
define('DB_USER', getenv('DB_USER') ?: 'mailsandboxer');
define('DB_PASS', getenv('DB_PASS') ?: 'FMR7zrq5ucr!jau-pgb');
define('DB_PORT', 3306);

// IMAP Settings (Hostinger or general)
define('IMAP_HOST', '{imap.hostinger.com:993/imap/ssl}INBOX');
define('IMAP_USER', 'emailtesting@evicio.site');
define('IMAP_PASS', 'YOUR_IMAP_PASSWORD_HERE'); // Needs to be configured

// Paths
define('BASE_PATH', dirname(__DIR__));
define('STORAGE_PATH', BASE_PATH . '/storage');
define('ATTACHMENTS_PATH', STORAGE_PATH . '/attachments');
