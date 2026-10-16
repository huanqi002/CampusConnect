<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';
require __DIR__ . '/../general/notifications.php';
require __DIR__ . '/../general/format.php';
require __DIR__ . '/summary.php';
require __DIR__ . '/types.php';

requireLogin();

$myId          = currentUser()['id'];
$notifications = listNotifications($conn, $myId)->fetch_all(MYSQLI_ASSOC);
markAllNotificationsRead($conn, $myId);   // the rows above were read first, so this visit still shows which were new

$unread = count(array_filter($notifications, static fn(array $n): bool => $n['is_read'] === 'N'));

// Group by day, newest first (listNotifications already sorts newest first).
$groups = [];
foreach ($notifications as $n) {
    $groups[noticeDayLabel($n['created_at'])][] = $n;
}

include __DIR__ . '/../general/header.php';
echo renderNotificationSummary($conn, (int)$myId, currentUser()['role']);
?>
<style>
.notice-head { display: flex; align-items: center; justify-content: space-between; margin: 0 0 14px; }
.notice-head h2 { margin: 0; color: #1f2933; font-size: 17px; }
.notice-new { padding: 4px 12px; border-radius: 999px; background: #fdecec; color: #c41225; font-size: 12.5px; font-weight: 700; }
.notice-day { margin: 22px 0 10px; color: #6b7480; font-size: 12px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; }
.notice-day:first-of-type { margin-top: 0; }
.notice-list { display: flex; flex-direction: column; gap: 10px; }
.notice { position: relative; display: flex; align-items: flex-start; gap: 14px; padding: 14px 18px; border: 1px solid #e9e4e3; border-radius: 14px; background: #fff; box-shadow: 0 1px 6px rgba(0, 0, 0, 0.04); }
.notice.is-new { border-color: #f3c6cb; background: linear-gradient(90deg, #fff6f7, #fff 45%); }
.notice.is-new::before { content: ""; position: absolute; top: 18px; right: 18px; width: 9px; height: 9px; border-radius: 50%; background: #e21b2d; }
.notice-icon { flex: none; display: grid; place-items: center; width: 42px; height: 42px; border-radius: 12px; }
.notice-icon svg { width: 21px; height: 21px; fill: none; stroke: currentColor; stroke-width: 1.9; stroke-linecap: round; stroke-linejoin: round; }
.notice-blue .notice-icon   { background: #e5f0fb; color: #1565c0; }
.notice-green .notice-icon  { background: #e6f6ea; color: #2e7d32; }
.notice-orange .notice-icon { background: #fff1de; color: #c76a00; }
.notice-red .notice-icon    { background: #fdecec; color: #c62828; }
.notice-purple .notice-icon { background: #efe9fb; color: #5e35b1; }
.notice-grey .notice-icon   { background: #f1f3f4; color: #5f6b76; }
.notice-body { flex: 1; min-width: 0; padding-right: 18px; }
.notice-meta { display: flex; align-items: baseline; gap: 10px; }
.notice-type { color: #1f2933; font-size: 13.5px; font-weight: 700; }
.notice-time { color: #8a9199; font-size: 12px; }
.notice-text { margin: 3px 0 0; color: #3b434b; font-size: 14px; line-height: 1.5; overflow-wrap: anywhere; }
.notice-empty { display: grid; justify-items: center; gap: 10px; padding: 48px 20px; border: 1px dashed #d9d3d2; border-radius: 16px; background: #fff; color: #6b7480; text-align: center; }
.notice-empty svg { width: 44px; height: 44px; fill: none; stroke: #b9bfc6; stroke-width: 1.6; stroke-linecap: round; stroke-linejoin: round; }
.notice-empty strong { color: #1f2933; font-size: 16px; }
</style>

<?php if ($notifications === []): ?>
<div class="notice-empty">
    <svg viewBox="0 0 24 24" aria-hidden="true"><?php echo NOTICE_ICONS['bell']; ?></svg>
    <strong>You're all caught up</strong>
    <span>New activity will show up here.</span>
</div>
<?php else: ?>
<div class="notice-head">
    <h2>Recent activity</h2>
    <?php if ($unread > 0): ?><span class="notice-new"><?php echo $unread; ?> new</span><?php endif; ?>
</div>

<?php foreach ($groups as $day => $items): ?>
<h3 class="notice-day"><?php echo htmlspecialchars($day); ?></h3>
<div class="notice-list">
    <?php foreach ($items as $n): [$iconPaths, $tone] = noticeStyle($n['notice_type']); ?>
    <article class="notice notice-<?php echo $tone; ?><?php echo $n['is_read'] === 'N' ? ' is-new' : ''; ?>">
        <span class="notice-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><?php echo $iconPaths; ?></svg></span>
        <div class="notice-body">
            <div class="notice-meta">
                <span class="notice-type"><?php echo htmlspecialchars($n['notice_type']); ?></span>
                <time class="notice-time" datetime="<?php echo htmlspecialchars($n['created_at']); ?>"
                      title="<?php echo htmlspecialchars(formatDateTime($n['created_at'])); ?>"><?php echo htmlspecialchars(noticeTime($n['created_at'])); ?></time>
            </div>
            <p class="notice-text"><?php echo htmlspecialchars($n['message']); ?></p>
        </div>
    </article>
    <?php endforeach; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php include __DIR__ . '/../general/footer.php'; ?>
