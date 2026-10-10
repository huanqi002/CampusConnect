<?php
require_once __DIR__ . '/notifications.php';

$currentUserId = $_SESSION['user_id'] ?? null;
$currentRole = $_SESSION['role'] ?? 'student';
$currentPage = basename(dirname($_SERVER['SCRIPT_NAME'])) . '/' . basename($_SERVER['SCRIPT_NAME']);
$activeItem = [
    'user_management/profile.php' => 'profile',
    'request_management/request.php' => 'request',
    'session_management/index.php' => 'dashboard',
    'session_management/edit_session.php' => 'dashboard',
    'session_management/session_details.php' => 'dashboard',
    'session_management/schedule_list.php' => 'schedule',
    'session_management/schedule.php' => 'schedule',
    'request_management/support_requests.php' => 'support_requests',
    'session_management/volunteer_requests.php' => 'session_requests',
    'history_management/history.php' => 'history',
    'history_management/feedback.php' => 'history',
    'history_management/history_details.php' => 'history',
][$currentPage] ?? '';

$navLink = static function (string $href, string $label, string $key, string $icon) use ($activeItem): string {
    $active = $key === $activeItem ? ' active' : '';
    return '<a class="' . $active . '" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '"><span class="side-nav-icon" aria-hidden="true">' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . '</span>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';
};

$isVolunteer = $currentRole === 'volunteer';
$requestHref = $isVolunteer ? '../request_management/support_requests.php' : '../request_management/request.php';
$requestLabel = $isVolunteer ? 'Upcoming Request' : 'Request';
$openSessions = in_array($activeItem, ['dashboard', 'schedule', 'session_requests'], true);

$subLink = static function (string $href, string $label, string $key, string $icon) use ($activeItem): string {
    $active = $key === $activeItem ? ' active' : '';
    return '<a class="' . $active . '" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '"><span class="side-nav-icon" aria-hidden="true">' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . '</span>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';
};

$isVolunteer = $currentRole === 'volunteer';
$requestHref = $isVolunteer ? '../request_management/volunteer_requests.php' : '../request_management/request.php';
$requestLabel = $isVolunteer ? 'Upcoming Request' : 'Request';
$openSessions = in_array($activeItem, ['dashboard', 'schedule'], true);
?>
<aside class="dashboard-sidebar">
    <a class="account-brand account-brand-light" href="../../profile.php" aria-label="Campus Connect home"><img class="account-brand-logo" src="../../frontend/images/campus-connect-logo.png" alt="Campus Connect"></a>
    <nav class="dashboard-side-nav" aria-label="Main navigation">
        <?= $navLink('../../profile.php', 'My Profile', 'profile', 'P') ?>
        <?= $navLink($requestHref, $requestLabel, $isVolunteer ? 'volunteer_requests' : 'request', 'R') ?>
        <details class="dashboard-side-group" <?= $openSessions ? 'open' : '' ?>>
            <summary><span class="side-nav-icon" aria-hidden="true">S</span>Session</summary>
            <?= $subLink('../session_management/index.php', 'Dashboard', 'dashboard', 'D') ?>
            <?= $subLink('../session_management/schedule_list.php', 'Schedule', 'schedule', 'C') ?>
            <?php if ($isVolunteer): ?><?= $subLink('../request_management/volunteer_requests.php', 'Request', 'volunteer_requests', 'R') ?><?php endif; ?>
        </details>
        <?= $navLink('../history_management/history.php', 'History', 'history', 'H') ?>
    </nav>
    <a class="dashboard-side-logout" href="../user_management/logout.php"><span class="side-nav-icon" aria-hidden="true">↪</span>Log out</a>
</aside>
