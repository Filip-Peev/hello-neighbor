<?php
$db = Database::getConnection();

$userId = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['role'] ?? 'guest';

/* --------------------------
   Load Polls
-------------------------- */

if ($userId) {
    $stmt = $db->query("SELECT * FROM polls ORDER BY created_at DESC");
} else {
    $stmt = $db->query("SELECT * FROM polls WHERE category='public' ORDER BY created_at DESC");
}

$polls = $stmt->fetchAll();
?>

<h2>📊 Community Polls</h2>


<?php if ($userRole === 'admin'): ?>

    <!-- =========================
     ADMIN CREATE POLL
========================= -->

    <div class="container">

        <h3>🆕 Create New Community Poll</h3>

        <form method="POST" action="create_poll.php">

            <label><strong>Question:</strong></label>
            <input type="text" name="question" required
                style="width:100%; margin:10px 0 15px; padding:10px; border:1px solid #ccc; border-radius:4px;">

            <div style="margin-bottom:15px;">
                <label><strong>Visibility:</strong></label>

                <select name="category"
                    style="width:100%; margin-top:5px; padding:10px; border:1px solid #ccc; border-radius:4px;">
                    <option value="public">Public (Everyone)</option>
                    <option value="private">Private (Residents Only)</option>
                </select>
            </div>

            <label><strong>Options:</strong></label>

            <div id="options-list">
                <input type="text" name="options[]" placeholder="Option 1" required
                    style="width:100%; margin-bottom:8px; padding:8px; border:1px solid #ccc; border-radius:4px;">

                <input type="text" name="options[]" placeholder="Option 2" required
                    style="width:100%; margin-bottom:8px; padding:8px; border:1px solid #ccc; border-radius:4px;">
            </div>

            <button type="button"
                onclick="addOption()"
                style="background:#6c757d;color:white;border:none;padding:5px 10px;border-radius:4px;cursor:pointer;font-size:0.8rem;margin-bottom:15px;">
                + Add Another Option
            </button>

            <br>

            <button type="submit">Publish Poll</button>

        </form>

    </div>

<?php endif; ?>


<!-- =========================
     NO POLLS MESSAGE
========================= -->

<?php if (empty($polls)): ?>

    <div class="card-poll" style="text-align:center;padding:30px;color:var(--text-muted);">
        📭 No polls yet.<br>
        <?= $userRole === 'admin'
            ? "Create the first poll above."
            : "Check back later!" ?>
    </div>

<?php endif; ?>


<!-- =========================
     POLL LIST
========================= -->

<?php foreach ($polls as $poll): ?>

    <?php

    $postedDate = date("M j, Y", strtotime($poll['created_at']));
    $isPrivate = ($poll['category'] === 'private');

    /* --------------------------
   Check if user voted
-------------------------- */

    $hasVoted = false;
    $userVote = null;

    if ($userId) {
        $checkVote = $db->prepare(
            "SELECT option_id FROM poll_votes WHERE poll_id=? AND user_id=?"
        );
        $checkVote->execute([$poll['id'], $userId]);
        $userVote = $checkVote->fetch();
        $hasVoted = (bool)$userVote;
    }


    /* --------------------------
   Load poll options
-------------------------- */

    $optStmt = $db->prepare("
SELECT o.*, COUNT(v.id) vote_count
FROM poll_options o
LEFT JOIN poll_votes v ON o.id=v.option_id
WHERE o.poll_id=?
GROUP BY o.id
");

    $optStmt->execute([$poll['id']]);
    $options = $optStmt->fetchAll();

    $totalVotes = array_sum(array_column($options, 'vote_count'));

    ?>


    <div class="card-poll">

        <!-- ADMIN DELETE -->

        <?php if ($userRole === 'admin'): ?>

            <form method="POST"
                action="delete_poll.php"
                onsubmit="return confirm('Delete this poll?');"
                style="position:absolute;top:15px;right:15px;">

                <input type="hidden" name="poll_id" value="<?= $poll['id'] ?>">

                <button type="submit"
                    style="background:none;border:none;color:var(--danger);cursor:pointer;font-size:0.8rem;">
                    🗑️ Delete
                </button>

            </form>

        <?php endif; ?>


        <!-- POLL HEADER -->

        <h3 style="margin:0;padding-right:80px;color:var(--primary);">
            <?= htmlspecialchars($poll['question']) ?>
        </h3>

        <p style="font-size:0.75rem;color:var(--text-muted);margin:5px 0 15px;">

            📅 <?= $postedDate ?>

            <?php if ($isPrivate): ?>
                <span class="poll-badge-private">🔒 Private</span>
            <?php endif; ?>

        </p>



        <!-- =========================
     SHOW RESULTS
========================= -->

        <?php if ($hasVoted || !$userId): ?>

            <?php foreach ($options as $option):

                $percent = $totalVotes > 0
                    ? round(($option['vote_count'] / $totalVotes) * 100)
                    : 0;

            ?>

                <div class="poll-result-item">

                    <div class="poll-result-info">

                        <span>
                            <?= htmlspecialchars($option['option_text']) ?>

                            <?php if ($userId && $userVote['option_id'] == $option['id']): ?>
                                <strong>(Yours)</strong>
                            <?php endif; ?>

                        </span>

                        <span><?= $percent ?>%</span>

                    </div>

                    <div class="poll-progress-bg">
                        <div class="poll-progress-fill" style="width:<?= $percent ?>%;"></div>
                    </div>

                </div>

            <?php endforeach; ?>


            <!-- ADMIN VOTER AUDIT -->

            <?php if ($userRole === 'admin'): ?>

                <details class="voter-audit">

                    <summary style="cursor:pointer;font-weight:bold;">
                        🕵️ View Voter List
                    </summary>

                    <?php

                    $voterStmt = $db->prepare("
SELECT u.username, o.option_text
FROM poll_votes v
JOIN users u ON v.user_id=u.id
JOIN poll_options o ON v.option_id=o.id
WHERE v.poll_id=?
ORDER BY u.username
");

                    $voterStmt->execute([$poll['id']]);
                    $voters = $voterStmt->fetchAll();

                    ?>

                    <?php if ($voters): ?>

                        <ul style="margin:10px 0 0;padding-left:20px;">

                            <?php foreach ($voters as $v): ?>

                                <li>
                                    <strong><?= htmlspecialchars($v['username']) ?></strong>:
                                    <?= htmlspecialchars($v['option_text']) ?>
                                </li>

                            <?php endforeach; ?>

                        </ul>

                    <?php else: ?>

                        <p style="margin-top:10px;">
                            No votes recorded yet.
                        </p>

                    <?php endif; ?>

                </details>

            <?php endif; ?>


            <!-- =========================
     SHOW VOTING FORM
========================= -->

        <?php else: ?>

            <form method="POST" action="submit_vote.php">

                <?php foreach ($options as $option): ?>

                    <label class="poll-option-label">

                        <input type="radio"
                            name="option_id"
                            value="<?= $option['id'] ?>"
                            required>

                        <span><?= htmlspecialchars($option['option_text']) ?></span>

                    </label>

                <?php endforeach; ?>

                <input type="hidden" name="poll_id" value="<?= $poll['id'] ?>">

                <button type="submit" class="primary-button">
                    Vote
                </button>

            </form>

        <?php endif; ?>


    </div>

<?php endforeach; ?>


<!-- =========================
     JAVASCRIPT
========================= -->

<script>
    function addOption() {
        const container = document.getElementById('options-list');

        const input = document.createElement('input');

        input.type = 'text';
        input.name = 'options[]';
        input.placeholder = 'Next Option';

        input.style =
            "width:100%; margin-bottom:8px; padding:8px; border:1px solid #ccc; border-radius:4px;";

        container.appendChild(input);
    }
</script>