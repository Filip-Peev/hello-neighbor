<?php
require_once '../config/database.php';

$message = "";
$type = "error";

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $db = Database::getConnection();

    // Find the user with this token
    $stmt = $db->prepare("SELECT id FROM users WHERE verification_token = ? AND is_verified = 0");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        // Mark user as verified and clear the token
        $update = $db->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?");
        $update->execute([$user['id']]);

        $message = "Success! Your email has been verified. You can now log in.";
        $type = "success";
    } else {
        $message = "Invalid or expired verification link.";
    }
} else {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Email Verification</title>
    <link rel="stylesheet" href="css/styles.css">
</head>

<body>
    <div class="container" style="margin-top: 50px; text-align: center;">
        <h2>Email Verification</h2>
        <p style="color: <?= $type === 'success' ? 'green' : 'red' ?>;">
            <?= htmlspecialchars($message) ?>
        </p>
        <a href="index.php?page=login" class="primary-button">Go to Login</a>
    </div>
</body>

</html>