<?php
// public/profile.php

// 1. Security check: Only members allowed
if (!isset($_SESSION['user_id'])) {
    echo "<h3>Access Denied</h3><p>Please log in to view this page.</p>";
    return;
}

$userId = $_SESSION['user_id'];
$msg = "";

// LOGIC 5: DELETE ACCOUNT (soft‑delete) moved up here for the "headers already sent" error, must be before all the html
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
    // We double-check the password one last time for safety
    $confirmPass = $_POST['delete_confirm_pass'] ?? '';

    $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (password_verify($confirmPass, $user['password_hash'])) {
        // Instead of DELETE, we mark the row as deleted
        $deleteStmt = $db->prepare("UPDATE users SET is_deleted = 1 WHERE id = ?");
        $deleteStmt->execute([$userId]);

        session_destroy();
        header("Location: index.php?page=feed&msg=deleted");
        exit;
    } else {
        $msg = "<p style='color: red;'>❌ Incorrect password. Account not deleted.</p>";
    }
}

// 2. LOGIC: Handle Email Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_email'])) {
    $newEmail = trim($_POST['email'] ?? '');
    if (filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        try {
            $stmt = $db->prepare("UPDATE users SET email = ? WHERE id = ?");
            $stmt->execute([$newEmail, $userId]);
            $msg = "<p style='color: green;'>✅ Email updated successfully!</p>";
        } catch (PDOException $e) {
            $msg = "<p style='color: red;'>❌ Error: This email is already registered to another account.</p>";
        }
    } else {
        $msg = "<p style='color: red;'>❌ Please enter a valid email address.</p>";
    }
}

// 3. LOGIC: Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPass = $_POST['current_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!password_verify($currentPass, $user['password_hash'])) {
        $msg = "<p style='color: red;'>❌ Current password is incorrect.</p>";
    } elseif ($newPass !== $confirmPass) {
        $msg = "<p style='color: red;'>❌ New passwords do not match.</p>";
    } elseif (strlen($newPass) < 8) {
        $msg = "<p style='color: red;'>❌ New password must be at least 8 characters long.</p>";
    } else {
        $newHash = password_hash($newPass, PASSWORD_DEFAULT);
        $update = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $update->execute([$newHash, $userId]);
        $msg = "<p style='color: green;'>✅ Password successfully updated!</p>";
    }
}

// 4. DATA FETCH: Get user details for display
$stmt = $db->prepare("SELECT username, email, created_at, last_login FROM users WHERE id = ?");
$stmt->execute([$userId]);
$userData = $stmt->fetch();
?>

<h2>Profile Settings</h2>
<?php echo $msg; ?>

<div style="background: #e9ecef; padding: 15px; border-radius: 8px; margin-bottom: 25px; border: 1px solid #ced4da;">
    <p style="margin: 5px 0;"><strong>Username:</strong> <?php echo htmlspecialchars($userData['username']); ?></p>
    <p style="margin: 5px 0;"><strong>Member Since:</strong> <?php echo date('F j, Y', strtotime($userData['created_at'])); ?></p>
    <p style="margin: 5px 0;"><strong>Last Login:</strong>
        <?php echo $userData['last_login'] ? date('M j, Y | g:i a', strtotime($userData['last_login'])) : 'Your first session!'; ?>
    </p>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">

    <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <h3>Update Email</h3>
        <form method="POST" action="index.php?page=profile">
            <label style="font-size: 0.9rem; color: #666;">Current Email:</label><br>
            <input type="email" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" required style="width: 90%; padding: 8px; margin: 10px 0;">
            <button type="submit" name="update_email" style="background: #007bff; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer;">Save Email</button>
        </form>
    </div>

    <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <h3>Change Password</h3>
        <form method="POST" action="index.php?page=profile">
            <input type="password" name="current_password" placeholder="Current Password" required style="width: 90%; padding: 8px; margin-bottom: 10px;">
            <input type="password" name="new_password" placeholder="New Password (min 8 chars)" required style="width: 90%; padding: 8px; margin-bottom: 10px;">
            <input type="password" name="confirm_password" placeholder="Confirm New Password" required style="width: 90%; padding: 8px; margin-bottom: 15px;">
            <button type="submit" name="change_password" style="background: #28a745; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer;">Update Password</button>
        </form>
    </div>

</div>

<hr style="margin: 40px 0; border: 0; border-top: 1px solid #eee;">

<div style="background: #fff5f5; padding: 20px; border: 1px solid #ffa8a8; border-radius: 8px;">
    <h3 style="color: #c92a2a; margin-top: 0;">Danger Zone</h3>
    <p style="font-size: 0.9rem; color: #555;">Once you delete your account, there is no going back. Please be certain.</p>

    <form method="POST" action="index.php?page=profile" onsubmit="return confirm('Are you ABSOLUTELY sure? This cannot be undone.');">
        <input type="password" name="delete_confirm_pass" placeholder="Enter password to confirm" required style="padding: 8px; margin-right: 10px;">
        <button type="submit" name="delete_account" style="background: #c92a2a; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer;">Delete My Account</button>
    </form>
</div>