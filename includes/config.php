<?php
session_start();

define('DB_HOST', 'localhost');
define('DB_NAME', 'support_system');
define('DB_USER', 'root');
define('DB_PASS', '');
define('BASE_URL', '/CampusConnect');

// OTP codes are never shown in the UI. Configure Microsoft Graph in
// mail_config.php (copy config/mail_config.example.php) to send real messages.
define('DEV_SHOW_OTP', false);
$MAIL_CONFIG = is_file(dirname(__DIR__) . '/mail_config.php') ? require dirname(__DIR__) . '/mail_config.php' : [];
?>
