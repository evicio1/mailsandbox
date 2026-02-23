<?php
// app/retention.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$logFile = STORAGE_PATH . '/logs/retention_' . date('Y-m-d') . '.log';
if (!is_dir(dirname($logFile))) {
    mkdir(dirname($logFile), 0777, true);
}

function writeLog($msg) {
    global $logFile;
    $time = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$time] $msg\n", FILE_APPEND);
    echo "[$time] $msg\n";
}

writeLog("Starting retention process. Deleting older than " . RETENTION_DAYS . " days...");

$pdo = getDbConnection();

$cutoffDate = date('Y-m-d H:i:s', strtotime('-' . RETENTION_DAYS . ' days'));

// Find old messages
$stmt = $pdo->prepare("SELECT id FROM messages WHERE received_at < ?");
$stmt->execute([$cutoffDate]);
$oldMessages = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($oldMessages)) {
    writeLog("No messages to delete.");
    exit(0);
}

writeLog("Found " . count($oldMessages) . " messages to delete.");

$pdo->beginTransaction();

try {
    $deletedAttCount = 0;
    
    // Process in batches or one by one
    foreach ($oldMessages as $msgId) {
        // Find attachments
        $stmtAtt = $pdo->prepare("SELECT id, storage_path FROM attachments WHERE message_id = ?");
        $stmtAtt->execute([$msgId]);
        $attachments = $stmtAtt->fetchAll();
        
        foreach ($attachments as $att) {
            if (file_exists($att['storage_path'])) {
                unlink($att['storage_path']);
            }
            $stmtDelAtt = $pdo->prepare("DELETE FROM attachments WHERE id = ?");
            $stmtDelAtt->execute([$att['id']]);
            $deletedAttCount++;
        }
        
        // Remove attachment directory for the message
        $msgAttachPath = ATTACHMENTS_PATH . '/' . $msgId;
        if (is_dir($msgAttachPath)) {
            @rmdir($msgAttachPath); // only works if empty, which it should be now
        }
        
        // Delete message
        $stmtDelMsg = $pdo->prepare("DELETE FROM messages WHERE id = ?");
        $stmtDelMsg->execute([$msgId]);
    }
    
    // Optionally: delete orphaned mailboxes
    $stmtOrphan = $pdo->query("
        DELETE mailboxes 
        FROM mailboxes 
        LEFT JOIN messages ON mailboxes.id = messages.mailbox_id 
        WHERE messages.id IS NULL
    ");
    $orphansDeleted = $stmtOrphan->rowCount();
    
    $pdo->commit();
    writeLog("Successfully deleted " . count($oldMessages) . " messages and $deletedAttCount attachments.");
    writeLog("Cleaned up $orphansDeleted empty mailboxes.");

} catch (Exception $e) {
    $pdo->rollBack();
    writeLog("Error during retention: " . $e->getMessage());
}

writeLog("Retention process finished.");
