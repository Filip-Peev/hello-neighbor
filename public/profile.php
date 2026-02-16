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

$rsvpStmt = $db->prepare("
    SELECT e.title, e.event_date, e.id 
    FROM event_rsvps r 
    JOIN events e ON r.event_id = e.id 
    WHERE r.user_id = ? AND e.event_date >= CURDATE()
    ORDER BY e.event_date ASC
");
$rsvpStmt->execute([$userId]);
$myRsvps = $rsvpStmt->fetchAll();

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
    <h3 class="card-title">🏠 Your Summary</h3>

    <p class="card-description">
        Tell your neighbors what you do or how you can help (e.g., "Professional Plumber", "Available for pet sitting", "I have a ladder you can borrow").
    </p>

    <form method="POST" action="index.php?page=profile"><textarea name="summary" id="postContent" class="summary-textarea" maxlength="500" minlength="2" placeholder="Write a short summary of your skills or how you can help..."><?php echo htmlspecialchars($userData['summary'] ?? ''); ?></textarea>

        <div style="text-align: right; font-size: 0.75rem; color: #888; margin-top: 0px;">
            <span id="charCount">0</span>/500
        </div>

        <button type="submit" name="update_summary" class="primary-button">
            Save Summary
        </button>
    </form>
</div>

<div class="profile-grid">

    <div class="profile-card" style="margin-bottom: 25px;">
        <h3 style="margin-top:0;">📅 Your Upcoming Events</h3>
        <?php if (empty($myRsvps)): ?>
            <p style="color: #666; font-size: 0.9rem;">You haven't joined any events yet. Check the calendar!</p>
        <?php else: ?>
            <ul style="list-style: none; padding: 0;">
                <?php foreach ($myRsvps as $rsvp): ?>
                    <li style="margin-bottom: 8px; display: flex; justify-content: space-between;">
                        <span><strong><?= date('M j', strtotime($rsvp['event_date'])) ?></strong> - <?= htmlspecialchars($rsvp['title']) ?></span>
                        <a href="index.php?page=events" style="font-size: 0.8rem; color: var(--primary);">View</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="profile-card" style="margin-bottom: 25px;">
        <h3 style="margin-top:0;">📋 TaskBoard</h3>

        <p style="color: #666; font-size: 0.9rem;">
            Manage your tasks, track progress, and stay organized in one place.
        </p>

        <a href="http://127.0.0.1/task-board/" target="_blank"
            style="font-size: 0.9rem; color: var(--primary); font-weight: 600; text-decoration: none;">
            <br>Open TaskBoard →
        </a>
    </div>

    <div class="profile-card">
        <h3>Update Email</h3>
        <form method="POST" action="index.php?page=profile">
            <input
                type="email"
                name="email"
                value="<?php echo htmlspecialchars($userData['email']); ?>"
                required
                class="profile-input">

            <button type="submit" name="update_email" class="primary-button">
                Save Email
            </button>
        </form>
    </div>

    <div class="profile-card">
        <h3>Change Password</h3>

        <form method="POST" action="index.php?page=profile">
            <input
                type="password"
                name="current_password"
                placeholder="Current Password"
                required
                class="profile-input">

            <input
                type="password"
                name="new_password"
                placeholder="New Password"
                required
                class="profile-input">

            <input
                type="password"
                name="confirm_password"
                placeholder="Confirm New"
                required
                class="profile-input profile-input--last">

            <button type="submit" name="change_password" class="primary-button">
                Update Password
            </button>
        </form>
    </div>
</div>


<hr style="margin: 40px 0; border: 0; border-top: 1px solid #eee;">

<div class="danger-card">
    <h3 class="danger-title">Danger Zone</h3>

    <p class="card-description">
        Deleting your account is permanent. This will mark your account as inactive, and you will no longer be able to log in. Your public profile will be hidden from the neighbor directory, but your existing posts and comments will remain to keep the conversation history intact.
    </p>

    <form
        method="POST"
        action="index.php?page=profile"
        onsubmit="return confirm('Are you sure?');">
        <input
            type="password"
            name="delete_confirm_pass"
            placeholder="Enter password to confirm"
            required
            class="danger-input">

        <button type="submit" name="delete_account" class="delete-account-button">
            Delete My Account
        </button>
    </form>
</div>