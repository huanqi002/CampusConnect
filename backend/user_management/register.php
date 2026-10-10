<?php
require_once dirname(__DIR__) . '/general/account_functions.php';

if (!empty($_SESSION['user_id'])) {
    redirect('profile.php');
}

$errors = [];
$studentId = '';
$fullName = '';
$email = '';
$university = 'HELP University';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $studentId = strtoupper(trim($_POST['student_id'] ?? ''));
    $fullName = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $university = trim($_POST['university_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($studentId === '') $errors[] = 'Enter your student or staff ID.';
    if ($fullName === '' || $university === '') $errors[] = 'Please complete all the fields.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    elseif (!university_email_valid($email)) $errors[] = 'Use your HELP University email address.';
    if (strlen($password) < 8) $errors[] = 'Your password must be at least 8 characters.';
    if ($password !== $confirmPassword) $errors[] = 'The passwords do not match.';

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? OR student_id = ? LIMIT 1');
        $stmt->execute([$email, $studentId]);
        if ($stmt->fetch()) {
            $errors[] = 'This email or student ID is already registered. Try logging in instead.';
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare("INSERT INTO users (student_id, email, full_name, university_name, password_hash, is_volunteer, is_active) VALUES (?, ?, ?, ?, ?, 'N', 'Y')");
        $stmt->execute([$studentId, $email, $fullName, $university, password_hash($password, PASSWORD_DEFAULT)]);
        $_SESSION['created_email'] = $email;
        redirect('account_created.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Campus Connect</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/frontend/css/style.css">
</head>
<body class="account-page">
<main class="account-auth-layout">
    <aside class="account-showcase account-showcase-register">
        <a class="account-brand account-brand-light" href="<?= BASE_URL ?>/" aria-label="Campus Connect home"><img class="account-brand-logo" src="<?= BASE_URL ?>/frontend/images/campus-connect-logo.png" alt="Campus Connect — Connecting Students"></a>
        <div class="account-showcase-copy"><span>CONNECT. SUPPORT. GROW.</span><h2>Small actions<br>create a big impact.</h2><p>Be part of a community that learns together and grows stronger, one act of support at a time.</p></div>
        <div class="account-showcase-campus" aria-hidden="true"></div>
        <span class="account-showcase-caption">A stronger campus starts with you.</span>
    </aside>
    <section class="account-auth-main">
    <section class="account-card">
        <span class="account-eyebrow">JOIN YOUR COMMUNITY</span>
        <h1>Create your account</h1>
        <p class="account-subtitle">Start with a student profile. You can choose to volunteer from your dashboard anytime.</p>
        <?php if ($errors): ?><div class="message message-error" role="alert"><?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?></div><?php endif; ?>
        <form method="post" action="<?= BASE_URL ?>/register.php">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label for="student_id">Student / staff ID</label>
            <input type="text" id="student_id" name="student_id" autocomplete="off" value="<?= e($studentId) ?>" required>
            <label for="full_name">Full name</label>
            <input type="text" id="full_name" name="full_name" autocomplete="name" value="<?= e($fullName) ?>" required>
            <label for="email">University email</label>
            <input type="email" id="email" name="email" autocomplete="email" value="<?= e($email) ?>" required>
            <label for="university_name">University</label>
            <input type="text" id="university_name" name="university_name" autocomplete="organization" value="<?= e($university) ?>" required>
            <label for="password">Password</label>
            <input type="password" id="password" name="password" autocomplete="new-password" minlength="8" required>
            <p class="help-text">Use at least 8 characters.</p>
            <label for="confirm_password">Confirm password</label>
            <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" minlength="8" required>
            <button class="btn btn-primary account-submit" type="submit">Create account</button>
        </form>
        <p class="account-switch">Already have an account? <a href="<?= BASE_URL ?>/login.php">Log in</a></p>
    </section>
    <p class="account-footer">Campus Connect · Peer support starts with a conversation.</p>
    </section>
</main>
</body>
</html>
