<?php

$loginSuccess = false;

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

                // --- NEW: Email Verification Check ---
                if (isset($user['is_verified']) && $user['is_verified'] == 0) {
                    echo "<div style='background: #fff3cd; border: 1px solid #ffeeba; color: #856404; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>";
                    echo "⚠️ <strong>Account Not Verified:</strong> Please check your email inbox to activate your account before logging in.";
                    echo "</div>";
                } else {
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
                }
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
    <h3 class="form-title">Login to Your Account</h3>
    <form id="loginForm" method="POST" action="index.php?page=login">
        <input type="text" maxlength="50" minlength="2" name="identifier" placeholder="Username or Email" required><br>
        <input type="password" maxlength="50" minlength="2" name="password" placeholder="Password" required><br>
        <button type="submit" id="loginBtn">Login</button>
        <a href="forgot_password.php" style="text-decoration: none; display: block; margin-top: 15px; font-size: 0.85rem; color: var(--text-muted);">Forgot your password?</a>
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