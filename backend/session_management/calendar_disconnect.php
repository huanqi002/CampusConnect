<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';
require __DIR__ . '/calendar_functions.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (int)currentUser()['id'] === calendarOrganizerId()) {
    $userId  = (int)currentUser()['id'];
    $refresh = findCalendarToken($conn, $userId);
    if ($refresh !== null) {
        revokeCalendarToken($refresh);
        deleteCalendarToken($conn, $userId);
    }
}

header('Location: index.php?msg=' . urlencode('Google Calendar disconnected. New sessions will no longer be added automatically.'));
exit;
