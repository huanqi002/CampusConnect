<?php
require_once dirname(__DIR__) . '/general/account_functions.php';

$pending = $_SESSION['pending_registration'] ?? null;
if (!is_array($pending) || empty($pending['email'])) redirect('register.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $otp = trim($_POST['otp'] ?? '');
    $pending = $_SESSION['pending_registration'] ?? null;
    if (!is_array($pending) || ($pending['expires'] ?? 0) < time()) {
        unset($_SESSION['pending_registration']);
        $error = 'This verification code has expired. Please register again.';
    } elseif (!preg_match('/^\d{6}$/', $otp) || !hash_equals((string)$pending['code'], $otp)) {
        $error = 'That verification code is incorrect. Please try again.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$pending['email']]);
        if ($stmt->fetch()) {
            unset($_SESSION['pending_registration']);
            $error = 'This email is already registered. Try logging in instead.';
        } else {
            do {
                $studentId = 'CC' . strtoupper(bin2hex(random_bytes(10)));
                $stmt = $pdo->prepare('SELECT id FROM users WHERE student_id = ? LIMIT 1');
                $stmt->execute([$studentId]);
            } while ($stmt->fetch());
            $stmt = $pdo->prepare("INSERT INTO users (student_id, email, full_name, university_name, password_hash, is_volunteer, is_active) VALUES (?, ?, ?, ?, ?, 'N', 'Y')");
            $stmt->execute([$studentId, $pending['email'], $pending['full_name'], $pending['university_name'], $pending['password_hash']]);
            $_SESSION['created_email'] = $pending['email'];
            unset($_SESSION['pending_registration']);
            redirect('account_created.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - Campus Connect</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/frontend/css/style.css">
</head>
<body class="account-page">
<main class="account-auth-layout login-layout">
    <section class="account-auth-main">
        <a class="account-brand" href="<?= BASE_URL ?>/login.php" aria-label="Campus Connect"><img class="account-brand-logo" src="<?= BASE_URL ?>/frontend/images/campus-connect-logo.png" alt="Campus Connect"></a>
        <section class="account-card">
            <span class="account-eyebrow">EMAIL VERIFICATION</span>
            <h1>Check your email</h1>
            <p class="account-subtitle">Enter the 6-digit code sent to <strong><?= e($pending['email']) ?></strong>. The code expires in 10 minutes.</p>
            <?php if ($error): ?><div class="message message-error" role="alert"><?= e($error) ?></div><?php endif; ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <label for="otp">Verification code</label>
                <input type="text" id="otp" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required autofocus>
                <button class="btn btn-primary account-submit" type="submit">Verify email</button>
            </form>
            <p class="account-switch"><a href="<?= BASE_URL ?>/register.php">Start registration again</a></p>
        </section>
        <p class="account-footer">Campus Connect · Peer support starts with a conversation.</p>
    </section>
    <aside class="account-login-art" aria-label="HELP University campus"><div><span>ONE LAST STEP,</span><strong>YOU’RE IN.</strong><p>Verify your email to create your account.</p></div></aside>
</main>
</body>
</html>
