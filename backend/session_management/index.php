<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';
require __DIR__ . '/../general/card_template.php';
require __DIR__ . '/functions.php';

requireLogin();

$myId     = currentUser()['id'];
$sessions = fetchActiveSessions($conn, $myId);

include __DIR__ . '/../general/header.php';
?>

<h1 class="page-title">Session Dashboard</h1>

<?php if (isset($_GET['msg'])): ?>
<div class="message message-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
<?php endif; ?>
<?php if (isset($_GET['err'])): ?>
<div class="message message-error"><?php echo htmlspecialchars($_GET['err']); ?></div>
<?php endif; ?>

<?php if ($sessions->num_rows === 0): ?>
<p class="empty-state">You have no active sessions. Completed and cancelled sessions are in History.</p>
<?php endif; ?>

<div class="card-list">
<?php while ($s = $sessions->fetch_assoc()):
    $id      = (int)$s['id'];
    $actions = '';
    if ($s['status'] === 'Pending') {
        $actions .= cardIconLink("edit_session.php?session_id=$id", 'edit', 'Edit category and description');
    }
    if ($s['volunteer_id'] == $myId && $s['status'] === 'Scheduled' && hasSessionStarted($s)) {
        $actions .= cardIconForm('mark_completed.php', ['session_id' => $id], 'complete', 'Mark as complete', 'Mark this session as complete?');
    }
    $actions .= cardIconForm('cancel_session.php', ['session_id' => $id], 'cancel', 'Cancel session', 'Cancel this session? The other participant will be notified.');

    echo renderCard([
        'href'    => "session_details.php?session_id=$id",
        'fields'  => ['Category' => $s['category'], 'Description' => $s['description']],
        'status'  => sessionDisplayStatus($s),
        'actions' => $actions,
    ]);
endwhile; ?>
</div>

<?php include __DIR__ . '/../general/footer.php'; ?>
