<?php

function countUnreadNotifications(mysqli $conn, int $userId): int
{
    $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 'N'");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    return (int)$stmt->get_result()->fetch_row()[0];
}

function addNotification(mysqli $conn, int $userId, string $message, string $noticeType): void
{
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, notice_type) VALUES (?, ?, ?)");
    $stmt->bind_param('iss', $userId, $message, $noticeType);
    $stmt->execute();
}

function listNotifications(mysqli $conn, int $userId): mysqli_result
{
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC, id DESC");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    return $stmt->get_result();
}

function markAllNotificationsRead(mysqli $conn, int $userId): void
{
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 'Y' WHERE user_id = ? AND is_read = 'N'");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
}
