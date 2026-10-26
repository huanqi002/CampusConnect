<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';
require __DIR__ . '/../general/notifications.php';
require __DIR__ . '/../general/format.php';
require __DIR__ . '/functions.php';
require __DIR__ . '/booking_parts.php';

requireLogin();

$me        = currentUser();
$myId      = (int)$me['id'];
$sessionId = (int)($_POST['session_id'] ?? $_GET['session_id'] ?? 0);
$session   = findSessionWithStatus($conn, $sessionId, 'Pending');

if (!$session || !isMySession($session, $myId, $me['role'])) {
    header('Location: index.php?err=' . urlencode('Only a Pending session can be edited.'));
    exit;
}

$volunteerId = (int)$session['volunteer_id'];
$error       = null;
$description = $session['description'];
$mode        = $session['support_mode'];
$date        = $session['session_date'];
$start       = substr((string)$session['start_time'], 0, 5);
$end         = $session['end_time'] !== null ? substr($session['end_time'], 0, 5) : date('H:i', strtotime($session['start_time'] . ' +1 hour'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = trim($_POST['description'] ?? '');
    $mode        = $_POST['mode'] ?? '';
    $date        = $_POST['session_date'] ?? '';
    $start       = $_POST['start_time'] ?? '';
    $end         = $_POST['end_time'] ?? '';

    if ($description === '' || mb_strlen($description) > 500) {
        $error = 'Please enter a description of up to 500 characters.';
    } elseif (!in_array($mode, ['Online', 'Face-to-face'], true) || !$date || !$start || !$end) {
        $error = 'Please choose a date, a start and end time, and a support mode.';
    } else {
        $error = validateSessionTime($conn, $volunteerId, $date, $start, $end, $sessionId);
    }

    if (!$error) {
        $conn->begin_transaction();
        try {
            updatePendingSession($conn, $sessionId, $description, $date, $start, $end, $mode);
            $category = $session['category'];
            $when     = date('D, j M Y', strtotime($date)) . ', ' . formatTimeRange($start, $end);
            if ($myId === $volunteerId) {
                addNotification($conn, (int)$session['user_id'], "{$me['name']} changed your $category session to $when.", 'Session Updated', $sessionId);
            } else {
                addNotification($conn, $volunteerId, "{$me['name']} changed the $category session to $when. Please accept or reject it in Session Request.", 'Session Updated', $sessionId);
            }
            $conn->commit();
            header('Location: index.php?done=updated');
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Something went wrong. Please try again.';
        }
    }
}

$preferredTimes = getPreferredTimes($conn, $volunteerId);

$scheduleConfig = [
    'volunteerId'      => $volunteerId,
    'excludeSessionId' => $sessionId,
    'preferredDays'    => array_keys($preferredTimes),
    'preferredTimes'   => (object)$preferredTimes,
    'today'            => date('Y-m-d'),
    'now'              => date('H:i'),
    'dayStart'         => SLOT_DAY_START,
    'dayEnd'           => SLOT_DAY_END,
    'initial'          => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$date) && isSlotTime($start) && isSlotTime($end)
                          ? ['date' => $date, 'start' => $start, 'end' => $end] : null,
];

include __DIR__ . '/../general/header.php';
?>
<link rel="stylesheet" href="../../frontend/css/session.css?v=<?php echo filemtime(__DIR__ . '/../../frontend/css/session.css'); ?>">

<?php if ($error): ?>
<div class="message message-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<form id="scheduleForm" class="schedule-card" method="post" action="edit_session.php">
    <input type="hidden" name="session_id" value="<?php echo $sessionId; ?>">
    <input type="hidden" name="session_date" id="sessionDate">
    <input type="hidden" name="start_time" id="startTime">
    <input type="hidden" name="end_time" id="endTime">

    <section class="booking-intro">
        <?php echo bookingPeopleHtml($session, $myId); ?>
        <div class="booking-topic">
            <div class="topic-head"><span class="topic-label">Session category</span><?php echo categoryChip($session['category']); ?></div>
            <label class="topic-label" for="description">Description</label>
            <textarea id="description" name="description" class="topic-input" maxlength="500" required><?php echo htmlspecialchars($description); ?></textarea>
        </div>
    </section>

    <?php echo bookingPickerHtml($mode); ?>

    <div class="schedule-actions">
        <a class="btn btn-plain" href="index.php">Cancel</a>
        <button type="submit" class="btn btn-success">Save Changes</button>
    </div>
</form>

<script src="../../frontend/js/script.js"></script>
<script>
    initScheduleForm(<?php echo json_encode($scheduleConfig); ?>);
</script>

<?php include __DIR__ . '/../general/footer.php'; ?>
