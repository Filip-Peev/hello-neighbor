<?php
$db = Database::getConnection();
$userId = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['role'] ?? 'guest';

// Security: Redirect if not logged in
if (!$userId) {
    echo "<div class='container'><h2>🔒 Access Denied</h2><p>Please log in to view building documents.</p></div>";
    return;
}

echo "<h2>📜 Building Documents & Info</h2>";

// --- 1. ADMIN ONLY: UPLOAD FORM ---
if ($userRole === 'admin'): ?>
    <div class="container">
        <h3 style="margin-top: 0;">➕ Upload New Document</h3>
        <form method="POST" action="add_document.php" enctype="multipart/form-data">
            <div style="margin-bottom: 10px;">
                <label style="display:block; font-weight:bold;">Document Title:</label>
                <input type="text" name="title" placeholder="e.g. House Rules 2026" required style="width:100%; padding:10px; border-radius:4px; border:1px solid #ccc;">
            </div>

            <div style="margin-bottom: 10px;">
                <label style="display:block; font-weight:bold;">Description (Optional):</label>
                <textarea name="description" placeholder="Short summary of the document..." class="summary-textarea"></textarea>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: bold; margin-bottom: 5px;">Select File (PDF, JPG, PNG):</label>
                <input type="file" name="doc_file" accept=".pdf,.jpg,.jpeg,.png" required>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display:block; font-weight:bold;">Category:</label>
                <select name="category" style="width:100%; padding:10px; border-radius:4px; border:1px solid #ccc;">
                    <option value="legal">⚖️ Legal & Contracts</option>
                    <option value="maintenance">🛠️ Maintenance & Technical</option>
                    <option value="financial">💰 Financial Reports</option>
                    <option value="general" selected>📄 General Info</option>
                </select>
            </div>

            <button type="submit">Upload to Library</button>
        </form>
    </div>
<?php endif;

// --- 2. DISPLAY DOCUMENTS BY CATEGORY ---
$categories = [
    'legal' => '⚖️ Legal & Contracts',
    'maintenance' => '🛠️ Maintenance & Technical',
    'financial' => '💰 Financial Reports',
    'general' => '📄 General Info'
];

foreach ($categories as $key => $label) {
    $stmt = $db->prepare("SELECT * FROM documents WHERE category = ? ORDER BY created_at DESC");
    $stmt->execute([$key]);
    $docs = $stmt->fetchAll();

    if ($docs) {
        echo "<h3 style='border-bottom: 2px solid #eee; padding-bottom: 8px; margin-top: 40px; color: #444;'>$label</h3>";
        echo "<div style='display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-top: 15px;'>";

        foreach ($docs as $doc) {
            // Safe pathinfo check to prevent NULL warnings in PHP 8.1+
            $ext = !empty($doc['file_path']) ? strtolower(pathinfo($doc['file_path'], PATHINFO_EXTENSION)) : '';
            $icon = ($ext === 'pdf') ? '📕' : '🖼️';

            echo "<div class='card'>";

            // Delete Button for Admins
            if ($userRole === 'admin') {
                echo "<form method='POST' action='delete_document.php' style='position:absolute; right:10px; top:10px;'>
                        <input type='hidden' name='id' value='{$doc['id']}'>
                        <button type='submit' onclick='return confirm(\"Are you sure you want to delete this document?\")' style='background:none; border:none; color:#ff4757; cursor:pointer; font-size: 1.2rem;'>&times;</button>
                      </form>";
            }

            echo "<div style='font-size: 2rem; margin-bottom: 10px;'>$icon</div>";
            echo "<h4 style='margin: 0 0 8px 0; color: #007bff;'>" . htmlspecialchars($doc['title']) . "</h4>";
            echo "<p style='font-size: 0.85rem; color: #666; margin-bottom: 15px; min-height: 40px;'>" . htmlspecialchars($doc['description'] ?? '') . "</p>";

            if (!empty($doc['file_path'])) {
                $filePath = "../uploads/" . htmlspecialchars($doc['file_path']);
                echo "<a href='$filePath' target='_blank' style='display: block; text-align: center; background: #f0f7ff; padding: 10px; border-radius: 6px; color: #007bff; font-weight: bold; text-decoration: none; border: 1px solid #cce5ff;'>View / Download</a>";
            }

            echo "<div style='font-size: 0.7rem; color: #999; margin-top: 10px; text-align: right;'>Added: " . date('d M Y', strtotime($doc['created_at'])) . "</div>";
            echo "</div>";
        }
        echo "</div>";
    }
}

// Show message if no documents exist at all
$totalCount = $db->query("SELECT COUNT(*) FROM documents")->fetchColumn();
if ($totalCount == 0) {
    echo "<p style='text-align:center; color:#888; margin-top:20px;'>No documents uploaded yet.</p>";
}
?>