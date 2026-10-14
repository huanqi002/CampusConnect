<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';
require __DIR__ . '/functions.php';

requireLogin();

$myId      = currentUser()['id'];
$sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
$session   = findSessionWithStatus($conn, $sessionId, 'Unscheduled');

if (!$session || !isMySession($session, (int)$myId, currentUser()['role'])) {
    header('Location: schedule_list.php?err=' . urlencode('This session is not ready to be scheduled.'));
    exit;
}

$sections = [
    'Student Details'   => ['Name' => $session['student_name'], 'Email' => $session['student_email']],
    'Volunteer Details' => ['Name' => $session['volunteer_name'], 'Email' => $session['volunteer_email']],
    'Session Details'   => ['Category' => $session['category'], 'Description' => $session['description']],
];

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
<link rel="stylesheet" href="../../frontend/css/session.css">

<?php if (isset($_GET['err'])): ?>
<div class="message message-error"><?php echo htmlspecialchars($_GET['err']); ?></div>
<?php endif; ?>

<div class="schedule-card">
    <div class="schedule-info-grid">
    <?php foreach ($sections as $heading => $fields): ?>
    <section class="schedule-info">
        <h2><?php echo htmlspecialchars($heading); ?></h2>
        <?php foreach ($fields as $label => $value): ?>
        <div class="schedule-info-row">
            <span><?php echo htmlspecialchars($label); ?></span>
            <div class="schedule-info-value"><?php echo nl2br(htmlspecialchars((string)($value ?? '-'))); ?></div>
        </div>
        <?php endforeach; ?>
    </section>
    <?php endforeach; ?>
    </div>

    <form id="scheduleForm" class="schedule-form" method="post" action="confirm_session.php">
        <input type="hidden" name="session_id" value="<?php echo (int)$session['id']; ?>">
        <input type="hidden" name="session_date" id="sessionDate">
        <input type="hidden" name="start_time" id="startTime">
        <input type="hidden" name="end_time" id="endTime">

        <div class="schedule-picker">
            <div class="calendar">
                <div class="calendar-head">
                    <button type="button" class="calendar-nav" data-step="-1" aria-label="Previous month">&lsaquo;</button>
                    <span id="calendarMonth"></span>
                    <button type="button" class="calendar-nav" data-step="1" aria-label="Next month">&rsaquo;</button>
                </div>
                <div class="calendar-grid" id="calendarGrid"></div>
            </div>
            <div class="slot-grid" id="slotGrid"></div>
        </div>

        <div class="schedule-legend">
            <span><i class="legend-dot is-off"></i>Unavailable</span>
            <span><i class="legend-dot is-open"></i>Available</span>
            <span><i class="legend-dot is-pref"></i>Preference</span>
            <span><i class="legend-dot is-busy"></i>Busy</span>
        </div>

        <div class="schedule-fields">
            <label for="selectedDate">Selected Date:</label>
            <input type="text" id="selectedDate" placeholder="DD/MM/YYYY" readonly tabindex="-1">

            <label for="startDisplay">Start Time:</label>
            <input type="text" id="startDisplay" placeholder="--:-- --" readonly tabindex="-1">

            <label for="endDisplay">End Time:</label>
            <input type="text" id="endDisplay" placeholder="--:-- --" readonly tabindex="-1">

            <label>Support Mode:</label>
            <div class="mode-toggle">
                <label><input type="radio" name="mode" value="Online" <?php echo $session['support_mode'] !== 'Face-to-face' ? 'checked' : ''; ?>><span>Virtual</span></label>
                <span class="mode-divider">/</span>
                <label><input type="radio" name="mode" value="Face-to-face" <?php echo $session['support_mode'] === 'Face-to-face' ? 'checked' : ''; ?>><span>Face-to-face</span></label>
            </div>
        </div>

        <p class="schedule-help" id="slotHelp" aria-live="polite">Pick a green date to see the volunteer's times.</p>

        <div class="schedule-actions">
            <a class="btn btn-plain" href="schedule_list.php">Cancel</a>
            <button type="submit" class="btn btn-success">Schedule</button>
        </div>
    </form>
</div>

<script src="../../frontend/js/script.js"></script>
<script>
    initScheduleForm(<?php echo json_encode($scheduleConfig); ?>);
</script>

<?php include __DIR__ . '/../general/footer.php'; ?>
