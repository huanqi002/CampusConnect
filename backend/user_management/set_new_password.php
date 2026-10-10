<?php
require_once dirname(__DIR__) . '/general/account_functions.php';
require_login();

if (empty($_SESSION['password_change_verified'])) {
    redirect('change_password.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (strlen($newPassword) < 8) $errors[] = 'New password must be at least 8 characters.';
    if (!preg_match('/[0-9]/', $newPassword)) $errors[] = 'Include at least one number in your password.';
    if (!preg_match('/[^a-zA-Z0-9]/', $newPassword)) $errors[] = 'Include at least one symbol in your password.';
    if ($newPassword !== $confirmPassword) $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE users SET password_hash = ?, remember_token_hash = NULL, remember_expires = NULL WHERE id = ?');
        $stmt->execute([$hash, $_SESSION['user_id']]);

        unset($_SESSION['password_change_user_id'], $_SESSION['password_change_verified']);
        $_SESSION['password_changed_success'] = true;
        redirect('password_changed.php');
    }
}
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Password - Campus Connect</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/frontend/css/style.css">
</head>
<body class="account-page security-page">
<div class="dashboard-app">
<?php include dirname(__DIR__) . '/general/account_sidebar.php'; ?>
<main class="security-main">
        <header class="security-topbar"><a href="<?= BASE_URL ?>/profile.php">‹ <span>Back to dashboard</span></a><span>Account security</span></header>
        <div class="security-content-wrap">
            <div class="security-page-heading"><span class="account-eyebrow">ACCOUNT SECURITY</span><h1>Create a new password</h1><p>Choose a strong password you haven’t used before.</p></div>
            <div class="security-stepper" aria-label="Password change steps"><div class="complete"><span>✓</span><small>Current Password</small></div><i></i><div class="complete"><span>✓</span><small>OTP / 2FA</small></div><i></i><div class="current"><span>3</span><small>New Password</small></div></div>
            <section class="security-card">
                <div class="security-form-panel">
                    <h2>Set your new password</h2><p>Make it unique to help protect your account.</p>
                    <?php if ($errors): ?><div class="message message-error" role="alert"><?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?></div><?php endif; ?>
                    <form method="post" id="newPasswordForm">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <label for="new_password">New password</label>
                        <input type="password" id="new_password" name="new_password" autocomplete="new-password" minlength="8" required>
                        <ul class="password-rules" aria-label="Password requirements">
                            <li data-rule="length">At least 8 characters</li><li data-rule="number">Include a number</li><li data-rule="symbol">Include a symbol</li>
                        </ul>
                        <label for="confirm_password">Confirm new password</label>
                        <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" required>
                        <button class="btn btn-primary security-submit" type="submit">Change password</button>
                    </form>
                </div>
                <aside class="security-promo"><span class="security-shield" aria-hidden="true">✓</span><h3>Stronger password,<br>safer you.</h3><p>A unique password is an important step in protecting your account.</p></aside>
            </section>
        </div>
    </main>
</div>
<script>
const passwordInput = document.getElementById('new_password');
passwordInput.addEventListener('input', () => {
    const value = passwordInput.value;
    document.querySelector('[data-rule="length"]').classList.toggle('met', value.length >= 8);
    document.querySelector('[data-rule="number"]').classList.toggle('met', /[0-9]/.test(value));
    document.querySelector('[data-rule="symbol"]').classList.toggle('met', /[^a-zA-Z0-9]/.test(value));
});
</script>
</body>
</html>
