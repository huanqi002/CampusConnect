<?php
require_once __DIR__ . '/functions.php';
require_login();
$user = current_user();
if (!$user) {
    redirect('logout.php');
}

$volunteerSlots = [];
if ($user['is_volunteer'] === 'Y') {
    $stmt = $pdo->prepare('SELECT * FROM volunteers WHERE volunteer_id = ? ORDER BY preferred_day, preferred_time');
    $stmt->execute([$user['id']]);
    $volunteerSlots = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Dashboard - Campus Connect</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/navigation.css">
</head>
<body>
<div class="layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="page">
        <h1 class="page-title">Profile Dashboard</h1>
        <?php flash(); ?>

        <div class="detail-card">
            <div class="card" style="margin-bottom:16px;">
                <h2><?= e($user['full_name']) ?></h2>
                <p class="meta">Student ID: <?= e($user['student_id']) ?></p>
                <p class="meta">Email: <?= e($user['email']) ?></p>
                <p class="meta">University: <?= e($user['university_name']) ?></p>
                <?php if ($user['is_volunteer'] === 'Y'): ?>
                    <p><span class="role-tag">Volunteer</span></p>
                <?php endif; ?>
            </div>

            <dl class="detail-list">
                <div class="detail-row">
                    <dt>Education</dt>
                    <dd><?= nl2br(e($user['education'] ?: 'Not added yet.')) ?></dd>
                </div>
                <div class="detail-row">
                    <dt>Skills</dt>
                    <dd><?= nl2br(e($user['skills'] ?: 'Not added yet.')) ?></dd>
                </div>
                <div class="detail-row">
                    <dt>Support Experience</dt>
                    <dd><?= nl2br(e($user['support_experience'] ?: 'Not added yet.')) ?></dd>
                </div>
            </dl>
        </div>

        <?php if ($user['is_volunteer'] === 'Y'): ?>
            <div class="card">
                <h2>Volunteer Profile</h2>
                <?php if (!$volunteerSlots): ?>
                    <p class="empty-state">No volunteer slot added yet.</p>
                <?php else: ?>
                    <div class="card-list">
                        <?php foreach ($volunteerSlots as $slot): ?>
                            <div class="info-card">
                                <div class="card-text">
                                    <p><strong><?= e($slot['category']) ?></strong></p>
                                    <p><?= e($slot['preferred_day']) ?> at <?= e($slot['preferred_time']) ?></p>
                                    <p><?= e($slot['support_mode']) ?></p>
                                </div>
                                <span class="pill <?= $slot['is_available'] === 'Y' ? 'pill-Scheduled' : 'pill-Cancelled' ?>">
                                    <?= $slot['is_available'] === 'Y' ? 'Active' : 'Inactive' ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="btn-row">
                        <a class="btn btn-secondary" href="<?= BASE_URL ?>/volunteer.php">Manage Volunteer Slots</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="card">
                <h2>Want to help other students?</h2>
                <p>Register as a peer volunteer and provide support.</p>
                <a class="btn btn-primary" href="<?= BASE_URL ?>/become_volunteer.php">Become a Volunteer</a>
            </div>
        <?php endif; ?>

        <div class="btn-row">
            <a class="btn btn-primary" href="<?= BASE_URL ?>/edit_profile.php">Edit Profile</a>
            <a class="btn btn-secondary" href="<?= BASE_URL ?>/change_password.php">Change Password</a>
            <a class="btn btn-plain" href="<?= BASE_URL ?>/profile_picture.php">Profile Picture</a>
            <a class="btn btn-plain" href="<?= BASE_URL ?>/logout.php">Logout</a>
        </div>
    </main>
</div>
</body>
</html>
