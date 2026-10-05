<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/functions.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ../user_management/select_user.php');
    exit;
}

$sessionId = (int)($_POST['session_id'] ?? 0);
$rating    = (int)($_POST['rating'] ?? 0);
$comments  = trim($_POST['comments'] ?? '');

if ($rating < 1 || $rating > 5) {
    header('Location: feedback.php?session_id=' . $sessionId . '&err=' . urlencode('Please choose a rating from 1 to 5.'));
    exit;
}

$session = findSessionForFeedback($conn, $sessionId);

if (!$session || $session['user_id'] != $_SESSION['user_id']) {
    header('Location: history.php?err=' . urlencode('Only the student can leave feedback.'));
    exit;
}
if ($session['status'] !== 'Completed') {
    header('Location: feedback.php?session_id=' . $sessionId . '&err=' . urlencode('You can only rate a session after it is completed.'));
    exit;
}

if (hasFeedback($conn, $sessionId)) {
    header('Location: history.php?err=' . urlencode('Feedback has already been submitted for this session.'));
    exit;
}

$conn->begin_transaction();
try {
    $feedbackId = insertFeedback($conn, $session, $rating, $comments);
    attachFeedbackToHistory($conn, $sessionId, $feedbackId);
    $conn->commit();
    header('Location: history.php?msg=' . urlencode('Thank you! Your feedback has been saved.'));
} catch (Exception $e) {
    $conn->rollback();
    header('Location: feedback.php?session_id=' . $sessionId . '&err=' . urlencode('Something went wrong. Please try again.'));
}
exit;
