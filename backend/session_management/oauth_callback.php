<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';
require __DIR__ . '/calendar_functions.php';

requireLogin();

$expected = $_SESSION['calendar_oauth_state'] ?? '';
unset($_SESSION['calendar_oauth_state']);

if (isset($_GET['error'])) {
    header('Location: index.php?err=' . urlencode('Google Calendar was not connected.'));
    exit;
}
if ($expected === '' || !hash_equals($expected, (string)($_GET['state'] ?? '')) || empty($_GET['code'])) {
    header('Location: index.php?err=' . urlencode('Could not verify the Google sign-in. Please try again.'));
    exit;
}

$refreshToken = googleCalendarExchangeCode((string)$_GET['code']);
if ($refreshToken === null) {
    header('Location: index.php?err=' . urlencode('Google did not accept the connection. Please try again.'));
    exit;
}

saveCalendarToken($refreshToken, (int)currentUser()['id']);

header('Location: index.php');
exit;
