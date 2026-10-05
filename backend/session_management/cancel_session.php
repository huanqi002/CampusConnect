<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';
require __DIR__ . '/../general/notifications.php';
require __DIR__ . '/functions.php';
require __DIR__ . '/../history_management/functions.php';

requireLogin();

$sessionId = (int)($_POST['session_id'] ?? 0);
$me        = currentUser();

$session = findActiveSession($conn, $sessionId);

if (!$session || ($session['user_id'] != $me['id'] && $session['volunteer_id'] != $me['id'])) {
    header('Location: index.php?err=' . urlencode('This session cannot be cancelled.'));
    exit;
}

$conn->begin_transaction();
try {
    updateSessionStatus($conn, $sessionId, 'Cancelled');
    recordHistory($conn, $session, 'Cancelled');

    $category = $session['category'];
    $participants = [
        (int)$session['user_id']      => $session['volunteer_name'],
        (int)$session['volunteer_id'] => $session['student_name'],
    ];
    foreach ($participants as $userId => $otherName) {
        $message = $userId == $me['id']
            ? "You cancelled your $category session with $otherName."
            : "{$me['name']} cancelled your $category session.";
        addNotification($conn, $userId, $message, 'Session Cancelled');
    }

    $conn->commit();
    header('Location: index.php?msg=' . urlencode('Session cancelled. The student and volunteer have been notified.'));
} catch (Exception $e) {
    $conn->rollback();
    header('Location: index.php?err=' . urlencode('Something went wrong. Please try again.'));
}
exit;
