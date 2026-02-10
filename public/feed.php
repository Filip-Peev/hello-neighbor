<?php
// public/feed.php

// Define user identity but don't block access if null
$userId = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['role'] ?? 'guest';

$pageNumber = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($pageNumber < 1) $pageNumber = 1;

// --- LOGIC SECTION: Permission check added for all actions ---

// 2. Handle Delete Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post_id'])) {
    if (!$userId) die("Unauthorized"); // Extra safety
    
    $postId = $_POST['delete_post_id'];
    $returnPage = isset($_POST['return_page']) ? (int)$_POST['return_page'] : 1;

    if ($userRole === 'admin') {
        $stmt = $db->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->execute([$postId]);
    } else {
        $stmt = $db->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
        $stmt->execute([$postId, $userId]);
    }

    header("Location: index.php?page=feed&p=$returnPage&msg=deleted");
    exit;
}

// 3. Handle Edit/Update Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_post_id'])) {
    if (!$userId) die("Unauthorized");

    $postId = $_POST['update_post_id'];
    $newContent = trim($_POST['edit_content']);
    $returnPage = isset($_POST['return_page']) ? (int)$_POST['return_page'] : 1;

    if (!empty($newContent)) {
        if ($userRole === 'admin') {
            $stmt = $db->prepare("UPDATE posts SET content = ? WHERE id = ?");
            $stmt->execute([$newContent, $postId]);
        } else {
            $stmt = $db->prepare("UPDATE posts SET content = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$newContent, $postId, $userId]);
        }
    }
    header("Location: index.php?page=feed&p=$returnPage&msg=updated");
    exit;
}

// 4. Handle New Post Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_content'])) {
    if (!$userId) die("Unauthorized");

    $content = trim($_POST['post_content']);
    if (!empty($content)) {
        $stmt = $db->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)");
        $stmt->execute([$userId, $content]);
    }
    header("Location: index.php?page=feed&p=1&msg=posted");
    exit;
}

// --- DATA FETCHING (Publicly Accessible) ---

$postsPerPage = 10;
$offset = ($pageNumber - 1) * $postsPerPage;

$totalPosts = $db->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$totalPages = ceil($totalPosts / $postsPerPage);

$query = "SELECT posts.*, users.username, users.role as author_role 
          FROM posts 
          JOIN users ON posts.user_id = users.id 
          ORDER BY posts.created_at DESC 
          LIMIT $postsPerPage OFFSET $offset";
$posts = $db->query($query)->fetchAll();
?>

<h3>Building Notice Board</h3>

<?php if (isset($_GET['msg'])): ?>
    <div style="padding: 10px; margin-bottom: 20px; border-radius: 4px; background: #e7f3ff; color: #0c5460; border: 1px solid #d1ecf1;">
        <?php
            if ($_GET['msg'] == 'posted') echo "✅ Notice posted successfully!";
            if ($_GET['msg'] == 'updated') echo "✏️ Notice updated!";
            if ($_GET['msg'] == 'deleted') echo "🗑️ Notice removed.";
        ?>
    </div>
<?php endif; ?>

<div class="container" style="margin-bottom: 30px; background: #fff;">
    <?php if ($userId): ?>
        <form method="POST" action="index.php?page=feed">
            <label><strong>Post a new notice:</strong></label><br>
            <textarea name="post_content" placeholder="What's happening in the building?" required 
                      style="width: 100%; height: 80px; padding: 10px; margin-top: 10px; border-radius: 4px; border: 1px solid #ddd; font-family: sans-serif; resize: vertical;"></textarea><br>
            <button type="submit" style="margin-top: 10px;">Post Announcement</button>
        </form>
    <?php else: ?>
        <p style="text-align: center; color: #666;">
            Please <a href="index.php?page=login">Login</a> or <a href="index.php?page=register">Register</a> to post on the board.
        </p>
    <?php endif; ?>
</div>

<div class="feed-container">
    <?php if (empty($posts)): ?>
        <p>No notices found.</p>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
            <div id="post-<?php echo $post['id']; ?>" style="background: white; padding: 15px; margin-bottom: 15px; border-radius: 8px; border: 1px solid #ddd; border-left: 5px solid <?php echo ($post['author_role'] === 'admin') ? '#007bff' : '#28a745'; ?>; position: relative;">
                
                <div style="margin-bottom: 8px;">
                    <strong style="color: #333;"><?php echo htmlspecialchars($post['username']); ?></strong>
                    <?php if ($post['author_role'] === 'admin'): ?>
                        <span style="font-size: 0.7rem; background: #007bff; color: white; padding: 2px 6px; border-radius: 10px; margin-left: 5px;">Admin</span>
                    <?php endif; ?>
                    <small style="color: #888; margin-left: 10px;"><?php echo date('M j, g:i a', strtotime($post['created_at'])); ?></small>
                </div>

                <div id="view-mode-<?php echo $post['id']; ?>">
                    <p style="color: #444; line-height: 1.4; margin: 10px 0;"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                    
                    <?php if ($userId && ($post['user_id'] == $userId || $userRole === 'admin')): ?>
                        <div style="display: flex; gap: 8px; margin-top: 10px;">
                            <button onclick="toggleEdit(<?php echo $post['id']; ?>)" style="background: #ffc107; color: #000; padding: 5px 10px; font-size: 0.75rem; border-radius: 4px; border: none; cursor: pointer;">Edit</button>
                            
                            <form method="POST" action="index.php?page=feed&p=<?php echo $pageNumber; ?>" onsubmit="return confirm('Delete this post?');">
                                <input type="hidden" name="delete_post_id" value="<?php echo $post['id']; ?>">
                                <input type="hidden" name="return_page" value="<?php echo $pageNumber; ?>">
                                <button type="submit" style="background: #dc3545; color: white; padding: 5px 10px; font-size: 0.75rem; border-radius: 4px; border: none; cursor: pointer;">Delete</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>

                <div id="edit-mode-<?php echo $post['id']; ?>" style="display: none;">
                    <form method="POST" action="index.php?page=feed&p=<?php echo $pageNumber; ?>">
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
<div class="pagination" style="margin-top: 30px; display: flex; justify-content: center; gap: 15px; align-items: center;">
    <?php if ($pageNumber > 1): ?>
        <a href="index.php?page=feed&p=<?php echo $pageNumber - 1; ?>" style="text-decoration: none; color: #28a745; font-weight: bold;">&laquo; Newer</a>
    <?php endif; ?>

    <span style="color: #666;">Page <?php echo $pageNumber; ?> of <?php echo $totalPages; ?></span>

    <?php if ($pageNumber < $totalPages): ?>
        <a href="index.php?page=feed&p=<?php echo $pageNumber + 1; ?>" style="text-decoration: none; color: #28a745; font-weight: bold;">Older &raquo;</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<script>
function toggleEdit(postId) {
    const viewDiv = document.getElementById('view-mode-' + postId);
    const editDiv = document.getElementById('edit-mode-' + postId);
    viewDiv.style.display = viewDiv.style.display === 'none' ? 'block' : 'none';
    editDiv.style.display = editDiv.style.display === 'none' ? 'block' : 'none';
}
</script>