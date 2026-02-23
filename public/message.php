<?php
// public/message.php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/utils.php';
require_once __DIR__ . '/../app/otp_helper.php';
requireAuth();

$id = $_GET['id'] ?? 0;
$pdo = getDbConnection();

$stmt = $pdo->prepare("
    SELECT m.*, b.mailbox_key 
    FROM messages m
    JOIN mailboxes b ON m.mailbox_id = b.id
    WHERE m.id = ?
");
$stmt->execute([$id]);
$msg = $stmt->fetch();

if (!$msg) {
    die("Message not found.");
}

// Mark as read
if (!$msg['is_read']) {
    $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ?")->execute([$id]);
}

// Fetch attachments
$stmtAtt = $pdo->prepare("SELECT id, filename, size_bytes FROM attachments WHERE message_id = ?");
$stmtAtt->execute([$id]);
$attachments = $stmtAtt->fetchAll();

// Extract OTP
$otpSourceText = !empty($msg['text_body']) ? $msg['text_body'] : strip_tags($msg['html_body_sanitized']);
$extractedOtp = extractBestOtp($otpSourceText);

$toRaw = json_decode($msg['to_raw'] ?? '[]', true);
$ccRaw = json_decode($msg['cc_raw'] ?? '[]', true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($msg['subject'] ?: 'Message Details'); ?></title>
    <link rel="stylesheet" href="/assets/style.css">
    <style>
        .msg-header {
            background: var(--surface);
            border: 1px solid var(--surface-border);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .header-grid {
            display: grid;
            grid-template-columns: 100px 1fr;
            gap: 0.5rem 1rem;
            font-size: 0.95rem;
            margin-top: 1rem;
        }
        .lbl { color: var(--text-muted); font-weight: 500; text-align: right; }
        .val { color: var(--text-main); word-break: break-all; }
        
        .otp-box {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            padding: 1rem 1.5rem;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
        }
        .otp-text { font-size: 1.5rem; font-weight: 700; font-family: monospace; letter-spacing: 2px; color: var(--success); }
        
        .tabs { display: flex; gap: 0.5rem; margin-bottom: -1px; }
        .tab-btn {
            background: var(--surface);
            border: 1px solid var(--surface-border);
            border-bottom: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px 8px 0 0;
            color: var(--text-muted);
            cursor: pointer;
            font-weight: 500;
        }
        .tab-btn.active { background: var(--bg); color: var(--primary); border-top-color: var(--primary); }
        
        .body-content {
            background: var(--bg);
            border: 1px solid var(--surface-border);
            border-radius: 0 8px 8px 8px;
            padding: 1.5rem;
            min-height: 300px;
        }
        .body-text { white-space: pre-wrap; font-family: monospace; font-size: 0.9rem; overflow-x: auto; }
        
        .attachments-box {
            margin-top: 2rem;
            background: var(--surface);
            border: 1px solid var(--surface-border);
            border-radius: 12px;
            padding: 1.5rem;
        }
        .att-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 1rem;
            background: var(--bg);
            border: 1px solid var(--surface-border);
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }
        .att-item:last-child { margin-bottom: 0; }
        .copy-btn { margin-left: auto; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header" style="margin-bottom: 1rem; border: none; padding: 0;">
            <div class="nav-links" style="margin-left: 0;">
                <a href="/mailbox.php?id=<?php echo $msg['mailbox_id']; ?>" style="margin-left: 0;">&larr; Back to <?php echo htmlspecialchars($msg['mailbox_key']); ?></a>
            </div>
            <div class="nav-links">
                <a href="/index.php">Search</a>
                <a href="/logout.php">Logout</a>
            </div>
        </div>

        <div class="msg-header">
            <h2 style="margin-bottom: 0;"><?php echo htmlspecialchars($msg['subject'] ?: '(No Subject)'); ?></h2>
            <div class="header-grid">
                <div class="lbl">From:</div>
                <div class="val">
                    <strong><?php echo htmlspecialchars($msg['from_name']); ?></strong> 
                    &lt;<?php echo htmlspecialchars($msg['from_email']); ?>&gt;
                </div>
                
                <div class="lbl">To:</div>
                <div class="val"><?php echo htmlspecialchars(implode(', ', $toRaw)); ?></div>
                
                <?php if (!empty($ccRaw)): ?>
                    <div class="lbl">CC:</div>
                    <div class="val"><?php echo htmlspecialchars(implode(', ', $ccRaw)); ?></div>
                <?php endif; ?>
                
                <div class="lbl">Date:</div>
                <div class="val"><?php echo htmlspecialchars(date('D, M j, Y \a\t g:i A', strtotime($msg['received_at']))); ?></div>
            </div>
        </div>

        <?php if ($extractedOtp): ?>
            <div class="otp-box">
                <div>
                    <div style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase;">Detected OTP / Code</div>
                    <div class="otp-text" id="otpText"><?php echo htmlspecialchars($extractedOtp); ?></div>
                </div>
                <button class="btn btn-sm copy-btn" onclick="copyOtp()">Copy Code</button>
            </div>
        <?php endif; ?>

        <?php if (!empty($attachments)): ?>
            <div class="attachments-box">
                <h4 style="margin-bottom: 1rem;">📎 Attachments (<?php echo count($attachments); ?>)</h4>
                <?php foreach ($attachments as $att): ?>
                    <div class="att-item">
                        <div>
                            <strong><?php echo htmlspecialchars($att['filename']); ?></strong>
                            <span style="color: var(--text-muted); font-size: 0.85rem; margin-left: 0.5rem;">
                                (<?php echo formatBytes($att['size_bytes']); ?>)
                            </span>
                        </div>
                        <a href="/attachment.php?id=<?php echo $att['id']; ?>" class="btn btn-sm btn-outline" target="_blank">Download</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div style="margin-top: 2rem;">
            <div class="tabs">
                <button class="tab-btn active" onclick="switchTab('text')">Text View</button>
                <?php if (!empty($msg['html_body_sanitized'])): ?>
                    <button class="tab-btn" onclick="switchTab('html')">HTML View</button>
                <?php endif; ?>
            </div>
            
            <div class="body-content">
                <div id="view-text" class="body-text"><?php echo htmlspecialchars($msg['text_body'] ?: 'No text body.'); ?></div>
                
                <?php if (!empty($msg['html_body_sanitized'])): ?>
                    <div id="view-html" style="display: none;">
                        <?php echo $msg['html_body_sanitized']; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function switchTab(type) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelector('.tab-btn[onclick="switchTab(\'' + type + '\')"]').classList.add('active');
            
            document.getElementById('view-text').style.display = type === 'text' ? 'block' : 'none';
            if (document.getElementById('view-html')) {
                document.getElementById('view-html').style.display = type === 'html' ? 'block' : 'none';
            }
        }

        function copyOtp() {
            var text = document.getElementById('otpText').innerText;
            navigator.clipboard.writeText(text).then(function() {
                var btn = document.querySelector('.copy-btn');
                var original = btn.innerText;
                btn.innerText = 'Copied!';
                btn.classList.add('btn-outline');
                setTimeout(function() {
                    btn.innerText = original;
                    btn.classList.remove('btn-outline');
                }, 2000);
            });
        }
    </script>
</body>
</html>
