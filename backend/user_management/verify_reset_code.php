<?php
require_once dirname(__DIR__) . '/general/account_functions.php';

if (empty($_SESSION['password_reset_email'])) redirect('forgot_password.php');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $code = trim($_POST['otp'] ?? '');
    $attempts = (int)($_SESSION['reset_code_attempts'] ?? 0);
    $userId = (int)($_SESSION['password_reset_user_id'] ?? 0);
    $stmt = $pdo->prepare('SELECT reset_code, reset_expiry FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if ($attempts >= 5) {
        $error = 'Too many incorrect codes. Request a new reset code to continue.';
    } elseif (!$userId || !$user || empty($user['reset_code']) || empty($user['reset_expiry']) || strtotime($user['reset_expiry']) < time()) {
        $error = 'That code is invalid or expired. Request a new reset code and try again.';
    } elseif (!hash_equals((string)$user['reset_code'], $code)) {
        $_SESSION['reset_code_attempts'] = $attempts + 1;
        $error = 'That code is invalid or expired. Request a new reset code and try again.';
    } else {
        $_SESSION['password_reset_verified'] = true;
        unset($_SESSION['reset_code_attempts']);
        $stmt = $pdo->prepare('UPDATE users SET reset_code = NULL, reset_expiry = NULL WHERE id = ?');
        $stmt->execute([$userId]);
        redirect('reset_password.php');
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Reset Code - Campus Connect</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/frontend/css/style.css">
</head>
<body class="account-page security-page">
<div class="security-layout">
    <aside class="security-sidebar">
        <a class="account-brand account-brand-light" href="<?= BASE_URL ?>/login.php"><img class="account-brand-logo" src="<?= BASE_URL ?>/frontend/images/campus-connect-logo.png" alt="Campus Connect"></a>
        <div class="security-sidebar-label">ACCOUNT RECOVERY</div>
        <nav class="security-nav" aria-label="Account recovery"><a class="active" href="<?= BASE_URL ?>/forgot_password.php"><span class="security-nav-icon">R</span>Reset password</a><a href="<?= BASE_URL ?>/login.php"><span class="security-nav-icon">L</span>Back to login</a></nav>
    </aside>
    <main class="security-main">
        <header class="security-topbar"><a href="<?= BASE_URL ?>/forgot_password.php">‹ <span>Back</span></a><span>Account recovery</span></header>
        <div class="security-content-wrap">
            <div class="security-page-heading"><span class="account-eyebrow">ACCOUNT RECOVERY</span><h1>Verify your email</h1><p>Enter the 6-digit code sent to your email address.</p></div>
            <div class="security-stepper" aria-label="Password recovery steps"><div class="complete"><span>✓</span><small>Email</small></div><i></i><div class="current"><span>2</span><small>Verify code</small></div><i></i><div><span>3</span><small>New password</small></div></div>
            <section class="security-card otp-card">
                <aside class="security-promo otp-promo"><span class="security-shield" aria-hidden="true">✓</span><h3>Secure account<br>recovery</h3><p>Your reset code expires after 10 minutes.</p></aside>
                <div class="security-form-panel otp-form-panel">
                    <h2>Check your inbox</h2><p>If <strong><?= e($_SESSION['password_reset_email']) ?></strong> belongs to an account, a reset code has been sent.</p>
                    <?php if ($error): ?><div class="message message-error" role="alert"><?= e($error) ?></div><?php endif; ?>
                    <form method="post" id="resetCodeForm">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <label for="reset_code_digit_1">6-digit reset code</label>
                        <div class="otp-digit-group" role="group" aria-label="6-digit reset code">
                            <?php for ($digit = 1; $digit <= 6; $digit++): ?><input class="otp-digit" id="reset_code_digit_<?= $digit ?>" type="text" inputmode="numeric" pattern="[0-9]" maxlength="1" aria-label="Digit <?= $digit ?>" <?= $digit === 1 ? 'autocomplete="one-time-code" autofocus' : 'autocomplete="off"' ?> required><?php endfor; ?>
                        </div>
                        <input type="hidden" id="otp" name="otp">
                        <p class="otp-expiry-note">Check your spam folder if it’s not in your inbox.</p>
                        <button class="btn btn-primary security-submit" type="submit">Verify code <span aria-hidden="true">→</span></button>
                    </form>
                    <p class="account-switch"><a href="<?= BASE_URL ?>/forgot_password.php">Request another code</a></p>
                </div>
            </section>
        </div>
    </main>
</div>
<script>
const codeBoxes = Array.from(document.querySelectorAll('.otp-digit'));
const codeValue = document.getElementById('otp');
function updateCode() { codeValue.value = codeBoxes.map(box => box.value).join(''); }
codeBoxes.forEach((box, index) => {
    box.addEventListener('input', () => { box.value = box.value.replace(/\D/g, '').slice(-1); updateCode(); if (box.value && codeBoxes[index + 1]) codeBoxes[index + 1].focus(); });
    box.addEventListener('keydown', event => { if (event.key === 'Backspace' && !box.value && codeBoxes[index - 1]) codeBoxes[index - 1].focus(); });
    box.addEventListener('paste', event => { const text = (event.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6); if (!text) return; event.preventDefault(); text.split('').forEach((digit, i) => { if (codeBoxes[i]) codeBoxes[i].value = digit; }); updateCode(); codeBoxes[Math.min(text.length, 5)].focus(); });
});
</script>
</body>
</html>
