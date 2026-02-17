<?php
require_once __DIR__ . '/../config/database.php';
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $db = Database::getConnection();

    $stmt = $db->prepare("SELECT id, username FROM users WHERE email = ? AND is_deleted = 0");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        // Set expiry for 1 hour from now
        $expires = date("Y-m-d H:i:s", strtotime('+1 hour'));

        $update = $db->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
        $update->execute([$token, $expires, $user['id']]);

        // Email logic
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
        $resetLink = "$protocol://{$_SERVER['HTTP_HOST']}" . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=$token";

        $subject = "Reset your Hello Neighbor Password";
        $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: Hello Neighbor <noreply@{$_SERVER['HTTP_HOST']}>";

        $body = "<h2>Hello, {$user['username']}</h2>
                 <p>You requested a password reset. Click the link below to set a new password. This link expires in 1 hour.</p>
                 <a href='$resetLink' style='padding: 10px 20px; background: #2563eb; color: white; text-decoration: none; border-radius: 5px;'>Reset Password</a>";

        mail($email, $subject, $body, $headers);
    }
    // We show the same message regardless of whether the email exists for security (preventing email harvesting)
    $message = "✅ If that email is in our system, a reset link has been sent.";
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Forgot Password | Hello Neighbor</title>
    <link rel="stylesheet" href="../config/styles.css">
</head>

<body>
    <div class="container" style="max-width: 400px; margin-top: 50px;">
        <h2>Reset Password</h2>
        <?php if ($message): ?>
            <p style="color: var(--success);"><?= $message ?></p>
        <?php endif; ?>
        <form method="POST">
            <label>Enter your account email</label>
            <input type="email" name="email" placeholder="yourmail@example.com" required>
            <button type="submit" class="primary-button" style="width: 100%; margin-top: 10px;">Send Reset Link</button>
        </form>
        <p style="margin-top: 20px;"><a href="index.php?page=login" style="text-decoration: none; display: block; margin-top: 15px; font-size: 0.85rem; color: var(--text-muted);">Back to Login</a></p>
    </div>
</body>

</html>