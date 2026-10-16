<?php

// Live counts shown at the top of the Notifications page. They come straight from the sessions table,
// so they are always current and need no stored notification rows.

// Confirmed sessions happening today that have not started yet (the "Upcoming" status), on the user's side of the session.
function countTodayUpcomingSessions(mysqli $conn, int $userId, ?string $role): int
{
    $column = $role === 'volunteer' ? 'volunteer_id' : 'user_id';   // same rule as sessionRoleColumn() in session_management
    $today  = date('Y-m-d');
    $now    = date('H:i:s');
    $stmt   = $conn->prepare("SELECT COUNT(*) FROM sessions
                               WHERE $column = ? AND status = 'Scheduled' AND session_date = ? AND start_time > ?");
    $stmt->bind_param('iss', $userId, $today, $now);
    $stmt->execute();
    return (int)$stmt->get_result()->fetch_row()[0];
}

// Session requests waiting for this volunteer to accept or reject.
function countPendingConfirmations(mysqli $conn, int $volunteerId): int
{
    $stmt = $conn->prepare("SELECT COUNT(*) FROM sessions WHERE volunteer_id = ? AND status = 'Pending'");
    $stmt->bind_param('i', $volunteerId);
    $stmt->execute();
    return (int)$stmt->get_result()->fetch_row()[0];
}

// Confirmed sessions of this volunteer whose end time has passed but that are still not marked complete.
function countEndedNotCompleted(mysqli $conn, int $volunteerId): int
{
    $today = date('Y-m-d');
    $now   = date('H:i:s');
    $stmt  = $conn->prepare("SELECT COUNT(*) FROM sessions
                              WHERE volunteer_id = ? AND status = 'Scheduled'
                                AND (session_date < ? OR (session_date = ? AND COALESCE(end_time, ADDTIME(start_time, '01:00:00')) <= ?))");
    $stmt->bind_param('isss', $volunteerId, $today, $today, $now);
    $stmt->execute();
    return (int)$stmt->get_result()->fetch_row()[0];
}

// Completed sessions this student has not rated yet. Also returns the id when there is exactly one, to link straight to its form.
function findUnratedSessions(mysqli $conn, int $studentId): array
{
    $stmt = $conn->prepare("SELECT s.id FROM sessions s
                             LEFT JOIN feedbacks f ON f.session_id = s.id
                             WHERE s.user_id = ? AND s.status = 'Completed' AND f.id IS NULL
                             ORDER BY s.session_date DESC, s.id DESC");
    $stmt->bind_param('i', $studentId);
    $stmt->execute();
    return array_map('intval', array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'id'));
}

// $tone: 'blue' (informational), 'orange' (needs action) or 'green' (invitation to do something optional).
function renderSummaryTile(string $label, int $count, string $hint, string $href, string $tone): string
{
    return '<a class="summary-tile summary-tile-' . $tone . ($count === 0 ? ' is-zero' : '') . '" href="' . htmlspecialchars($href) . '">'
         . '<span class="summary-tile-count">' . $count . '</span>'
         . '<span class="summary-tile-text"><strong>' . htmlspecialchars($label) . '</strong>'
         . '<span>' . htmlspecialchars($hint) . '</span></span></a>';
}

// The tile row. Everyone sees today's sessions; volunteers also see requests waiting for them.
function renderNotificationSummary(mysqli $conn, int $userId, ?string $role): string
{
    $today = countTodayUpcomingSessions($conn, $userId, $role);
    $tiles = renderSummaryTile("Today's upcoming sessions", $today,
        $today === 0 ? 'Nothing left to attend today' : 'Confirmed and starting later today',
        '../session_management/index.php', 'blue');

    if ($role === 'volunteer') {
        $pending = countPendingConfirmations($conn, $userId);
        $tiles  .= renderSummaryTile('Waiting for your confirmation', $pending,
            $pending === 0 ? 'No requests to answer' : 'Accept or reject these requests',
            '../session_management/volunteer_requests.php', 'orange');

        $ended  = countEndedNotCompleted($conn, $userId);
        $tiles .= renderSummaryTile('Ended, not marked complete', $ended,
            $ended === 0 ? 'All finished sessions are marked' : 'Tick these off on your dashboard',
            '../session_management/index.php', 'orange');
    } else {
        $unrated = findUnratedSessions($conn, $userId);
        $tiles  .= renderSummaryTile('Rate your session', count($unrated),
            $unrated === [] ? 'No sessions waiting for feedback' : 'Tell us how helpful it was',
            count($unrated) === 1 ? '../history_management/feedback.php?session_id=' . $unrated[0] : '../history_management/history.php', 'green');
    }

    return '<style>'
         . '.summary-tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px; margin-bottom: 22px; }'
         . '.summary-tile { display: flex; align-items: center; gap: 14px; padding: 16px 18px; border: 1px solid #e9e4e3; border-radius: 14px; background: #fff; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); color: inherit; text-decoration: none; transition: box-shadow 0.15s, transform 0.15s; }'
         . '.summary-tile:hover { box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1); transform: translateY(-1px); }'
         . '.summary-tile:focus-visible { outline: 3px solid rgba(21, 101, 192, 0.4); outline-offset: 2px; }'
         . '.summary-tile-count { flex: none; display: grid; place-items: center; min-width: 52px; height: 52px; padding: 0 10px; border-radius: 14px; font-size: 24px; font-weight: 700; }'
         . '.summary-tile-blue .summary-tile-count { background: #e5f0fb; color: #1565c0; }'
         . '.summary-tile-orange .summary-tile-count { background: #fff1de; color: #c76a00; }'
         . '.summary-tile-green .summary-tile-count { background: #e6f6ea; color: #2e7d32; }'
         . '.summary-tile.is-zero .summary-tile-count { background: #f1f3f4; color: #8a9199; }'
         . '.summary-tile-text strong { display: block; color: #1f2933; font-size: 15px; }'
         . '.summary-tile-text span { display: block; margin-top: 2px; color: #5f6b76; font-size: 12.5px; }'
         . '</style><div class="summary-tiles">' . $tiles . '</div>';
}
