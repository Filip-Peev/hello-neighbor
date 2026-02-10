<?php
session_start();

$supported_langs = ['en', 'bg'];

$lang_code = $_GET['lang'] ?? $_SESSION['lang'] ?? 'en';

if (!in_array($lang_code, $supported_langs)) {
    $lang_code = 'en';
}

$_SESSION['lang'] = $lang_code;
$lang = include "../config/languages/{$lang_code}.php";

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

$page = $_GET['page'] ?? 'feed';
$tab = $_GET['tab'] ?? 'public';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Hello Neighbor</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../config/styles.css">
    <link rel="icon" type="image/png" href="../config/favicon.webp">
</head>

<body>

    <nav>
        <div class="nav-container">
            <div class="nav-left">
                <a href="index.php?page=feed&tab=public" style="display: flex; align-items: center; gap: 10px; text-decoration: none;">
                    <img src="../config/logo.webp" alt="Hello Neighbor Logo" style="height: 40px; width: auto; border-radius: 4px;">
                    <span style="font-size: 1.2rem; letter-spacing: 0.5px;">Hello Neighbor</span>
                </a>

                <a href="index.php?page=feed&tab=public" style="<?php echo ($page === 'feed' && $tab === 'public') ? 'text-decoration: underline;' : ''; ?>"><?php echo $lang['nav_public']; ?></a>

                <a href="index.php?page=polls">Polls</a>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="index.php?page=feed&tab=private" style="<?php echo ($page === 'feed' && $tab === 'private') ? 'text-decoration: underline;' : ''; ?>"><?php echo $lang['nav_private']; ?></a>
                    <a href="index.php?page=feed&tab=other" style="<?php echo ($page === 'feed' && $tab === 'other') ? 'text-decoration: underline;' : ''; ?>">Other</a>
                    <a href="index.php?page=documents" style="<?php echo ($page === 'documents') ? 'text-decoration: underline;' : ''; ?>">Documents</a>
                <?php endif; ?>
            </div>

            <div class="lang-switcher" style="margin-left: 20px; font-weight: bold;">
                <a href="?lang=en" style="color: <?php echo $lang_code === 'en' ? 'var(--primary)' : '#aaa'; ?>;">EN</a> |
                <a href="?lang=bg" style="color: <?php echo $lang_code === 'bg' ? 'var(--primary)' : '#aaa'; ?>;">BG</a>
            </div>

            <div class="nav-right">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="index.php?page=profile" style="<?php echo $page === 'profile' ? 'text-decoration: underline;' : ''; ?>"><?php echo $lang['nav_profile']; ?></a>

                    <span style="color: #aaa;">| Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
                    <a href="logout.php" style="color: #ff6666;"><?php echo $lang['nav_logout']; ?></a>
                <?php else: ?>
                    <a href="index.php?page=register" style="<?php echo $page === 'register' ? 'text-decoration: underline;' : ''; ?>">Register</a>
                    <a href="index.php?page=login" style="<?php echo $page === 'login' ? 'text-decoration: underline;' : ''; ?>">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php
        switch ($page) {
            case 'polls':
                include 'polls.php';
                break;
            case 'register':
                include 'register.php';
                break;
            case 'documents':
                include 'documents.php';
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
        <p>&copy; <?php echo date('Y'); ?> Hello Neighbor - <em>Unofficial Learning Web App</em></p>
        <a href="mailto:filip@filip-peev.com" style="color: #007bff; text-decoration: none; font-weight: bold;">Feedback</a>
    </footer>

</body>

</html>