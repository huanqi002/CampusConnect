<?php

const SESSION_CATEGORIES = ['Academic', 'Technology', 'New Student', 'General Student'];

const FREE_SLOT_SQL = "FROM volunteers v
                       WHERE v.user_id = ? AND v.preferred_day = DAYNAME(?) AND v.is_available = 'Y'
                         AND NOT EXISTS (SELECT 1 FROM sessions s
                                         WHERE s.volunteer_id = v.user_id AND s.session_date = ?
                                           AND s.start_time = v.preferred_time AND s.status = 'Scheduled')";

const SESSION_WITH_NAMES_SQL = "SELECT s.*, us.full_name AS student_name, uv.full_name AS volunteer_name
                                FROM sessions s
                                JOIN users us ON s.user_id = us.id
                                JOIN users uv ON s.volunteer_id = uv.id";

function fetchActiveSessions(mysqli $conn, int $myId): mysqli_result
{
    $stmt = $conn->prepare(SESSION_WITH_NAMES_SQL . "
                             WHERE (s.user_id = ? OR s.volunteer_id = ?) AND s.status IN ('Pending', 'Scheduled')
                             ORDER BY s.session_date IS NULL, s.session_date, s.start_time, s.id");
    $stmt->bind_param('ii', $myId, $myId);
    $stmt->execute();
    return $stmt->get_result();
}

function findSessionById(mysqli $conn, int $sessionId): ?array
{
    $stmt = $conn->prepare(SESSION_WITH_NAMES_SQL . " WHERE s.id = ?");
    $stmt->bind_param('i', $sessionId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

function findActiveSession(mysqli $conn, int $sessionId): ?array
{
    $stmt = $conn->prepare(SESSION_WITH_NAMES_SQL . " WHERE s.id = ? AND s.status IN ('Pending', 'Scheduled')");
    $stmt->bind_param('i', $sessionId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

function updateSessionDetails(mysqli $conn, int $sessionId, string $category, string $description): void
{
    $stmt = $conn->prepare("UPDATE sessions SET category = ?, description = ? WHERE id = ? AND status = 'Pending'");
    $stmt->bind_param('ssi', $category, $description, $sessionId);
    $stmt->execute();
}

function getAvailableTimes(mysqli $conn, int $volunteerId, string $date): array
{
    $stmt = $conn->prepare("SELECT v.preferred_time " . FREE_SLOT_SQL . " ORDER BY v.preferred_time");
    $stmt->bind_param('iss', $volunteerId, $date, $date);
    $stmt->execute();
    $result = $stmt->get_result();

    $times = [];
    while ($row = $result->fetch_assoc()) {
        $times[] = substr($row['preferred_time'], 0, 5);
    }
    return $times;
}

function findAvailableSlot(mysqli $conn, int $volunteerId, string $date, string $time): ?array
{
    $stmt = $conn->prepare("SELECT v.id " . FREE_SLOT_SQL . " AND v.preferred_time = ?");
    $stmt->bind_param('isss', $volunteerId, $date, $date, $time);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

function createPendingSession(mysqli $conn, array $request): void
{
    $requestId   = (int)$request['id'];
    $userId      = (int)$request['user_id'];
    $volunteerId = (int)$request['volunteer_id'];
    $category    = $request['category'];
    $description = $request['description'];
    $mode        = $request['support_mode'];

    $stmt = $conn->prepare("INSERT INTO sessions (request_id, user_id, volunteer_id, category, description, support_mode, status)
                             VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
    $stmt->bind_param('iiisss', $requestId, $userId, $volunteerId, $category, $description, $mode);
    $stmt->execute();
}

function scheduleSession(mysqli $conn, int $sessionId, string $date, string $time, string $mode): void
{
    $stmt = $conn->prepare("UPDATE sessions SET session_date = ?, start_time = ?, support_mode = ?, status = 'Scheduled'
                             WHERE id = ? AND status = 'Pending'");
    $stmt->bind_param('sssi', $date, $time, $mode, $sessionId);
    $stmt->execute();
}

function findSessionWithStatus(mysqli $conn, int $sessionId, string $status): ?array
{
    $stmt = $conn->prepare("SELECT s.*, uv.full_name AS volunteer_name
                             FROM sessions s
                             JOIN users uv ON s.volunteer_id = uv.id
                             WHERE s.id = ? AND s.status = ?");
    $stmt->bind_param('is', $sessionId, $status);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

function findScheduledSession(mysqli $conn, int $sessionId): ?array
{
    return findSessionWithStatus($conn, $sessionId, 'Scheduled');
}

function updateSessionStatus(mysqli $conn, int $sessionId, string $status): void
{
    $stmt = $conn->prepare("UPDATE sessions SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $status, $sessionId);
    $stmt->execute();
}

function hasSessionStarted(array $session): bool
{
    return strtotime($session['session_date'] . ' ' . $session['start_time']) <= time();
}

function sessionDisplayStatus(array $session): string
{
    if ($session['status'] === 'Scheduled'
        && $session['session_date'] === date('Y-m-d')
        && !hasSessionStarted($session)) {
        return 'Upcoming';
    }
    return $session['status'];
}
