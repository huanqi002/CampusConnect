<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/functions.php';
header('Content-Type: application/json');

$volunteerId = isset($_GET['volunteer_id']) ? (int)$_GET['volunteer_id'] : 0;
$date        = $_GET['date'] ?? '';
$excludeId   = isset($_GET['exclude_session']) ? (int)$_GET['exclude_session'] : 0;

if (!$volunteerId || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['busy' => []]);
    exit;
}

echo json_encode(['busy' => getBusyTimes($conn, $volunteerId, $date, $excludeId)]);
