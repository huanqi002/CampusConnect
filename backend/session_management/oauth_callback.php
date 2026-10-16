<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';
require __DIR__ . '/calendar_functions.php';

requireLogin();

if ((int)currentUser()['id'] !== calendarOrganizerId()) {
    header('Location: index.php?err=' . urlencode('Only the calendar organizer account can connect Google Calendar.'));
    exit;
}

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

try {
    saveCalendarToken($conn, (int)currentUser()['id'], $refreshToken);
} catch (mysqli_sql_exception $e) {
    error_log('Saving calendar token failed: ' . $e->getMessage());
    header('Location: index.php?err=' . urlencode('Could not save the connection. Please try again.'));
    exit;
}

header('Location: index.php?msg=' . urlencode('Google Calendar connected. Confirmed sessions are now added automatically and both people are invited by email.'));
exit;
