<?php
require_once __DIR__ . '/notifications.php';

$currentUserId = $_SESSION['user_id'] ?? null;
$currentRole   = $_SESSION['role'] ?? null;
$currentName   = $_SESSION['name'] ?? null;
$unreadCount   = $currentUserId ? countUnreadNotifications($conn, (int)$currentUserId) : 0;

$currentPage = basename(dirname($_SERVER['SCRIPT_NAME'])) . '/' . basename($_SERVER['SCRIPT_NAME']);
$activeItem = [
    'user_management/profile.php'               => 'profile',
    'request_management/request.php'            => 'request',
    'session_management/index.php'              => 'dashboard',
    'session_management/edit_session.php'       => 'dashboard',
    'session_management/session_details.php'    => 'dashboard',
    'session_management/schedule_list.php'      => 'schedule',
    'session_management/schedule.php'           => 'schedule',
    'session_management/volunteer_requests.php' => 'volunteer_requests',
    'history_management/history.php'            => 'history',
    'history_management/feedback.php'           => 'history',
    'history_management/history_details.php'    => 'history',
][$currentPage] ?? '';

$sessionItems = ['dashboard', 'schedule', 'volunteer_requests'];

$navLink = function (string $href, string $label, string $key, string $class) use ($activeItem): string {
    $class .= $key === $activeItem ? ' active' : '';
    return '<a class="' . $class . '" href="' . $href . '">' . htmlspecialchars($label) . '</a>';
};
?>
<aside class="sidebar">
    <div class="sidebar-top">
        <a class="avatar" href="../user_management/profile.php" aria-label="My profile">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4.5"/><path d="M3.5 21c0-4.7 3.8-7.5 8.5-7.5s8.5 2.8 8.5 7.5z"/></svg>
        </a>
        <?php if ($currentUserId): ?>
        <a class="bell" href="../user_management/notifications.php"
           aria-label="Notifications<?php echo $unreadCount ? ", $unreadCount unread" : ''; ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a6 6 0 0 0-6 6v4.5L4 17h16l-2-3.5V9a6 6 0 0 0-6-6z"/><path d="M9.5 19.5a2.5 2.5 0 0 0 5 0"/></svg>
            <?php if ($unreadCount): ?><span class="bell-badge"><?php echo $unreadCount > 9 ? '9+' : $unreadCount; ?></span><?php endif; ?>
        </a>
        <?php endif; ?>
        <button type="button" class="nav-toggle" aria-expanded="false">Menu</button>
    </div>

    <?php if ($currentUserId): ?>
    <div class="user-name"><?php echo htmlspecialchars($currentName); ?></div>
    <span class="user-role"><?php echo htmlspecialchars(ucfirst($currentRole)); ?></span>
    <?php endif; ?>

    <div class="sidebar-menu">
        <?php if ($currentUserId): ?>
        <nav>
            <ul class="nav-list">
                <li><?php echo $navLink('../user_management/profile.php', 'My Profile', 'profile', 'nav-box'); ?></li>
                <li><?php echo $navLink('../request_management/request.php', 'Request', 'request', 'nav-box'); ?></li>
                <li>
                    <details class="nav-box nav-group" <?php echo in_array($activeItem, $sessionItems, true) ? 'open' : ''; ?>>
                        <summary>Session</summary>
                        <ul class="nav-sub">
                            <li><?php echo $navLink('../session_management/index.php', 'Dashboard', 'dashboard', 'nav-sub-link'); ?></li>
                            <li><?php echo $navLink('../session_management/schedule_list.php', 'Schedule', 'schedule', 'nav-sub-link'); ?></li>
                            <?php if ($currentRole === 'volunteer'): ?>
                            <li><?php echo $navLink('../session_management/volunteer_requests.php', 'Request', 'volunteer_requests', 'nav-sub-link'); ?></li>
                            <?php endif; ?>
                        </ul>
                    </details>
                </li>
                <li><?php echo $navLink('../history_management/history.php', 'History', 'history', 'nav-box'); ?></li>
            </ul>
        </nav>
        <?php endif; ?>

        <?php include __DIR__ . '/dev_user_switcher.php'; ?>
    </div>
</aside>
