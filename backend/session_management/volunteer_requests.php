<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';
require __DIR__ . '/../general/card_template.php';
require __DIR__ . '/../general/format.php';
require __DIR__ . '/functions.php';
require __DIR__ . '/result_modal.php';

requireLogin();

if (currentUser()['role'] !== 'volunteer') {
    header('Location: index.php?err=' . urlencode('Only volunteers can view session requests.'));
    exit;
}

$myId     = currentUser()['id'];
$requests = fetchSessionRequests($conn, $myId);

$results = [
    'accepted' => ['check', 'Accepted', 'You have accepted the session.', 'success'],
    'rejected' => ['rejected', 'Rejected', 'You have rejected the session.', 'danger'],
];
$result = $results[$_GET['done'] ?? ''] ?? null;

include __DIR__ . '/../general/header.php';
?>
<link rel="stylesheet" href="../../frontend/css/session.css">

<?php if ($result) {
    echo renderResultModal(...$result);
}
echo renderConfirmModal(); ?>

<?php if (isset($_GET['msg'])): ?>
<div class="message message-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
<?php endif; ?>
<?php if (isset($_GET['err'])): ?>
<div class="message message-error"><?php echo htmlspecialchars($_GET['err']); ?></div>
<?php endif; ?>

<?php if ($requests->num_rows === 0): ?>
<p class="empty-state">No sessions waiting for you to accept.</p>
<?php endif; ?>

<div class="card-list">
<?php while ($s = $requests->fetch_assoc()):
    $id = (int)$s['id'];

    echo renderCard([
        'href'    => "session_request_details.php?session_id=$id",
        'title'   => sessionPartnerLabel($s, (int)$myId),
        'meta'    => [[$s['category'], $s['support_mode']], date('D, j M Y', strtotime($s['session_date'])) . ' · ' . formatTimeRange($s['start_time'], $s['end_time'])],
        'actions' => cardIconForm('accept_session.php', ['session_id' => $id], 'complete', 'Accept session')
                   . cardIconForm('reject_session.php', ['session_id' => $id], 'cancel', 'Reject session'),
    ]);
endwhile; ?>
</div>

<?php include __DIR__ . '/../general/footer.php'; ?>
