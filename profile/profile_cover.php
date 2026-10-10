<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_login();
$user = current_user();
$isVolunteer = ($user['is_volunteer'] ?? 'N') === 'Y';
$coverColumn = $isVolunteer ? 'volunteer_cover_photo_url' : 'cover_photo_url';
$coverPhoto = $user[$coverColumn] ?? '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if (!isset($_FILES['cover_photo']) || $_FILES['cover_photo']['error'] !== UPLOAD_ERR_OK) {
        $error = ($_FILES['cover_photo']['error'] ?? null) === UPLOAD_ERR_INI_SIZE
            ? 'The cover photo must be 5MB or smaller.'
            : 'Choose a JPG or PNG cover photo.';
    } else {
        $file = $_FILES['cover_photo'];
        $maxSize = 5 * 1024 * 1024;
        $imageInfo = $file['size'] <= $maxSize ? @getimagesize($file['tmp_name']) : false;
        $mime = $imageInfo['mime'] ?? '';
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
        if ($file['size'] > $maxSize) {
            $error = 'The cover photo must be 5MB or smaller.';
        } elseif (!$imageInfo || !isset($allowed[$mime])) {
            $error = 'Only valid JPG and PNG images are allowed.';
        } elseif ($imageInfo[0] > 8000 || $imageInfo[1] > 6000) {
            $error = 'The image dimensions are too large. Choose an image up to 8000 × 6000 pixels.';
        } else {
            $directory = dirname(__DIR__) . '/uploads/covers';
            if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
                $error = 'The cover photo folder could not be created.';
            } else {
                $filename = 'cover_' . (int)$user['id'] . '_' . bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
                $target = $directory . '/' . $filename;
                if (move_uploaded_file($file['tmp_name'], $target)) {
                    $url = BASE_URL . '/uploads/covers/' . $filename;
                    $stmt = $pdo->prepare("UPDATE users SET {$coverColumn} = ? WHERE id = ?");
                    $stmt->execute([$url, $user['id']]);
                    set_flash('success', 'Your cover photo has been updated.');
                    redirect($isVolunteer ? 'volunteer.php' : 'profile.php');
                } else {
                    $error = 'The cover photo could not be saved. Please try again.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Cover Photo - Campus Connect</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/frontend/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/frontend/css/profile.css">
</head>
<body class="account-page dashboard-page">
<div class="dashboard-app">
    <?php include dirname(__DIR__) . '/includes/account_sidebar.php'; ?>
    <main class="dashboard-main">
        <header class="dashboard-header"><div class="dashboard-header-title">My Profile</div><a class="dashboard-user-chip" href="<?= $isVolunteer ? BASE_URL . '/volunteer.php' : BASE_URL . '/profile.php' ?>"><?= e($user['full_name']) ?></a></header>
        <div class="dashboard-shell cover-editor-shell">
            <div class="cover-editor-heading"><span class="account-eyebrow">YOUR PROFILE</span><h1>Edit cover photo</h1><p>Choose a wide photo that shows your campus story.</p></div>
            <?php if ($error): ?><div class="message message-error" role="alert"><?= e($error) ?></div><?php endif; ?>
            <section class="cover-editor-card">
                <div class="cover-current-row">
                    <div class="cover-current-preview"><?php if ($coverPhoto): ?><img src="<?= e($coverPhoto) ?>" alt=""><?php else: ?><img src="<?= BASE_URL ?>/frontend/images/help-university-building.png" alt=""><?php endif; ?></div>
                    <div><h2>Current cover photo</h2><p>This appears across the top of your profile.</p></div>
                </div>
                <form method="post" enctype="multipart/form-data" id="coverUploadForm">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input class="cover-file-input" type="file" name="cover_photo" id="cover_photo" accept="image/jpeg,image/png,.jpg,.jpeg,.png" required>
                    <label class="cover-dropzone" id="coverDropzone" for="cover_photo" tabindex="0">
                        <span class="cover-upload-icon" aria-hidden="true">↑</span>
                        <strong id="coverPrompt">Drag and drop your cover photo here or click to browse</strong>
                        <span id="coverFileNote">JPG or PNG · Maximum file size 5MB</span>
                        <img class="cover-selected-preview" id="coverSelectedPreview" alt="Selected cover photo preview" hidden>
                    </label>
                    <div class="cover-editor-actions"><a class="btn btn-cancel" href="<?= $isVolunteer ? BASE_URL . '/volunteer.php' : BASE_URL . '/profile.php' ?>">Cancel</a><button class="btn btn-primary" type="submit">Save Changes</button></div>
                </form>
            </section>
        </div>
    </main>
</div>
<script>
(() => {
    const input = document.getElementById('cover_photo');
    const dropzone = document.getElementById('coverDropzone');
    const preview = document.getElementById('coverSelectedPreview');
    const prompt = document.getElementById('coverPrompt');
    const note = document.getElementById('coverFileNote');
    const showFile = file => {
        if (!file) return;
        if (!['image/jpeg', 'image/png'].includes(file.type) || file.size > 5 * 1024 * 1024) {
            input.value = '';
            note.textContent = 'Choose a JPG or PNG image up to 5MB.';
            note.classList.add('cover-file-error');
            return;
        }
        preview.src = URL.createObjectURL(file);
        preview.hidden = false;
        dropzone.classList.add('has-selection');
        prompt.textContent = 'Selected cover photo';
        note.textContent = file.name;
        note.classList.remove('cover-file-error');
    };
    input.addEventListener('change', () => showFile(input.files && input.files[0]));
    dropzone.addEventListener('dragover', event => { event.preventDefault(); dropzone.classList.add('is-dragging'); });
    dropzone.addEventListener('dragleave', () => dropzone.classList.remove('is-dragging'));
    dropzone.addEventListener('drop', event => {
        event.preventDefault(); dropzone.classList.remove('is-dragging');
        const file = event.dataTransfer.files && event.dataTransfer.files[0];
        if (!file) return;
        const transfer = new DataTransfer(); transfer.items.add(file); input.files = transfer.files;
        showFile(file);
    });
    dropzone.addEventListener('keydown', event => { if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); input.click(); } });
})();
</script>
</body>
</html>
