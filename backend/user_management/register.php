<?php
require_once __DIR__ . '/functions.php';

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

    $studentId = trim($_POST['student_id'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $university = trim($_POST['university_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($studentId === '') $errors[] = 'Student ID is required.';
    if ($fullName === '') $errors[] = 'Full name is required.';
    if ($university === '') $errors[] = 'University is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    elseif (!university_email_valid($email)) $errors[] = 'Please use your HELP university email address.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirmPassword) $errors[] = 'Passwords do not match.';

    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? OR student_id = ? LIMIT 1');
    $stmt->execute([$email, $studentId]);
    if ($stmt->fetch()) $errors[] = 'The email or student ID is already registered.';

    if (!$errors) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            'INSERT INTO users (student_id, email, full_name, university_name, password_hash, is_volunteer, is_active)
             VALUES (?, ?, ?, ?, ?, \'N\', \'Y\')'
        );
        $stmt->execute([$studentId, $email, $fullName, $university, $passwordHash]);

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
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
</head>
<body>
<div class="page">
    <h1 class="page-title">Create Account</h1>
    <p class="subtitle">Register for a Campus Connect student account.</p>

    <?php if ($errors): ?>
        <div class="message message-error">
            <?php foreach ($errors as $error): ?>
                <div><?= e($error) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <label for="student_id">Student ID</label>
            <input type="text" id="student_id" name="student_id" value="<?= e($studentId) ?>" required>

            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" value="<?= e($fullName) ?>" required>

            <label for="email">University Email</label>
            <input type="text" id="email" name="email" value="<?= e($email) ?>" placeholder="e.g. b2200824@helplive.edu.my" required>
            <div class="help-text">Use your HELP University email address.</div>

            <label for="university_name">University</label>
            <input type="text" id="university_name" name="university_name" value="<?= e($university) ?>" required>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>

            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required>

            <div class="btn-row">
                <button class="btn btn-primary" type="submit">Create Account</button>
                <a class="btn btn-secondary" href="<?= BASE_URL ?>/login.php">Back to Login</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
