<?php

function findSupportRequestById(mysqli $conn, int $id): ?array
{
    $stmt = $conn->prepare("SELECT r.*, us.full_name AS student_name, uv.full_name AS volunteer_name
                             FROM requests r
                             JOIN users us ON r.user_id = us.id
                             LEFT JOIN users uv ON r.volunteer_id = uv.id
                             WHERE r.id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

function updateSupportRequestStatus(mysqli $conn, int $id, string $status): void
{
    $stmt = $conn->prepare("UPDATE requests SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $status, $id);
    $stmt->execute();
}
