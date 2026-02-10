<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $db = Database::getConnection();
    $userId = $_SESSION['user_id'];
    $pollId = $_POST['poll_id'];
    $optionId = $_POST['option_id'];

    try {
        // Use an INSERT statement. The UNIQUE constraint in the DB 
        // will prevent the same user from voting twice on the same poll.
        $stmt = $db->prepare("INSERT INTO poll_votes (poll_id, option_id, user_id) VALUES (?, ?, ?)");
        $stmt->execute([$pollId, $optionId, $userId]);
    } catch (PDOException $e) {
        // If they already voted, the UNIQUE constraint triggers an error
        // You can handle this silently or set a session message
    }

    header("Location: index.php?page=polls");
    exit;
}