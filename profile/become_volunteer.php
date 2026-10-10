<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_login();
$user = current_user();
if (($user['is_volunteer'] ?? 'N') === 'Y') redirect('volunteer.php');
$existingVolunteer = $pdo->prepare('SELECT id FROM volunteers WHERE volunteer_id = ? LIMIT 1');
$existingVolunteer->execute([$user['id']]);
if ($existingVolunteer->fetchColumn()) redirect('volunteer.php');

$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
$categories = ['Academic','Technology','New Student','General Student'];
$errors = [];
$category = '';
$skills = (string)($user['skills'] ?? '');
$experience = (string)($user['support_experience'] ?? '');
$selectedDays = ['Monday'];
$time = '10:00';
$mode = 'Online';
$timeOptions = [];
for ($hour = 0; $hour < 24; $hour++) {
    foreach ([0, 30] as $minute) {
        $value = sprintf('%02d:%02d', $hour, $minute);
        $timeOptions[$value] = date('g:i A', strtotime($value));
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $category = trim($_POST['category'] ?? '');
    $skills = trim($_POST['skills'] ?? '');
    $experience = trim($_POST['support_experience'] ?? '');
    $selectedDays = array_values(array_unique((array)($_POST['preferred_days'] ?? [])));
    $time = $_POST['preferred_time'] ?? '';
    $mode = $_POST['support_mode'] ?? '';

    $validModes = ['Online','Face-to-face'];
    if (!in_array($category, $categories, true)) $errors[] = 'Please choose a valid support category.';
    if (!$selectedDays || array_diff($selectedDays, $days)) $errors[] = 'Choose at least one valid preferred day.';
    if (!array_key_exists($time, $timeOptions)) $errors[] = 'Please choose a valid preferred time.';
    if (!in_array($mode, $validModes, true)) $errors[] = 'Please choose a valid support mode.';
    if (mb_strlen($skills) > 500) $errors[] = 'Your skills list must be 500 characters or fewer.';
    if (mb_strlen($experience) > 1000) $errors[] = 'Your experience must be 1000 characters or fewer.';

    if (!$errors) {
        try {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE users SET is_volunteer = 'Y', skills = ?, support_experience = ? WHERE id = ?")
                ->execute([$skills, $experience, $user['id']]);
            $insert = $pdo->prepare("INSERT INTO volunteers (volunteer_id, category, preferred_day, preferred_time, support_mode, is_available) VALUES (?, ?, ?, ?, ?, 'Y') ON DUPLICATE KEY UPDATE category = VALUES(category), support_mode = VALUES(support_mode), is_available = 'Y'");
            foreach ($selectedDays as $preferredDay) {
                $insert->execute([$user['id'], $category, $preferredDay, $time . ':00', $mode]);
            }
            $pdo->commit();
            $_SESSION['role'] = 'volunteer';
            set_flash('success', 'You are now registered as a volunteer.');
            redirect('volunteer.php');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Volunteer registration failed for user ' . (int)$user['id'] . ': ' . $exception->getMessage());
            $errors[] = 'We could not save your volunteer profile. Please try again.';
        }
    }
}
$initials = '';
foreach (array_slice(preg_split('/\s+/', trim($user['full_name'] ?? 'Student')), 0, 2) as $part) $initials .= strtoupper(substr($part, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Become a Volunteer - Campus Connect</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/frontend/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/frontend/css/profile.css">
</head>
<body class="account-page dashboard-page volunteer-form-page">
<div class="dashboard-app">
<?php include dirname(__DIR__) . '/includes/account_sidebar.php'; ?>
<main class="dashboard-main">
    <header class="dashboard-header"><div class="dashboard-header-title">Become a Volunteer</div><nav class="dashboard-nav"><a class="dashboard-user-chip" href="<?= BASE_URL ?>/profile.php"><span class="dashboard-user-avatar"><?php if (!empty($user['picture_url'])): ?><img src="<?= e($user['picture_url']) ?>" alt=""><?php else: ?><?= e($initials) ?><?php endif; ?></span><?= e($user['full_name']) ?></a><a class="dashboard-logout" href="<?= BASE_URL ?>/logout.php">Log out</a></nav></header>
    <div class="dashboard-shell volunteer-form-shell">
        <div class="volunteer-form-heading"><span class="account-eyebrow">SHARE WHAT YOU KNOW</span><h1>Become a Volunteer</h1><p>Register as a peer volunteer to help other students.</p></div>
        <?php if ($errors): ?><div class="message message-error" role="alert"><?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?></div><?php endif; ?>
        <section class="volunteer-form-card">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <label for="category">Support Category</label>
                <select id="category" name="category" required><option value="" disabled <?= $category === '' ? 'selected' : '' ?>>Select a category</option><?php foreach ($categories as $option): ?><option value="<?= e($option) ?>" <?= $category === $option ? 'selected' : '' ?>><?= e($option) ?></option><?php endforeach; ?></select>
                <label for="volunteer_skills">Skills</label>
                <input type="text" id="volunteer_skills" name="skills" maxlength="500" value="<?= e($skills) ?>" placeholder="e.g. Peer Mentoring, Listening...">
                <fieldset class="volunteer-choice-fieldset"><legend>Preferred Days</legend><div class="volunteer-day-options"><?php foreach ($days as $option): ?><label class="volunteer-choice-pill"><input type="checkbox" name="preferred_days[]" value="<?= e($option) ?>" <?= in_array($option, $selectedDays, true) ? 'checked' : '' ?>><span><?= e(substr($option, 0, 3)) ?></span></label><?php endforeach; ?></div></fieldset>
                <label for="preferred_time">Preferred Time</label>
                <select id="preferred_time" name="preferred_time" required><?php foreach ($timeOptions as $value => $label): ?><option value="<?= e($value) ?>" <?= $time === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select>
                <fieldset class="volunteer-choice-fieldset"><legend>Support Mode</legend><div class="volunteer-mode-options"><?php foreach (['Online' => 'Virtual','Face-to-face' => 'Face-to-Face'] as $value => $label): ?><label class="volunteer-choice-pill"><input type="radio" name="support_mode" value="<?= e($value) ?>" <?= $mode === $value ? 'checked' : '' ?>><span><?= e($label) ?></span></label><?php endforeach; ?></div></fieldset>
                <label for="support_experience">Support Experience <span class="volunteer-optional">(optional)</span></label>
                <textarea id="support_experience" name="support_experience" maxlength="1000" rows="3" placeholder="Briefly describe any volunteering, teaching, mentoring or counselling experience you have..."><?= e($experience) ?></textarea>
                <div class="volunteer-form-actions"><a class="btn btn-secondary" href="<?= BASE_URL ?>/profile.php">Cancel</a><button class="btn btn-primary" type="submit">Register</button></div>
            </form>
        </section>
    </div>
</main>
</div>
</body>
</html>
