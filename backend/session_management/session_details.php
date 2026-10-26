<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';
require __DIR__ . '/../general/card_template.php';
require __DIR__ . '/../general/format.php';
require __DIR__ . '/functions.php';
require __DIR__ . '/booking_parts.php';

requireLogin();

$myId      = currentUser()['id'];
$sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
$session   = findSessionById($conn, $sessionId);

if (!$session || !isMySession($session, (int)$myId, currentUser()['role'])) {
    header('Location: index.php?err=' . urlencode('Session not found.'));
    exit;
}

$status = sessionDisplayStatus($session);

// Only confirmed sessions that have not finished yet are worth putting on a calendar.
$calendarUrl = $session['status'] === 'Scheduled' && !hasSessionEnded($session)
             ? googleCalendarUrl($session, (int)$myId) : null;

include __DIR__ . '/../general/header.php';
?>
<link rel="stylesheet" href="../../frontend/css/session.css?v=<?php echo filemtime(__DIR__ . '/../../frontend/css/session.css'); ?>">

<div class="schedule-card session-view">
    <?php echo sessionOverviewHtml($session, $status, (int)$myId); ?>

    <div class="schedule-actions">
        <a class="btn btn-plain" href="index.php"><?php echo cardIcon('back'); ?>Back to Dashboard</a>
        <?php if ($calendarUrl): ?>
        <a class="btn btn-plain" href="<?php echo htmlspecialchars($calendarUrl); ?>" target="_blank" rel="noopener noreferrer">Add to Google Calendar</a>
        <?php endif; ?>
        <?php if ($session['status'] === 'Unscheduled'): ?>
        <a class="btn btn-success" href="schedule.php?session_id=<?php echo (int)$session['id']; ?>">Schedule Now</a>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../general/footer.php'; ?>
