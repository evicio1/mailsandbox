<?php
// public/index.php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';
requireAuth();

$pdo = getDbConnection();
$search = $_GET['q'] ?? '';

if ($search) {
    $stmt = $pdo->prepare("SELECT id, mailbox_key, created_at FROM mailboxes WHERE mailbox_key LIKE ? ORDER BY created_at DESC LIMIT 50");
    $stmt->execute(['%' . $search . '%']);
} else {
    $stmt = $pdo->query("SELECT id, mailbox_key, created_at FROM mailboxes ORDER BY created_at DESC LIMIT 50");
}
$mailboxes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inbox Search - Evicio Test Inbox</title>
    <link rel="stylesheet" href="/public/assets/style.css">
    <style>
        .search-wrap { display: flex; gap: 1rem; margin-bottom: 2rem; }
        .mailbox-card {
            background: var(--surface);
            border: 1px solid var(--surface-border);
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: transform 0.2s, background 0.2s;
            text-decoration: none;
            color: var(--text-main);
        }
        .mailbox-card:hover {
            transform: translateY(-2px);
            background: var(--surface-border);
        }
        .mb-info h3 { margin-bottom: 0.2rem; }
        .mb-meta { color: var(--text-muted); font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>🏠 Inbox Directory</h2>
            <div class="nav-links">
                <a href="/public/index.php">Search</a>
                <a href="/public/logout.php">Logout</a>
            </div>
        </div>

        <div class="search-wrap">
            <form action="/public/index.php" method="GET" style="display:flex; width:100%; gap: 1rem;">
                <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" class="form-input" placeholder="Search for mailbox e.g., 'qa+login'...">
                <button type="submit" class="btn btn-sm" style="width:auto;">Search</button>
            </form>
        </div>

        <div>
            <?php if (count($mailboxes) === 0): ?>
                <p>No mailboxes found.</p>
            <?php else: ?>
                <?php foreach ($mailboxes as $mb): ?>
                    <a href="/public/mailbox.php?id=<?php echo $mb['id']; ?>" class="mailbox-card">
                        <div class="mb-info">
                            <h3><?php echo htmlspecialchars($mb['mailbox_key']); ?></h3>
                            <div class="mb-meta">Created: <?php echo htmlspecialchars($mb['created_at']); ?></div>
                        </div>
                        <div class="mb-action">
                            <span class="btn btn-outline btn-sm">View Inbox</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
