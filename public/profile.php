<?php
// public/profile.php

// 1. Security check: Only members allowed
if (!isset($_SESSION['user_id'])) {
    echo "<h3>Access Denied</h3><p>Please log in to view this page.</p>";
    return;
}

$userId = $_SESSION['user_id'];
$msg = "";

// --- LOGIC SECTION: All POST actions redirect to clear the request buffer ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // A. Handle Summary Update
    if (isset($_POST['update_summary'])) {
        $summary = trim($_POST['summary'] ?? '');
        $stmt = $db->prepare("UPDATE users SET summary = ? WHERE id = ?");
        $stmt->execute([$summary, $userId]);
        header("Location: index.php?page=profile&status=summary_updated");
        exit;
    }

    // B. Handle Email Update
    if (isset($_POST['update_email'])) {
        $newEmail = trim($_POST['email'] ?? '');
        if (filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            try {
                $stmt = $db->prepare("UPDATE users SET email = ? WHERE id = ?");
                $stmt->execute([$newEmail, $userId]);
                header("Location: index.php?page=profile&status=email_updated");
                exit;
            } catch (PDOException $e) {
                header("Location: index.php?page=profile&status=error_email_exists");
                exit;
            }
        } else {
            header("Location: index.php?page=profile&status=error_invalid_email");
            exit;
        }
    }

    // C. Handle Password Change
    if (isset($_POST['change_password'])) {
        $currentPass = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!password_verify($currentPass, $user['password_hash'])) {
            header("Location: index.php?page=profile&status=error_wrong_pass");
        } elseif ($newPass !== $confirmPass) {
            header("Location: index.php?page=profile&status=error_mismatch");
        } elseif (strlen($newPass) < 8) {
            header("Location: index.php?page=profile&status=error_short_pass");
        } else {
            $newHash = password_hash($newPass, PASSWORD_DEFAULT);
            $update = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $update->execute([$newHash, $userId]);
            header("Location: index.php?page=profile&status=pass_updated");
        }
        exit;
    }

    // D. Handle Account Deletion
    if (isset($_POST['delete_account'])) {
        $confirmPass = $_POST['delete_confirm_pass'] ?? '';
        $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (password_verify($confirmPass, $user['password_hash'])) {
            $deleteStmt = $db->prepare("UPDATE users SET is_deleted = 1 WHERE id = ?");
            $deleteStmt->execute([$userId]);
            session_destroy();
            header("Location: index.php?page=feed&msg=deleted");
            exit;
        } else {
            header("Location: index.php?page=profile&status=error_delete_pass");
            exit;
        }
    }
}

// --- MESSAGE HANDLING: Convert URL status to user-friendly messages ---
$status = $_GET['status'] ?? '';
switch ($status) {
    case 'summary_updated':
        $msg = "<p style='color: green;'>✅ Neighbor summary updated!</p>";
        break;
    case 'email_updated':
        $msg = "<p style='color: green;'>✅ Email updated successfully!</p>";
        break;
    case 'pass_updated':
        $msg = "<p style='color: green;'>✅ Password successfully updated!</p>";
        break;
    case 'error_email_exists':
        $msg = "<p style='color: red;'>❌ Email already in use.</p>";
        break;
    case 'error_invalid_email':
        $msg = "<p style='color: red;'>❌ Invalid email address.</p>";
        break;
    case 'error_wrong_pass':
        $msg = "<p style='color: red;'>❌ Current password incorrect.</p>";
        break;
    case 'error_mismatch':
        $msg = "<p style='color: red;'>❌ New passwords do not match.</p>";
        break;
    case 'error_short_pass':
        $msg = "<p style='color: red;'>❌ Password too short (min 8 chars).</p>";
        break;
    case 'error_delete_pass':
        $msg = "<p style='color: red;'>❌ Incorrect password. Account not deleted.</p>";
        break;
}

// 2. DATA FETCH: Get user details for display
$stmt = $db->prepare("SELECT username, email, summary, created_at, last_login FROM users WHERE id = ?");
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

<div class="card">
    <h3 class="card-title">🏠 Your Neighbor Summary</h3>

    <p class="card-description">
        Tell your neighbors what you do or how you can help (e.g., "Professional Plumber", "Available for pet sitting", "I have a ladder you can borrow").
    </p>

    <form method="POST" action="index.php?page=profile">
        <textarea
            name="summary"
            class="summary-textarea"
            placeholder="Write a short summary of your skills or how you can help..."><?php echo htmlspecialchars($userData['summary'] ?? ''); ?></textarea>

        <button type="submit" name="update_summary" class="primary-button">
            Save Summary
        </button>
    </form>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
    <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <h3>Update Email</h3>
        <form method="POST" action="index.php?page=profile">
            <input type="email" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" required style="width: 90%; padding: 8px; margin: 10px 0;">
            <button type="submit" name="update_email" class="primary-button">
                Save Email
            </button>
        </form>
    </div>

    <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <h3>Change Password</h3>
        <form method="POST" action="index.php?page=profile">
            <input type="password" name="current_password" placeholder="Current Password" required style="width: 90%; padding: 8px; margin-bottom: 10px;">
            <input type="password" name="new_password" placeholder="New Password" required style="width: 90%; padding: 8px; margin-bottom: 10px;">
            <input type="password" name="confirm_password" placeholder="Confirm New" required style="width: 90%; padding: 8px; margin-bottom: 15px;">
            <button type="submit" name="change_password" class="primary-button">
                Update Password
            </button>
        </form>
    </div>
</div>

<hr style="margin: 40px 0; border: 0; border-top: 1px solid #eee;">

<div style="background: #fff5f5; padding: 20px; border: 1px solid #ffa8a8; border-radius: 8px;">
    <h3 style="color: #c92a2a; margin-top: 0;">Danger Zone</h3>
    <form method="POST" action="index.php?page=profile" onsubmit="return confirm('Are you sure?');">
        <input type="password" name="delete_confirm_pass" placeholder="Enter password to confirm" required style="padding: 8px; margin-right: 10px;">
        <button type="submit" name="delete_account" class="delete-account-button">
            Delete My Account
        </button>

    </form>
</div>