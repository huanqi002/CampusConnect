<?php

function dashboardHeaderUser(mysqli $conn, int $userId): array
{
    $stmt = $conn->prepare('SELECT full_name, picture_url, volunteer_picture_url, is_volunteer FROM users WHERE id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: [];
}

function renderDashboardHeader(string $title, array $user, ?array $back = null, int $unreadNotifications = 0): string
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

    $scriptKey = basename(dirname($_SERVER['SCRIPT_NAME'] ?? '')) . '/' . basename($_SERVER['SCRIPT_NAME'] ?? '');
    $isNotificationsPage = $scriptKey === 'notification_management/notifications.php';
    $bellLabel = $isNotificationsPage
        ? 'Close notifications'
        : ($unreadNotifications > 0 ? "Notifications, $unreadNotifications unread" : 'Notifications');
    $bellHref = $isNotificationsPage
        ? BASE_URL . ($isVolunteer ? '/volunteer.php' : '/profile.php')
        : BASE_URL . '/backend/notification_management/notifications.php';
    $bell = '<a class="dashboard-bell"' . ($isNotificationsPage ? ' data-notification-toggle="close"' : '')
          . ' href="' . $bellHref . '" title="' . $bellLabel . '" aria-label="' . $bellLabel . '">'
          . '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9a6 6 0 0 1 12 0c0 6 2.5 7.5 2.5 7.5h-17S6 15 6 9z"/><path d="M10 20a2 2 0 0 0 4 0"/></svg>'
          . ($unreadNotifications > 0 ? '<span class="dashboard-bell-badge">' . ($unreadNotifications > 9 ? '9+' : $unreadNotifications) . '</span>' : '')
          . '</a>';

    return '<header class="dashboard-header">'
         . '<div class="dashboard-header-title">' . $backLink . '<span>' . htmlspecialchars($title) . '</span></div>'
         . '<nav class="dashboard-nav" aria-label="Account navigation">'
         . $bell
         . '<a class="dashboard-user-chip" href="' . BASE_URL . ($isVolunteer ? '/volunteer.php' : '/profile.php') . '" aria-label="View your profile">'
         . '<span class="dashboard-user-avatar">' . $avatar . '</span>' . htmlspecialchars($name) . '</a>'
         . '<span class="dashboard-role-badge ' . ($isVolunteer ? 'is-volunteer' : 'is-student') . '" aria-label="Current role: ' . ($isVolunteer ? 'Volunteer' : 'Student') . '">' . ($isVolunteer ? 'Volunteer' : 'Student') . '</span>'
         . '</nav></header>'
         . ($isNotificationsPage
             ? '<script>(function () {'
             . 'var closeLink = document.querySelector("[data-notification-toggle=\\"close\\"]");'
             . 'if (!closeLink) { return; }'
             . 'closeLink.addEventListener("click", function (event) {'
             . '  if (window.history.length < 2 || !document.referrer) { return; }'
             . '  if (document.referrer === window.location.origin || document.referrer.indexOf(window.location.origin + "/") === 0) {'
             . '    event.preventDefault();'
             . '    window.history.back();'
             . '  }'
             . '});'
             . '})();</script>'
             : '');
}
