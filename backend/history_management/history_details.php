<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';
require __DIR__ . '/../general/card_template.php';
require __DIR__ . '/../general/format.php';
require __DIR__ . '/functions.php';

requireLogin();

$myId      = currentUser()['id'];
$sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
$entry     = findHistoryEntry($conn, $sessionId);

if (!$entry || ($entry['user_id'] != $myId && $entry['volunteer_id'] != $myId)) {
    header('Location: history.php?err=' . urlencode('History entry not found.'));
    exit;
}

$isCompleted = $entry['final_status'] === 'Completed';

$time = $entry['start_time'] !== null ? formatTimeRange($entry['start_time'], $entry['end_time']) : null;

$details = [
    'Category'     => $entry['category'],
    'Description'  => $entry['description'],
    'Student'      => $entry['student_name'],
    'Volunteer'    => $entry['volunteer_name'],
    'Date'         => $entry['session_date'] ? date('l, j F Y', strtotime($entry['session_date'])) : 'Not booked',
    'Time'         => $time ?? 'Not booked',
    'Support mode' => $entry['support_mode'],
    ($isCompleted ? 'Completed on' : 'Cancelled on') => formatDateTime($entry['completion_time']),
];
if (!$isCompleted && $entry['cancellation_reason'] !== null) {
    $details['Cancellation reason'] = $entry['cancellation_reason'];
}
if ($isCompleted) {
    $details['Rating']   = $entry['rating'] !== null ? $entry['rating'] . ' / 5' : 'Not rated yet';
    $details['Comments'] = $entry['comments'] ?: '-';
}

include __DIR__ . '/../general/header.php';
?>

<h1 class="page-title">History Details</h1>

<div class="detail-card">
    <span class="pill pill-<?php echo htmlspecialchars($entry['final_status']); ?>"><?php echo htmlspecialchars($entry['final_status']); ?></span>

    <?php echo renderDetails($details); ?>

    <div class="btn-row">
        <?php if (canRate($entry, $myId)): ?>
        <a class="btn btn-primary" href="feedback.php?session_id=<?php echo (int)$entry['session_id']; ?>">Rate this session</a>
        <?php endif; ?>
        <a class="btn btn-plain" href="history.php">Back to history</a>
    </div>
</div>

<?php include __DIR__ . '/../general/footer.php'; ?>
