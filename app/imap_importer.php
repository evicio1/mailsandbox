<?php
// app/imap_importer.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/mail_parser.php';
require_once __DIR__ . '/html_sanitizer.php';

$logFile = STORAGE_PATH . '/logs/importer_' . date('Y-m-d') . '.log';
if (!is_dir(dirname($logFile))) {
    mkdir(dirname($logFile), 0777, true);
}

function writeLog($msg) {
    global $logFile;
    $time = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$time] $msg\n", FILE_APPEND);
    echo "[$time] $msg\n";
}

writeLog("Starting IMAP import...");

$mailbox = IMAP_HOST;
$username = IMAP_USER;
$password = IMAP_PASS;

$inbox = @imap_open($mailbox, $username, $password);

if (!$inbox) {
    writeLog("Failed to connect to IMAP: " . imap_last_error());
    exit(1);
}

$emails = imap_search($inbox, 'UNSEEN');
if (!$emails) {
    writeLog("No new emails found.");
    imap_close($inbox);
    exit(0);
}

$pdo = getDbConnection();

$newCount = 0;
$skippedCount = 0;
$errorCount = 0;

foreach ($emails as $email_number) {
    try {
        $parser = new MailParser($inbox, $email_number);
        $parser->parse();

        $mbKey = normalizeMailboxKey($parser->getTargetMailbox());
        
        $dedupeKey = generateDedupeKey(
            $mbKey,
            $parser->messageId,
            $parser->fromEmail,
            $parser->subject,
            $parser->receivedAt,
            $parser->sizeBytes
        );

        // Check if exists
        $stmt = $pdo->prepare("SELECT id FROM messages WHERE dedupe_key = ?");
        $stmt->execute([$dedupeKey]);
        if ($stmt->fetchColumn()) {
            $skippedCount++;
            // Optionally mark as read
            imap_setflag_full($inbox, $email_number, "\\Seen");
            continue;
        }

        $pdo->beginTransaction();

        // Ensure mailbox exists
        $stmtMb = $pdo->prepare("SELECT id FROM mailboxes WHERE mailbox_key = ?");
        $stmtMb->execute([$mbKey]);
        $mbId = $stmtMb->fetchColumn();
        
        if (!$mbId) {
            $stmt = $pdo->prepare("INSERT INTO mailboxes (mailbox_key) VALUES (?)");
            $stmt->execute([$mbKey]);
            $mbId = $pdo->lastInsertId();
        }

        $htmlSanitized = sanitizeHtml($parser->htmlBody);

        $stmtMsg = $pdo->prepare("
            INSERT INTO messages (
                mailbox_id, dedupe_key, message_id, subject, from_name, from_email,
                to_raw, cc_raw, received_at, snippet, text_body, html_body_sanitized,
                headers_raw, size_bytes, is_read
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0
            )
        ");

        $snippet = mb_substr(trim($parser->textBody), 0, 150);
        $toRawJson = json_encode($parser->toRaw);
        $ccRawJson = json_encode($parser->ccRaw);

        $stmtMsg->execute([
            $mbId, $dedupeKey, $parser->messageId, $parser->subject, $parser->fromName, $parser->fromEmail,
            $toRawJson, $ccRawJson, $parser->receivedAt, $snippet, $parser->textBody, $htmlSanitized,
            $parser->headersRaw, $parser->sizeBytes
        ]);

        $msgId = $pdo->lastInsertId();

        // Process attachments
        if (!empty($parser->attachments)) {
            $msgAttachPath = ATTACHMENTS_PATH . '/' . $msgId;
            if (!is_dir($msgAttachPath)) {
                mkdir($msgAttachPath, 0777, true);
            }

            $stmtAtt = $pdo->prepare("
                INSERT INTO attachments (message_id, filename, content_type, size_bytes, storage_path)
                VALUES (?, ?, ?, ?, ?)
            ");

            foreach ($parser->attachments as $att) {
                // Ensure unique safe filename
                $safeName = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $att['filename']);
                if (empty($safeName)) {
                    $safeName = 'attachment_' . time();
                }
                $filePath = $msgAttachPath . '/' . $safeName;
                
                // Write file
                file_put_contents($filePath, $att['content']);

                $stmtAtt->execute([
                    $msgId,
                    $att['filename'], // original
                    $att['type'],
                    $att['size'],
                    $filePath
                ]);
            }
        }

        $pdo->commit();
        $newCount++;
        
        // Mark as seen
        imap_setflag_full($inbox, $email_number, "\\Seen");

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $errorCount++;
        writeLog("Error processing msg $email_number: " . $e->getMessage());
    }
}

imap_close($inbox);

writeLog("Finished. Imported: $newCount, Skipped: $skippedCount, Errors: $errorCount");
