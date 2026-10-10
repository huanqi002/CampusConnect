<?php
require_once dirname(__DIR__) . '/general/account_functions.php';
if (empty($_SESSION['password_reset_verified']) || empty($_SESSION['password_reset_user_id'])) redirect('forgot_password.php');
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $password = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (strlen($password) < 8) $errors[] = 'Use at least 8 characters.';
    if (!preg_match('/[0-9]/', $password)) $errors[] = 'Include at least one number.';
    if (!preg_match('/[^a-zA-Z0-9]/', $password)) $errors[] = 'Include at least one symbol.';
    if ($password !== $confirm) $errors[] = 'The passwords do not match.';

    if (!$errors) {
        $stmt = $pdo->prepare('UPDATE users SET password_hash = ?, failed_attempts = 0, locked_until = NULL, remember_token_hash = NULL, remember_expires = NULL, reset_code = NULL, reset_expiry = NULL WHERE id = ?');
        $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $_SESSION['password_reset_user_id']]);
        unset($_SESSION['password_reset_verified'], $_SESSION['password_reset_user_id'], $_SESSION['password_reset_email']);
        set_flash('success', 'Your password has been reset. Log in with your new password.');
        redirect('login.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password - Campus Connect</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/frontend/css/style.css">
</head>
<body class="account-page security-page">
<div class="security-layout">
    <aside class="security-sidebar"><a class="account-brand account-brand-light" href="<?= BASE_URL ?>/login.php"><img class="account-brand-logo" src="<?= BASE_URL ?>/frontend/images/campus-connect-logo.png" alt="Campus Connect"></a><div class="security-sidebar-label">ACCOUNT RECOVERY</div><nav class="security-nav"><a class="active" href="#new-password"><span class="security-nav-icon">N</span>New password</a></nav></aside>
    <main class="security-main">
        <header class="security-topbar"><span>Account recovery</span><span>Verified email</span></header>
        <div class="security-content-wrap">
            <div class="security-page-heading"><span class="account-eyebrow">ACCOUNT RECOVERY</span><h1>Create a new password</h1><p>Choose a strong password to secure your account.</p></div>
            <div class="security-stepper" aria-label="Password recovery steps"><div class="complete"><span>✓</span><small>Email</small></div><i></i><div class="complete"><span>✓</span><small>Verify code</small></div><i></i><div class="current"><span>3</span><small>New password</small></div></div>
            <section class="security-card">
                <div id="new-password" class="security-form-panel">
                    <h2>Set your new password</h2><p>Use a password you haven’t used before.</p>
                    <?php if ($errors): ?><div class="message message-error" role="alert"><?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?></div><?php endif; ?>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <label for="new_password">New password</label><input type="password" id="new_password" name="new_password" autocomplete="new-password" minlength="8" required>
                        <ul class="password-rules"><li data-rule="length">At least 8 characters</li><li data-rule="number">Include a number</li><li data-rule="symbol">Include a symbol</li></ul>
                        <label for="confirm_password">Confirm new password</label><input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" required>
                        <button class="btn btn-primary security-submit" type="submit">Save new password</button>
                    </form>
                </div>
                <aside class="security-promo"><span class="security-shield" aria-hidden="true">✓</span><h3>Your account,<br>secure again.</h3><p>Keep your password private and unique.</p></aside>
            </section>
        </div>
    </main>
</div>
<script>
const passwordInput = document.getElementById('new_password');
passwordInput.addEventListener('input', () => { const value = passwordInput.value; document.querySelector('[data-rule="length"]').classList.toggle('met', value.length >= 8); document.querySelector('[data-rule="number"]').classList.toggle('met', /[0-9]/.test(value)); document.querySelector('[data-rule="symbol"]').classList.toggle('met', /[^a-zA-Z0-9]/.test(value)); });
</script>
</body>
</html>
