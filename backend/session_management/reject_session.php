<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';
require __DIR__ . '/../general/notifications.php';
require __DIR__ . '/../general/format.php';
require __DIR__ . '/functions.php';
require __DIR__ . '/../history_management/functions.php';

requireLogin();

$sessionId = (int)($_POST['session_id'] ?? 0);
$me        = currentUser();
$session   = findSessionRequest($conn, $sessionId, (int)$me['id']);

if (!$session) {
    header('Location: volunteer_requests.php?err=' . urlencode('This session request cannot be rejected.'));
    exit;
}

$conn->begin_transaction();
try {
    rejectSessionRequest($conn, $sessionId);
    recordHistory($conn, $session, 'Rejected');
    $when = date('D, j M Y', strtotime($session['session_date'])) . ' at ' . formatTime($session['start_time']);
    addNotification($conn, (int)$session['user_id'],
        "{$me['name']} rejected your {$session['category']} session on $when.", 'Session Rejected');
    $conn->commit();
    header('Location: volunteer_requests.php?msg=' . urlencode('Session rejected. The student has been notified.'));
} catch (Exception $e) {
    $conn->rollback();
    header('Location: volunteer_requests.php?err=' . urlencode('Something went wrong. Please try again.'));
}
exit;
