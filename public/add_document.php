<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if ($_SESSION['role'] === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = Database::getConnection();
    
    $title = $_POST['title'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    
    // File Upload Logic
    if (isset($_FILES['doc_file']) && $_FILES['doc_file']['error'] === 0) {
        $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
        $filename = $_FILES['doc_file']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            // Generate a unique name to prevent overwriting
            $newName = bin2hex(random_bytes(8)) . "." . $ext;
            $uploadPath = __DIR__ . "/../uploads/" . $newName;

            if (move_uploaded_file($_FILES['doc_file']['tmp_name'], $uploadPath)) {
                $stmt = $db->prepare("INSERT INTO documents (title, description, file_path, category) VALUES (?, ?, ?, ?)");
                $stmt->execute([$title, $description, $newName, $category]);
            }
        }
    }
}
header("Location: index.php?page=documents");