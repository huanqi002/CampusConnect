<?php

function dashboardHeaderUser(mysqli $conn, int $userId): array
{
    $stmt = $conn->prepare('SELECT full_name, picture_url, volunteer_picture_url, is_volunteer FROM users WHERE id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: [];
}

function renderDashboardHeader(string $title, array $user, ?array $back = null): string
{
    $backLink = '';
    if ($back) {
        [$backHref, $backLabel] = $back;
        $backLabel = htmlspecialchars($backLabel);
        $backLink  = '<a class="back-link" href="' . htmlspecialchars($backHref) . '" title="' . $backLabel . '" aria-label="' . $backLabel . '">'
                   . '<svg class="icon-stroke" viewBox="0 0 24 24" aria-hidden="true"><path d="M15 4l-8 8 8 8"/></svg></a>';
    }

    $isVolunteer = ($user['is_volunteer'] ?? 'N') === 'Y';
    $name        = $user['full_name'] ?? 'Student';
    $picture     = $isVolunteer ? ($user['volunteer_picture_url'] ?? '') : ($user['picture_url'] ?? '');

    $initials = '';
    foreach (array_slice(preg_split('/\s+/', trim($name)), 0, 2) as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
    }

    $avatar = $picture
        ? '<img src="' . htmlspecialchars($picture) . '" alt="">'
        : htmlspecialchars($initials);

    return '<header class="dashboard-header">'
         . '<div class="dashboard-header-title">' . $backLink . '<span>' . htmlspecialchars($title) . '</span></div>'
         . '<nav class="dashboard-nav" aria-label="Account navigation">'
         . '<a class="dashboard-user-chip" href="' . BASE_URL . ($isVolunteer ? '/volunteer.php' : '/profile.php') . '" aria-label="View your profile">'
         . '<span class="dashboard-user-avatar">' . $avatar . '</span>' . htmlspecialchars($name) . '</a>'
         . '<a class="dashboard-logout" href="' . BASE_URL . '/logout.php">Log out</a>'
         . '</nav></header>';
}
