<?php
ob_start();
session_start();

// --- Configuration & Language ---
$supported_langs = ['en', 'bg'];
$lang_code = $_GET['lang'] ?? $_SESSION['lang'] ?? 'en';
if (!in_array($lang_code, $supported_langs)) {
    $lang_code = 'en';
}
$_SESSION['lang'] = $lang_code;
$lang = include "../languages/{$lang_code}.php";

// --- Environment & Database ---
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

// --- User Context & Messaging ---
$isLoggedIn = isset($_SESSION['user_id']);
$unreadCount = 0;
if ($isLoggedIn) {
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

// --- Routing Configuration ---
$page = $_GET['page'] ?? 'feed';
$tab = $_GET['tab'] ?? 'public';

/**
 * Route Structure:
 * 'key' => ['auth' => bool, 'label' => string/null (null hides it from nav)]
 */
$routes = [
    'feed'      => ['auth' => false, 'label' => $lang['nav_public']],
    'polls'     => ['auth' => false, 'label' => $lang['nav_polls']],
    'directory' => ['auth' => true,  'label' => 'Directory'],
    'documents' => ['auth' => true,  'label' => $lang['nav_documents']],
    'events'    => ['auth' => true,  'label' => 'Events'],
    'messages'  => ['auth' => true,  'label' => 'Messages'],
    'profile'   => ['auth' => true,  'label' => $_SESSION['username'] ?? 'Profile'],
    'login'     => ['auth' => false, 'label' => $lang['nav_login']],
    'register'  => ['auth' => false, 'label' => $lang['nav_register']],
];

// Content Metadata for Access Denied screens
$pageMeta = [
    'directory' => ['title' => 'Directory', 'reason' => 'the neighbor list and skills search'],
    'documents' => ['title' => 'Documents', 'reason' => 'building files and records'],
    'profile'   => ['title' => 'Profile',   'reason' => 'your personal settings'],
    'messages'  => ['title' => 'Messages',  'reason' => 'your conversations'],
    'private'   => ['title' => 'Private',   'reason' => 'resident-only discussions'],
    'other'     => ['title' => 'Other',     'reason' => 'resident-only discussions'],
];

function showAccessDenied($title, $reason)
{
    echo "<div style='text-align: center; padding: 40px 20px;'>
            <h2>🔒 Member Access Only</h2>
            <p>Access to <strong>$title</strong> ($reason) is reserved for registered residents.</p>
            <p>Please <a href='index.php?page=login' style='color: var(--primary); font-weight: bold;'>Login</a> or 
               <a href='index.php?page=register' style='color: var(--primary); font-weight: bold;'>Register</a> to continue.</p>
          </div>";
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

                <?php foreach (['feed', 'polls', 'directory', 'documents'] as $key): ?>
                    <?php if (!$routes[$key]['auth'] || $isLoggedIn): ?>
                        <a href="index.php?page=<?= $key ?><?= ($key === 'feed') ? '&tab=public' : '' ?>"
                            style="<?= ($page === $key) ? 'text-decoration: underline;' : '' ?>">
                            <?= $routes[$key]['label'] ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>

                <?php if ($isLoggedIn): ?>
                    <a href="index.php?page=feed&tab=private" style="<?= ($page === 'feed' && $tab === 'private') ? 'text-decoration: underline;' : '' ?>"><?= $lang['nav_private'] ?></a>
                    <a href="index.php?page=feed&tab=other" style="<?= ($page === 'feed' && $tab === 'other') ? 'text-decoration: underline;' : '' ?>"><?= $lang['nav_other'] ?></a>
                <?php endif; ?>
            </div>

            <div class="nav-right">
                <?php if ($isLoggedIn): ?>
                    <a href="index.php?page=profile" style="<?= $page === 'profile' ? 'text-decoration: underline;' : '' ?>">
                        <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
                    </a>
                    <a href="index.php?page=events" class="<?= ($page === 'events') ? 'active' : '' ?>">Events</a>
                    <a href="index.php?page=messages" class="<?= ($page === 'messages') ? 'active' : '' ?>">
                        Messages<?php if ($unreadCount > 0): ?>
                        <span style="background: var(--danger); color: white; padding: 2px 6px; border-radius: 50%; font-size: 0.7rem; margin-left: 5px;"><?= $unreadCount ?></span>
                    <?php endif; ?>
                    </a>
                    <a href="logout.php" style="color: #ff6666;"><?= $lang['nav_logout'] ?></a>
                <?php else: ?>
                    <a href="index.php?page=login" style="<?= $page === 'login' ? 'text-decoration: underline;' : '' ?>"><?= $lang['nav_login'] ?></a>
                    <a href="index.php?page=register" style="<?= $page === 'register' ? 'text-decoration: underline;' : '' ?>"><?= $lang['nav_register'] ?></a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php
        // --- Router Logic ---
        if (!array_key_exists($page, $routes)) {
            $page = 'feed';
        }

        $requiresAuth = $routes[$page]['auth'];
        $isProtectedTab = ($page === 'feed' && in_array($tab, ['private', 'other']));

        if (($requiresAuth || $isProtectedTab) && !$isLoggedIn) {
            $lookup = $isProtectedTab ? $tab : $page;
            $meta = $pageMeta[$lookup] ?? ['title' => 'Page', 'reason' => 'registered members only'];
            showAccessDenied($meta['title'], $meta['reason']);
        } else {
            // Securely include the file
            $filePath = __DIR__ . "/{$page}.php";
            if (file_exists($filePath)) {
                include $filePath;
            } else {
                echo "<h2>Page not found.</h2>";
            }
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
                charCount.style.color = length >= 450 ? '#dc3545' : '#888';
            });
        }
    </script>

    <footer style="text-align: center; margin-top: 40px; padding: 20px; color: #888; font-size: 0.85rem;">
        <hr style="border: 0; border-top: 1px solid #eee; margin-bottom: 20px;">
        <p>&copy; <?= date('Y') ?> Hello Neighbor - <em>Unofficial Learning Web App</em></p>
        <a href="mailto:filip@filip-peev.com" style="color: #007bff; text-decoration: none; font-weight: bold;">Feedback</a>

        <?php if (isset($fully_implemented_yet) && $fully_implemented_yet): ?>
            <div class="lang-switcher" style="display: inline-block; font-weight: bold; margin-left: 15px;">
                <a href="?lang=en" style="color: <?= $lang_code === 'en' ? 'var(--primary)' : '#aaa' ?>;">EN</a> |
                <a href="?lang=bg" style="color: <?= $lang_code === 'bg' ? 'var(--primary)' : '#aaa' ?>;">BG</a>
            </div>
        <?php endif; ?>
    </footer>

</body>

</html>
<?php
ob_end_flush();
?>