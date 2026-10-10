<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT id, student_id, full_name, email, university_name, picture_url, education, skills, support_experience, is_volunteer FROM users WHERE id = ?');
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    set_flash('error', 'User profile not found.');
    redirect('profile.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Profile - Campus Connect</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/frontend/css/style.css">
</head>
<body>
<div class="page">
    <h1 class="page-title">Student Profile</h1>

    <div class="detail-card">
        <h2><?= e($user['full_name']) ?></h2>
        <p class="meta">Student ID: <?= e($user['student_id']) ?></p>
        <p class="meta">University: <?= e($user['university_name']) ?></p>
        <?php if ($user['is_volunteer'] === 'Y'): ?><span class="role-tag">Volunteer</span><?php endif; ?>

        <dl class="detail-list" style="margin-top:18px;">
            <div class="detail-row"><dt>Education</dt><dd><?= nl2br(e($user['education'] ?: 'Not provided.')) ?></dd></div>
            <div class="detail-row"><dt>Skills</dt><dd><?= nl2br(e($user['skills'] ?: 'Not provided.')) ?></dd></div>
            <div class="detail-row"><dt>Support Experience</dt><dd><?= nl2br(e($user['support_experience'] ?: 'Not provided.')) ?></dd></div>
        </dl>
    </div>

    <div class="btn-row">
        <a class="btn btn-secondary" href="<?= BASE_URL ?>/profile.php">Back to Profile</a>
    </div>
</div>
</body>
</html>
