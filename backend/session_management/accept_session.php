<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';
require __DIR__ . '/../general/notifications.php';
require __DIR__ . '/../general/format.php';
require __DIR__ . '/functions.php';
require __DIR__ . '/calendar_functions.php';

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
        "{$me['name']} accepted your {$session['category']} session on $when.", 'Session Scheduled', $sessionId);
    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    header('Location: volunteer_requests.php?err=' . urlencode('Something went wrong. Please try again.'));
    exit;
}

// After the commit, so a slow or failing Google call can never undo the acceptance.
$eventLink = syncCalendarOnAccept($conn, $session);
if ($eventLink !== null) {
    // Handed to volunteer_requests.php through the session, so the link cannot be tampered with in the URL.
    $_SESSION['calendar_added'] = [
        'link' => $eventLink,
        'when' => date('D, j M Y', strtotime($session['session_date'])) . ' · ' . formatTimeRange($session['start_time'], $session['end_time']),
    ];
}
header('Location: volunteer_requests.php?done=accepted' . ($eventLink !== null ? '&cal=1' : ''));
exit;
