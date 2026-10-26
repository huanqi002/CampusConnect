<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';
require __DIR__ . '/../general/card_template.php';
require __DIR__ . '/../general/format.php';
require __DIR__ . '/functions.php';
require __DIR__ . '/result_modal.php';
require __DIR__ . '/session_card.php';
require __DIR__ . '/calendar_functions.php';

requireLogin();

$myId = currentUser()['id'];
$sessions = fetchActiveSessions($conn, $myId, currentUser()['role']);

$results = [
    'completed' => ['check', 'Marked as Completed', 'The session has been successfully marked as completed.', 'success'],
    'cancelled' => ['cancelled', 'Cancelled', 'Your session has been cancelled. If you would like to continue, please create a new support request.', 'danger'],
    'updated' => ['scheduled', 'Session Updated', 'Your changes have been saved. The other participant has been notified.', 'success'],
];
$result = $results[$_GET['done'] ?? ''] ?? null;

// The Google Calendar panel is for developers only ($DEV_TOOLS, set in config.local.php); regular users never see it.
// Only the organizer account ever connects; everyone else gets calendar invites automatically.
$calendarDev = !empty($DEV_TOOLS);
$calendarReady = $calendarDev && googleCalendarConfig() !== null && (int) $myId === calendarOrganizerId();
$calendarConnected = $calendarReady && isCalendarConnected($conn, (int) $myId);
$calendarToday = $calendarReady && wasCalendarConnectedToday($conn, (int) $myId);   // connected today = working, so Connect is disabled
$calendarHint = $calendarDev ? calendarSetupHint($conn, (int) $myId) : null;       // what is missing, e.g. no google_config.php

include __DIR__ . '/../general/header.php';
?>
<link rel="stylesheet" href="../../frontend/css/session.css?v=<?php echo filemtime(__DIR__ . '/../../frontend/css/session.css'); ?>">

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

<?php if ($calendarHint): ?>
    <p
        style="margin:0 0 14px;padding:9px 14px;border:1px solid #d6e2f3;border-radius:10px;background:#eef3fb;color:#34507a;font-size:12.5px">
        <?php echo htmlspecialchars($calendarHint); ?></p>
<?php endif; ?>

<?php if ($calendarReady): ?>
    <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;margin-bottom:14px;font-size:13px">
        <?php if ($calendarConnected): ?>
            <span><?php echo $calendarToday ? 'Google Calendar is working (connected today)' : 'Google Calendar was connected earlier. Connect again to refresh it.'; ?></span>
            <form method="post" action="calendar_disconnect.php" style="margin:0">
                <button type="submit" class="btn btn-plain">Disconnect</button>
            </form>
        <?php endif; ?>
        <?php if ($calendarToday): ?>
            <span title="Already connected today. This button is available again tomorrow." style="cursor:not-allowed">
                <span class="btn btn-plain" aria-disabled="true" style="opacity:.45;pointer-events:none">Connect Google
                    Calendar</span>
            </span>
        <?php else: ?>
            <a class="btn btn-plain" href="calendar_connect.php">Connect Google Calendar</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($sessions->num_rows === 0): ?>
    <p class="empty-state">You have no active sessions. Completed and cancelled sessions are in History.</p>
<?php endif; ?>

<div class="card-list">
    <?php while ($s = $sessions->fetch_assoc()):
        $id = (int) $s['id'];
        $isStudent = ($s['user_id'] == $myId);
        $showFinish = !$isStudent && $s['status'] === 'Scheduled';

        $actions = '';
        if ($s['status'] === 'Pending') {
            $actions .= cardIconLink("edit_session.php?session_id=$id", 'edit', 'Edit session');
        }
        if ($showFinish && hasSessionEnded($s)) {
            $actions .= cardIconForm('mark_completed.php', ['session_id' => $id], 'complete', 'Mark as complete');
        } elseif ($showFinish) {
            // Shown but not clickable; the wrapper carries the hover tooltip.
            $actions .= '<span title="Tick will be enabled after the event ends" style="cursor:not-allowed">'
                . '<span class="icon-btn" aria-disabled="true" style="opacity:.35;pointer-events:none;color:#2e7d32">'
                . cardIcon('complete') . '</span></span>';
        }
        $actions .= cardIconForm('cancel_session.php', ['session_id' => $id], 'cancel', 'Cancel session');

        echo renderCard([
            'href' => "session_details.php?session_id=$id",
            'textHtml' => sessionCardText($s, (int) $myId),
            'status' => sessionDisplayStatus($s),
            'actions' => $actions,
        ]);
    endwhile; ?>
</div>

<?php include __DIR__ . '/../general/footer.php'; ?>