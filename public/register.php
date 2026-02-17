<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user   = trim($_POST['username'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $pass   = $_POST['password'] ?? '';

    if (!empty($user) && !empty($email) && !empty($pass)) {
        // 1️⃣ Check for an existing active account
        try {
            $checkStmt = $db->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND is_deleted = 0");
            $checkStmt->execute([$user, $email]);

            if ($checkStmt->fetch()) {
                echo "<p style='color: red;'>❌ Username or e‑mail already in use.</p>";
                return;
            }
        } catch (PDOException $e) {
            echo "<p style='color: red;'>❌ DB error: " . htmlspecialchars($e->getMessage()) . "</p>";
            return;
        }

        // 2️⃣ All good – hash password and create verification token
        $hashedPassword = password_hash($pass, PASSWORD_DEFAULT);
        $token = bin2hex(random_bytes(32)); // Create unique token

        try {
            $stmt = $db->prepare(
                "INSERT INTO users (username, email, password_hash, verification_token, is_verified) VALUES (?, ?, ?, ?, 0)"
            );
            $stmt->execute([$user, $email, $hashedPassword, $token]);

            // 3️⃣ Construct the Verification Email
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            $verifyLink = "$protocol://$host" . dirname($_SERVER['PHP_SELF']) . "/verify.php?token=$token";

            $subject = "Welcome to Hello Neighbor!";

            $emailBody = "
            <html>
            <body style='font-family: Arial, sans-serif; color: #333; line-height: 1.6;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 10px;'>
                    <h2 style='color: #2563eb;'>🏘️ Welcome to the Neighborhood!</h2>
                    <p>Hi <strong>$user</strong>,</p>
                    <p>Thanks for joining <strong>Hello Neighbor</strong>. Please confirm your email address to activate your account and start connecting with your neighbors.</p>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='$verifyLink' style='background-color: #2563eb; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;'>Verify My Account</a>
                    </div>
                    
                    <p style='font-size: 0.85rem; color: #64748b;'>
                        If the button doesn't work, copy and paste this link into your browser:<br>
                        <a href='$verifyLink'>$verifyLink</a>
                    </p>
                    <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
                    <p style='font-size: 0.8rem; color: #94a3b8;'>If you did not sign up for this account, you can safely ignore this email.</p>
                </div>
            </body>
            </html>";

            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: Hello Neighbor <noreply@$host>" . "\r\n";

            mail($email, $subject, $emailBody, $headers);

            echo "<div style='background: #d1fae5; border: 1px solid #059669; padding: 20px; border-radius: 8px; margin-bottom: 20px;'>
                    <h4 style='margin: 0 0 10px 0; color: #065f46;'>✅ Registration Successful!</h4>
                    <p style='color: #065f46; margin: 0;'>A verification link has been sent to <strong>" . htmlspecialchars($email) . "</strong>. Please click it to activate your account.</p>
                  </div>";
        } catch (PDOException $e) {
            $msg = ($e->getCode() == 23000) ? "User already exists." : $e->getMessage();
            echo "<p style='color: var(--danger);'>❌ Error: " . htmlspecialchars($msg) . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ All fields are required.</p>";
    }
}
?>

<h3 class="form-title">Create an Account</h3>
<form method="POST" id="registerForm" action="index.php?page=register">
    <input type="text" maxlength="50" minlength="2" name="username" placeholder="Username" required><br>
    <input type="email" name="email" placeholder="Email Address" required><br>
    <input type="password" maxlength="50" minlength="2" name="password" placeholder="Password" required><br>
    <button type="submit">Register</button>
</form>