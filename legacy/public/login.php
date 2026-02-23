<?php
require_once __DIR__ . '/../app/auth.php';

if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    header("Location: /index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    if (login($password)) {
        header("Location: /index.php");
        exit;
    } else {
        $error = 'Invalid password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Evicio Test Inbox</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="glass-panel">
            <h2>Welcome Back</h2>
            <p style="color: var(--text-muted); margin-bottom: 2rem;">Secure Internal Mailbox</p>
            
            <?php if ($error): ?>
                <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-input" required autofocus autocomplete="current-password">
                </div>
                <button type="submit" class="btn">Sign In</button>
            </form>
        </div>
    </div>
</body>
</html>
