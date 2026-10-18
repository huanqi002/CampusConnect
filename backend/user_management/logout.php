<?php
require_once dirname(__DIR__) . '/general/account_functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    forget_remembered_login();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $cookie = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $cookie['path'], $cookie['domain'], $cookie['secure'], $cookie['httponly']);
    }
    session_destroy();
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

header('Location: ' . BASE_URL . '/profile.php');
exit;
