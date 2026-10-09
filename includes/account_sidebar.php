<?php
$isVolunteer = ($user['is_volunteer'] ?? 'N') === 'Y';
$currentFile = basename($_SERVER['SCRIPT_NAME'] ?? 'profile.php');
$requestUrl = BASE_URL . ($isVolunteer ? '/backend/request_management/volunteer_requests.php' : '/backend/request_management/request.php');
?>
<aside class="dashboard-sidebar">
    <a class="account-brand account-brand-light" href="<?= BASE_URL ?>/profile.php" aria-label="Campus Connect home"><img class="account-brand-logo" src="<?= BASE_URL ?>/frontend/images/campus-connect-logo.png" alt="Campus Connect"></a>
    <nav class="dashboard-side-nav" aria-label="Main navigation">
        <a class="<?= in_array($currentFile, ['profile.php', 'edit_profile.php', 'profile_picture.php', 'volunteer.php', 'change_password.php', 'verify_password_otp.php', 'set_new_password.php'], true) ? 'active' : '' ?>" href="<?= BASE_URL ?>/profile.php"><span class="side-nav-icon">P</span>My Profile</a>
        <a href="<?= e($requestUrl) ?>"><span class="side-nav-icon">R</span><?= $isVolunteer ? 'Upcoming Request' : 'Request' ?></a>
        <details class="dashboard-side-group" <?= str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/session_management/') ? 'open' : '' ?>>
            <summary><span class="side-nav-icon">S</span>Session</summary>
            <a href="<?= BASE_URL ?>/backend/session_management/index.php"><span class="side-nav-sub-icon">D</span>Dashboard</a>
            <a href="<?= BASE_URL ?>/backend/session_management/schedule_list.php"><span class="side-nav-sub-icon">C</span>Schedule</a>
            <?php if ($isVolunteer): ?><a href="<?= BASE_URL ?>/backend/request_management/volunteer_requests.php"><span class="side-nav-sub-icon">R</span>Request</a><?php endif; ?>
        </details>
        <a href="<?= BASE_URL ?>/backend/history_management/history.php"><span class="side-nav-icon">H</span>History</a>
    </nav>
    <a class="dashboard-side-logout" href="<?= BASE_URL ?>/logout.php"><span class="side-nav-icon">↪</span>Log out</a>
</aside>
