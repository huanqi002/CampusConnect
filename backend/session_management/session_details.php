<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';
require __DIR__ . '/../general/card_template.php';
require __DIR__ . '/../general/format.php';
require __DIR__ . '/functions.php';

requireLogin();

$myId      = currentUser()['id'];
$sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
$session   = findSessionById($conn, $sessionId);

if (!$session || ($session['user_id'] != $myId && $session['volunteer_id'] != $myId)) {
    header('Location: index.php?err=' . urlencode('Session not found.'));
    exit;
}

$status    = sessionDisplayStatus($session);
$isBooked  = $session['session_date'] !== null;
$isActive  = in_array($session['status'], ['Pending', 'Scheduled'], true);
$canFinish = $session['volunteer_id'] == $myId && $session['status'] === 'Scheduled' && hasSessionStarted($session);

$time = $isBooked ? formatTimeRange($session['start_time'], $session['end_time']) : null;

include __DIR__ . '/../general/header.php';
?>

<h1 class="page-title">Session Details</h1>

<div class="detail-card">
    <span class="pill pill-<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></span>

    <?php echo renderDetails([
        'Category'     => $session['category'],
        'Description'  => $session['description'],
        'Student'      => $session['student_name'],
        'Volunteer'    => $session['volunteer_name'],
        'Date'         => $isBooked ? date('l, j F Y', strtotime($session['session_date'])) : 'Not booked yet',
        'Time'         => $time ?? 'Not booked yet',
        'Support mode' => $session['support_mode'],
        'Created'      => formatDateTime($session['created_at']),
    ]); ?>

    <div class="btn-row">
        <?php if ($session['status'] === 'Pending'): ?>
        <a class="btn btn-primary" href="schedule.php?session_id=<?php echo (int)$session['id']; ?>">Book date and time</a>
        <a class="btn btn-secondary" href="edit_session.php?session_id=<?php echo (int)$session['id']; ?>">Edit</a>
        <?php endif; ?>

        <?php if ($canFinish): ?>
        <form method="post" action="mark_completed.php" onsubmit="return confirm('Mark this session as complete?');">
            <input type="hidden" name="session_id" value="<?php echo (int)$session['id']; ?>">
            <button type="submit" class="btn btn-primary">Mark as complete</button>
        </form>
        <?php endif; ?>

        <?php if ($isActive): ?>
        <form method="post" action="cancel_session.php" onsubmit="return confirm('Cancel this session? The other participant will be notified.');">
            <input type="hidden" name="session_id" value="<?php echo (int)$session['id']; ?>">
            <button type="submit" class="btn btn-secondary">Cancel session</button>
        </form>
        <?php endif; ?>

        <a class="btn btn-plain" href="index.php">Back to dashboard</a>
    </div>
</div>

<?php include __DIR__ . '/../general/footer.php'; ?>
