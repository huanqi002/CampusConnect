<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';
require __DIR__ . '/../general/card_template.php';
require __DIR__ . '/functions.php';
require __DIR__ . '/result_modal.php';

requireLogin();

$myId     = currentUser()['id'];
$sessions = fetchUnscheduledSessions($conn, $myId, currentUser()['role']);

include __DIR__ . '/../general/header.php';
?>
<link rel="stylesheet" href="../../frontend/css/session.css">

<?php if (isset($_GET['msg'])): ?>
<div class="message message-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
<?php endif; ?>
<?php if (isset($_GET['err'])): ?>
<div class="message message-error"><?php echo htmlspecialchars($_GET['err']); ?></div>
<?php endif; ?>

<?php if ($sessions->num_rows === 0): ?>
<p class="empty-state">No sessions waiting to be scheduled.</p>
<?php endif; ?>

<?php if (isset($_GET['scheduled'])) {
    echo renderResultModal('scheduled', 'Scheduled', 'The session has been scheduled successfully.');
} ?>

<div class="card-list schedule-list">
<?php while ($s = $sessions->fetch_assoc()):
    $id = (int)$s['id'];

    echo renderCard([
        'title'   => sessionPartnerLabel($s, (int)$myId),
        'meta'    => [[$s['category'], $s['support_mode']], 'Not scheduled yet'],
        'actions' => '<a class="btn btn-schedule" href="schedule.php?session_id=' . $id . '">Schedule Now</a>',
    ]);
endwhile; ?>
</div>

<?php include __DIR__ . '/../general/footer.php'; ?>
