<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';
require __DIR__ . '/../general/card_template.php';
require __DIR__ . '/../general/format.php';
require __DIR__ . '/../session_management/booking_parts.php';
require __DIR__ . '/functions.php';

requireLogin();

$myId      = (int)currentUser()['id'];
$sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
$entry     = findHistoryEntry($conn, $sessionId);

if (!$entry || ($entry['user_id'] != $myId && $entry['volunteer_id'] != $myId)) {
    header('Location: history.php?err=' . urlencode('History entry not found.'));
    exit;
}

$entry['id'] = $entry['session_id'];   // sessionOverviewHtml() expects the session id as 'id'
$status      = $entry['final_status'];
$isCompleted = $status === 'Completed';
$isStudent   = (int)$entry['user_id'] === $myId;
$other       = htmlspecialchars($isStudent ? $entry['volunteer_name'] : $entry['student_name']);
$endedOn     = htmlspecialchars(formatDateTime($entry['completion_time']));

// Outcome note under the topic, in the same style as the Session Details status note.
$noteText = match ($status) {
    'Completed' => 'Completed with <strong>' . $other . '</strong> on ' . $endedOn . '.',
    'Rejected'  => 'Rejected on ' . $endedOn . '.',
    default     => 'Cancelled on ' . $endedOn . '.',
};
if (!$isCompleted && $entry['cancellation_reason'] !== null && $entry['cancellation_reason'] !== '') {
    $noteText .= ' Reason: ' . htmlspecialchars($entry['cancellation_reason']);
}
$outcomeNote = '<p class="status-note status-note-' . htmlspecialchars($status) . '">'
             . SESSION_CARD_ICONS[$isCompleted ? 'check' : 'clock'] . '<span>' . $noteText . '</span></p>';

// The overview ends with the When section; the outcome note belongs with the topic, before it.
$overview = sessionOverviewHtml($entry, $status, $myId);
$overview = str_replace('</section><section class="view-when">', $outcomeNote . '</section><section class="view-when">', $overview);

include __DIR__ . '/../general/header.php';
?>
<link rel="stylesheet" href="../../frontend/css/session.css">
<link rel="stylesheet" href="../../frontend/css/history.css">

<div class="schedule-card session-view history-view">
    <?php echo $overview; ?>

    <?php if ($isCompleted): ?>
    <section class="view-when">
        <h3 class="view-label">Feedback</h3>
        <?php if ($entry['rating'] !== null):
            $rating = (int)$entry['rating']; ?>
        <div class="feedback-card">
            <div class="feedback-score" aria-label="Rated <?php echo $rating; ?> out of 5">
                <span class="card-stars" aria-hidden="true"><?php
                    echo str_repeat('<span class="is-on">&#9733;</span>', $rating) . str_repeat('<span>&#9733;</span>', 5 - $rating);
                ?></span>
                <strong><?php echo $rating; ?>/5</strong>
            </div>
            <?php if ($entry['comments']): ?>
            <p class="feedback-comment"><?php echo nl2br(htmlspecialchars($entry['comments'])); ?></p>
            <?php else: ?>
            <p class="feedback-comment is-empty">No comments were left.</p>
            <?php endif; ?>
            <?php if ($entry['submitted_at']): ?>
            <p class="feedback-meta">Rated by <?php echo htmlspecialchars($entry['student_name']); ?> on <?php echo htmlspecialchars(formatDateTime($entry['submitted_at'])); ?></p>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <p class="feedback-empty"><?php echo canRate($entry, $myId)
            ? 'You have not rated this session yet. Let ' . $other . ' know how it went.'
            : 'The student has not rated this session yet.'; ?></p>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <div class="schedule-actions">
        <a class="btn btn-plain" href="history.php"><?php echo cardIcon('back'); ?>Back to History</a>
        <?php if (canRate($entry, $myId)): ?>
        <a class="btn btn-rate" href="feedback.php?session_id=<?php echo (int)$entry['session_id']; ?>"><span aria-hidden="true">&#9733;</span>Rate Now</a>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../general/footer.php'; ?>
