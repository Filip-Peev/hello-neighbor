<?php
session_start();

// 1. Check if the app is installed. If not, redirect to install.php
if (!file_exists(__DIR__ . '/../.env')) {
    header("Location: install.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();
} catch (Exception $e) {
    die("Application Error: " . $e->getMessage());
}

// Get current page and tab from URL
$page = $_GET['page'] ?? 'feed';
$tab = $_GET['tab'] ?? 'public'; // Default tab is now 'public'
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Hello Neighbor</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../config/styles.css">
</head>
<body>

    <nav>
        <div class="nav-left">
            <a href="index.php?page=feed&tab=public" style="<?php echo $tab === 'public' ? 'text-decoration: underline;' : ''; ?>">Public</a>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="index.php?page=feed&tab=private" style="<?php echo $tab === 'private' ? 'text-decoration: underline;' : ''; ?>">Private</a>
                <a href="index.php?page=feed&tab=other" style="<?php echo $tab === 'other' ? 'text-decoration: underline;' : ''; ?>">Other</a>
            <?php endif; ?>
        </div>

        <div class="nav-right">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="index.php?page=profile">Profile</a>
                <span style="color: #aaa;">| Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
                <a href="logout.php" style="color: #ff6666;">Logout</a>
            <?php else: ?>
                <a href="index.php?page=register">Register</a>
                <a href="index.php?page=login">Login</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">
        <?php
        switch ($page) {
            case 'register':
                include 'register.php';
                break;
            case 'login':
                include 'login.php';
                break;
            case 'profile':
                include 'profile.php';
                break;
            case 'feed':
            default:
                // SECURITY: Prevent guests from seeing 'private' or 'other' content via URL manipulation
                $protectedTabs = ['private', 'other'];
                if (in_array($tab, $protectedTabs) && !isset($_SESSION['user_id'])) {
                    echo "<h2>🔒 Member Access Only</h2>";
                    echo "<p>The content in <strong>" . ucfirst($tab) . "</strong> is reserved for registered residents.</p>";
                    echo "<p>Please <a href='index.php?page=login'>Login</a> or <a href='index.php?page=register'>Register</a> to continue.</p>";
                } else {
                    include 'feed.php';
                }
                break;
        }
        ?>
    </div>

    <footer style="text-align: center; margin-top: 40px; padding: 20px; color: #888; font-size: 0.85rem;">
        <hr style="border: 0; border-top: 1px solid #eee; margin-bottom: 20px;">
        <p>&copy; <?php echo date('Y'); ?> Hello Neighbor - <em>Unofficial Learning Platform App</em></p>
        <a href="mailto:filip@filip-peev.com" style="color: #007bff; text-decoration: none; font-weight: bold;">Feedback</a>
    </footer>

</body>

</html>