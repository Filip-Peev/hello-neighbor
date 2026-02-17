<?php
require_once __DIR__ . '/../config/database.php';

$token = $_GET['token'] ?? '';
$db = Database::getConnection();

// Validate token and expiry
$stmt = $db->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    die("Invalid or expired reset link. Please request a new one.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPass = $_POST['password'];
    $confirmPass = $_POST['confirm_password'];

    if ($newPass === $confirmPass) {
        $hashed = password_hash($newPass, PASSWORD_DEFAULT);
        $update = $db->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $update->execute([$hashed, $user['id']]);

        echo "<script>alert('Password updated! You can now login.'); window.location.href='index.php?page=login';</script>";
        exit;
    } else {
        $error = "Passwords do not match.";
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Set New Password | Hello Neighbor</title>
    <link rel="stylesheet" href="../config/styles.css">
</head>

<body>
    <div class="container" style="max-width: 400px; margin-top: 50px;">
        <h2>Create New Password</h2>
        <?php if (isset($error)): ?> <p style="color: red;"><?= $error ?></p> <?php endif; ?>
        <form method="POST">
            <input type="password" name="password" placeholder="New Password" required minlength="6">
            <input type="password" name="confirm_password" placeholder="Confirm New Password" required minlength="6">
            <button type="submit" class="primary-button" style="width: 100%; margin-top: 10px;">Update Password</button>
        </form>
    </div>
</body>

</html>