<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/functions.php';

logoutUser();
header('Location: select_user.php');
exit;
