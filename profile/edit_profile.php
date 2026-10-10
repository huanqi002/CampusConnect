<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_login();
$user = current_user();
$isVolunteer = ($user['is_volunteer'] ?? 'N') === 'Y';
$profilePicture = $isVolunteer ? ($user['volunteer_picture_url'] ?? '') : ($user['picture_url'] ?? '');
$errors = [];
$fieldErrors = [];
$fullName = $user['full_name'];
$education = (string)($user['education'] ?? '');
$skills = (string)($user['skills'] ?? '');
$experience = (string)($user['support_experience'] ?? '');
$volunteerCategories = ['Academic','Technology','New Student','General Student'];
$validDays = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
$validModes = ['Online','Face-to-face'];
$category = '';
$preferredDays = [];
$preferredTime = '';
$supportMode = '';
$volunteerSlots = [];
$timeOptions = [];
for ($hour = 0; $hour < 24; $hour++) {
    foreach ([0, 30] as $minute) {
        $timeOption = sprintf('%02d:%02d', $hour, $minute);
        $timeOptions[$timeOption] = date('g:i A', strtotime($timeOption));
    }
}
if ($isVolunteer) {
    $slotQuery = $pdo->prepare('SELECT category, preferred_day, preferred_time, support_mode, is_available FROM volunteers WHERE volunteer_id = ? ORDER BY FIELD(preferred_day, \'Monday\', \'Tuesday\', \'Wednesday\', \'Thursday\', \'Friday\', \'Saturday\', \'Sunday\'), preferred_time');
    $slotQuery->execute([$user['id']]);
    $volunteerSlots = $slotQuery->fetchAll();
    if ($volunteerSlots) {
        $category = $volunteerSlots[0]['category'];
        $preferredDays = array_values(array_unique(array_column($volunteerSlots, 'preferred_day')));
        $preferredTime = substr($volunteerSlots[0]['preferred_time'], 0, 5);
        $supportMode = $volunteerSlots[0]['support_mode'];
    }
}
$initials = '';
foreach (array_slice(preg_split('/\s+/', trim($user['full_name'] ?? 'Student')), 0, 2) as $part) $initials .= strtoupper(substr($part, 0, 1));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if ($isVolunteer) {
        $category = trim($_POST['category'] ?? '');
        $skills = implode(', ', array_values(array_unique(array_filter(array_map('trim', explode(',', $_POST['skills'] ?? ''))))));
        $experience = trim($_POST['support_experience'] ?? '');
        $preferredDays = array_values(array_unique((array)($_POST['preferred_days'] ?? [])));
        $preferredTime = $_POST['preferred_time'] ?? '';
        $supportMode = $_POST['support_mode'] ?? '';
        if (!in_array($category, $volunteerCategories, true)) $fieldErrors['category'] = 'Please select a support category.';
        if ($skills === '') $fieldErrors['skills'] = 'Please enter at least one skill.';
        elseif (mb_strlen($skills) > 500) $fieldErrors['skills'] = 'Skills must be 500 characters or fewer.';
        if (mb_strlen($experience) > 1000) $fieldErrors['support_experience'] = 'Support experience must be 1000 characters or fewer.';
        if (!$preferredDays || array_diff($preferredDays, $validDays)) $fieldErrors['preferred_days'] = 'Please select at least one day.';
        if (!array_key_exists($preferredTime, $timeOptions)) $fieldErrors['preferred_time'] = 'Please select a preferred time.';
        if (!in_array($supportMode, $validModes, true)) $fieldErrors['support_mode'] = 'Please select a support mode.';
        if (!$fieldErrors) {
            try {
                $pdo->beginTransaction();
                $pdo->prepare('UPDATE users SET skills = ?, support_experience = ? WHERE id = ?')->execute([$skills, $experience, $user['id']]);
                $pdo->prepare('DELETE FROM volunteers WHERE volunteer_id = ?')->execute([$user['id']]);
                $availability = array_reduce($volunteerSlots, static fn($active, $slot) => $active || ($slot['is_available'] ?? 'N') === 'Y', false) ? 'Y' : 'N';
                $insert = $pdo->prepare('INSERT INTO volunteers (volunteer_id, category, preferred_day, preferred_time, support_mode, is_available) VALUES (?, ?, ?, ?, ?, ?)');
                foreach ($preferredDays as $preferredDay) $insert->execute([$user['id'], $category, $preferredDay, $preferredTime . ':00', $supportMode, $availability]);
                $pdo->commit();
                set_flash('success', 'Your volunteer profile has been updated.');
                redirect('volunteer.php');
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log('Volunteer profile update failed for user ' . (int)$user['id'] . ': ' . $exception->getMessage());
                $errors[] = 'We could not save your volunteer profile. Please check your details and try again.';
            }
        }
    } else {
    $fullName = trim($_POST['full_name'] ?? '');
    $education = trim($_POST['education'] ?? '');
    $skillParts = array_filter(array_map('trim', explode(',', $_POST['skills'] ?? '')));
    $skillParts = array_values(array_unique(array_slice($skillParts, 0, 30)));
    $skills = implode(', ', $skillParts);
    $experience = trim($_POST['support_experience'] ?? '');
    if ($fullName === '') $errors[] = 'Full name is required.';
    if (mb_strlen($fullName) > 100) $errors[] = 'Full name must be 100 characters or fewer.';
    if (mb_strlen($education) > 500) $errors[] = 'Educational background must be 500 characters or fewer.';
    if (mb_strlen($skills) > 500) $errors[] = 'Your skills list is too long.';
    if (mb_strlen($experience) > 1000) $errors[] = 'Support experience must be 1000 characters or fewer.';

    if (!$errors) {
        $stmt = $pdo->prepare('UPDATE users SET full_name = ?, education = ?, skills = ?, support_experience = ? WHERE id = ?');
        $stmt->execute([$fullName, $education, $skills, $experience, $user['id']]);
        $_SESSION['name'] = $fullName;
        set_flash('success', 'Your profile has been updated.');
        redirect('profile.php');
    }
    }
}
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Campus Connect</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/frontend/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/frontend/css/profile.css">
</head>
<body class="account-page dashboard-page">
<div class="dashboard-app">
<?php include dirname(__DIR__) . '/includes/account_sidebar.php'; ?>
<main class="dashboard-main">
    <header class="dashboard-header">
        <div class="dashboard-header-title">Edit Profile</div>
        <nav class="dashboard-nav" aria-label="Account navigation">
            <a class="dashboard-user-chip" href="<?= $isVolunteer ? BASE_URL . '/volunteer.php' : BASE_URL . '/profile.php' ?>" aria-label="View your profile"><span class="dashboard-user-avatar"><?php if ($profilePicture): ?><img src="<?= e($profilePicture) ?>" alt=""><?php else: ?><?= e($initials) ?><?php endif; ?></span><?= e($user['full_name']) ?></a>
            <a class="dashboard-logout" href="<?= BASE_URL ?>/logout.php">Log out</a>
        </nav>
    </header>
    <div class="dashboard-shell edit-profile-shell">
        <div class="profile-page-heading edit-profile-heading"><div><span class="account-eyebrow">YOUR ACCOUNT</span><h1>Edit Profile</h1><p>Keep your information up to date so your campus community can get to know you.</p></div></div>
        <?php if ($errors): ?><div class="message message-error edit-profile-errors" role="alert"><?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?></div><?php endif; ?>
        <section class="edit-profile-card">
            <form method="post" id="editProfileForm">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <?php if ($isVolunteer): ?>
                <div class="volunteer-edit-intro"><p>Update the support you offer so students can find the right help.</p></div>
                <label for="category" class="<?= isset($fieldErrors['category']) ? 'has-error' : '' ?>">Support Category</label>
                <select id="category" name="category" class="<?= isset($fieldErrors['category']) ? 'is-invalid' : '' ?>" required><option value="" disabled <?= $category === '' ? 'selected' : '' ?>>Select...</option><?php foreach ($volunteerCategories as $option): ?><option value="<?= e($option) ?>" <?= $category === $option ? 'selected' : '' ?>><?= e($option) ?></option><?php endforeach; ?></select>
                <?php if (isset($fieldErrors['category'])): ?><small class="volunteer-field-error"><?= e($fieldErrors['category']) ?></small><?php endif; ?>
                <fieldset class="volunteer-choice-fieldset <?= isset($fieldErrors['preferred_days']) ? 'has-error' : '' ?>"><legend>Preferred Day</legend><div class="volunteer-day-options"><?php foreach ($validDays as $option): ?><label class="volunteer-choice-pill"><input type="checkbox" name="preferred_days[]" value="<?= e($option) ?>" <?= in_array($option, $preferredDays, true) ? 'checked' : '' ?>><span><?= e(substr($option, 0, 3)) ?></span></label><?php endforeach; ?></div></fieldset>
                <?php if (isset($fieldErrors['preferred_days'])): ?><small class="volunteer-field-error"><?= e($fieldErrors['preferred_days']) ?></small><?php endif; ?>
                <label for="preferred_time" class="<?= isset($fieldErrors['preferred_time']) ? 'has-error' : '' ?>">Preferred Time</label>
                <select id="preferred_time" name="preferred_time" class="<?= isset($fieldErrors['preferred_time']) ? 'is-invalid' : '' ?>" required><option value="" disabled <?= $preferredTime === '' ? 'selected' : '' ?>>Select preferred time slot...</option><?php foreach ($timeOptions as $value => $label): ?><option value="<?= e($value) ?>" <?= $preferredTime === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select>
                <?php if (isset($fieldErrors['preferred_time'])): ?><small class="volunteer-field-error"><?= e($fieldErrors['preferred_time']) ?></small><?php endif; ?>
                <fieldset class="volunteer-choice-fieldset <?= isset($fieldErrors['support_mode']) ? 'has-error' : '' ?>"><legend>Support Mode</legend><div class="volunteer-mode-options"><?php foreach (['Online' => 'Virtual','Face-to-face' => 'Face-to-Face'] as $value => $label): ?><label class="volunteer-choice-pill"><input type="radio" name="support_mode" value="<?= e($value) ?>" <?= $supportMode === $value ? 'checked' : '' ?>><span><?= e($label) ?></span></label><?php endforeach; ?></div></fieldset>
                <?php if (isset($fieldErrors['support_mode'])): ?><small class="volunteer-field-error"><?= e($fieldErrors['support_mode']) ?></small><?php endif; ?>
                <label for="skillEntry" class="<?= isset($fieldErrors['skills']) ? 'has-error' : '' ?>">Skills</label>
                <div class="skill-editor <?= isset($fieldErrors['skills']) ? 'is-invalid' : '' ?>" id="skillEditor">
                    <div class="skill-tags" id="skillTags"></div>
                    <input type="text" id="skillEntry" placeholder="Type a skill and press Enter..." autocomplete="off" aria-label="Add a skill">
                    <input type="hidden" name="skills" id="skillsValue" value="<?= e($skills) ?>">
                </div>
                <p class="edit-field-note">Press Enter or comma after each skill. Select a skill tag to remove it.</p>
                <?php if (isset($fieldErrors['skills'])): ?><small class="volunteer-field-error"><?= e($fieldErrors['skills']) ?></small><?php endif; ?>
                <label for="support_experience" class="<?= isset($fieldErrors['support_experience']) ? 'has-error' : '' ?>">Support Experience</label>
                <textarea id="support_experience" name="support_experience" maxlength="1000" rows="3" placeholder="Briefly describe any volunteering, teaching, mentoring or counselling experience you have..." class="<?= isset($fieldErrors['support_experience']) ? 'is-invalid' : '' ?>"><?= e($experience) ?></textarea>
                <?php if (isset($fieldErrors['support_experience'])): ?><small class="volunteer-field-error"><?= e($fieldErrors['support_experience']) ?></small><?php endif; ?>
                <div class="edit-profile-actions"><a class="btn btn-cancel" href="<?= BASE_URL ?>/volunteer.php">Cancel</a><button class="btn btn-primary" type="submit">Save Changes</button></div>
                <?php else: ?>
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" value="<?= e($fullName) ?>" maxlength="100" autocomplete="name" required>
                <label for="email">University Email</label>
                <input class="profile-readonly" type="email" id="email" value="<?= e($user['email']) ?>" readonly aria-describedby="emailNote">
                <p class="edit-field-note" id="emailNote">Contact support if you need to change your university email.</p>
                <label for="education">Educational Background</label>
                <textarea id="education" name="education" maxlength="500" rows="3" placeholder="Add your course, major, and year of study…"><?= e($education) ?></textarea>
                <label for="skillEntry">Skills</label>
                <div class="skill-editor" id="skillEditor">
                    <div class="skill-tags" id="skillTags"></div>
                    <input type="text" id="skillEntry" placeholder="Type a skill and press Enter…" autocomplete="off" aria-label="Add a skill">
                    <input type="hidden" name="skills" id="skillsValue" value="<?= e($skills) ?>">
                </div>
                <p class="edit-field-note">Press Enter or comma after each skill. Select a skill tag to remove it.</p>
                <?php if (($user['is_volunteer'] ?? 'N') === 'Y'): ?>
                    <label for="support_experience">Support Experience</label>
                    <textarea id="support_experience" name="support_experience" maxlength="1000" rows="3" placeholder="Describe your experience supporting other students…"><?= e($experience) ?></textarea>
                <?php endif; ?>
                <div class="edit-profile-actions"><a class="btn btn-cancel" href="<?= BASE_URL ?>/profile.php">Cancel</a><button class="btn btn-primary" type="submit">Save Changes</button></div>
                <?php endif; ?>
            </form>
        </section>
    </div>
</main>
</div>
<script>
(() => {
    const editor = document.getElementById('skillEditor');
    const tags = document.getElementById('skillTags');
    const entry = document.getElementById('skillEntry');
    const hidden = document.getElementById('skillsValue');
    let skills = hidden.value.split(',').map(value => value.trim()).filter(Boolean);
    const render = () => {
        tags.replaceChildren();
        skills.forEach((skill, index) => {
            const tag = document.createElement('button');
            tag.type = 'button'; tag.className = 'skill-tag'; tag.textContent = skill + ' ×';
            tag.setAttribute('aria-label', 'Remove ' + skill);
            tag.addEventListener('click', () => { skills.splice(index, 1); update(); });
            tags.append(tag);
        });
        hidden.value = skills.join(', ');
    };
    const update = () => { skills = [...new Set(skills)].slice(0, 30); render(); };
    const addEntry = () => {
        const values = entry.value.split(',').map(value => value.trim()).filter(Boolean);
        skills.push(...values); entry.value = ''; update();
    };
    entry.addEventListener('keydown', event => { if (event.key === 'Enter' || event.key === ',') { event.preventDefault(); addEntry(); } if (event.key === 'Backspace' && !entry.value && skills.length) { skills.pop(); update(); } });
    entry.addEventListener('blur', () => { if (entry.value.trim()) addEntry(); });
    document.getElementById('editProfileForm').addEventListener('submit', render);
    editor.addEventListener('click', event => { if (event.target === editor || event.target === tags) entry.focus(); });
    render();
})();
</script>
</body>
</html>
