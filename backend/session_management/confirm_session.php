<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';
require __DIR__ . '/../general/notifications.php';
require __DIR__ . '/../general/format.php';
require __DIR__ . '/functions.php';

requireLogin();

$sessionId = (int)($_POST['session_id'] ?? 0);
$date      = $_POST['session_date'] ?? '';
$time      = $_POST['session_time'] ?? '';
$mode      = $_POST['mode'] ?? '';
$myId      = currentUser()['id'];

if (!$sessionId || !$date || !$time || !in_array($mode, ['Online','Face-to-face'], true)) {
    header('Location: schedule.php?session_id=' . $sessionId . '&err=' . urlencode('Please fill in every field.'));
    exit;
}

$session = findSessionWithStatus($conn, $sessionId, 'Unscheduled');

if (!$session || ($session['user_id'] != $myId && $session['volunteer_id'] != $myId)) {
    header('Location: schedule_list.php?err=' . urlencode('This session is not ready to be scheduled.'));
    exit;
}

if (!findAvailableSlot($conn, (int)$session['volunteer_id'], $date, $time)) {
    header('Location: schedule.php?session_id=' . $sessionId . '&err=' . urlencode('That time is no longer available. Please choose another.'));
    exit;
}

$conn->begin_transaction();
try {
    requestSessionTime($conn, $sessionId, $date, $time, $mode);
    $when = date('D, j M Y', strtotime($date)) . ' at ' . formatTime($time);
    $message = $session['volunteer_id'] == $myId
        ? "You picked $when for the {$session['category']} session. Please accept or reject it in Session Request."
        : currentUser()['name'] . " requested a {$session['category']} session on $when. Please accept or reject it in Session Request.";
    addNotification($conn, (int)$session['volunteer_id'], $message, 'Session Requested');
    $conn->commit();
    header('Location: schedule_list.php?msg=' . urlencode('Session time sent. It is now Pending until the volunteer accepts it.'));
} catch (Exception $e) {
    $conn->rollback();
    header('Location: schedule.php?session_id=' . $sessionId . '&err=' . urlencode('Something went wrong. Please try again.'));
}
exit;
