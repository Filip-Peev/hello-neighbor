<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Security Check: Only admins can create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'admin') {
    $db = Database::getConnection();
    
    $question = trim($_POST['question'] ?? '');
    $options = $_POST['options'] ?? [];
    $category = $_POST['category'] ?? 'public'; // NEW: capture the visibility choice

    if (!empty($question) && count($options) >= 2) {
        try {
            $db->beginTransaction();

            // Insert question with category
            $stmt = $db->prepare("INSERT INTO polls (question, category) VALUES (?, ?)");
            $stmt->execute([$question, $category]);
            $pollId = $db->lastInsertId();

            // Insert options
            $optStmt = $db->prepare("INSERT INTO poll_options (poll_id, option_text) VALUES (?, ?)");
            foreach ($options as $optText) {
                $optText = trim($optText);
                if (!empty($optText)) {
                    $optStmt->execute([$pollId, $optText]);
                }
            }

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
        }
    }
    
    header("Location: index.php?page=polls");
    exit;
} else {
    die("Unauthorized access.");
}