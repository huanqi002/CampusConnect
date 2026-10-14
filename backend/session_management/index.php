<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';
require __DIR__ . '/../general/card_template.php';
require __DIR__ . '/../general/format.php';
require __DIR__ . '/functions.php';
require __DIR__ . '/result_modal.php';

requireLogin();

$myId     = currentUser()['id'];
$sessions = fetchActiveSessions($conn, $myId, currentUser()['role']);

$results = [
    'completed' => ['check', 'Marked as Completed', 'The session has been successfully marked as completed.', 'success'],
    'cancelled' => ['cancelled', 'Cancelled', 'Your session has been cancelled. If you would like to continue, please create a new support request.', 'danger'],
    'updated'   => ['scheduled', 'Session Updated', 'Your changes have been saved. The other participant has been notified.', 'success'],
];
$result = $results[$_GET['done'] ?? ''] ?? null;

include __DIR__ . '/../general/header.php';
?>
<link rel="stylesheet" href="../../frontend/css/session.css">

<?php if (isset($_GET['msg'])): ?>
<div class="message message-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
<?php endif; ?>
<?php if (isset($_GET['err'])): ?>
<div class="message message-error"><?php echo htmlspecialchars($_GET['err']); ?></div>
<?php endif; ?>

<?php if ($result) {
    echo renderResultModal(...$result);
}
echo renderConfirmModal(); ?>

<?php if ($sessions->num_rows === 0): ?>
<p class="empty-state">You have no active sessions. Completed and cancelled sessions are in History.</p>
<?php endif; ?>

<div class="card-list">
<?php while ($s = $sessions->fetch_assoc()):
    $id        = (int)$s['id'];
    $isStudent = ($s['user_id'] == $myId);
    $canFinish = !$isStudent && $s['status'] === 'Scheduled' && hasSessionStarted($s);
    $when      = date('D, j M Y', strtotime($s['session_date'])) . ' · ' . formatTimeRange($s['start_time'], $s['end_time']);

    $actions = '';
    if ($s['status'] === 'Pending') {
        $actions .= cardIconLink("edit_session.php?session_id=$id", 'edit', 'Edit session');
    }
    if ($canFinish) {
        $actions .= cardIconForm('mark_completed.php', ['session_id' => $id], 'complete', 'Mark as complete');
    }
    $actions .= cardIconForm('cancel_session.php', ['session_id' => $id], 'cancel', 'Cancel session');

    echo renderCard([
        'href'    => "session_details.php?session_id=$id",
        'title'   => sessionPartnerLabel($s, (int)$myId),
        'meta'    => [[$s['category'], $s['support_mode']], $when],
        'status'  => sessionDisplayStatus($s),
        'actions' => $actions,
    ]);
endwhile; ?>
</div>

<?php include __DIR__ . '/../general/footer.php'; ?>
