<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Security Check: Only admins can delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_SESSION['role'] ?? '') === 'admin') {
    $db = Database::getConnection();
    $pollId = $_POST['poll_id'];

    try {
        // Because of the ON DELETE CASCADE in the database schema, 
        // deleting the poll will automatically delete its options and votes.
        $stmt = $db->prepare("DELETE FROM polls WHERE id = ?");
        $stmt->execute([$pollId]);
    } catch (PDOException $e) {
        // Handle error if necessary
    }

    header("Location: index.php?page=polls");
    exit;
} else {
    die("Unauthorized access.");
}