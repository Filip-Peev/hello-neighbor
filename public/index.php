<?php
session_start();

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

// Set 'feed' as the default page instead of 'home'
$page = $_GET['page'] ?? 'feed';
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
            <a href="index.php?page=feed">Notice Board</a>
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
                include 'feed.php';
                break;
        }
        ?>
    </div>
</body>
</html>