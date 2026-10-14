<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';
require __DIR__ . '/../general/card_template.php';
require __DIR__ . '/../general/format.php';
require __DIR__ . '/functions.php';
require __DIR__ . '/result_modal.php';

requireLogin();

$me        = currentUser();
$sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
$session   = $me['role'] === 'volunteer' ? findSessionRequest($conn, $sessionId, (int)$me['id']) : null;

if (!$session) {
    header('Location: volunteer_requests.php?err=' . urlencode('This session request is no longer waiting for you.'));
    exit;
}

include __DIR__ . '/../general/header.php';
?>
<link rel="stylesheet" href="../../frontend/css/session.css">

<?php echo renderConfirmModal(); ?>

<div class="detail-card">
    <span class="pill pill-Pending">Pending</span>

    <?php echo renderDetails(sessionDetailRows($session)); ?>

    <div class="detail-actions">
        <form method="post" action="accept_session.php">
            <input type="hidden" name="session_id" value="<?php echo (int)$session['id']; ?>">
            <button type="submit" class="btn btn-success"><?php echo cardIcon('complete'); ?>Accept</button>
        </form>
        <form method="post" action="reject_session.php">
            <input type="hidden" name="session_id" value="<?php echo (int)$session['id']; ?>">
            <button type="submit" class="btn btn-danger"><?php echo cardIcon('cancel'); ?>Reject</button>
        </form>
        <a class="btn btn-plain" href="volunteer_requests.php"><?php echo cardIcon('back'); ?>Back</a>
    </div>
</div>

<?php include __DIR__ . '/../general/footer.php'; ?>
