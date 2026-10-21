<?php

const SESSION_CATEGORIES = ['Academic', 'Technology', 'New Student', 'General Student'];

// Bookable hours on a volunteer's preferred day, in 30-minute slots.
const SLOT_DAY_START = '08:00';
const SLOT_DAY_END = '20:00';

// Older sessions were saved without an end time; treat them as one hour long.
const BUSY_END_SQL = "COALESCE(end_time, ADDTIME(start_time, '01:00:00'))";
// The last placeholder excludes one session (the one being edited); pass 0 to exclude none.
const BUSY_SESSIONS_SQL = "FROM sessions
                           WHERE volunteer_id = ? AND session_date = ? AND status IN ('Pending', 'Scheduled') AND id <> ?";

const SESSION_WITH_NAMES_SQL = "SELECT s.*, us.full_name AS student_name, us.email AS student_email,
                                       uv.full_name AS volunteer_name, uv.email AS volunteer_email
                                FROM sessions s
                                JOIN users us ON s.user_id = us.id
                                JOIN users uv ON s.volunteer_id = uv.id";

// Users see sessions only from the side of their current role: students as the student, volunteers as the volunteer.
function sessionRoleColumn(?string $role): string
{
    return $role === 'volunteer' ? 'volunteer_id' : 'user_id';
}

function isMySession(array $session, int $myId, ?string $role): bool
{
    return (int) $session[sessionRoleColumn($role)] === $myId;
}

function fetchActiveSessions(mysqli $conn, int $myId, ?string $role): mysqli_result
{
    $stmt = $conn->prepare(SESSION_WITH_NAMES_SQL . "
                             WHERE s." . sessionRoleColumn($role) . " = ? AND s.status IN ('Pending', 'Scheduled')
                             ORDER BY s.session_date IS NULL, s.session_date, s.start_time, s.id");
    $stmt->bind_param('i', $myId);
    $stmt->execute();
    return $stmt->get_result();
}

function fetchUnscheduledSessions(mysqli $conn, int $myId, ?string $role): mysqli_result
{
    $stmt = $conn->prepare(SESSION_WITH_NAMES_SQL . "
                             WHERE s." . sessionRoleColumn($role) . " = ? AND s.status = 'Unscheduled'
                             ORDER BY s.created_at, s.id");
    $stmt->bind_param('i', $myId);
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
    $stmt = $conn->prepare(SESSION_WITH_NAMES_SQL . " WHERE s.id = ? AND s.status IN ('Unscheduled', 'Pending', 'Scheduled')");
    $stmt->bind_param('i', $sessionId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

function updatePendingSession(
    mysqli $conn,
    int $sessionId,
    string $description,
    string $date,
    string $start,
    string $end,
    string $mode
): void {
    $stmt = $conn->prepare("UPDATE sessions SET description = ?, session_date = ?, start_time = ?, end_time = ?, support_mode = ?
                             WHERE id = ? AND status = 'Pending'");
    $stmt->bind_param('sssssi', $description, $date, $start, $end, $mode, $sessionId);
    $stmt->execute();
}

// Weekday => preferred time ranges [['start' => 'HH:MM', 'end' => 'HH:MM'], ...] from the volunteer's active rows.
function getPreferredTimes(mysqli $conn, int $volunteerId): array
{
    $stmt = $conn->prepare("SELECT preferred_day,
                                   TIME_FORMAT(start_time, '%H:%i') AS start,
                                   TIME_FORMAT(end_time, '%H:%i') AS end
                             FROM volunteers WHERE user_id = ? AND is_available = 'Y' ORDER BY start_time");
    $stmt->bind_param('i', $volunteerId);
    $stmt->execute();

    $times = [];
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        $times[$row['preferred_day']][] = ['start' => $row['start'], 'end' => $row['end']];
    }
    return $times;
}

function getPreferredDays(mysqli $conn, int $volunteerId): array
{
    return array_keys(getPreferredTimes($conn, $volunteerId));
}

function getBusyTimes(mysqli $conn, int $volunteerId, string $date, int $excludeSessionId = 0): array
{
    $stmt = $conn->prepare("SELECT TIME_FORMAT(start_time, '%H:%i') AS start, TIME_FORMAT(" . BUSY_END_SQL . ", '%H:%i') AS end "
        . BUSY_SESSIONS_SQL . " ORDER BY start_time");
    $stmt->bind_param('isi', $volunteerId, $date, $excludeSessionId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function isTimeRangeFree(mysqli $conn, int $volunteerId, string $date, string $start, string $end, int $excludeSessionId = 0): bool
{
    $stmt = $conn->prepare("SELECT 1 " . BUSY_SESSIONS_SQL . " AND start_time < ? AND " . BUSY_END_SQL . " > ? LIMIT 1");
    $stmt->bind_param('isiss', $volunteerId, $date, $excludeSessionId, $end, $start);
    $stmt->execute();
    return $stmt->get_result()->fetch_row() === null;
}

function isSlotTime(string $time): bool
{
    return (bool) preg_match('/^([01]\d|2[0-3]):(00|30)$/', $time)
        && $time >= SLOT_DAY_START && $time <= SLOT_DAY_END;
}

// Checks a date/time picked on the booking or edit form. Returns an error message, or null when it can be booked.
// Needs general/format.php.
function validateSessionTime(mysqli $conn, int $volunteerId, string $date, string $start, string $end, int $excludeSessionId = 0): ?string
{
    $day = DateTime::createFromFormat('!Y-m-d', $date);

    if (!$day || $day->format('Y-m-d') !== $date || $date < date('Y-m-d')) {
        return 'Please choose a date from today onwards.';
    }
    if (!in_array($day->format('l'), getPreferredDays($conn, $volunteerId), true)) {
        return 'The volunteer is not available on that day. Please choose a green date.';
    }
    if (!isSlotTime($start) || !isSlotTime($end) || $start >= $end) {
        return 'Please choose an end time after the start time, between ' . formatTime(SLOT_DAY_START) . ' and ' . formatTime(SLOT_DAY_END) . '.';
    }
    if ($date === date('Y-m-d') && $start <= date('H:i')) {
        return 'That start time has already passed. Please choose a later time.';
    }
    if (!isTimeRangeFree($conn, $volunteerId, $date, $start, $end, $excludeSessionId)) {
        return 'The volunteer is busy during part of that time. Please choose another time.';
    }
    return null;
}

function createUnscheduledSession(mysqli $conn, array $request): void
{
    $requestId = (int) $request['id'];
    $userId = (int) $request['user_id'];
    $volunteerId = (int) $request['volunteer_id'];
    $category = $request['category'];
    $description = $request['description'];
    $mode = $request['support_mode'];

    $stmt = $conn->prepare("INSERT INTO sessions (request_id, user_id, volunteer_id, category, description, support_mode, status)
                             VALUES (?, ?, ?, ?, ?, ?, 'Unscheduled')");
    $stmt->bind_param('iiisss', $requestId, $userId, $volunteerId, $category, $description, $mode);
    $stmt->execute();
}

function requestSessionTime(mysqli $conn, int $sessionId, string $date, string $start, string $end, string $mode): void
{
    $stmt = $conn->prepare("UPDATE sessions SET session_date = ?, start_time = ?, end_time = ?, support_mode = ?, status = 'Pending'
                             WHERE id = ? AND status = 'Unscheduled'");
    $stmt->bind_param('ssssi', $date, $start, $end, $mode, $sessionId);
    $stmt->execute();
}

function fetchSessionRequests(mysqli $conn, int $volunteerId): mysqli_result
{
    $stmt = $conn->prepare(SESSION_WITH_NAMES_SQL . "
                             WHERE s.volunteer_id = ? AND s.status = 'Pending'
                             ORDER BY s.session_date, s.start_time, s.id");
    $stmt->bind_param('i', $volunteerId);
    $stmt->execute();
    return $stmt->get_result();
}

function findSessionRequest(mysqli $conn, int $sessionId, int $volunteerId): ?array
{
    $stmt = $conn->prepare(SESSION_WITH_NAMES_SQL . " WHERE s.id = ? AND s.volunteer_id = ? AND s.status = 'Pending'");
    $stmt->bind_param('ii', $sessionId, $volunteerId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

function acceptSessionRequest(mysqli $conn, int $sessionId): bool
{
    $stmt = $conn->prepare("UPDATE sessions SET status = 'Scheduled', conf_volunteer = 'Y' WHERE id = ? AND status = 'Pending'");
    $stmt->bind_param('i', $sessionId);
    $stmt->execute();
    return $stmt->affected_rows === 1;
}

function rejectSessionRequest(mysqli $conn, int $sessionId): bool
{
    $stmt = $conn->prepare("UPDATE sessions SET status = 'Rejected' WHERE id = ? AND status = 'Pending'");
    $stmt->bind_param('i', $sessionId);
    $stmt->execute();
    return $stmt->affected_rows === 1;
}

function findSessionWithStatus(mysqli $conn, int $sessionId, string $status): ?array
{
    $stmt = $conn->prepare(SESSION_WITH_NAMES_SQL . " WHERE s.id = ? AND s.status = ?");
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

// Older sessions have no end time; treat them as one hour long (same as BUSY_END_SQL).
function hasSessionEnded(array $session): bool
{
    $end = $session['end_time'] ?? null;
    $endTs = $end !== null
        ? strtotime($session['session_date'] . ' ' . $end)
        : strtotime($session['session_date'] . ' ' . $session['start_time']) + 3600;
    return $endTs <= time();
}

// Calendar event fields for a session. With a $viewerId the title names the other person (Add-to-Calendar link);
// without one it names both people (the shared Google event). Null while the session has no booked time.
function sessionCalendarDetails(array $session, ?int $viewerId = null): ?array
{
    if ($session['session_date'] === null || $session['start_time'] === null) {
        return null;
    }
    $start = strtotime($session['session_date'] . ' ' . $session['start_time']);
    $end = $session['end_time'] !== null
        ? strtotime($session['session_date'] . ' ' . $session['end_time'])
        : $start + 3600;
    $online = $session['support_mode'] === 'Online';
    $title = $viewerId === null
        ? 'CampusConnect: ' . $session['category'] . ' - ' . $session['student_name'] . ' & ' . $session['volunteer_name']
        : 'CampusConnect: ' . $session['category'] . ' with '
        . ((int) $session['volunteer_id'] === $viewerId ? $session['student_name'] : $session['volunteer_name']);

    return [
        'title' => $title,
        'start' => $start,
        'end' => $end,
        'description' => $session['description'] . "\n\nSupport mode: " . ($online ? 'Online' : 'Face-to-face'),
        'location' => $online ? 'Online' : 'Campus (face-to-face)',
    ];
}

// Pre-filled "create event" link for Google Calendar; no API or sign-in needed on our side.
function googleCalendarUrl(array $session, int $myId): ?string
{
    $event = sessionCalendarDetails($session, $myId);
    if ($event === null) {
        return null;
    }
    return 'https://calendar.google.com/calendar/render?' . http_build_query([
        'action' => 'TEMPLATE',
        'text' => $event['title'],
        'dates' => date('Ymd\THis', $event['start']) . '/' . date('Ymd\THis', $event['end']),
        'ctz' => date_default_timezone_get(),
        'details' => $event['description'],
        'location' => $event['location'],
    ], '', '&', PHP_QUERY_RFC3986);
}

function sessionDisplayStatus(array $session): string
{
    if (
        $session['status'] === 'Scheduled'
        && $session['session_date'] === date('Y-m-d')
        && !hasSessionStarted($session)
    ) {
        return 'Upcoming';
    }
    return $session['status'];
}
