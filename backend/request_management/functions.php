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

function findMatchingVolunteers($pdo, $day, $time, $mode, $skills)
{
    $sql = "
        SELECT v.*, u.full_name, u.email, u.picture_url
        FROM volunteers v
        JOIN users u ON v.user_id = u.id
        WHERE v.preferred_day = ? 
          AND v.preferred_time = ? 
          AND v.support_mode = ?
    ";

    $params = [$day, $time, $mode];

    // Check if skills filter was provided
    if (!empty(trim($skills))) {
        // Split the comma-separated skills into an array of individual tags
        $skillsArray = array_map('trim', explode(',', $skills));

        foreach ($skillsArray as $skill) {
            if ($skill !== '') {
                // Dynamically add a LIKE condition for EACH skill tag
                $sql .= " AND v.skills LIKE ?";
                $params[] = '%' . $skill . '%';
            }
        }
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}