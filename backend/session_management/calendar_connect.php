<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';
require __DIR__ . '/calendar_functions.php';

requireLogin();

if (googleCalendarConfig() === null) {
    header('Location: index.php?err=' . urlencode('Google Calendar is not set up on this server yet.'));
    exit;
}
if (isCalendarConnected()) {
    header('Location: index.php?msg=' . urlencode('Google Calendar is already connected.'));
    exit;
}

// Random value Google sends back, so oauth_callback.php can tell this request came from us.
$_SESSION['calendar_oauth_state'] = bin2hex(random_bytes(16));

header('Location: ' . googleCalendarAuthUrl($_SESSION['calendar_oauth_state']));
exit;
