<?php
require_once dirname(__DIR__) . '/general/account_functions.php';
require_login();
$user = current_user();
$isVolunteer = ($user['is_volunteer'] ?? 'N') === 'Y';
$volunteerCheck = $pdo->prepare('SELECT id FROM volunteers WHERE user_id = ? LIMIT 1');
$volunteerCheck->execute([$user['id']]);
$volunteerProfileExists = (bool)$volunteerCheck->fetchColumn();
$initials = '';
foreach (array_slice(preg_split('/\s+/', trim($user['full_name'] ?? 'Student')), 0, 2) as $part) $initials .= strtoupper(substr($part, 0, 1));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['role'] ?? '') === 'student') {
    check_csrf();
    $pdo->prepare("UPDATE users SET is_volunteer = 'N' WHERE id = ?")->execute([$user['id']]);
    $pdo->prepare("UPDATE volunteers SET is_available = 'N' WHERE user_id = ?")->execute([$user['id']]);
    $_SESSION['role'] = 'student';
    set_flash('success', 'Your profile is now set to student. You can volunteer again anytime.');
    redirect('profile.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['role'] ?? '') === 'volunteer' && $volunteerProfileExists) {
    check_csrf();
    $pdo->beginTransaction();
    $pdo->prepare("UPDATE users SET is_volunteer = 'Y' WHERE id = ?")->execute([$user['id']]);
    $pdo->prepare("UPDATE volunteers SET is_available = 'Y' WHERE user_id = ?")->execute([$user['id']]);
    $pdo->commit();
    $_SESSION['role'] = 'volunteer';
    set_flash('success', 'Welcome back! Your saved volunteer profile is active again.');
    redirect('volunteer.php');
}
$user = current_user();
$isVolunteer = ($user['is_volunteer'] ?? 'N') === 'Y';
if ($isVolunteer) redirect('volunteer.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Campus Connect</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/frontend/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/frontend/css/profile.css">
</head>
<body class="account-page dashboard-page profile-view-page">
<div class="dashboard-app">
<?php include dirname(__DIR__) . '/general/account_sidebar.php'; ?>
<main class="dashboard-main">
    <header class="dashboard-header">
        <div class="dashboard-header-title">My Profile</div>
        <nav class="dashboard-nav" aria-label="Account navigation">
            <a class="dashboard-user-chip profile-photo-chip" href="<?= BASE_URL ?>/profile_picture.php" aria-label="Change profile picture"><span class="dashboard-user-avatar"><?php if (!empty($user['picture_url'])): ?><img src="<?= e($user['picture_url']) ?>" alt=""><?php else: ?><?= e($initials) ?><?php endif; ?></span></a>
            <a class="profile-header-name" href="<?= BASE_URL ?>/edit_profile.php" aria-label="View and edit your profile"><?= e($user['full_name']) ?></a>
            <a class="dashboard-logout" href="<?= BASE_URL ?>/logout.php">Log out</a>
        </nav>
    </header>
    <div class="dashboard-shell profile-dashboard-shell">
        <?php flash(); ?>
        <div class="profile-page-heading">
            <div><span class="account-eyebrow">YOUR CAMPUS CONNECT PROFILE</span><h1>Profile Dashboard</h1></div>
            <?php if (!$isVolunteer && $volunteerProfileExists): ?><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="role" value="volunteer"><button class="btn btn-volunteer" type="submit">Switch to Volunteer</button></form><?php elseif (!$isVolunteer): ?><a class="btn btn-volunteer" href="<?= BASE_URL ?>/become_volunteer.php">Become a Volunteer</a><?php else: ?><a class="btn btn-volunteer" href="<?= BASE_URL ?>/volunteer.php">Volunteer Profile</a><?php endif; ?>
        </div>
        <section class="profile-showcase-card">
            <a class="profile-cover" href="<?= BASE_URL ?>/profile_cover.php" aria-label="<?= empty($user['cover_photo_url']) ? 'Add a cover photo' : 'Change cover photo' ?>">
                <img class="profile-cover-image" src="<?= e($user['cover_photo_url'] ?: BASE_URL . '/frontend/images/help-university-building.png') ?>" alt="">
                <?php if (empty($user['cover_photo_url'])): ?><span class="profile-cover-prompt">Make this profile yours</span><?php endif; ?>
                <span class="profile-cover-action"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7.5h3l1.4-2h7.2l1.4 2h3v11H4z"/><circle cx="12" cy="13" r="3.5"/></svg><?= empty($user['cover_photo_url']) ? 'Add cover photo' : 'Change cover photo' ?></span>
            </a>
            <div class="profile-identity-card">
                <div class="profile-avatar-wrap">
                    <a class="profile-identity-avatar<?= empty($user['picture_url']) ? ' is-empty' : '' ?>" href="<?= BASE_URL ?>/profile_picture.php" aria-label="<?= empty($user['picture_url']) ? 'Add a profile photo' : 'Change your profile photo' ?>"><?php if (!empty($user['picture_url'])): ?><img src="<?= e($user['picture_url']) ?>" alt=""><?php else: ?><span><?= e($initials) ?></span><?php endif; ?></a>
                    <a class="profile-avatar-camera" href="<?= BASE_URL ?>/profile_picture.php" aria-label="Change profile photo"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7.5h3l1.4-2h7.2l1.4 2h3v11H4z"/><circle cx="12" cy="13" r="3.5"/></svg></a>
                    <a class="profile-photo-edit" href="<?= BASE_URL ?>/profile_picture.php"><?= empty($user['picture_url']) ? '＋ Add photo' : 'Edit photo' ?></a>
                </div>
                <div class="profile-identity-copy"><h2><?= e($user['full_name']) ?></h2><p>Role: <?= $isVolunteer ? 'Volunteer' : 'Student' ?> <span aria-hidden="true">·</span> <?= e($user['university_name']) ?> <span aria-hidden="true">·</span> <?= e($user['email']) ?></p></div>
            </div>
        </section>
        <div class="profile-info-grid">
            <section class="profile-info-card"><span class="account-eyebrow">ABOUT YOUR STUDIES</span><h2>Educational Background</h2><p><?= nl2br(e($user['education'] ?: 'Add your course, major, and year of study to introduce yourself to the community.')) ?></p></section>
            <section class="profile-info-card"><span class="account-eyebrow">WHAT YOU KNOW</span><h2>Skills</h2><p class="profile-skill-hint">Tags and areas of expertise</p><div class="profile-skill-list"><?php foreach (array_filter(array_map('trim', explode(',', (string)($user['skills'] ?? '')))) as $skill): ?><span><?= e($skill) ?></span><?php endforeach; ?><?php if (empty(trim((string)($user['skills'] ?? '')))): ?><span class="profile-empty-skill">Add skills to help others find you.</span><?php endif; ?></div></section>
            <?php if ($isVolunteer && !empty($user['support_experience'])): ?><section class="profile-info-card profile-experience-card"><span class="account-eyebrow">VOLUNTEER EXPERIENCE</span><h2>Support Experience</h2><p><?= nl2br(e($user['support_experience'])) ?></p></section><?php endif; ?>
        </div>
        <div class="profile-page-actions"><a class="btn btn-secondary" href="<?= BASE_URL ?>/edit_profile.php">Edit Profile</a><a class="profile-security-link" href="<?= BASE_URL ?>/change_password.php">Change password</a><?php if ($isVolunteer): ?><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="role" value="student"><button class="btn btn-plain" type="submit">Remain a Student</button></form><?php endif; ?></div>
    </div>
</main>
</div>
</body>
</html>
