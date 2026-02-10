<?php
$db = Database::getConnection();
$userId = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['role'] ?? 'guest';

// Security: Redirect if not logged in
if (!$userId) {
    echo "<div class='container'><h2>🔒 Access Denied</h2><p>Please log in to view building documents.</p></div>";
    return;
}
?>

<div class="container">
    <h2 style="margin-bottom: 30px;">📜 Building Documents & Info</h2>

    <?php if ($userRole === 'admin'): ?>
        <div style="background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px dashed #cbd5e1; margin-bottom: 40px;">
            <h3 style="margin-top: 0; font-size: 1.1rem;">➕ Upload New Document</h3>
            <form method="POST" action="add_document.php" enctype="multipart/form-data" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <label style="display:block; font-size: 0.9rem; font-weight:bold; margin-bottom:5px;">Document Title:</label>
                    <input type="text" maxlength="100" minlength="2" name="title" placeholder="e.g. House Rules 2026" required style="width:100%; padding:10px; border-radius:4px; border:1px solid #ccc;">
                </div>
                <div>
                    <label style="display:block; font-size: 0.9rem; font-weight:bold; margin-bottom:5px;">Category:</label>
                    <select name="category" style="width:100%; padding:10px; border-radius:4px; border:1px solid #ccc;">
                        <option value="general" selected>📄 General Info</option>
                        <option value="maintenance">🛠️ Maintenance & Technical</option>
                        <option value="financial">💰 Financial Reports</option>
                        <option value="legal">⚖️ Legal & Contracts</option>
                    </select>
                </div>
                <div style="grid-column: span 2;">
                    <label style="display:block; font-size: 0.9rem; font-weight:bold; margin-bottom:5px;">Description:</label>
                    <textarea name="description" maxlength="500" minlength="2" placeholder="What is this document about?" style="width:100%; height: 60px; padding:10px; border-radius:4px; border:1px solid #ccc;"></textarea>
                </div>
                <div style="grid-column: span 2;">
                    <input type="file" name="doc_file" accept=".pdf,.jpg,.jpeg,.png" required>
                    <button type="submit" style="background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; float: right;">Upload to Library</button>
                </div>
            </form>
            <div style="clear:both;"></div>
        </div>
    <?php endif; ?>

    <?php
    $categories = [
        'general' => '📄 General Info',
        'maintenance' => '🛠️ Maintenance & Technical',
        'financial' => '💰 Financial Reports',
        'legal' => '⚖️ Legal & Contracts'

    ];

    foreach ($categories as $key => $label):
        $stmt = $db->prepare("SELECT * FROM documents WHERE category = ? ORDER BY created_at DESC");
        $stmt->execute([$key]);
        $docs = $stmt->fetchAll();

        if ($docs): ?>
            <h3 style="border-left: 4px solid #007bff; padding-left: 12px; margin-top: 40px; color: #1e293b;"><?php echo $label; ?></h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-top: 15px;">
                <?php foreach ($docs as $doc):
                    $ext = !empty($doc['file_path']) ? strtolower(pathinfo($doc['file_path'], PATHINFO_EXTENSION)) : 'file';
                    $filePath = "../uploads/" . htmlspecialchars($doc['file_path']);
                ?>

                    <div class="doc-card">
                        <div class="doc-type-badge type-<?php echo $ext; ?>">
                            <?php echo $ext; ?>
                        </div>

                        <?php if ($userRole === 'admin'): ?>
                            <form method="POST" action="delete_document.php" class="doc-delete-form">
                                <input type="hidden" name="id" value="<?php echo $doc['id']; ?>">
                                <button type="submit" class="btn-remove-comment" onclick="return confirm('Delete document?')">🗑️</button>
                            </form>
                        <?php endif; ?>

                        <div>
                            <h4><?php echo htmlspecialchars($doc['title']); ?></h4>
                            <p class="doc-description">
                                <?php echo htmlspecialchars($doc['description'] ?: 'No detailed description available.'); ?>
                            </p>
                        </div>

                        <div>
                            <a href="<?php echo $filePath; ?>" target="_blank" class="btn-doc-action">Open Document</a>
                            <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 15px; text-align: right;">
                                Added: <?php echo date('M d, Y', strtotime($doc['created_at'])); ?>
                            </div>
                        </div>
                    </div>

                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <?php if ($db->query("SELECT COUNT(*) FROM documents")->fetchColumn() == 0): ?>
        <p style="text-align:center; color:#888; margin-top:50px;">No documents uploaded yet.</p>
    <?php endif; ?>
</div>