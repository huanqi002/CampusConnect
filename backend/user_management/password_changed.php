<?php
require_once dirname(__DIR__) . '/general/account_functions.php';
require_login();
if (empty($_SESSION['password_changed_success'])) {
    redirect('profile.php');
}
unset($_SESSION['password_changed_success']);
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Changed - Campus Connect</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/frontend/css/style.css">
</head>
<body class="account-page success-page">
<main class="success-card">
    <a class="account-brand" href="<?= BASE_URL ?>/profile.php" aria-label="Campus Connect dashboard"><img class="account-brand-logo" src="<?= BASE_URL ?>/frontend/images/campus-connect-logo.png" alt="Campus Connect — Connecting Students"></a>
    <div class="success-illustration"><span class="success-check" aria-hidden="true">&#10003;</span><i></i><b></b></div>
    <span class="account-eyebrow">ALL DONE</span>
    <h1>Password changed!</h1>
    <p>Your password has been updated successfully. Your account is ready to use.</p>
    <a class="btn btn-primary success-button" href="<?= BASE_URL ?>/profile.php">Back to dashboard <span aria-hidden="true">→</span></a>
</main>
</body>
</html>
