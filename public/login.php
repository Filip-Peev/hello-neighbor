<?php
// public/login.php

$loginSuccess = false; // Track if login was successful to control the UI

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

                $loginSuccess = true;
                echo "<p style='color: green; font-weight: bold;'>✅ Login successful! Welcome back, " . htmlspecialchars($user['username']) . ".</p>";
                echo "<p>Redirecting you to the home page...</p>";
                echo "<script>setTimeout(() => { window.location.href = 'index.php?page=feed'; }, 2000);</script>";
            } else {
                echo "<p style='color: red;'>❌ Invalid username or password.</p>";
            }
        } catch (PDOException $e) {
            echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
        }
    }
}
?>

<?php if (!$loginSuccess): ?>
    <h3>Login to Your Account</h3>
    <form id="loginForm" method="POST" action="index.php?page=login">
        <input type="text" maxlength="50" minlength="2" name="identifier" placeholder="Username or Email" required><br>
        <input type="password" maxlength="50" minlength="2" name="password" placeholder="Password" required><br>
        <button type="submit" id="loginBtn">Login</button>
    </form>
<?php endif; ?>

<script>
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.onsubmit = function() {
            const btn = document.getElementById('loginBtn');
            setTimeout(() => {
                btn.disabled = true;
                btn.innerText = 'Verifying...';
                btn.style.opacity = '0.7';
                btn.style.cursor = 'not-allowed';
            }, 10);
        };
    }
</script>