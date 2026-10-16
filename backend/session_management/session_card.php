<?php

// Card content shared by the Session Dashboard, Schedule and Session Request lists. Needs general/format.php.
const SESSION_CARD_ICONS = [
    'calendar' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg>',
    'clock'    => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
    'online'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/></svg>',
    'inperson' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6M15 14.5c3 0 6 1.8 6 5"/></svg>',
    'duration' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="13" r="8"/><path d="M12 9v4l2.5 2M10 2h4M12 2v3M18.5 6.5l1.5-1.5"/></svg>',
    'check'    =>'<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.7 2.7L16 10"/></svg>',
];

// Round initials badge; each name always gets the same of 6 colours.
function personAvatar(string $name): string
{
    $initials = '';
    foreach (array_slice(preg_split('/\s+/', trim($name)), 0, 2) as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
    }
    return '<span class="sc-avatar sc-avatar-' . (crc32($name) % 6) . '" aria-hidden="true">' . htmlspecialchars($initials) . '</span>';
}

function categoryChip(string $category): string
{
    return '<span class="sc-cat sc-cat-' . htmlspecialchars(strtolower(str_replace(' ', '-', $category))) . '">' . htmlspecialchars($category) . '</span>';
}

function sessionCardText(array $session, int $myId): string
{
    $isStudent = (int)$session['user_id'] === $myId;
    $name      = $isStudent ? $session['volunteer_name'] : $session['student_name'];
    $role      = $isStudent ? 'Volunteer' : 'Student';
    $isOnline      = $session['support_mode'] === 'Online';

    if ($session['session_date'] !== null) {
        $when = '<span>' . SESSION_CARD_ICONS['calendar'] . htmlspecialchars(date('D, j M Y', strtotime($session['session_date']))) . '</span>'
              . '<span>' . SESSION_CARD_ICONS['clock'] . htmlspecialchars(formatTimeRange($session['start_time'], $session['end_time'])) . '</span>';
    } else {
        $when = '<span class="sc-when-empty">' . SESSION_CARD_ICONS['clock'] . 'Not scheduled yet</span>';
    }

    return '<div class="sc">'
         . personAvatar($name)
         . '<div class="sc-body">'
         . '<p class="sc-title">' . htmlspecialchars($name) . '<span class="sc-role">' . $role . '</span></p>'
         . '<p class="sc-desc" title="' . htmlspecialchars($session['description']) . '">' . htmlspecialchars($session['description']) . '</p>'
         . '<p class="sc-meta">' . categoryChip($session['category'])
         . '<span class="sc-mode">' . SESSION_CARD_ICONS[$isOnline ? 'online' : 'inperson'] . ($isOnline ? 'Online' : 'Face-to-face') . '</span>'
         . '<span class="sc-dot" aria-hidden="true"></span>'
         . '<span class="sc-when">' . $when . '</span></p>'
         . '</div></div>';
}
