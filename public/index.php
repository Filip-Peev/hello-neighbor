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

$page = $_GET['page'] ?? 'home';
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
            <a href="index.php?page=home">Home</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="index.php?page=dashboard">Dashboard</a>
                <a href="index.php?page=feed">Notice Board</a>
                <a href="index.php?page=profile">Profile</a>
            <?php endif; ?>
        </div>

        <div class="nav-right">
            <?php if (isset($_SESSION['user_id'])): ?>
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
                include 'feed.php';
                break;
            case 'dashboard':
                if (!isset($_SESSION['user_id'])) {
                    echo "<h2>🚫 Access Denied</h2><p>You must <a href='index.php?page=login'>Login</a> to see this page.</p>";
                } else {
                    echo "<h2>Member Dashboard</h2>";
                    echo "<p>Secret Info: The server time is " . date('H:i:s') . "</p>";
                    echo "<p>This content is only visible to logged-in users!</p>";
                }
                break;
            default:
                echo "<h2>Welcome to the Home Page</h2>";
                if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') {
                    echo "<p style='color: orange;'>Account deleted successfully.</p>";
                }
                echo "<p>The database is connected and ready for action.</p>";
                break;
        }
        ?>
    </div>

</body>

</html>