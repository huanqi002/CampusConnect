<?php
require_once dirname(__DIR__) . '/general/account_functions.php';
require_once dirname(__DIR__) . '/general/dashboard_header.php';
require_once dirname(__DIR__) . '/general/notifications.php';
require_login();
$user = current_user();
$isVolunteer = ($user['is_volunteer'] ?? 'N') === 'Y';
$profilePicture = $user['volunteer_picture_url'] ?? '';
$coverPhoto = $user['volunteer_cover_photo_url'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    if (in_array($action, ['student', 'volunteer'], true)) {
        $slotCheck = $pdo->prepare('SELECT id FROM volunteers WHERE user_id = ? LIMIT 1');
        $slotCheck->execute([$user['id']]);
        if (!$slotCheck->fetchColumn()) redirect('become_volunteer.php');
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE users SET is_volunteer = ? WHERE id = ?')->execute([$action === 'volunteer' ? 'Y' : 'N', $user['id']]);
        $pdo->prepare('UPDATE volunteers SET is_available = ? WHERE user_id = ?')->execute([$action === 'volunteer' ? 'Y' : 'N', $user['id']]);
        $pdo->commit();
        $_SESSION['role'] = $action;
        set_flash('success', $action === 'volunteer' ? 'Welcome back! Your saved volunteer profile is active again.' : 'Your profile is now set to student. You can volunteer again anytime.');
        redirect('profile.php');
    }
    if (in_array($action, ['active', 'inactive'], true)) {
        $availability = $action === 'active' ? 'Y' : 'N';
        $pdo->prepare('UPDATE volunteers SET is_available = ? WHERE user_id = ?')->execute([$availability, $user['id']]);
        set_flash('success', $availability === 'Y' ? 'Your volunteer availability is active again.' : 'Your volunteer profile is now inactive. Your preferences are saved.');
        redirect('volunteer.php');
    }
}

$stmt = $pdo->prepare('SELECT category, preferred_day, start_time, end_time, support_mode, is_available FROM volunteers WHERE user_id = ? ORDER BY FIELD(preferred_day, \'Monday\', \'Tuesday\', \'Wednesday\', \'Thursday\', \'Friday\', \'Saturday\', \'Sunday\'), start_time');
$stmt->execute([$user['id']]);
$slots = $stmt->fetchAll();
if (!$slots) redirect('become_volunteer.php');
$available = (bool)array_filter($slots, static fn($slot) => ($slot['is_available'] ?? 'N') === 'Y');
$categories = array_values(array_unique(array_column($slots, 'category')));
$skills = array_filter(array_map('trim', explode(',', (string)($user['skills'] ?? ''))));
$days = array_values(array_unique(array_column($slots, 'preferred_day')));
$daySchedules = [];
foreach ($slots as $slot) $daySchedules[$slot['preferred_day']] = date('g:i A', strtotime($slot['start_time'])) . ' - ' . date('g:i A', strtotime($slot['end_time']));
$modes = array_values(array_unique(array_map(static fn($slot) => $slot['support_mode'] === 'Online' ? 'Virtual' : $slot['support_mode'], $slots)));
$initials = '';
foreach (array_slice(preg_split('/\s+/', trim($user['full_name'] ?? 'Student')), 0, 2) as $part) $initials .= strtoupper(substr($part, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Dashboard - Campus Connect</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/frontend/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/frontend/css/profile.css">
</head>
<body class="account-page dashboard-page profile-view-page volunteer-profile-page">
<div class="dashboard-app">
<?php include dirname(__DIR__) . '/general/account_sidebar.php'; ?>
<main class="dashboard-main">
    <?= renderDashboardHeader('Profile Dashboard', $user, null, countUnreadNotifications($conn, (int)$user['id'], 'volunteer')) ?>
    <div class="dashboard-shell profile-dashboard-shell volunteer-profile-shell">
        <?php flash(); ?>
        <div class="profile-page-heading volunteer-profile-heading"><div><span class="account-eyebrow">YOUR CAMPUS CONNECT PROFILE</span><h1>Profile Dashboard</h1></div><span class="volunteer-status <?= $available ? 'is-active' : 'is-paused' ?>"><?= $available ? 'Active' : 'Inactive' ?></span></div>
        <section class="profile-showcase-card">
            <a class="profile-cover" href="<?= BASE_URL ?>/profile_cover.php" aria-label="Change cover photo">
                <img class="profile-cover-image" src="<?= e($coverPhoto ?: BASE_URL . '/frontend/images/help-university-building.png') ?>" alt="">
                <span class="profile-cover-action"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7.5h3l1.4-2h7.2l1.4 2h3v11H4z"/><circle cx="12" cy="13" r="3.5"/></svg>Change cover photo</span>
            </a>
            <div class="profile-identity-card">
                <div class="profile-avatar-wrap">
                    <a class="profile-identity-avatar<?= empty($profilePicture) ? ' is-empty' : '' ?>" href="<?= BASE_URL ?>/profile_picture.php" aria-label="Change profile photo"><?php if ($profilePicture): ?><img src="<?= e($profilePicture) ?>" alt=""><?php else: ?><span><?= e($initials) ?></span><?php endif; ?></a>
                    <a class="profile-avatar-camera" href="<?= BASE_URL ?>/profile_picture.php" aria-label="Change profile photo"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7.5h3l1.4-2h7.2l1.4 2h3v11H4z"/><circle cx="12" cy="13" r="3.5"/></svg></a>
                    <a class="profile-photo-edit" href="<?= BASE_URL ?>/profile_picture.php">Edit photo</a>
                </div>
                <div class="profile-identity-copy"><h2><?= e($user['full_name']) ?></h2><p>Role: Volunteer <span aria-hidden="true">·</span> <?= e($user['university_name'] ?? 'Campus Connect') ?> <span aria-hidden="true">·</span> <?= e($user['email']) ?></p></div>
            </div>
        </section>
        <section class="volunteer-preferences-card">
            <span class="account-eyebrow">HOW YOU CAN HELP</span>
            <h2>Your Support Preferences</h2>
            <div class="volunteer-preference-row"><span>Category</span><strong><?= $categories ? e(implode(', ', $categories)) : 'Not set' ?></strong></div>
            <div class="volunteer-preference-row"><span>Availability</span><div class="volunteer-schedule-list"><?php foreach ($daySchedules as $day => $time): ?><span><?= e($day) ?>: <?= e($time) ?></span><?php endforeach; ?></div></div>
            <div class="volunteer-preference-row"><span>Support Mode</span><strong><?= $modes ? e(implode(', ', $modes)) : 'Not set' ?></strong></div>
        </section>
        <section class="volunteer-skills-card"><span class="account-eyebrow">WHAT YOU KNOW</span><h2>Skills</h2><p class="profile-skill-hint">Tags and areas of expertise</p><div class="profile-skill-list"><?php foreach ($skills as $skill): ?><span><?= e($skill) ?></span><?php endforeach; ?><?php if (!$skills): ?><span class="profile-empty-skill">Add skills to help students find you.</span><?php endif; ?></div></section>
        <section class="volunteer-experience-card"><span class="account-eyebrow">YOUR VOLUNTEER STORY</span><h2>Support Experience</h2><p><?= nl2br(e($user['support_experience'] ?: 'Add your peer support, teaching, mentoring, or volunteering experience to help students get to know you.')) ?></p></section>
        <div class="volunteer-profile-actions">
            <a class="btn btn-secondary" href="<?= BASE_URL ?>/edit_profile.php">Edit Profile</a>
            <?php if ($isVolunteer): ?>
                <form method="post" data-confirm-title="Switch to <?= $available ? 'Inactive' : 'Active' ?>?" data-confirm-message="<?= $available ? 'You will no longer appear available for new student requests. Your schedule will remain saved.' : 'Your volunteer profile will appear available for new student requests.' ?>" data-confirm-submit="Switch status" data-confirm-icon="<?= $available ? 'inactive' : 'active' ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="<?= $available ? 'inactive' : 'active' ?>"><button class="btn <?= $available ? 'btn-inactive' : 'btn-active' ?>" type="submit" <?= !$available ? 'style="border:1px solid #218b55 !important;color:#218b55 !important;background:#fff !important" onmouseenter="this.style.setProperty(\'background-color\',\'#218b55\',\'important\');this.style.setProperty(\'color\',\'#fff\',\'important\')" onmouseleave="this.style.setProperty(\'background-color\',\'#fff\',\'important\');this.style.setProperty(\'color\',\'#218b55\',\'important\')" onfocus="this.style.setProperty(\'background-color\',\'#218b55\',\'important\');this.style.setProperty(\'color\',\'#fff\',\'important\')" onblur="this.style.setProperty(\'background-color\',\'#fff\',\'important\');this.style.setProperty(\'color\',\'#218b55\',\'important\')"' : '' ?>>Switch to <?= $available ? 'Inactive' : 'Active' ?></button></form>
                <a class="profile-security-link" href="<?= BASE_URL ?>/change_password.php">Change password</a>
                <form method="post" data-confirm-title="Switch to Student?" data-confirm-message="You will stop appearing as available to students. Your volunteer profile and schedule will remain saved." data-confirm-submit="Switch to Student" data-confirm-icon="switch"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="student"><button class="btn btn-plain" type="submit">Switch to Student</button></form>
            <?php else: ?>
                <form method="post" data-confirm-title="Switch to Volunteer?" data-confirm-message="Your saved volunteer profile and availability will become active." data-confirm-submit="Switch role" data-confirm-icon="switch"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="volunteer"><button class="btn btn-primary" type="submit">Switch to Volunteer</button></form>
            <?php endif; ?>
        </div>
    </div>
</main>
</div>
</body>
</html>
