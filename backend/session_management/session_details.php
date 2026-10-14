<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';
require __DIR__ . '/../general/card_template.php';
require __DIR__ . '/../general/format.php';
require __DIR__ . '/functions.php';

requireLogin();

$myId      = currentUser()['id'];
$sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
$session   = findSessionById($conn, $sessionId);

if (!$session || !isMySession($session, (int)$myId, currentUser()['role'])) {
    header('Location: index.php?err=' . urlencode('Session not found.'));
    exit;
}

$status = sessionDisplayStatus($session);

include __DIR__ . '/../general/header.php';
?>
<link rel="stylesheet" href="../../frontend/css/session.css">

<div class="detail-card">
    <span class="pill pill-<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></span>

    <?php echo renderDetails(sessionDetailRows($session)); ?>

    <div class="detail-actions">
        <?php if ($session['status'] === 'Unscheduled'): ?>
        <a class="btn btn-primary" href="schedule.php?session_id=<?php echo (int)$session['id']; ?>">Schedule Now</a>
        <?php endif; ?>
        <a class="btn btn-plain" href="index.php"><?php echo cardIcon('back'); ?>Back</a>
    </div>
</div>

<?php include __DIR__ . '/../general/footer.php'; ?>
