<?php
// public/login.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userOrEmail = trim($_POST['identifier'] ?? '');
    $pass = $_POST['password'] ?? '';

    if (!empty($userOrEmail) && !empty($pass)) {
        try {
            // 1. Look for the user by username OR email
            $stmt = $db->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND is_deleted = 0");
            $stmt->execute([$userOrEmail, $userOrEmail]);
            $user = $stmt->fetch();

            // 2. Verify the password against the stored hash
            if ($user && password_verify($pass, $user['password_hash'])) {
                // 3. Start a session and "log them in"
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                // 4. Update the last_login timestamp
                $updateStmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$user['id']]);

                echo "<p style='color: green;'>✅ Login successful! Welcome back, " . htmlspecialchars($user['username']) . ".</p>";
                echo "<script>setTimeout(() => { window.location.href = 'index.php?page=home'; }, 2000);</script>";
            } else {
                echo "<p style='color: red;'>❌ Invalid username or password.</p>";
            }
        } catch (PDOException $e) {
            echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
        }
    }
}
?>

<h3>Login to Your Account</h3>
<form method="POST" action="index.php?page=login">
    <input type="text" name="identifier" placeholder="Username or Email" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <button type="submit" style="background: #007bff;">Login</button>
</form>