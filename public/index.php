<?php
ob_start();
session_start();

$supported_langs = ['en', 'bg'];

$lang_code = $_GET['lang'] ?? $_SESSION['lang'] ?? 'en';

if (!in_array($lang_code, $supported_langs)) {
    $lang_code = 'en';
}

$_SESSION['lang'] = $lang_code;
$lang = include "../languages/{$lang_code}.php";

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

$unreadCount = 0;
if (isset($_SESSION['user_id'])) {
    $unreadStmt = $db->prepare("
        SELECT COUNT(*) FROM messages m
        JOIN conversations c ON m.conversation_id = c.id
        WHERE m.sender_id != ? 
        AND m.is_read = 0 
        AND (c.user_one = ? OR c.user_two = ?)
    ");
    $unreadStmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
    $unreadCount = $unreadStmt->fetchColumn();
}

$page = $_GET['page'] ?? 'feed';
$tab = $_GET['tab'] ?? 'public';

/**
 * Helper function to display a consistent "Access Denied" message
 */
function showAccessDenied($title, $reason)
{
    echo "<div style='text-align: center; padding: 40px 20px;'>";
    echo "<h2>🔒 Member Access Only</h2>";
    echo "<p>Access to <strong>$title</strong> ($reason) is reserved for registered residents.</p>";
    echo "<p>Please <a href='index.php?page=login' style='color: var(--primary); font-weight: bold;'>Login</a> or <a href='index.php?page=register' style='color: var(--primary); font-weight: bold;'>Register</a> to continue.</p>";
    echo "</div>";
}
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
                <a href="index.php?page=feed&tab=public" class="logo-link">
                    <img src="../config/logo.webp" alt="Hello Neighbor Logo" class="glow-logo">
                    <span class="logo-text">Hello Neighbor</span>
                </a>

                <a href="index.php?page=feed&tab=public" style="<?php echo ($page === 'feed' && $tab === 'public') ? 'text-decoration: underline;' : ''; ?>">
                    <?php echo $lang['nav_public']; ?>
                </a>

                <a href="index.php?page=polls" style="<?php echo ($page === 'polls') ? 'text-decoration: underline;' : ''; ?>">
                    <?php echo $lang['nav_polls']; ?>
                </a>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="index.php?page=directory" style="<?php echo ($page === 'directory') ? 'text-decoration: underline;' : ''; ?>">Directory</a>

                    <a href="index.php?page=feed&tab=private" style="<?php echo ($page === 'feed' && $tab === 'private') ? 'text-decoration: underline;' : ''; ?>"><?php echo $lang['nav_private']; ?></a>

                    <a href="index.php?page=feed&tab=other" style="<?php echo ($page === 'feed' && $tab === 'other') ? 'text-decoration: underline;' : ''; ?>"><?php echo $lang['nav_other']; ?></a>

                    <a href="index.php?page=documents" style="<?php echo ($page === 'documents') ? 'text-decoration: underline;' : ''; ?>">
                        <?php echo $lang['nav_documents']; ?></a>
                <?php endif; ?>
            </div>

            <div class="nav-right">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="index.php?page=profile" style="<?php echo $page === 'profile' ? 'text-decoration: underline;' : ''; ?>">
                        <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
                    </a>

                    <a href="index.php?page=events" class="<?= ($page === 'events') ? 'active' : '' ?>">Events</a>

                    <a href="index.php?page=messages" class="<?= ($page === 'messages') ? 'active' : '' ?>">Messages<?php if ($unreadCount > 0): ?>
                        <span style="background: var(--danger); color: white; padding: 2px 6px; border-radius: 50%; font-size: 0.7rem; margin-left: 5px;">
                            <?= $unreadCount ?>
                        </span>
                    <?php endif; ?>
                    </a>

                    <a href="logout.php" style="color: #ff6666;"><?php echo $lang['nav_logout']; ?></a>

                <?php else: ?>
                    <a href="index.php?page=register" style="<?php echo $page === 'register' ? 'text-decoration: underline;' : ''; ?>">
                        <?php echo $lang['nav_register']; ?>
                    </a>
                    <a href="index.php?page=login" style="<?php echo $page === 'login' ? 'text-decoration: underline;' : ''; ?>">
                        <?php echo $lang['nav_login']; ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php
        switch ($page) {
            case 'directory':
                if (!isset($_SESSION['user_id'])) {
                    showAccessDenied("Directory", "the neighbor list and skills search");
                } else {
                    include 'directory.php';
                }
                break;

            case 'events':
                include 'events.php';
                break;

            case 'polls':
                include 'polls.php';
                break;

            case 'register':
                include 'register.php';
                break;

            case 'documents':
                if (!isset($_SESSION['user_id'])) {
                    showAccessDenied("Documents", "building files and records");
                } else {
                    include 'documents.php';
                }
                break;

            case 'login':
                include 'login.php';
                break;

            case 'profile':
                if (!isset($_SESSION['user_id'])) {
                    showAccessDenied("Profile", "your personal settings");
                } else {
                    include 'profile.php';
                }
                break;

            case 'messages':
                if (!isset($_SESSION['user_id'])) {
                    showAccessDenied("Messages", "your conversations");
                } else {
                    include 'messages.php';
                }
                break;

            case 'feed':
            default:
                $protectedTabs = ['private', 'other'];
                if (in_array($tab, $protectedTabs) && !isset($_SESSION['user_id'])) {
                    showAccessDenied(ucfirst($tab), "resident-only discussions");
                } else {
                    include 'feed.php';
                }
                break;
        }
        ?>
    </div>

    <script>
        const postArea = document.getElementById('postContent');
        const charCount = document.getElementById('charCount');

        if (postArea) {
            postArea.addEventListener('input', function() {
                const length = this.value.length;
                charCount.textContent = length;

                if (length >= 450) {
                    charCount.style.color = '#dc3545';
                } else {
                    charCount.style.color = '#888';
                }
            });
        }
    </script>

    <footer style="text-align: center; margin-top: 40px; padding: 20px; color: #888; font-size: 0.85rem;">
        <hr style="border: 0; border-top: 1px solid #eee; margin-bottom: 20px;">
        <p>&copy; <?php echo date('Y'); ?> Hello Neighbor - <em>Unofficial Learning Web App</em></p>
        <div style="display: inline-block; margin-right: 20px;">
            <a href="mailto:filip@filip-peev.com" style="color: #007bff; text-decoration: none; font-weight: bold;">Feedback</a>
        </div>

        <?php
        // Flag to control the visibility of the language switcher
        $fully_implemented_yet = false;
        if ($fully_implemented_yet): ?>
            <div class="lang-switcher" style="display: inline-block; font-weight: bold;">
                <a href="?lang=en" style="color: <?php echo $lang_code === 'en' ? 'var(--primary)' : '#aaa'; ?>;">EN</a> |
                <a href="?lang=bg" style="color: <?php echo $lang_code === 'bg' ? 'var(--primary)' : '#aaa'; ?>;">BG</a>
            </div>
        <?php endif; ?>

    </footer>

</body>

</html>
<?php
ob_end_flush();
?>