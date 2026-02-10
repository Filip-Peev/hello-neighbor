<?php
// public/directory.php

$db = Database::getConnection();
$searchTerm = trim($_GET['search'] ?? '');

// 1. Build the Query
$sql = "SELECT username, summary, role, created_at 
        FROM users 
        WHERE is_deleted = 0";

if (!empty($searchTerm)) {
    $sql .= " AND (username LIKE ? OR summary LIKE ?)";
}

$sql .= " ORDER BY summary DESC, username ASC"; // Users with summaries appear first

$stmt = $db->prepare($sql);

if (!empty($searchTerm)) {
    $stmt->execute(["%$searchTerm%", "%$searchTerm%"]);
} else {
    $stmt->execute();
}

$neighbors = $stmt->fetchAll();
?>

<!-- Ensure the viewport is responsive -->
<meta name="viewport" content="width=device-width, initial-scale=1">

<div class="directory-header">
    <h2>🏘️ Neighbor Directory</h2>

    <form method="GET" action="index.php" class="directory-search-form">
        <input type="hidden" name="page" value="directory">

        <div class="search-wrapper">
            <input
                type="text"
                name="search"
                placeholder="Search skills or names..."
                value="<?php echo htmlspecialchars($searchTerm); ?>">

            <?php if (!empty($searchTerm)): ?>
                <a href="index.php?page=directory" title="Clear search" class="clear-search">Clear</a>
            <?php endif; ?>
        </div>

        <button type="submit">Search</button>
    </form>
</div>

<p class="directory-description">
    Find neighbors by their skills or expertise. Help is just a door away!
</p>

<div class="neighbor-grid">
    <?php if (empty($neighbors)): ?>
        <p>No neighbors found matching your search.</p>
    <?php else: ?>
        <?php foreach ($neighbors as $neighbor): ?>
            <div class="neighbor-card">
                <div class="neighbor-header">

                    <div>
                        <strong class="neighbor-name">
                            <?php echo htmlspecialchars($neighbor['username']); ?>
                            <?php if ($neighbor['role'] === 'admin'): ?>
                                <small class="neighbor-role">(Admin)</small>
                            <?php endif; ?>
                        </strong>

                        <small class="neighbor-meta">
                            Member since <?php echo date('M Y', strtotime($neighbor['created_at'])); ?>
                        </small>
                    </div>
                </div>

                <div class="neighbor-summary-box">
                    <?php if (!empty($neighbor['summary'])): ?>
                        <p class="neighbor-summary">
                            <?php echo htmlspecialchars($neighbor['summary']); ?>
                        </p>
                    <?php else: ?>
                        <p class="neighbor-summary-empty">
                            No summary provided yet.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>