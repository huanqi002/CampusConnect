<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';
require __DIR__ . '/calendar_functions.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $refresh = findCalendarToken();
    if ($refresh !== null) {
        revokeCalendarToken($refresh);
        deleteCalendarToken();
    }
}

header('Location: index.php');
exit;
