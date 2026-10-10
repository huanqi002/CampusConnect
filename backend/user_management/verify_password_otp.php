<?php
require_once dirname(__DIR__) . '/general/account_functions.php';
require_login();
$currentUser = current_user();

if (empty($_SESSION['password_change_user_id']) || (int)$_SESSION['password_change_user_id'] !== (int)$_SESSION['user_id']) {
    redirect('change_password.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $otp = trim($_POST['otp'] ?? '');

    $stmt = $pdo->prepare('SELECT tfa_code, tfa_expiry FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user || empty($user['tfa_code']) || empty($user['tfa_expiry']) || strtotime($user['tfa_expiry']) < time()) {
        $error = 'The OTP has expired. Please request a new OTP.';
    } elseif (!hash_equals((string)$user['tfa_code'], $otp)) {
        $error = 'Invalid OTP. Please try again.';
    } else {
        $_SESSION['password_change_verified'] = true;
        $stmt = $pdo->prepare('UPDATE users SET tfa_code = NULL, tfa_expiry = NULL WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        redirect('set_new_password.php');
    }
}

$user = $currentUser;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - Campus Connect</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/frontend/css/style.css">
</head>
<body class="account-page security-page">
<div class="dashboard-app">
<?php include dirname(__DIR__) . '/general/account_sidebar.php'; ?>
<main class="security-main">
        <header class="security-topbar"><a href="<?= BASE_URL ?>/change_password.php">‹ <span>Back</span></a><span><?= e($currentUser['full_name']) ?></span></header>
        <div class="security-content-wrap">
            <div class="security-page-heading"><span class="account-eyebrow">ACCOUNT SECURITY</span><h1>Two-factor authentication</h1><p>One quick check to make sure it’s really you.</p></div>
            <div class="security-stepper" aria-label="Password change steps"><div class="complete"><span>✓</span><small>Current Password</small></div><i></i><div class="current"><span>2</span><small>OTP / 2FA</small></div><i></i><div><span>3</span><small>New Password</small></div></div>
            <section class="security-card otp-card">
                <aside class="security-promo otp-promo"><span class="security-shield" aria-hidden="true">✓</span><h3>Your security<br>is our priority</h3><p>This extra step helps keep your account safe.</p></aside>
                <div class="security-form-panel otp-form-panel">
                    <h2>Check your email</h2><p>We sent a 6-digit code to<br><strong><?= e($currentUser['email']) ?></strong></p>
                    <?php if ($error): ?><div class="message message-error"><?= e($error) ?></div><?php endif; ?>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <label for="otp">6-digit verification code</label>
                        <div class="otp-digit-group" role="group" aria-label="6-digit verification code">
                            <?php for ($digit = 1; $digit <= 6; $digit++): ?>
                                <input class="otp-digit" type="text" inputmode="numeric" pattern="[0-9]" maxlength="1" aria-label="Digit <?= $digit ?>" <?= $digit === 1 ? 'autocomplete="one-time-code" autofocus' : 'autocomplete="off"' ?> required>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" id="otp" name="otp">
                        <p class="otp-expiry-note">The code expires in 10 minutes.</p>
                        <button class="btn btn-primary security-submit" type="submit">Verify code <span aria-hidden="true">→</span></button>
                    </form>
                </div>
            </section>
        </div>
    </main>
</div>
<script>
const otpBoxes = Array.from(document.querySelectorAll('.otp-digit'));
const otpValue = document.getElementById('otp');
function syncOtp() { otpValue.value = otpBoxes.map(box => box.value).join(''); }
otpBoxes.forEach((box, index) => {
    box.addEventListener('input', () => {
        box.value = box.value.replace(/\D/g, '').slice(-1);
        syncOtp();
        if (box.value && otpBoxes[index + 1]) otpBoxes[index + 1].focus();
    });
    box.addEventListener('keydown', event => {
        if (event.key === 'Backspace' && !box.value && otpBoxes[index - 1]) otpBoxes[index - 1].focus();
    });
    box.addEventListener('paste', event => {
        const pasted = (event.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
        if (!pasted) return;
        event.preventDefault();
        pasted.split('').forEach((digit, i) => { if (otpBoxes[i]) otpBoxes[i].value = digit; });
        syncOtp();
        otpBoxes[Math.min(pasted.length, 5)].focus();
    });
});
</script>
</body>
</html>
