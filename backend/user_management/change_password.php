<?php
require_once dirname(__DIR__) . '/general/account_functions.php';
require_login();
$user = current_user();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $currentPassword = $_POST['current_password'] ?? '';

    if (!password_verify($currentPassword, $user['password_hash'])) {
        $error = 'Current password is incorrect.';
    } else {
        $otp = create_otp();
        $expiry = date('Y-m-d H:i:s', time() + 600);

        $stmt = $pdo->prepare('UPDATE users SET tfa_code = ?, tfa_expiry = ? WHERE id = ?');
        $stmt->execute([$otp, $expiry, $user['id']]);

        if (!send_otp($user['email'], 'Campus Connect Password Change OTP', $otp)) {
            $stmt = $pdo->prepare('UPDATE users SET tfa_code = NULL, tfa_expiry = NULL WHERE id = ?');
            $stmt->execute([$user['id']]);
            $error = 'The OTP could not be sent. Please try again.';
        } else {
            $_SESSION['password_change_user_id'] = $user['id'];
            unset($_SESSION['password_change_verified']);
            redirect('verify_password_otp.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Campus Connect</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/frontend/css/style.css">
</head>
<body class="account-page security-page">
<div class="dashboard-app">
<?php include dirname(__DIR__) . '/general/account_sidebar.php'; ?>
<main class="security-main">
        <header class="security-topbar"><a href="<?= BASE_URL ?>/profile.php">‹ <span>Back to dashboard</span></a><span><?= e($user['full_name']) ?></span></header>
        <div class="security-content-wrap">
            <div class="security-page-heading"><span class="account-eyebrow">ACCOUNT SECURITY</span><h1>Change password</h1><p>Keep your account safe with a strong, unique password.</p></div>
            <div class="security-stepper" aria-label="Password change steps"><div class="current"><span>1</span><small>Current Password</small></div><i></i><div><span>2</span><small>OTP / 2FA</small></div><i></i><div><span>3</span><small>New Password</small></div></div>
            <section class="security-card">
                <div class="security-form-panel">
                    <h2>Verify it’s you</h2><p>Enter your current password. We’ll send a 6-digit verification code to <strong><?= e($user['email']) ?></strong>.</p>
                    <?php if ($error): ?><div class="message message-error"><?= e($error) ?></div><?php endif; ?>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <label for="current_password">Current password</label>
                        <input type="password" id="current_password" name="current_password" autocomplete="current-password" placeholder="Enter your current password" required>
                        <button class="btn btn-primary security-submit" type="submit">Next <span aria-hidden="true">→</span></button>
                    </form>
                </div>
                <aside class="security-promo"><span class="security-shield" aria-hidden="true">✓</span><h3>Your security<br>matters</h3><p>Use a strong password to keep your account safe.</p></aside>
            </section>
        </div>
    </main>
</div>
</body>
</html>
