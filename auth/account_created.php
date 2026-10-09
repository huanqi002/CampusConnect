<?php
require_once dirname(__DIR__) . '/includes/functions.php';

$email = $_SESSION['created_email'] ?? '';
unset($_SESSION['created_email']);

if ($email === '') {
    redirect('register.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Created - Campus Connect</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/frontend/css/style.css">
</head>
<body class="account-page success-page">
<main class="success-card account-created-success">
    <a class="account-brand" href="<?= BASE_URL ?>/" aria-label="Campus Connect home"><img class="account-brand-logo" src="<?= BASE_URL ?>/frontend/images/campus-connect-logo.png" alt="Campus Connect — Connecting Students"></a>
    <div class="success-illustration"><span class="success-check" aria-hidden="true">&#10003;</span><i></i><b></b></div>
    <span class="account-eyebrow">YOU’RE ALL SET</span>
    <h1>Account created!</h1>
    <p>Your account is ready. Log in with your email and password to continue.</p>
    <p class="account-created-email"><span>Registered email</span><strong><?= e($email) ?></strong></p>
    <a class="btn btn-primary success-button" href="<?= BASE_URL ?>/login.php">Go to login <span aria-hidden="true">→</span></a>
</main>
</body>
</html>
