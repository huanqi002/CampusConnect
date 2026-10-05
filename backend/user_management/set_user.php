<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/functions.php';

$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user = findUserById($conn, $id);

if (!$user) {
    header('Location: select_user.php');
    exit;
}

loginAsUser($user);

header('Location: ../session_management/index.php');
exit;
