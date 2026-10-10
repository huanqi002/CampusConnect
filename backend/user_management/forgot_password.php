<?php
require_once dirname(__DIR__) . '/general/account_functions.php';

$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $email = strtolower(trim($_POST['email'] ?? ''));
    unset($_SESSION['password_reset_user_id'], $_SESSION['password_reset_verified']);
    unset($_SESSION['reset_code_attempts']);
    $_SESSION['password_reset_email'] = $email;

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $pdo->prepare('SELECT id, email FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            $code = create_otp();
            $expiry = date('Y-m-d H:i:s', time() + 600);
            $stmt = $pdo->prepare('UPDATE users SET reset_code = ?, reset_expiry = ? WHERE id = ?');
            $stmt->execute([$code, $expiry, $user['id']]);
            if (send_otp($email, 'Campus Connect Password Reset Code', $code)) {
                $_SESSION['password_reset_user_id'] = (int)$user['id'];
            } else {
                $stmt = $pdo->prepare('UPDATE users SET reset_code = NULL, reset_expiry = NULL WHERE id = ?');
                $stmt->execute([$user['id']]);
                unset($_SESSION['password_reset_email']);
                set_flash('error', 'We could not send the reset email. Check the Microsoft Graph mail configuration and try again.');
                redirect('forgot_password.php');
            }
        }
    }

    redirect('verify_reset_code.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Campus Connect</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/frontend/css/style.css">
</head>
<body class="account-page">
<main class="account-auth-layout login-layout">
    <section class="account-auth-main">
        <a class="account-brand" href="<?= BASE_URL ?>/login.php" aria-label="Campus Connect"><img class="account-brand-logo" src="<?= BASE_URL ?>/frontend/images/campus-connect-logo.png" alt="Campus Connect"></a>
        <section class="account-card">
            <span class="account-eyebrow">ACCOUNT RECOVERY</span>
            <h1>Forgot your password?</h1>
            <p class="account-subtitle">Enter the email address on your account. If it matches, we’ll send a 6-digit reset code.</p>
            <?php flash(); ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <label for="email">University email</label>
                <input type="email" id="email" name="email" autocomplete="email" required autofocus>
                <button class="btn btn-primary account-submit" type="submit">Send reset code</button>
            </form>
            <p class="account-switch"><a href="<?= BASE_URL ?>/login.php">Back to login</a></p>
        </section>
        <p class="account-footer">Campus Connect · Peer support starts with a conversation.</p>
    </section>
    <aside class="account-login-art" aria-label="HELP University campus"><div><span>HERE FOR YOU,</span><strong>EVERY STEP.</strong><p>Let’s get you safely back into your account.</p></div></aside>
</main>
</body>
</html>
