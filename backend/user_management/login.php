<?php
require_once dirname(__DIR__) . '/general/account_functions.php';
restore_remembered_login();

if (!empty($_SESSION['user_id'])) {
    redirect('profile.php');
}

$error = '';
$email = '';
$attemptDisplay = null;
$loginLockoutEnabled = (bool)$pdo->query("SHOW COLUMNS FROM users LIKE 'failed_attempts'")->fetch();
$sessionLocked = !empty($_SESSION['unknown_login_locked_until']) && $_SESSION['unknown_login_locked_until'] > time();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($sessionLocked) {
        $error = 'Too many attempts. Try again in 15 minutes.';
        $attemptDisplay = 3;
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
    }

    if ($sessionLocked) {
        // Requests remain blocked until the session lock expires.
    } elseif (!$user) {
        $unknownAttempts = (int)($_SESSION['unknown_login_attempts'] ?? 0) + 1;
        $_SESSION['unknown_login_attempts'] = $unknownAttempts;
        $attemptDisplay = min($unknownAttempts, 3);
        if ($unknownAttempts >= 3) {
            $_SESSION['unknown_login_locked_until'] = time() + 900;
            $error = 'Too many attempts. Try again in 15 minutes.';
        } else {
            $error = 'Invalid email or password. Please try again.';
        }
    } elseif ($loginLockoutEnabled && !empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
        $error = 'Your account is locked. Try again in 15 minutes.';
        $attemptDisplay = 3;
    } elseif (!password_verify($password, $user['password_hash'])) {
        if ($loginLockoutEnabled) {
            $failedAttempts = (int)$user['failed_attempts'] + 1;
            $attemptDisplay = min($failedAttempts, 3);
            if ($failedAttempts >= 3) {
                $stmt = $pdo->prepare('UPDATE users SET failed_attempts = 0, locked_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id = ?');
                $stmt->execute([$user['id']]);
                $error = 'Account locked after 3 failed attempts. Try again in 15 minutes.';
            } else {
                $stmt = $pdo->prepare('UPDATE users SET failed_attempts = ? WHERE id = ?');
                $stmt->execute([$failedAttempts, $user['id']]);
                $error = 'Invalid email or password. Please try again.';
            }
        } else {
            $attemptDisplay = min((int)($_SESSION['unknown_login_attempts'] ?? 0) + 1, 3);
            $_SESSION['unknown_login_attempts'] = $attemptDisplay;
            if ($attemptDisplay >= 3) {
                $_SESSION['unknown_login_locked_until'] = time() + 900;
                $error = 'Too many attempts. Try again in 15 minutes.';
            } else {
                $error = 'Invalid email or password. Please try again.';
            }
        }
    } else {
        if ($loginLockoutEnabled) {
            $stmt = $pdo->prepare('UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = ?');
            $stmt->execute([$user['id']]);
        }
        unset($_SESSION['unknown_login_attempts'], $_SESSION['unknown_login_locked_until']);
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['role'] = ($user['is_volunteer'] ?? 'N') === 'Y' ? 'volunteer' : 'student';
        $_SESSION['name'] = $user['full_name'];
        if (isset($_POST['remember_me']) && $loginLockoutEnabled && (bool)$pdo->query("SHOW COLUMNS FROM users LIKE 'remember_token_hash'")->fetch()) {
            remember_login((int)$user['id']);
        }
        redirect('profile.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Campus Connect</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/frontend/css/style.css">
</head>
<body class="account-page">
<main class="account-auth-layout login-layout">
    <section class="account-auth-main">
    <a class="account-brand" href="<?= BASE_URL ?>/" aria-label="Campus Connect home"><img class="account-brand-logo" src="<?= BASE_URL ?>/frontend/images/campus-connect-logo.png" alt="Campus Connect — Connecting Students"></a>
    <section class="account-card">
    <span class="account-eyebrow">WELCOME BACK</span>
    <h1>Log in to Campus Connect</h1>
    <p class="account-subtitle">Sign in to connect with your campus community.</p>
    <?php flash(); ?>
    <?php if ($error): ?>
        <div class="message message-error login-error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <label for="email">University Email</label>
            <input type="email" id="email" name="email" autocomplete="email" value="<?= e($email) ?>" required>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>

            <div class="login-options">
                <label class="remember-option"><input type="checkbox" name="remember_me" value="1"> <span>Remember me</span></label>
                <a href="<?= BASE_URL ?>/forgot_password.php">Forgot Password?</a>
            </div>
            <button class="btn btn-primary account-submit" type="submit">Login</button>
        </form>
        <p class="login-lockout-note">Account will be locked after 3 failed attempts</p>
        <?php if ($attemptDisplay !== null && $attemptDisplay < 3): ?><p class="login-attempt-count">Attempt <?= (int)$attemptDisplay ?> of 3</p><?php endif; ?>
        <p class="account-switch">Don’t have an account? <a href="<?= BASE_URL ?>/register.php">Create Account</a></p>
    </section>
    <p class="account-footer">Campus Connect · Peer support starts with a conversation.</p>
    </section>
    <aside class="account-login-art" aria-label="Campus Connect university community"><div><span>YOUR CAMPUS,</span><strong>CONNECTED.</strong><p>Find support. Share what you know.<br>Grow together.</p></div></aside>
</main>
</body>
</html>
