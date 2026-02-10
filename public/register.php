<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Grab & trim form data
    $user   = trim($_POST['username'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $pass   = $_POST['password'] ?? '';

    if (!empty($user) && !empty($email) && !empty($pass)) {
        // 1️⃣ Check for an existing active account
        try {
            $checkStmt = $db->prepare(
                "SELECT id FROM users WHERE (username = ? OR email = ?) AND is_deleted = 0"
            );
            $checkStmt->execute([$user, $email]);

            if ($checkStmt->fetch()) {   // already exists
                echo "<p style='color: red;'>❌ Username or e‑mail already in use.</p>";
                return;                 // stop further processing
            }
        } catch (PDOException $e) {
            echo "<p style='color: red;'>❌ DB error during uniqueness check: " . htmlspecialchars($e->getMessage()) . "</p>";
            return;
        }

        // 2️⃣ All good – hash the password and insert
        $hashedPassword = password_hash($pass, PASSWORD_DEFAULT);

        try {
            $stmt = $db->prepare(
                "INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)"
            );
            $stmt->execute([$user, $email, $hashedPassword]);

            echo "<p style='color: green;'>✅ Success! You can now <a href='index.php?page=login'>Login</a>.</p>";
        } catch (PDOException $e) {
            // 23000 = duplicate entry (race‑condition after check)
            $msg = ($e->getCode() == 23000)
                ? "User already exists."
                : $e->getMessage();
            echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($msg) . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ All fields are required.</p>";
    }
}
?>

<h3 class="form-title">Create an Account</h3>
<form method="POST" id="registerForm" action="index.php?page=register">
    <input type="text" maxlength="50" minlength="2" name="username" placeholder="Username" required><br>
    <input type="email" name="email" placeholder="Email" required><br>
    <input type="password" maxlength="50" minlength="2" name="password" placeholder="Password" required><br>
    <button type="submit">Join Now</button>
</form>