<?php
require __DIR__ . '/backend/user_management/login.php';
?>
=======
require_once __DIR__ . '/includes/functions.php';

if (!empty($_SESSION['user_id'])) {
    redirect('profile.php');
}
redirect('login.php');
?>
