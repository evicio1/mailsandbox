<?php
// public/mailbox.php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';
requireAuth();

$id = $_GET['id'] ?? 0;
$pdo = getDbConnection();

$stmt = $pdo->prepare("SELECT * FROM mailboxes WHERE id = ?");
$stmt->execute([$id]);
$mailbox = $stmt->fetch();

if (!$mailbox) {
    die("Mailbox not found.");
}

$page = $_GET['page'] ?? 1;
$limit = 50;
$offset = ($page - 1) * $limit;

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE mailbox_id = ?");
$stmtCount->execute([$id]);
$total = $stmtCount->fetchColumn();

$stmtMsg = $pdo->prepare("SELECT * FROM messages WHERE mailbox_id = ? ORDER BY received_at DESC, id DESC LIMIT ? OFFSET ?");
$stmtMsg->bindParam(1, $id, PDO::PARAM_INT);
$stmtMsg->bindParam(2, $limit, PDO::PARAM_INT);
$stmtMsg->bindParam(3, $offset, PDO::PARAM_INT);
$stmtMsg->execute();
$messages = $stmtMsg->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($mailbox['mailbox_key']); ?> - Test Inbox</title>
    <link rel="stylesheet" href="/public/assets/style.css">
    <style>
        .msg-list { border-radius: 12px; overflow: hidden; border: 1px solid var(--surface-border); background: var(--surface); }
        .msg-row {
            display: grid;
            grid-template-columns: 200px 1fr 150px;
            gap: 1rem;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--surface-border);
            text-decoration: none;
            color: var(--text-main);
            transition: background 0.2s;
        }
        .msg-row:last-child { border-bottom: none; }
        .msg-row:hover { background: var(--surface-border); }
        .msg-row.unread { font-weight: 600; color: #fff; background: rgba(99, 102, 241, 0.05); }
        .msg-from { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .msg-subject-snippet { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .msg-snippet { color: var(--text-muted); font-weight: 400; font-size: 0.9rem; }
        .msg-date { text-align: right; color: var(--text-muted); font-size: 0.9rem; }
        
        @media (max-width: 768px) {
            .msg-row { grid-template-columns: 1fr; gap: 0.5rem; }
            .msg-date { text-align: left; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h2>📬 <?php echo htmlspecialchars($mailbox['mailbox_key']); ?></h2>
                <div style="font-size: 0.9rem; color: var(--text-muted);">Total Emails: <?php echo $total; ?></div>
            </div>
            <div class="nav-links">
                <a href="/public/index.php">Back to Search</a>
                <a href="/public/logout.php">Logout</a>
            </div>
        </div>

        <?php if ($total === 0): ?>
            <p>No messages in this mailbox yet.</p>
        <?php else: ?>
            <div class="msg-list">
                <?php foreach ($messages as $msg): ?>
                    <a href="/public/message.php?id=<?php echo $msg['id']; ?>" class="msg-row <?php echo !$msg['is_read'] ? 'unread' : ''; ?>">
                        <div class="msg-from">
                            <?php echo htmlspecialchars($msg['from_name'] ?: $msg['from_email']); ?>
                        </div>
                        <div class="msg-subject-snippet">
                            <span><?php echo htmlspecialchars($msg['subject'] ?: '(No Subject)'); ?></span>
                            <span class="msg-snippet"> - <?php echo htmlspecialchars($msg['snippet']); ?></span>
                        </div>
                        <div class="msg-date">
                            <?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($msg['received_at']))); ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <div style="margin-top: 1rem; display: flex; justify-content: space-between;">
                <?php if ($page > 1): ?>
                    <a href="?id=<?php echo $id; ?>&page=<?php echo $page - 1; ?>" class="btn btn-sm btn-outline">Previous</a>
                <?php else: ?>
                    <span style="width:80px"></span>
                <?php endif; ?>
                
                <?php if ($offset + $limit < $total): ?>
                    <a href="?id=<?php echo $id; ?>&page=<?php echo $page + 1; ?>" class="btn btn-sm btn-outline">Next</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
