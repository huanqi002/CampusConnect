<?php
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'CampusConnect';

$BASE_URL = '/CampusConnect';

$DEV_TOOLS = false;

$localConfig = __DIR__ . '/config.local.php';
if (is_file($localConfig)) {
    require $localConfig;
}

define('DB_HOST', $DB_HOST);
define('DB_NAME', $DB_NAME);
define('DB_USER', $DB_USER);
define('DB_PASS', $DB_PASS);
define('BASE_URL', $BASE_URL);

// Shown when a user has not uploaded their own photo.
define('DEFAULT_PROFILE_PICTURE', BASE_URL . '/uploads/profile/profile_default.png');
define('DEFAULT_COVER_PHOTO', BASE_URL . '/uploads/covers/cover_default.png');

define('DEV_SHOW_OTP', false);

$mailConfigFile = __DIR__ . '/mail_config.php';
$MAIL_CONFIG = is_file($mailConfigFile) ? require $mailConfigFile : [];

date_default_timezone_set('Asia/Kuala_Lumpur');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
} catch (mysqli_sql_exception $e) {
    die('Connection failed: ' . $e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
