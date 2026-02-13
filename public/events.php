<?php
// public/events.php
if (!isset($_SESSION['user_id'])) {
    showAccessDenied("Events", "Neighborhood Calendar");
    return;
}

$db = Database::getConnection();
$myId = $_SESSION['user_id'];
$statusMsg = "";

// 1. Handle Deletion (Creator or Admin only)
if (isset($_GET['delete_id'])) {
    $delId = (int)$_GET['delete_id'];
    // Check if user is admin or the creator
    $check = $db->prepare("SELECT created_by FROM events WHERE id = ?");
    $check->execute([$delId]);
    $creator = $check->fetchColumn();

    if ($creator == $myId || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin')) {
        $del = $db->prepare("DELETE FROM events WHERE id = ?");
        $del->execute([$delId]);
        header("Location: index.php?page=events&status=deleted");
        exit;
    }
}

// 2. Handle New Event Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_event'])) {
    $title = mb_strimwidth(trim($_POST['title']), 0, 100);
    $desc  = mb_strimwidth(trim($_POST['description']), 0, 500);
    $loc   = mb_strimwidth(trim($_POST['location']), 0, 255);
    $date  = $_POST['event_date'];

    if (!empty($title) && !empty($date)) {
        $ins = $db->prepare("INSERT INTO events (title, description, location, event_date, created_by) VALUES (?, ?, ?, ?, ?)");
        $ins->execute([$title, $desc, $loc, $date, $myId]);
        header("Location: index.php?page=events&status=success");
        exit;
    }
}

// 3. Handle RSVP toggle
if (isset($_GET['rsvp_event_id'])) {
    $eventId = (int)$_GET['rsvp_event_id'];

    // Check if we are already going
    $checkRsvp = $db->prepare("SELECT id FROM event_rsvps WHERE event_id = ? AND user_id = ?");
    $checkRsvp->execute([$eventId, $myId]);
    $existing = $checkRsvp->fetch();

    if ($existing) {
        // If already going, clicking again "un-RSVPs" (Cancel)
        $delRsvp = $db->prepare("DELETE FROM event_rsvps WHERE event_id = ? AND user_id = ?");
        $delRsvp->execute([$eventId, $myId]);
    } else {
        // Otherwise, join the event
        $insRsvp = $db->prepare("INSERT IGNORE INTO event_rsvps (event_id, user_id, status) VALUES (?, ?, 'going')");
        $insRsvp->execute([$eventId, $myId]);
    }
    header("Location: index.php?page=events");
    exit;
}

// 4. Fetch Upcoming Events (Sorted by date)
$stmt = $db->prepare("
    SELECT e.*, u.username,
        (SELECT COUNT(*) FROM event_rsvps WHERE event_id = e.id) as rsvp_count,
        (SELECT COUNT(*) FROM event_rsvps WHERE event_id = e.id AND user_id = ?) as is_my_rsvp
    FROM events e 
    JOIN users u ON e.created_by = u.id 
    WHERE e.event_date >= CURDATE() 
    ORDER BY e.event_date ASC
");
$stmt->execute([$myId]);
$events = $stmt->fetchAll();
?>

<div class="events-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>📅 Neighborhood Events</h2>
        <button onclick="document.getElementById('event-form').style.display='block'" class="primary-button" style="width: auto;">+ Plan Event</button>
    </div>

    <div id="event-form" style="display:none; background: var(--bg-card); padding: 20px; border-radius: 8px; border: 1px solid var(--border-color); margin-bottom: 30px; box-shadow: var(--shadow);">
        <h3>Post a New Event</h3>
        <form method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <input type="text" name="title" minlength="2" maxlength="100" placeholder="Event Title (e.g. Board Game Night)" required style="padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                <input type="datetime-local" name="event_date" required style="padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                <input type="text" name="location" minlength="2" maxlength="255" placeholder="Location (e.g. Apt 4B or Backyard)" style="padding: 10px; border-radius: 4px; border: 1px solid #ddd; grid-column: span 2;">
                <textarea name="description" minlength="2" maxlength="500" placeholder="Description (Tell your neighbors what to bring!)" rows="3" style="padding: 10px; border-radius: 4px; border: 1px solid #ddd; grid-column: span 2;"></textarea>
            </div>
            <div style="margin-top: 15px; display: flex; gap: 10px;">
                <button type="submit" name="add_event" class="primary-button" style="width: auto;">Create Event</button>
                <button type="button" onclick="document.getElementById('event-form').style.display='none'" style="background:none; border:none; color: var(--text-muted); cursor: pointer;">Cancel</button>
            </div>
        </form>
    </div>

    <div class="events-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
        <?php if (empty($events)): ?>
            <p style="color: var(--text-muted);">No upcoming events. Why not plan one?</p>
        <?php else: ?>
            <?php foreach ($events as $event): ?>
                <div class="event-card">

                    <div style="background: var(--primary); color: white; display: inline-block; padding: 4px 10px; border-radius: 4px; font-size: 0.8rem; margin-bottom: 10px;">
                        <?= date('D, M j - g:i A', strtotime($event['event_date'])) ?>
                    </div>

                    <h3 style="margin: 0 0 10px 0;"><?= htmlspecialchars($event['title']) ?></h3>

                    <p style="font-size: 0.9rem; color: var(--text-main); margin-bottom: 15px;">
                        <?= nl2br(htmlspecialchars($event['description'])) ?>
                    </p>

                    <div style="font-size: 0.85rem; color: var(--text-muted);">
                        <strong>📍 Location:</strong> <?= htmlspecialchars($event['location'] ?: 'Not specified') ?><br>
                        <strong>👤 Hosted by:</strong> <?= htmlspecialchars($event['username']) ?>
                    </div>

                    <div style="margin-top: 15px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #eee;">
                        <div style="font-size: 0.85rem; color: var(--text-muted);">
                            <strong>👥 <?= $event['rsvp_count'] ?> Neighbors Going</strong>
                        </div>

                        <button
                            onclick="handleRSVP(this, '<?= $event['id'] ?>')"
                            class="primary-button rsvp-btn"
                            style="width: auto; padding: 5px 15px; font-size: 0.8rem; background: <?= $event['is_my_rsvp'] ? '#28a745' : 'var(--primary)' ?>; cursor: pointer; border: none; border-radius: 4px; color: white;">
                            <?= $event['is_my_rsvp'] ? '✓ Going' : 'Count Me In!' ?>
                        </button>
                    </div>

                    <?php if ($event['created_by'] == $myId || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin')): ?>
                        <a href="index.php?page=events&delete_id=<?= $event['id'] ?>"
                            onclick="return confirm('Cancel this event?')"
                            style="position: absolute; top: 10px; right: 10px; text-decoration: none; color: #ff4444; font-size: 1.2rem;">&times;</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    function handleRSVP(btn, eventId) {
        btn.disabled = true;
        btn.innerText = 'Wait...';

        setTimeout(() => {
            window.location.href = 'index.php?page=events&rsvp_event_id=' + eventId;
        }, 200);
    }
</script>