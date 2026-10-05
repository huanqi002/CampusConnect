<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';
require __DIR__ . '/../general/notifications.php';
require __DIR__ . '/../general/format.php';

requireLogin();

$myId          = currentUser()['id'];
$notifications = listNotifications($conn, $myId);
markAllNotificationsRead($conn, $myId);

include __DIR__ . '/../general/header.php';
?>

<h1>Notifications</h1>

<?php if ($notifications->num_rows === 0): ?>
<p class="empty-state">You have no notifications.</p>
<?php endif; ?>

<?php while ($n = $notifications->fetch_assoc()): ?>
<div class="card<?php echo $n['is_read'] === 'N' ? ' card-unread' : ''; ?>">
    <p class="meta"><?php echo htmlspecialchars($n['notice_type']); ?> &middot;
       <?php echo htmlspecialchars(formatDateTime($n['created_at'])); ?></p>
    <p><?php echo htmlspecialchars($n['message']); ?></p>
</div>
<?php endwhile; ?>

<?php include __DIR__ . '/../general/footer.php'; ?>
