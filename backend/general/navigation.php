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
    'request_management/upcoming_request.php' => 'upcoming_request',
    'session_management/volunteer_requests.php' => 'session_requests',
    'session_management/session_request_details.php' => 'session_requests',
    'history_management/history.php' => 'history',
    'history_management/feedback.php' => 'history',
    'history_management/history_details.php' => 'history',
][$currentPage] ?? '';

$navLink = static function (string $href, string $label, string $key, string $icon) use ($activeItem): string {
    $active = $key === $activeItem ? ' active' : '';
    return '<a class="' . $active . '" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '"><span class="side-nav-icon" aria-hidden="true">' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . '</span>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';
};

$isVolunteer = $currentRole === 'volunteer';
$requestHref = $isVolunteer ? '../request_management/upcoming_request.php' : '../request_management/request.php';
$requestLabel = $isVolunteer ? 'Upcoming Request' : 'Request';
$openSessions = in_array($activeItem, ['dashboard', 'schedule', 'session_requests'], true);

$subLink = static function (string $href, string $label, string $key, string $icon) use ($activeItem): string {
    $active = $key === $activeItem ? ' active' : '';
    return '<a class="' . $active . '" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '"><span class="side-nav-icon" aria-hidden="true">' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . '</span>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';
};
?>
<aside class="dashboard-sidebar">
    <a class="account-brand account-brand-light" href="../../profile.php" aria-label="Campus Connect home"><img
            class="account-brand-logo" src="../../frontend/images/campus-connect-logo.png" alt="Campus Connect"></a>
    <nav class="dashboard-side-nav" aria-label="Main navigation">
        <?= $navLink('../../profile.php', 'My Profile', 'profile', 'P') ?>
        <?= $navLink($requestHref, $requestLabel, $isVolunteer ? 'upcoming_request' : 'request', 'R') ?>
        <details class="dashboard-side-group" <?= $openSessions ? 'open' : '' ?>>
            <summary><span class="side-nav-icon" aria-hidden="true">S</span>Session</summary>
            <?= $subLink('../session_management/index.php', 'Dashboard', 'dashboard', 'D') ?>
            <?= $subLink('../session_management/schedule_list.php', 'Schedule', 'schedule', 'C') ?>
            <?php if ($isVolunteer): ?>    <?= $subLink('../session_management/volunteer_requests.php', 'Request', 'session_requests', 'R') ?><?php endif; ?>
        </details>
        <?= $navLink('../history_management/history.php', 'History', 'history', 'H') ?>
    </nav>
    <a class="dashboard-side-logout" href="../user_management/logout.php" data-logout-trigger><span class="side-nav-icon"
            aria-hidden="true">↪</span>Log out</a>
</aside>
<div class="logout-confirm-backdrop" id="logoutConfirm" role="dialog" aria-modal="true" aria-labelledby="logoutConfirmTitle" hidden>
    <section class="logout-confirm-box">
        <span class="logout-confirm-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M10 17l5-5-5-5M15 12H3"/><path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/></svg></span>
        <h2 id="logoutConfirmTitle">Ready to leave?</h2>
        <p>You can sign back in anytime to continue using Campus Connect.</p>
        <form class="logout-confirm-actions" method="post" action="../user_management/logout.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <button class="logout-confirm-cancel" type="button" id="logoutCancel">Stay here</button>
            <button class="logout-confirm-submit" type="submit">Log out</button>
        </form>
    </section>
</div>
<script>
(function () {
    var modal = document.getElementById('logoutConfirm');
    var cancel = document.getElementById('logoutCancel');
    if (!modal || !cancel) return;
    document.querySelectorAll('[data-logout-trigger]').forEach(function (link) {
        link.addEventListener('click', function (event) { event.preventDefault(); modal.hidden = false; cancel.focus(); });
    });
    function close() { modal.hidden = true; }
    cancel.addEventListener('click', close);
    modal.addEventListener('click', function (event) { if (event.target === modal) close(); });
    document.addEventListener('keydown', function (event) { if (event.key === 'Escape' && !modal.hidden) close(); });
})();
</script>
