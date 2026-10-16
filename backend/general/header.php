<?php
require_once __DIR__ . '/dashboard_header.php';
require_once __DIR__ . '/notifications.php';

$scriptKey = basename(dirname($_SERVER['SCRIPT_NAME'])) . '/' . basename($_SERVER['SCRIPT_NAME']);

$headerTitle ??= [
    'session_management/index.php'              => 'Session Dashboard',
    'session_management/schedule_list.php'      => 'Session Schedule',
    'session_management/schedule.php'           => 'Book a Session',
    'session_management/edit_session.php'       => 'Edit Session',
    'session_management/session_details.php'    => 'Session Details',
    'session_management/volunteer_requests.php' => 'Session Request',
    'session_management/session_request_details.php' => 'Session Request Details',
    'history_management/history.php'            => 'History',
    'history_management/history_details.php'    => 'History Details',
    'history_management/feedback.php'           => 'Rate Your Session',
    'request_management/request.php'            => 'Support Requests',
    'request_management/request_create.php'     => 'Create Support Request',
    'request_management/request_review.php'     => 'Request Details',
    'request_management/upcoming_request.php'   => 'Upcoming Request',
    'notification_management/notifications.php' => 'Notifications',
][$scriptKey] ?? 'Campus Connect';

$headerBack ??= [
    'history_management/history_details.php' => ['history.php', 'Back to history'],
    'request_management/request_review.php'  => ['request.php', 'Back to requests'],
][$scriptKey] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Campus Connect</title>
<link rel="stylesheet" href="../../frontend/css/style.css">
<link rel="stylesheet" href="../../frontend/css/navigation.css">
<link rel="stylesheet" href="../../frontend/css/profile.css">
</head>
<body>
<div class="layout">
<?php include __DIR__ . '/navigation.php'; ?>
<div class="layout-main">
<?php $headerUserId = (int)($_SESSION['user_id'] ?? 0);
echo renderDashboardHeader($headerTitle, dashboardHeaderUser($conn, $headerUserId), $headerBack, countUnreadNotifications($conn, $headerUserId)); ?>
<main class="page">
