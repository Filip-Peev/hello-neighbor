<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $db = Database::getConnection();
    $postId = $_POST['post_id'];
    $userId = $_SESSION['user_id'];
    $content = trim($_POST['comment_content']);
    $returnUrl = $_POST['return_url'] ?? 'index.php';

    if (!empty($content)) {
        $stmt = $db->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$postId, $userId, $content]);
    }
    header("Location: " . $returnUrl);
    exit;
}