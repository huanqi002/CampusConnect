<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';
require __DIR__ . '/../general/format.php';
require __DIR__ . '/functions.php';
require __DIR__ . '/booking_parts.php';

requireLogin();

$myId      = currentUser()['id'];
$sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
$session   = findSessionWithStatus($conn, $sessionId, 'Unscheduled');

if (!$session || !isMySession($session, (int)$myId, currentUser()['role'])) {
    header('Location: schedule_list.php?err=' . urlencode('This session is not ready to be scheduled.'));
    exit;
}

$preferredTimes = getPreferredTimes($conn, (int)$session['volunteer_id']);

$scheduleConfig = [
    'volunteerId'    => (int)$session['volunteer_id'],
    'preferredDays'  => array_keys($preferredTimes),
    'preferredTimes' => (object)$preferredTimes,
    'today'         => date('Y-m-d'),
    'now'           => date('H:i'),
    'dayStart'      => SLOT_DAY_START,
    'dayEnd'        => SLOT_DAY_END,
];

include __DIR__ . '/../general/header.php';
?>
<link rel="stylesheet" href="../../frontend/css/session.css?v=<?php echo filemtime(__DIR__ . '/../../frontend/css/session.css'); ?>">

<?php if (isset($_GET['err'])): ?>
<div class="message message-error"><?php echo htmlspecialchars($_GET['err']); ?></div>
<?php endif; ?>

<form id="scheduleForm" class="schedule-card" method="post" action="confirm_session.php">
    <input type="hidden" name="session_id" value="<?php echo (int)$session['id']; ?>">
    <input type="hidden" name="session_date" id="sessionDate">
    <input type="hidden" name="start_time" id="startTime">
    <input type="hidden" name="end_time" id="endTime">

    <section class="booking-intro">
        <?php echo bookingPeopleHtml($session, (int)$myId); ?>
        <div class="booking-topic">
            <div class="topic-head"><span class="topic-label">Session category</span><?php echo categoryChip($session['category']); ?></div>
            <p class="topic-desc"><?php echo nl2br(htmlspecialchars($session['description'])); ?></p>
        </div>
    </section>

    <?php echo bookingPickerHtml($session['support_mode']); ?>

    <div class="schedule-actions">
        <a class="btn btn-plain" href="schedule_list.php">Cancel</a>
        <button type="submit" class="btn btn-success">Schedule</button>
    </div>
</form>

<script src="../../frontend/js/script.js"></script>
<script>
    initScheduleForm(<?php echo json_encode($scheduleConfig); ?>);
</script>

<?php include __DIR__ . '/../general/footer.php'; ?>
