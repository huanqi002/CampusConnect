<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';
require __DIR__ . '/../general/notifications.php';
require __DIR__ . '/../general/format.php';
require __DIR__ . '/functions.php';

requireLogin();

$sessionId = (int)($_POST['session_id'] ?? 0);
$me        = currentUser();
$session   = findSessionRequest($conn, $sessionId, (int)$me['id']);

if (!$session) {
    header('Location: volunteer_requests.php?err=' . urlencode('This session request cannot be accepted.'));
    exit;
}

$conn->begin_transaction();
try {
    acceptSessionRequest($conn, $sessionId);
    $when = date('D, j M Y', strtotime($session['session_date'])) . ' at ' . formatTime($session['start_time']);
    addNotification($conn, (int)$session['user_id'],
        "{$me['name']} accepted your {$session['category']} session on $when.", 'Session Scheduled');
    $conn->commit();
    header('Location: volunteer_requests.php?msg=' . urlencode('Session accepted. It is now Scheduled.'));
} catch (Exception $e) {
    $conn->rollback();
    header('Location: volunteer_requests.php?err=' . urlencode('Something went wrong. Please try again.'));
}
exit;
