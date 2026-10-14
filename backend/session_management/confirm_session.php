<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';
require __DIR__ . '/../general/notifications.php';
require __DIR__ . '/../general/format.php';
require __DIR__ . '/functions.php';

requireLogin();

$sessionId = (int)($_POST['session_id'] ?? 0);
$date      = $_POST['session_date'] ?? '';
$start     = $_POST['start_time'] ?? '';
$end       = $_POST['end_time'] ?? '';
$mode      = $_POST['mode'] ?? '';
$myId      = currentUser()['id'];

$backToForm = static function (string $error) use ($sessionId): never {
    header('Location: schedule.php?session_id=' . $sessionId . '&err=' . urlencode($error));
    exit;
};

if (!$sessionId || !$date || !$start || !$end || !in_array($mode, ['Online', 'Face-to-face'], true)) {
    $backToForm('Please choose a date, a start and end time, and a support mode.');
}

$session = findSessionWithStatus($conn, $sessionId, 'Unscheduled');

if (!$session || !isMySession($session, (int)$myId, currentUser()['role'])) {
    header('Location: schedule_list.php?err=' . urlencode('This session is not ready to be scheduled.'));
    exit;
}

$error = validateSessionTime($conn, (int)$session['volunteer_id'], $date, $start, $end);
if ($error) {
    $backToForm($error);
}

$conn->begin_transaction();
try {
    requestSessionTime($conn, $sessionId, $date, $start, $end, $mode);
    $when = date('D, j M Y', strtotime($date)) . ', ' . formatTimeRange($start, $end);
    $message = $session['volunteer_id'] == $myId
        ? "You picked $when for the {$session['category']} session. Please accept or reject it in Session Request."
        : currentUser()['name'] . " requested a {$session['category']} session on $when. Please accept or reject it in Session Request.";
    addNotification($conn, (int)$session['volunteer_id'], $message, 'Session Requested');
    $conn->commit();
    header('Location: schedule_list.php?scheduled=1');
} catch (Exception $e) {
    $conn->rollback();
    $backToForm('Something went wrong. Please try again.');
}
exit;
