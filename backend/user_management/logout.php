<?php
<<<<<<< Updated upstream
require __DIR__ . '/../general/config.php';
require __DIR__ . '/functions.php';

logoutUser();
header('Location: select_user.php');
=======
require_once dirname(__DIR__) . '/general/config.php';
require_once dirname(__DIR__) . '/general/account_functions.php';
forget_remembered_login();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $cookie = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $cookie['path'], $cookie['domain'], $cookie['secure'], $cookie['httponly']);
}
session_destroy();
header('Location: ' . BASE_URL . '/login.php');
>>>>>>> Stashed changes
exit;
