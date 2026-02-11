<?php
$db = Database::getConnection();
$userId = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['role'] ?? 'guest';

echo "<h2>📊 Community Polls</h2>";

// --- 1. ADMIN: Create Poll ---
if ($userRole === 'admin'): ?>
    <div class="container">🆕 Create New Community Poll</h3>
        <form method="POST" action="create_poll.php">
            <label style="font-weight: bold;">Question:</label>
            <input type="text" name="question" placeholder="Enter question..." required
                style="width:100%; margin: 10px 0 15px 0; padding:10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">

            <div style="margin-bottom: 15px;">
                <label style="font-weight: bold;">Visibility:</label>
                <select name="category" style="width:100%; margin-top: 5px; padding:10px; border: 1px solid #ccc; border-radius: 4px;">
                    <option value="public">Public (Everyone)</option>
                    <option value="private">Private (Residents Only)</option>
                </select>
            </div>

            <label style="font-weight: bold;">Options:</label>
            <div id="options-list">
                <input type="text" name="options[]" placeholder="Option 1" required style="width:100%; margin-bottom:8px; padding:8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
                <input type="text" name="options[]" placeholder="Option 2" required style="width:100%; margin-bottom:8px; padding:8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            </div>

            <button type="button" onclick="addOption()" style="background: #6c757d; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 0.8rem; margin-bottom: 15px;">+ Add Another Option</button>
            <br>
            <button type="submit">Publish Poll</button>
        </form>
    </div>
    <script>
        function addOption() {
            const container = document.getElementById('options-list');
            const input = document.createElement('input');
            input.type = 'text';
            input.name = 'options[]';
            input.placeholder = 'Next Option';
            input.style = "width:100%; margin-bottom:8px; padding:8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;";
            container.appendChild(input);
        }
    </script>
<?php endif;

// --- 2. LIST POLLS ---
if ($userId) {
    $stmt = $db->query("SELECT * FROM polls ORDER BY created_at DESC");
} else {
    $stmt = $db->query("SELECT * FROM polls WHERE category = 'public' ORDER BY created_at DESC");
}
$polls = $stmt->fetchAll();

foreach ($polls as $poll) {
    $postedDate = date("M j, Y", strtotime($poll['created_at']));
    $isPrivate = ($poll['category'] === 'private');

    echo "<div class='card-poll'>";

    if ($userRole === 'admin') {
        echo "<form method='POST' action='delete_poll.php' onsubmit='return confirm(\"Delete this poll?\");' style='position: absolute; top: 15px; right: 15px;'>";
        echo "<input type='hidden' name='poll_id' value='{$poll['id']}'>";
        echo "<button type='submit' style='background: none; border: none; color: var(--danger); cursor: pointer; font-size: 0.8rem;'>🗑️ Delete</button>";
        echo "</form>";
    }

    echo "<h3 style='margin: 0; padding-right: 80px; color: var(--primary);'>" . htmlspecialchars($poll['question']) . "</h3>";

    $badge = $isPrivate ? "<span class='poll-badge-private'>🔒 Private</span>" : "";
    echo "<p style='font-size: 0.75rem; color: var(--text-muted); margin: 5px 0 15px 0;'>📅 $postedDate $badge</p>";

    $hasVoted = false;
    if ($userId) {
        $checkVote = $db->prepare("SELECT option_id FROM poll_votes WHERE poll_id = ? AND user_id = ?");
        $checkVote->execute([$poll['id'], $userId]);
        $userVote = $checkVote->fetch();
        $hasVoted = (bool)$userVote;
    }

    $optStmt = $db->prepare("SELECT o.*, COUNT(v.id) as vote_count FROM poll_options o LEFT JOIN poll_votes v ON o.id = v.option_id WHERE o.poll_id = ? GROUP BY o.id");
    $optStmt->execute([$poll['id']]);
    $options = $optStmt->fetchAll();
    $totalVotes = array_sum(array_column($options, 'vote_count'));

    if ($hasVoted || !$userId) {
        foreach ($options as $option) {
            $percent = $totalVotes > 0 ? round(($option['vote_count'] / $totalVotes) * 100) : 0;
            echo "<div class='poll-result-item'>";
            echo "<div class='poll-result-info'>";
            echo "<span>" . htmlspecialchars($option['option_text']) . ($userId && $userVote['option_id'] == $option['id'] ? " <strong>(Yours)</strong>" : "") . "</span>";
            echo "<span>$percent%</span>";
            echo "</div>";
            echo "<div class='poll-progress-bg'>";
            echo "<div class='poll-progress-fill' style='width: {$percent}%;'></div>";
            echo "</div></div>";
        }

        if ($userRole === 'admin') {
            echo "<details class='voter-audit'>";
            echo "<summary style='cursor:pointer; font-weight: bold;'>🕵️ View Voter List</summary>";

            $voterStmt = $db->prepare("SELECT u.username, o.option_text FROM poll_votes v JOIN users u ON v.user_id = u.id JOIN poll_options o ON v.option_id = o.id WHERE v.poll_id = ? ORDER BY u.username");
            $voterStmt->execute([$poll['id']]);
            $voters = $voterStmt->fetchAll();

            if ($voters) {
                echo "<ul style='margin: 10px 0 0 0; padding-left: 20px;'>";
                foreach ($voters as $v) {
                    echo "<li><strong>" . htmlspecialchars($v['username']) . "</strong>: " . htmlspecialchars($v['option_text']) . "</li>";
                }
                echo "</ul>";
            } else {
                echo "<p style='margin-top: 10px;'>No votes recorded yet.</p>";
            }
            echo "</details>";
        }
    } else {
        echo "<form method='POST' action='submit_vote.php'>";
        foreach ($options as $option) {
            echo "<label class='poll-option-label'>";
            echo "<input type='radio' name='option_id' value='{$option['id']}' required>";
            echo "<span>" . htmlspecialchars($option['option_text']) . "</span>";
            echo "</label>";
        }
        echo "<input type='hidden' name='poll_id' value='{$poll['id']}'>";
        echo "<button type='submit' class=primary-button>Vote</button>";
        echo "</form>";
    }
    echo "</div>";
}
?>

<script>
    window.addEventListener('load', () => {
        const bars = document.querySelectorAll('.poll-progress-fill');
        setTimeout(() => {
            bars.forEach(bar => {
                const targetWidth = bar.getAttribute('data-percent') + '%';
                bar.style.width = targetWidth;
            });
        }, 100);
    });
</script>