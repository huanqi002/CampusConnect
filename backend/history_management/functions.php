<?php

const HISTORY_ENTRY_SQL = "SELECT h.final_status, h.completion_time, h.cancellation_reason,
                                  s.id AS session_id, s.user_id, s.volunteer_id, s.category, s.description,
                                  s.session_date, s.start_time, s.end_time, s.support_mode,
                                  us.full_name AS student_name, uv.full_name AS volunteer_name,
                                  f.id AS feedback_id, f.rating, f.comments, f.submitted_at
                           FROM history h
                           JOIN sessions s ON s.id = h.session_id
                           JOIN users us ON us.id = s.user_id
                           JOIN users uv ON uv.id = s.volunteer_id
                           LEFT JOIN feedbacks f ON f.session_id = s.id";

function fetchHistory(mysqli $conn, int $myId): mysqli_result
{
    $stmt = $conn->prepare(HISTORY_ENTRY_SQL . " WHERE s.user_id = ? OR s.volunteer_id = ?
                                                ORDER BY h.completion_time DESC, h.id DESC");
    $stmt->bind_param('ii', $myId, $myId);
    $stmt->execute();
    return $stmt->get_result();
}

function findHistoryEntry(mysqli $conn, int $sessionId): ?array
{
    $stmt = $conn->prepare(HISTORY_ENTRY_SQL . " WHERE s.id = ?");
    $stmt->bind_param('i', $sessionId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

function canRate(array $entry, int $myId): bool
{
    return $entry['final_status'] === 'Completed' && $entry['user_id'] == $myId && $entry['feedback_id'] === null;
}

function recordHistory(mysqli $conn, array $session, string $finalStatus, ?string $cancellationReason = null): void
{
    $sessionId   = (int)$session['id'];
    $userId      = (int)$session['user_id'];
    $volunteerId = (int)$session['volunteer_id'];

    $stmt = $conn->prepare("INSERT INTO history (session_id, user_id, volunteer_id, final_status, cancellation_reason)
                             VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('iiiss', $sessionId, $userId, $volunteerId, $finalStatus, $cancellationReason);
    $stmt->execute();
}

function findSessionForFeedback(mysqli $conn, int $sessionId): ?array
{
    $stmt = $conn->prepare("SELECT s.*, uv.full_name AS volunteer_name
                             FROM sessions s
                             JOIN users uv ON s.volunteer_id = uv.id
                             WHERE s.id = ?");
    $stmt->bind_param('i', $sessionId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

function hasFeedback(mysqli $conn, int $sessionId): bool
{
    $stmt = $conn->prepare("SELECT id FROM feedbacks WHERE session_id = ?");
    $stmt->bind_param('i', $sessionId);
    $stmt->execute();
    return (bool) $stmt->get_result()->fetch_assoc();
}

function insertFeedback(mysqli $conn, array $session, int $rating, string $comments): int
{
    $sessionId   = (int)$session['id'];
    $userId      = (int)$session['user_id'];
    $volunteerId = (int)$session['volunteer_id'];

    $stmt = $conn->prepare("INSERT INTO feedbacks (session_id, user_id, volunteer_id, rating, comments)
                             VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('iiiis', $sessionId, $userId, $volunteerId, $rating, $comments);
    $stmt->execute();
    return $conn->insert_id;
}

function attachFeedbackToHistory(mysqli $conn, int $sessionId, int $feedbackId): void
{
    $stmt = $conn->prepare("UPDATE history SET feedback_id = ? WHERE session_id = ?");
    $stmt->bind_param('ii', $feedbackId, $sessionId);
    $stmt->execute();
}
