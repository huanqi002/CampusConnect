<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';
require __DIR__ . '/../general/notifications.php';
require __DIR__ . '/../general/format.php';
require __DIR__ . '/summary.php';
require __DIR__ . '/types.php';

requireLogin();

$currentUser = currentUser();
$myId = (int) $currentUser['id'];
$role = $currentUser['role'];
$notifications = listNotifications($conn, $myId, $role)->fetch_all(MYSQLI_ASSOC);
foreach ($notifications as &$n) {
    [$n['message'], $n['session_id']] = splitNotificationMessage($n['message']);   // hidden [session:12] tag
}
unset($n);
markAllNotificationsRead($conn, $myId, $role);   // Keep hidden other-role notices unread for that role's view.

// Where each notice leads: the session's details when it is linked to one, else the closest list page.
$sessionIds = array_values(array_unique(array_filter(array_map(static fn(array $n): int => (int)($n['session_id'] ?? 0), $notifications))));
$linkedSessions = [];
if ($sessionIds !== []) {
    $stmt = $conn->prepare('SELECT id, status, user_id, volunteer_id FROM sessions WHERE id IN (' . implode(',', array_fill(0, count($sessionIds), '?')) . ')');
    $stmt->bind_param(str_repeat('i', count($sessionIds)), ...$sessionIds);
    $stmt->execute();
    foreach ($stmt->get_result() as $row) {
        $linkedSessions[(int)$row['id']] = $row;
    }
}

function noticeHref(array $n, array $linkedSessions, int $myId, ?string $role): ?string
{
    $session = $linkedSessions[(int)($n['session_id'] ?? 0)] ?? null;
    // Detail pages only open a session from the side of the current role (student or volunteer),
    // so a notice about a session from the other role stays unlinked instead of leading to "not found".
    if ($session && (int)$session[$role === 'volunteer' ? 'volunteer_id' : 'user_id'] !== $myId) {
        return null;
    }
    if ($session) {
        $id = (int)$session['id'];
        if (in_array($session['status'], ['Completed', 'Cancelled', 'Rejected'], true)) {
            return "../history_management/history_details.php?session_id=$id";
        }
        if ($session['status'] === 'Pending' && $role === 'volunteer' && (int)$session['volunteer_id'] === $myId) {
            return "../session_management/session_request_details.php?session_id=$id";
        }
        return "../session_management/session_details.php?session_id=$id";
    }

    // Older notices without a session: open the page where that item now lives.
    return match ($n['notice_type']) {
        'Pending Request'                       => $role === 'volunteer' ? '../request_management/upcoming_request.php' : null,
        'Request Response', 'Status Update'     => $role === 'student' ? '../request_management/request.php' : null,
        'Session Requested', 'Session Updated'  => $role === 'volunteer' ? '../session_management/volunteer_requests.php' : '../session_management/index.php',
        'Session Scheduled'                     => '../session_management/index.php',
        'Session Cancelled', 'Session Rejected',
        'Session Completed', 'Feedback Received', 'Feedback Reminder' => '../history_management/history.php',
        default                                 => null,
    };
}

$unread = count(array_filter($notifications, static fn(array $n): bool => $n['is_read'] === 'N'));

// Group by day, newest first (listNotifications already sorts newest first).
$groups = [];
foreach ($notifications as $n) {
    $groups[noticeDayLabel($n['created_at'])][] = $n;
}

include __DIR__ . '/../general/header.php';
echo renderNotificationSummary($conn, (int) $myId, currentUser()['role']);
?>
<style>
    .notice-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin: 0 0 14px;
    }

    .notice-head h2 {
        margin: 0;
        color: #1f2933;
        font-size: 17px;
    }

    .notice-new {
        padding: 4px 12px;
        border-radius: 999px;
        background: #fdecec;
        color: #c41225;
        font-size: 12.5px;
        font-weight: 700;
    }

    .notice-day {
        margin: 22px 0 10px;
        color: #6b7480;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .notice-day:first-of-type {
        margin-top: 0;
    }

    .notice-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .notice {
        position: relative;
        display: flex;
        align-items: flex-start;
        gap: 14px;
        padding: 14px 18px;
        border: 1px solid #e9e4e3;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 1px 6px rgba(0, 0, 0, 0.04);
    }

    .notice.is-new {
        border-color: #f3c6cb;
        background: linear-gradient(90deg, #fff6f7, #fff 45%);
    }

    .notice.is-new::before {
        content: "";
        position: absolute;
        top: 18px;
        right: 18px;
        width: 9px;
        height: 9px;
        border-radius: 50%;
        background: #e21b2d;
    }

    .notice-icon {
        flex: none;
        display: grid;
        place-items: center;
        width: 42px;
        height: 42px;
        border-radius: 12px;
    }

    .notice-icon svg {
        width: 21px;
        height: 21px;
        fill: none;
        stroke: currentColor;
        stroke-width: 1.9;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .notice-blue .notice-icon {
        background: #e5f0fb;
        color: #1565c0;
    }

    .notice-green .notice-icon {
        background: #e6f6ea;
        color: #2e7d32;
    }

    .notice-orange .notice-icon {
        background: #fff1de;
        color: #c76a00;
    }

    .notice-red .notice-icon {
        background: #fdecec;
        color: #c62828;
    }

    .notice-purple .notice-icon {
        background: #efe9fb;
        color: #5e35b1;
    }

    .notice-grey .notice-icon {
        background: #f1f3f4;
        color: #5f6b76;
    }

    .notice-body {
        flex: 1;
        min-width: 0;
        padding-right: 18px;
    }

    .notice-meta {
        display: flex;
        align-items: baseline;
        gap: 10px;
    }

    .notice-type {
        color: #1f2933;
        font-size: 13.5px;
        font-weight: 700;
    }

    .notice-time {
        color: #8a9199;
        font-size: 12px;
    }

    .notice-text {
        margin: 3px 0 0;
        color: #3b434b;
        font-size: 14px;
        line-height: 1.5;
        overflow-wrap: anywhere;
    }

    .notice-empty {
        display: grid;
        justify-items: center;
        gap: 10px;
        padding: 48px 20px;
        border: 1px dashed #d9d3d2;
        border-radius: 16px;
        background: #fff;
        color: #6b7480;
        text-align: center;
    }

    .notice-empty svg {
        width: 44px;
        height: 44px;
        fill: none;
        stroke: #b9bfc6;
        stroke-width: 1.6;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .notice-empty strong {
        color: #1f2933;
        font-size: 16px;
    }
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
            <?php foreach ($items as $n):
                [$iconPaths, $tone] = noticeStyle($n['notice_type']);
                $href = noticeHref($n, $linkedSessions, $myId, $role);
                $isActionable = $href !== null; ?>
                <article
                    class="notice notice-<?php echo $tone; ?><?php echo $n['is_read'] === 'N' ? ' is-new' : ''; ?><?php echo $isActionable ? ' is-actionable' : ''; ?>">
                    <span class="notice-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><?php echo $iconPaths; ?></svg></span>
                    <?php if ($isActionable): ?>
                        <a class="notice-body notice-link" href="<?php echo htmlspecialchars($href); ?>">
                        <?php else: ?>
                            <div class="notice-body">
                            <?php endif; ?>
                            <div class="notice-meta">
                                <span class="notice-type"><?php echo htmlspecialchars($n['notice_type']); ?></span>
                                <time class="notice-time" datetime="<?php echo htmlspecialchars($n['created_at']); ?>"
                                    title="<?php echo htmlspecialchars(formatDateTime($n['created_at'])); ?>"><?php echo htmlspecialchars(noticeTime($n['created_at'])); ?></time>
                            </div>
                            <p class="notice-text"><?php echo nl2br(htmlspecialchars($n['message'])); ?></p>
                            <?php if ($isActionable): ?>
                        </a>
                    <?php else: ?>
                </div>
            <?php endif; ?>
            </article>
        <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
<style>
    .notice-link::after {
        content: "";
        position: absolute;
        inset: 0;
        border-radius: 14px;
    }

    .notice-link {
        display: block;
        color: inherit;
        text-decoration: none;
        border-radius: 10px;
        transition: background-color 0.16s ease, transform 0.16s ease;
    }

    .notice-link .notice-type {
        transition: color 0.16s ease;
    }

    .notice.is-actionable {
        transition: border-color 0.16s ease, box-shadow 0.16s ease;
    }

    .notice.is-actionable:hover {
        border-color: #e7b8bd;
        box-shadow: 0 4px 12px rgba(111, 44, 55, 0.1);
    }

    .notice-link:hover {
        color: inherit;
        background: #fff5f5;
        text-decoration: none;
    }

    .notice-link:hover .notice-type {
        color: #a52a3a;
    }

    .notice-link:focus-visible {
        outline: 2px solid #bd5864;
        outline-offset: 2px;
        border-radius: 10px;
    }
</style>

<?php include __DIR__ . '/../general/footer.php'; ?>