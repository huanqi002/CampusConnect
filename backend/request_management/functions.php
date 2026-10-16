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

// Match volunteers only when the day, time window, support mode, and skill filters all apply together.
function findMatchingVolunteers($pdo, $day, $startTime, $endTime, $mode, $skills, ?int $excludeUserId = null)
{
    $sql = "
        SELECT DISTINCT v.user_id, u.full_name, u.email, u.volunteer_picture_url AS picture_url,
                        v.preferred_day, v.start_time, v.end_time, v.support_mode, v.skills
        FROM volunteers v
        JOIN users u ON u.id = v.user_id
        WHERE v.preferred_day = ?
          AND v.support_mode = ?
          AND v.start_time < ?
          AND v.end_time > ?
    ";

    $params = [$day, $mode, $endTime, $startTime];

    if ($excludeUserId !== null) {
        $sql .= " AND v.user_id <> ?";
        $params[] = $excludeUserId;
    }

    if (!empty(trim((string) $skills))) {
        $skillClauses = [];
        $skillsArray = array_map('trim', explode(',', (string) $skills));
        foreach ($skillsArray as $skill) {
            if ($skill !== '') {
                $skillClauses[] = 'v.skills LIKE ?';
                $params[] = '%' . $skill . '%';
            }
        }

        if (!empty($skillClauses)) {
            $sql .= ' AND (' . implode(' AND ', $skillClauses) . ')';
        }
    }

    $sql .= " ORDER BY u.full_name, v.start_time";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $uniqueResults = [];
    foreach ($rows as $row) {
        $userId = (int) ($row['user_id'] ?? 0);
        if ($userId > 0 && !isset($uniqueResults[$userId])) {
            $uniqueResults[$userId] = $row;
        }
    }

    return array_values($uniqueResults);
}
