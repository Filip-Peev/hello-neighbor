<?php

// 1. Identify identity but allow guests to view the 'public' tab
$userId = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['role'] ?? 'guest';

// Capture the current tab (defaults to public)
$currentTab = $_GET['tab'] ?? 'public';

// Capture the filter date or default to today
$selectedDate = $_GET['date'] ?? date('Y-m-d');

// Calculate previous and next days
$dateObj = new DateTime($selectedDate);
$prevDay = (clone $dateObj)->modify('-1 day')->format('Y-m-d');
$nextDay = (clone $dateObj)->modify('+1 day')->format('Y-m-d');

// Pagination logic
$pageNumber = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($pageNumber < 1) $pageNumber = 1;

// --- LOGIC SECTION: Handles actions then redirects ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Permission check: Only registered users can interact
    if (!$userId) {
        die("Unauthorized: You must be logged in to post or edit.");
    }

    $returnPage = isset($_POST['return_page']) ? (int)$_POST['return_page'] : 1;
    $dateQuery = $selectedDate ? "&date=" . urlencode($selectedDate) : "";

    // A. Handle Delete Request
    if (isset($_POST['delete_post_id'])) {
        $postId = $_POST['delete_post_id'];
        if ($userRole === 'admin') {
            $stmt = $db->prepare("DELETE FROM posts WHERE id = ?");
            $stmt->execute([$postId]);
        } else {
            $stmt = $db->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
            $stmt->execute([$postId, $userId]);
        }
        header("Location: index.php?page=feed&tab=$currentTab&p=$returnPage$dateQuery&msg=deleted");
        exit;
    }

    // B. Handle Edit/Update Request
    if (isset($_POST['update_post_id'])) {
        $postId = $_POST['update_post_id'];
        $newContent = trim($_POST['edit_content']);
        if (!empty($newContent)) {
            if ($userRole === 'admin') {
                $stmt = $db->prepare("UPDATE posts SET content = ? WHERE id = ?");
                $stmt->execute([$newContent, $postId]);
            } else {
                $stmt = $db->prepare("UPDATE posts SET content = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$newContent, $postId, $userId]);
            }
        }
        header("Location: index.php?page=feed&tab=$currentTab&p=$returnPage$dateQuery&msg=updated");
        exit;
    }

    // C. Handle New Post Submission
    if (isset($_POST['post_content'])) {
        // Double-check that we are actually on "Today" before saving
        if ($selectedDate !== date('Y-m-d')) {
            die("Error: You can only post notices to the current date.");
        }

        $content = trim($_POST['post_content']);
        $allowed = ['public', 'private', 'other'];
        $categoryToSave = in_array($currentTab, $allowed) ? $currentTab : 'public';

        if (!empty($content)) {
            $stmt = $db->prepare("INSERT INTO posts (user_id, content, category) VALUES (?, ?, ?)");
            $stmt->execute([$userId, $content, $categoryToSave]);
        }

        header("Location: index.php?page=feed&tab=$categoryToSave&p=1&date=" . date('Y-m-d') . "&msg=posted");
        exit;
    }
}

// --- DATA FETCHING (Filtered by Categories and Date) ---

$postsPerPage = 10;
$offset = ($pageNumber - 1) * $postsPerPage;

// Build the WHERE clause dynamically
$whereClause = "WHERE posts.category = ?";
$params = [$currentTab];

if ($selectedDate) {
    $whereClause .= " AND DATE(posts.created_at) = ?";
    $params[] = $selectedDate;
}

// Count posts for this specific query
$stmtCount = $db->prepare("SELECT COUNT(*) FROM posts $whereClause");
$stmtCount->execute($params);
$totalPosts = $stmtCount->fetchColumn();
$totalPages = ceil($totalPosts / $postsPerPage);

// Fetch posts
$stmt = $db->prepare("SELECT posts.*, users.username, users.role as author_role 
                      FROM posts 
                      JOIN users ON posts.user_id = users.id 
                      $whereClause 
                      ORDER BY posts.created_at DESC 
                      LIMIT $postsPerPage OFFSET $offset");
$stmt->execute($params);
$posts = $stmt->fetchAll();

$titles = [
    'public'  => 'Public Info',
    'private' => 'Private Board',
    'other'   => 'Other Information'
];
$displayTitle = $titles[$currentTab] ?? 'Notice Board';
?>

<h3><?php echo htmlspecialchars($displayTitle); ?></h3>

<?php if (isset($_GET['msg'])): ?>
    <div style="padding: 10px; margin-bottom: 20px; border-radius: 4px; background: #e7f3ff; color: #0c5460; border: 1px solid #d1ecf1;">
        <?php
        if ($_GET['msg'] == 'posted') echo "✅ Notice posted to " . htmlspecialchars($displayTitle);
        if ($_GET['msg'] == 'updated') echo "✏️ Notice updated!";
        if ($_GET['msg'] == 'deleted') echo "🗑️ Notice removed.";
        ?>
    </div>
<?php endif; ?>

<div class="container" style="margin-bottom: 30px; background: #fff;">
    <?php if ($userId): ?>
        <?php if ($selectedDate === date('Y-m-d')): ?>
            <form method="POST" action="index.php?page=feed&tab=<?php echo htmlspecialchars($currentTab); ?>&date=<?php echo urlencode($selectedDate); ?>">
                <label><strong>Post to <?php echo htmlspecialchars($displayTitle); ?>:</strong></label><br>
                <textarea name="post_content" placeholder="Share something with the community..." required
                    style="width: 100%; height: 80px; padding: 10px; margin-top: 10px; border-radius: 4px; border: 1px solid #ddd; font-family: sans-serif; resize: vertical;"></textarea><br>
                <button type="submit" style="margin-top: 10px; cursor: pointer;"><?php echo $lang['btn_post']; ?></button>
            </form>
        <?php else: ?>
            <div style="text-align: center; padding: 20px; border: 1px dashed #ccc; border-radius: 8px; color: #666;">
                <p>💡 You are viewing an archived date. <br>
                    <a href="index.php?page=feed&tab=<?php echo $currentTab; ?>&date=<?php echo date('Y-m-d'); ?>" style="color: var(--primary); font-weight: bold; text-decoration: none;">Go to Today</a> to post a new notice.
                </p>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <p style="text-align: center; color: #666;">
            <strong>Want to post here?</strong> <br>
            Please <a href="index.php?page=login">Login</a> or <a href="index.php?page=register">Register</a>.
        </p>
    <?php endif; ?>
</div>

<div class="filter-container" style="margin-bottom: 20px; padding: 15px; background: #f4f4f4; border-radius: 8px; display: flex; align-items: center; justify-content: center; gap: 10px;">

    <a href="index.php?page=feed&tab=<?php echo $currentTab; ?>&date=<?php echo $prevDay; ?>"
        style="text-decoration: none; background: #fff; padding: 8px 12px; border-radius: 8px; border: 1px solid #ccc; color: var(--text-main); font-weight: bold;">
        &larr;
    </a>

    <form id="dateFilterForm" method="GET" action="index.php" style="display: flex; align-items: center; gap: 10px; margin: 0;">
        <input type="hidden" name="page" value="feed">
        <input type="hidden" name="tab" value="<?php echo htmlspecialchars($currentTab); ?>">

        <input type="date"
            id="filter_date"
            name="date"
            class="date-filter-input"
            value="<?php echo htmlspecialchars($selectedDate); ?>"
            onclick="this.showPicker();"
            onchange="document.getElementById('dateFilterForm').submit();"
            onkeydown="return false;"
            style="padding: 8px 12px; border-radius: 8px; border: 1px solid #ccc; width: auto; cursor: pointer; background-color: #fff; text-align: center;">
    </form>

    <a href="index.php?page=feed&tab=<?php echo $currentTab; ?>&date=<?php echo $nextDay; ?>"
        style="text-decoration: none; background: #fff; padding: 8px 12px; border-radius: 8px; border: 1px solid #ccc; color: var(--text-main); font-weight: bold;">
        &rarr;
    </a>

    <?php if ($selectedDate !== date('Y-m-d')): ?>
        <a href="index.php?page=feed&tab=<?php echo $currentTab; ?>&date=<?php echo date('Y-m-d'); ?>"
            style="font-size: 0.8rem; color: var(--danger); text-decoration: none; font-weight: bold; margin-left: 10px; padding: 5px 10px; border: 1px solid var(--danger); border-radius: 6px;">
            Today
        </a>
    <?php endif; ?>
</div>

<div class="feed-container">
    <?php if (empty($posts)): ?>
        <p>No notices found in the <?php echo htmlspecialchars($displayTitle); ?> section.</p>
    <?php else: ?>
        <?php
        $currentDateHeader = '';
        foreach ($posts as $post):
            $postDate = date('F j, Y', strtotime($post['created_at']));

            if ($postDate !== $currentDateHeader):
                $currentDateHeader = $postDate;
        ?>
                <div class="date-divider">
                    <h4>📅 <?php echo $postDate; ?></h4>
                </div>
            <?php endif; ?>

            <div id="post-<?php echo $post['id']; ?>" style="background: white; padding: 15px; margin-bottom: 15px; border-radius: 8px; border: 1px solid #ddd; border-left: 5px solid <?php echo ($post['author_role'] === 'admin') ? '#007bff' : '#28a745'; ?>; position: relative;">

                <div style="margin-bottom: 8px;">
                    <strong style="color: #333;"><?php echo htmlspecialchars($post['username']); ?></strong>
                    <small style="color: #888; margin-left: 10px;"><?php echo date('g:i a', strtotime($post['created_at'])); ?></small>
                </div>

                <div id="view-mode-<?php echo $post['id']; ?>">
                    <p style="color: #444; line-height: 1.4; margin: 10px 0;"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>

                    <?php if ($userId && ($post['user_id'] == $userId || $userRole === 'admin')): ?>
                        <div style="display: flex; gap: 8px; margin-top: 10px;">
                            <button onclick="toggleEdit(<?php echo $post['id']; ?>)" style="background: #ffc107; color: #000; padding: 5px 10px; font-size: 0.75rem; border-radius: 4px; border: none; cursor: pointer;">Edit</button>

                            <form method="POST" action="index.php?page=feed&tab=<?php echo $currentTab; ?>&p=<?php echo $pageNumber; ?><?php echo $selectedDate ? '&date=' . urlencode($selectedDate) : ''; ?>" onsubmit="return confirm('Delete this post?');">
                                <input type="hidden" name="delete_post_id" value="<?php echo $post['id']; ?>">
                                <input type="hidden" name="return_page" value="<?php echo $pageNumber; ?>">
                                <button type="submit" style="background: #dc3545; color: white; padding: 5px 10px; font-size: 0.75rem; border-radius: 4px; border: none; cursor: pointer;">Delete</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>

                <?php
                // Fetch comments for this specific post
                $stmtComm = $db->prepare("SELECT comments.*, users.username FROM comments 
     JOIN users ON comments.user_id = users.id 
     WHERE post_id = ? ORDER BY created_at ASC");
                $stmtComm->execute([$post['id']]);
                $comments = $stmtComm->fetchAll();

                // Only show the container if there are comments OR the user can post a new one
                if (!empty($comments) || $userId):
                ?>
                    <div class="comments-container" style="margin-top: 15px; border-top: 1px solid #eee; padding-top: 10px;">
                        <?php foreach ($comments as $comment): ?>
                            <div style="font-size: 0.9rem; margin-bottom: 8px; position: relative; padding-right: 60px;">
                                <strong><?php echo htmlspecialchars($comment['username']); ?>:</strong>
                                <?php echo htmlspecialchars($comment['content']); ?>
                                <small style="color: #999; display: block;"><?php echo date('M j, g:i a', strtotime($comment['created_at'])); ?></small>

                                <?php if ($userId && ($comment['user_id'] == $userId || $userRole === 'admin')): ?>
                                    <form method="POST" action="delete_comment.php" onsubmit="return confirm('Delete this comment?');" style="position: absolute; right: 0; top: 0;">
                                        <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                        <input type="hidden" name="return_url" value="<?php echo $_SERVER['REQUEST_URI']; ?>">
                                        <button type="submit" class="btn-remove-comment"><?php echo $lang['btn_remove']; ?></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                        <?php if ($userId): ?>
                            <form method="POST" action="add_comment.php" style="margin-top: 10px;">
                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                <input type="hidden" name="return_url" value="<?php echo $_SERVER['REQUEST_URI']; ?>">
                                <input type="text" name="comment_content" placeholder="Add a comment..." required
                                    style="width: 80%; padding: 5px; font-size: 0.8rem; border: 1px solid #ddd; border-radius: 4px;">
                                <button type="submit" style="padding: 4px 10px; font-size: 0.8rem;"><?php echo $lang['btn_reply']; ?></button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div id="edit-mode-<?php echo $post['id']; ?>" style="display: none;">
                    <form method="POST" action="index.php?page=feed&tab=<?php echo $currentTab; ?>&p=<?php echo $pageNumber; ?><?php echo $selectedDate ? '&date=' . urlencode($selectedDate) : ''; ?>">
                        <input type="hidden" name="update_post_id" value="<?php echo $post['id']; ?>">
                        <input type="hidden" name="return_page" value="<?php echo $pageNumber; ?>">
                        <textarea name="edit_content" style="width: 100%; height: 70px; padding: 8px; margin-bottom: 8px; border: 1px solid #ccc; font-family: sans-serif;"><?php echo htmlspecialchars($post['content']); ?></textarea><br>
                        <button type="submit" style="background: #28a745; color: white; padding: 5px 12px; font-size: 0.75rem; border: none; border-radius: 4px; cursor: pointer;">Save</button>
                        <button type="button" onclick="toggleEdit(<?php echo $post['id']; ?>)" style="background: #6c757d; color: white; padding: 5px 12px; font-size: 0.75rem; border: none; border-radius: 4px; cursor: pointer;">Cancel</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if ($totalPages > 1): ?>
    <?php $dateQuery = $selectedDate ? "&date=" . urlencode($selectedDate) : ""; ?>
    <div class="pagination" style="margin-top: 30px; display: flex; justify-content: center; gap: 15px; align-items: center;">
        <?php if ($pageNumber > 1): ?>
            <a href="index.php?page=feed&tab=<?php echo $currentTab; ?>&p=<?php echo ($pageNumber - 1) . $dateQuery; ?>" style="text-decoration: none; color: #28a745; font-weight: bold;">&laquo; Newer</a>
        <?php endif; ?>

        <span style="color: #666;">Page <?php echo $pageNumber; ?> of <?php echo $totalPages; ?></span>

        <?php if ($pageNumber < $totalPages): ?>
            <a href="index.php?page=feed&tab=<?php echo $currentTab; ?>&p=<?php echo ($pageNumber + 1) . $dateQuery; ?>" style="text-decoration: none; color: #28a745; font-weight: bold;">Older &raquo;</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<script>
    function toggleEdit(postId) {
        const viewDiv = document.getElementById('view-mode-' + postId);
        const editDiv = document.getElementById('edit-mode-' + postId);
        if (viewDiv.style.display === 'none') {
            viewDiv.style.display = 'block';
            editDiv.style.display = 'none';
        } else {
            viewDiv.style.display = 'none';
            editDiv.style.display = 'block';
        }
    }
</script>