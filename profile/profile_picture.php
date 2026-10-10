<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_login();
$user = current_user();
$isVolunteer = ($user['is_volunteer'] ?? 'N') === 'Y';
$pictureColumn = $isVolunteer ? 'volunteer_picture_url' : 'picture_url';
$profilePicture = $user[$pictureColumn] ?? '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_INI_SIZE) {
        $error = 'The image must be 2MB or smaller.';
    } elseif (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Choose a JPG or PNG image to upload.';
    } else {
        $file = $_FILES['profile_picture'];
        $maxSize = 2 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            $error = 'The image must be 2MB or smaller.';
        } else {
            $imageInfo = @getimagesize($file['tmp_name']);
            $mime = $imageInfo['mime'] ?? '';
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
            if (!isset($allowed[$mime])) {
                $error = 'Only JPG and PNG image files are allowed.';
            } elseif (!$imageInfo || $imageInfo[0] > 6000 || $imageInfo[1] > 6000) {
                $error = 'The image could not be read or is too large in dimensions.';
            } else {
                $uploadDirectory = dirname(__DIR__) . '/uploads/profile';
                if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
                    $error = 'The profile image folder could not be created.';
                } else {
                    $filename = 'user_' . (int)$user['id'] . '_' . bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
                    $target = $uploadDirectory . '/' . $filename;
                    if (move_uploaded_file($file['tmp_name'], $target)) {
                        $url = BASE_URL . '/uploads/profile/' . $filename;
                        $stmt = $pdo->prepare("UPDATE users SET {$pictureColumn} = ? WHERE id = ?");
                        $stmt->execute([$url, $user['id']]);
                        set_flash('success', 'Your profile photo has been updated.');
                        redirect($isVolunteer ? 'volunteer.php' : 'profile.php');
                    } else {
                        $error = 'The image could not be saved. Please try again.';
                    }
                }
            }
        }
    }
}

$initials = '';
foreach (array_slice(preg_split('/\s+/', trim($user['full_name'] ?? 'Student')), 0, 2) as $part) {
    $initials .= strtoupper(substr($part, 0, 1));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile Picture - Campus Connect</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/frontend/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/frontend/css/profile.css">
</head>
<body class="account-page photo-page">
<div class="photo-app">
    <?php include dirname(__DIR__) . '/includes/account_sidebar.php'; ?>
    <main class="photo-main">
        <header class="dashboard-header"><div class="dashboard-header-title">My profile</div><a class="dashboard-user-chip" href="<?= $isVolunteer ? BASE_URL . '/volunteer.php' : BASE_URL . '/profile.php' ?>"><span class="dashboard-user-avatar"><?php if ($profilePicture): ?><img src="<?= e($profilePicture) ?>" alt=""><?php else: ?><?= e($initials) ?><?php endif; ?></span><?= e($user['full_name']) ?></a></header>
        <section class="photo-content">
            <div class="security-page-heading"><span class="account-eyebrow">YOUR PROFILE</span><h1>Edit profile picture</h1><p>Choose a clear photo so your campus community can recognize you.</p></div>
            <?php if ($error): ?><div class="message message-error photo-error" role="alert"><?= e($error) ?></div><?php endif; ?>
            <section class="photo-card">
                <div class="photo-current-row">
                    <div class="photo-avatar" id="photoPreview">
                        <?php if ($profilePicture): ?><img src="<?= e($profilePicture) ?>" alt="Current profile photo">
                        <?php else: ?><span><?= e($initials) ?></span><?php endif; ?>
                    </div>
                    <div><h2>Current photo</h2><p>This photo is visible to other students and volunteers.</p></div>
                </div>
                <form method="post" enctype="multipart/form-data" id="photoUploadForm">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input class="photo-file-input" type="file" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,.jpg,.jpeg,.png" required>
                    <label class="photo-dropzone" for="profile_picture" id="photoDropzone" tabindex="0">
                        <span class="photo-upload-icon" aria-hidden="true">↑</span>
                        <strong id="photoPrompt">Drag and drop your photo here or click to browse</strong>
                        <span id="photoFilename">JPG or PNG · Maximum file size 2MB</span>
                        <img class="photo-selected-preview" id="photoSelectedPreview" alt="Selected profile photo preview" hidden>
                    </label>
                    <p class="photo-upload-help" id="photoStatus" aria-live="polite"></p>
                    <div class="photo-actions">
                        <a class="btn btn-secondary" href="<?= $isVolunteer ? BASE_URL . '/volunteer.php' : BASE_URL . '/profile.php' ?>">Cancel</a>
                        <button class="btn btn-primary" type="submit">Save photo</button>
                    </div>
                </form>
            </section>
        </section>
    </main>
</div>
<script>
(function () {
    const input = document.getElementById('profile_picture');
    const dropzone = document.getElementById('photoDropzone');
    const preview = document.getElementById('photoSelectedPreview');
    const prompt = document.getElementById('photoPrompt');
    const filename = document.getElementById('photoFilename');
    const status = document.getElementById('photoStatus');
    const maxSize = 2 * 1024 * 1024;

    function showFile(file) {
        if (!file) return;
        if (!['image/jpeg', 'image/png'].includes(file.type)) {
            input.value = '';
            status.textContent = 'Please choose a JPG or PNG image.';
            filename.textContent = 'JPG or PNG · Maximum file size 2MB';
            return;
        }
        if (file.size > maxSize) {
            input.value = '';
            status.textContent = 'This photo is larger than 2MB. Choose a smaller image.';
            filename.textContent = 'JPG or PNG · Maximum file size 2MB';
            return;
        }
        filename.textContent = file.name + ' · ' + (file.size / 1024 / 1024).toFixed(2) + 'MB';
        status.textContent = 'Photo ready to save.';
        preview.src = URL.createObjectURL(file);
        preview.hidden = false;
        dropzone.classList.add('has-selection');
        prompt.textContent = 'Selected profile photo';
    }

    input.addEventListener('change', () => showFile(input.files[0]));
    dropzone.addEventListener('keydown', event => { if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); input.click(); } });
    ['dragenter', 'dragover'].forEach(name => dropzone.addEventListener(name, event => { event.preventDefault(); dropzone.classList.add('is-dragging'); }));
    ['dragleave', 'dragend'].forEach(name => dropzone.addEventListener(name, () => dropzone.classList.remove('is-dragging')));
    dropzone.addEventListener('drop', event => {
        event.preventDefault();
        dropzone.classList.remove('is-dragging');
        const file = event.dataTransfer.files[0];
        if (!file) return;
        const transfer = new DataTransfer();
        transfer.items.add(file);
        input.files = transfer.files;
        showFile(file);
    });
})();
</script>
</body>
</html>
