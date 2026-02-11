<?php
// public/messages.php
if (!isset($_SESSION['user_id'])) {
    showAccessDenied("Messages", "Private Inbox");
    return;
}

$db = Database::getConnection();
$myId = $_SESSION['user_id'];
$withUserId = isset($_GET['with_user']) ? (int)$_GET['with_user'] : null;

// Initialize variables
$activeMessages = [];
$otherUserName = "";

// Handle Sending a Message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_text'], $_POST['recipient_id'])) {
    $text = trim($_POST['message_text']);
    $recipientId = (int)$_POST['recipient_id'];

    if (!empty($text)) {
        $u1 = min($myId, $recipientId);
        $u2 = max($myId, $recipientId);

        $stmt = $db->prepare("INSERT INTO conversations (user_one, user_two) VALUES (?, ?) 
                              ON DUPLICATE KEY UPDATE last_message_at = NOW()");
        $stmt->execute([$u1, $u2]);

        $conv = $db->prepare("SELECT id FROM conversations WHERE user_one = ? AND user_two = ?");
        $conv->execute([$u1, $u2]);
        $conversationId = $conv->fetchColumn();

        $msgStmt = $db->prepare("INSERT INTO messages (conversation_id, sender_id, message_text) VALUES (?, ?, ?)");
        $msgStmt->execute([$conversationId, $myId, $text]);

        header("Location: index.php?page=messages&with_user=" . $recipientId . "#end-of-chat");
        exit;
    }
}

// Fetch all conversations - ORDER BY username for a stable list
$convStmt = $db->prepare("
    SELECT 
        c.last_message_at, 
        u.id as other_id, 
        u.username,
        (SELECT COUNT(*) FROM messages m 
         WHERE m.conversation_id = c.id 
         AND m.sender_id = u.id 
         AND m.is_read = 0) as unread_from_them
    FROM conversations c
    JOIN users u ON (u.id = IF(c.user_one = ?, c.user_two, c.user_one))
    WHERE c.user_one = ? OR c.user_two = ?
    ORDER BY u.username ASC
");
$convStmt->execute([$myId, $myId, $myId]);
$chatList = $convStmt->fetchAll();

if ($withUserId) {
    // Mark as read
    $updateRead = $db->prepare("
        UPDATE messages m
        JOIN conversations c ON m.conversation_id = c.id
        SET m.is_read = 1
        WHERE m.sender_id = ? 
        AND m.is_read = 0
        AND (c.user_one = ? AND c.user_two = ?)
    ");
    $updateRead->execute([$withUserId, min($myId, $withUserId), max($myId, $withUserId)]);

    // Fetch user name
    $userStmt = $db->prepare("SELECT username FROM users WHERE id = ? AND is_deleted = 0");
    $userStmt->execute([$withUserId]);
    $otherUserName = $userStmt->fetchColumn();

    if ($otherUserName) {
        $u1 = min($myId, $withUserId);
        $u2 = max($myId, $withUserId);
        $msgStmt = $db->prepare("
            SELECT m.* FROM messages m
            JOIN conversations c ON m.conversation_id = c.id
            WHERE (c.user_one = ? AND c.user_two = ?)
            ORDER BY m.created_at ASC
        ");
        $msgStmt->execute([$u1, $u2]);
        $activeMessages = $msgStmt->fetchAll();
    }
}
?>

<div style="display: flex; gap: 20px; height: 65vh;">
    <div style="width: 30%; border-right: 1px solid var(--border-color); overflow-y: auto; padding-right: 10px;">
        <h3>Inbox</h3>
        <?php foreach ($chatList as $chat): ?>
            <a href="index.php?page=messages&with_user=<?= $chat['other_id'] ?>"
                class="neighbor-card" style="display: flex; justify-content: space-between; align-items: center; padding: 12px; margin-bottom: 8px; text-decoration: none; border-radius: 8px;
                background: <?= ($withUserId == $chat['other_id']) ? 'var(--primary)' : 'var(--bg-card)' ?>;
                color: <?= ($withUserId == $chat['other_id']) ? 'white' : 'var(--text-main)' ?>;
                box-shadow: var(--shadow);">
                <strong><?= htmlspecialchars($chat['username']) ?></strong>
                <?php if ($chat['unread_from_them'] > 0 && $withUserId != $chat['other_id']): ?>
                    <span style="background: var(--danger); color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.75rem; font-weight: bold;">
                        <?= $chat['unread_from_them'] ?>
                    </span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div style="flex: 1; display: flex; flex-direction: column;">
        <?php if ($otherUserName): ?>
            <h3 style="margin-top: 0;">Chat with <?= htmlspecialchars($otherUserName) ?></h3>
            <div id="chat-box" class="neighbor-card" style="flex: 1; border-radius: 8px; padding: 15px; overflow-y: auto; margin-bottom: 15px; margin-top: 5px;">
                <?php foreach ($activeMessages as $m): ?>
                    <div style="margin-bottom: 10px; text-align: <?= $m['sender_id'] == $myId ? 'right' : 'left' ?>;">
                        <span style="display: inline-block; padding: 10px; border-radius: 12px;
                                     background: <?= $m['sender_id'] == $myId ? 'var(--primary)' : '#eee' ?>;
                                     color: <?= $m['sender_id'] == $myId ? 'white' : 'black' ?>;
                                     max-width: 80%; 
                                     text-align: left;
                                     /* FIX: Prevent box deformation from long continuous text */
                                     overflow-wrap: break-word; 
                                     word-break: break-word;">
                            <?= htmlspecialchars($m['message_text']) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
                <div id="end-of-chat"></div>
            </div>

            <form method="POST" style="display: flex; gap: 10px;">
                <input type="hidden" name="recipient_id" value="<?= $withUserId ?>">
                <input type="text" name="message_text" maxlength="500" placeholder="Write a message..." required autocomplete="off"
                    style="flex: 1; padding: 12px; border-radius: 6px; border: 1px solid var(--border-color);">
                <button type="submit" class="primary-button" style="width: auto; padding: 0 20px;">Send</button>
            </form>
        <?php else: ?>
            <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: var(--text-muted); border: 2px dashed var(--border-color); border-radius: 8px;">
                Select a neighbor to view your conversation.
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    function jumpToBottom() {
        const endMarker = document.getElementById('end-of-chat');
        if (endMarker) {
            endMarker.scrollIntoView({
                behavior: "instant",
                block: "end"
            });
        }
    }
    document.addEventListener('DOMContentLoaded', jumpToBottom);
    window.onload = jumpToBottom;
</script>