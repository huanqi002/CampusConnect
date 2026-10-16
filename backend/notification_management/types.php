<?php

// How each notice_type looks on the Notifications page: [icon, colour].
// To style your own type, add ONE line here, using the exact text you pass to addNotification().
// Icons:   bell, calendar, check, x, edit, star, send, user, alert
// Colours: blue, green, orange, red, purple, grey
// A type that is not listed still shows up, with a grey bell.
const NOTICE_TYPES = [
    // Session
    'Session Requested' => ['calendar', 'orange'],
    'Session Scheduled' => ['check',    'green'],
    'Session Updated'   => ['edit',     'blue'],
    'Session Cancelled' => ['x',        'red'],
    'Session Rejected'  => ['x',        'red'],
    // Request
    'Request Submitted' => ['send',     'blue'],
    // History / feedback
    'Feedback Received' => ['star',     'purple'],
    'Feedback Reminder' => ['star',     'orange'],
];

const NOTICE_ICONS = [
    'bell'     => '<path d="M6 9a6 6 0 0 1 12 0c0 6 2.5 7.5 2.5 7.5h-17S6 15 6 9z"/><path d="M10 20a2 2 0 0 0 4 0"/>',
    'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
    'check'    => '<circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.7 2.7L16 10"/>',
    'x'        => '<circle cx="12" cy="12" r="9"/><path d="M9 9l6 6M15 9l-6 6"/>',
    'edit'     => '<path d="M4 20h4L19 9l-4-4L4 16z"/><path d="M13.5 6.5l4 4"/>',
    'star'     => '<path d="M12 3.5l2.6 5.4 5.9.8-4.3 4.1 1 5.9L12 16.9 6.8 19.7l1-5.9L3.5 9.7l5.9-.8z"/>',
    'send'     => '<path d="M21 3L10 14"/><path d="M21 3l-7 18-4-7-7-4z"/>',
    'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/>',
    'alert'    => '<path d="M12 4l9 16H3z"/><path d="M12 10v4M12 17.5v.5"/>',
];

// [icon svg path markup, colour] for a notice_type.
function noticeStyle(string $type): array
{
    [$icon, $tone] = NOTICE_TYPES[$type] ?? ['bell', 'grey'];
    return [NOTICE_ICONS[$icon] ?? NOTICE_ICONS['bell'], $tone];
}

// "Just now", "12 min ago", otherwise the clock time ("4:47 PM").
function noticeTime(string $createdAt): string
{
    $seconds = time() - strtotime($createdAt);
    if ($seconds < 60) {
        return 'Just now';
    }
    return $seconds < 3600 ? intdiv($seconds, 60) . ' min ago' : date('g:i A', strtotime($createdAt));
}

// "Today", "Yesterday" or "Mon, 12 Oct 2026" - the heading each day's notifications are grouped under.
function noticeDayLabel(string $createdAt): string
{
    $day = date('Y-m-d', strtotime($createdAt));
    if ($day === date('Y-m-d')) {
        return 'Today';
    }
    return $day === date('Y-m-d', strtotime('-1 day')) ? 'Yesterday' : date('D, j M Y', strtotime($createdAt));
}
