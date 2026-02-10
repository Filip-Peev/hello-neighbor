<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $db = Database::getConnection();
    $userId = $_SESSION['user_id'];
    $userRole = $_SESSION['role'] ?? 'guest';
    $commentId = $_POST['comment_id'];
    $returnUrl = $_POST['return_url'] ?? 'index.php';

    if ($userRole === 'admin') {
        // Admins can delete any comment
        $stmt = $db->prepare("DELETE FROM comments WHERE id = ?");
        $stmt->execute([$commentId]);
    } else {
        // Users can only delete their own comments
        $stmt = $db->prepare("DELETE FROM comments WHERE id = ? AND user_id = ?");
        $stmt->execute([$commentId, $userId]);
    }

    header("Location: " . $returnUrl);
    exit;
}