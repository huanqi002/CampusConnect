<?php

function notificationTypesForRole(?string $role): array
{
    return match ($role) {
        'student' => ['Session Requested', 'Feedback Received', 'Pending Request'],
        'volunteer' => ['Request Submitted', 'Request Response', 'Status Update', 'Session Scheduled', 'Session Rejected', 'Feedback Reminder'],
        default => [],
    };
}

function notificationRoleFilter(?string $role): array
{
    $types = notificationTypesForRole($role);
    if ($types === []) {
        return ['', ''];
    }

    return [' AND notice_type NOT IN (' . implode(', ', array_fill(0, count($types), '?')) . ')', str_repeat('s', count($types))];
}

function bindNotificationQueryParams(mysqli_stmt $stmt, int $userId, ?string $role, string $types): void
{
    $values = array_merge([$userId], notificationTypesForRole($role));
    $params = ['i' . $types];
    foreach ($values as &$value) {
        $params[] = &$value;
    }
    call_user_func_array([$stmt, 'bind_param'], $params);
}

function countUnreadNotifications(mysqli $conn, int $userId, ?string $role = null): int
{
    [$roleFilter, $types] = notificationRoleFilter($role);
    $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 'N'" . $roleFilter);
    bindNotificationQueryParams($stmt, $userId, $role, $types);
    $stmt->execute();
    return (int)$stmt->get_result()->fetch_row()[0];
}

// The session a notice is about is kept as a hidden "[session:12]" tag at the end of the message
// (no extra database column). splitNotificationMessage() reads it back and removes it for display.
const NOTIFICATION_SESSION_TAG = '/\s*\[session:(\d+)\]$/';

// $sessionId: the session this notice is about, so clicking it opens that session's details.
function addNotification(mysqli $conn, int $userId, string $message, string $noticeType, ?int $sessionId = null): void
{
    if ($sessionId !== null) {
        $tag     = ' [session:' . $sessionId . ']';
        $message = mb_strimwidth($message, 0, 500 - strlen($tag), '...') . $tag;   // message column is VARCHAR(500)
    }
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, notice_type) VALUES (?, ?, ?)");
    $stmt->bind_param('iss', $userId, $message, $noticeType);
    $stmt->execute();
}

// [message without the tag, session id or null]
function splitNotificationMessage(string $message): array
{
    if (preg_match(NOTIFICATION_SESSION_TAG, $message, $m)) {
        return [preg_replace(NOTIFICATION_SESSION_TAG, '', $message), (int)$m[1]];
    }
    return [$message, null];
}

function listNotifications(mysqli $conn, int $userId, ?string $role = null): mysqli_result
{
    [$roleFilter, $types] = notificationRoleFilter($role);
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ?" . $roleFilter . " ORDER BY created_at DESC, id DESC");
    bindNotificationQueryParams($stmt, $userId, $role, $types);
    $stmt->execute();
    return $stmt->get_result();
}

function markAllNotificationsRead(mysqli $conn, int $userId, ?string $role = null): void
{
    [$roleFilter, $types] = notificationRoleFilter($role);
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 'Y' WHERE user_id = ? AND is_read = 'N'" . $roleFilter);
    bindNotificationQueryParams($stmt, $userId, $role, $types);
    $stmt->execute();
}
