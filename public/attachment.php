<?php
// public/attachment.php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';
requireAuth();

$id = $_GET['id'] ?? 0;
if (!$id) {
    die("Invalid request.");
}

$pdo = getDbConnection();
$stmt = $pdo->prepare("SELECT filename, content_type, size_bytes, storage_path FROM attachments WHERE id = ?");
$stmt->execute([$id]);
$attachment = $stmt->fetch();

if (!$attachment) {
    die("Attachment not found.");
}

$filePath = $attachment['storage_path'];
if (!file_exists($filePath)) {
    die("File is missing from disk.");
}

$contentType = $attachment['content_type'] ?: 'application/octet-stream';

header("Content-Type: " . $contentType);
header("Content-Disposition: attachment; filename=\"" . addslashes($attachment['filename']) . "\"");
header("Content-Length: " . $attachment['size_bytes']);
header("Cache-Control: private, max-age=0, must-revalidate");
header("Pragma: public");

readfile($filePath);
exit;
