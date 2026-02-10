<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Security Check: Only admins can delete
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized access.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $db = Database::getConnection();
    $id = $_POST['id'];

    // 1. Fetch the file path from the database first
    $stmt = $db->prepare("SELECT file_path FROM documents WHERE id = ?");
    $stmt->execute([$id]);
    $doc = $stmt->fetch();

    if ($doc) {
        // 2. Identify the full path on the server
        // Using __DIR__ ensures we are looking in the right folder relative to this script
        $filePath = __DIR__ . "/../uploads/" . $doc['file_path'];

        // 3. Delete the physical file if it exists
        if (!empty($doc['file_path']) && file_exists($filePath)) {
            unlink($filePath);
        }

        // 4. Remove the record from the database
        $delStmt = $db->prepare("DELETE FROM documents WHERE id = ?");
        $delStmt->execute([$id]);
    }
}

// Redirect back to the documents page
header("Location: index.php?page=documents");
exit;