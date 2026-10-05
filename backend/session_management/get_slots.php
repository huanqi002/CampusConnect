<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/functions.php';
header('Content-Type: application/json');

$volunteerId = isset($_GET['volunteer_id']) ? (int)$_GET['volunteer_id'] : 0;
$date        = $_GET['date'] ?? '';

if (!$volunteerId || !$date) {
    echo json_encode([]);
    exit;
}

echo json_encode(getAvailableTimes($conn, $volunteerId, $date));
