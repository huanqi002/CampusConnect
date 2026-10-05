<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';
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

$session = findSessionWithStatus($conn, $sessionId, 'Pending');

if (!$session || ($session['user_id'] != $myId && $session['volunteer_id'] != $myId)) {
    header('Location: schedule_list.php?err=' . urlencode('This session is not ready to be scheduled.'));
    exit;
}

if (!findAvailableSlot($conn, (int)$session['volunteer_id'], $date, $time)) {
    header('Location: schedule.php?session_id=' . $sessionId . '&err=' . urlencode('That time is no longer available. Please choose another.'));
    exit;
}

scheduleSession($conn, $sessionId, $date, $time, $mode);
header('Location: schedule_list.php?msg=' . urlencode('Session booked successfully.'));
exit;
