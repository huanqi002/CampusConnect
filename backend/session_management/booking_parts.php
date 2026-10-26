<?php
require_once __DIR__ . '/session_card.php';

// Shared markup for Book a Session (schedule.php) and Edit Session (edit_session.php).
// script.js (initScheduleForm) fills the calendar, slot grid and summary by element id.

function bookingStepTitle(int $number, string $title): string
{
    return '<h2 class="step-title"><span class="step-num">' . $number . '</span>' . htmlspecialchars($title) . '</h2>';
}

// Student and volunteer as two compact person cards; the viewer's own card gets a "You" tag.
function bookingPeopleHtml(array $session, ?int $myId = null): string
{
    $html = '<div class="person-grid">';
    foreach ([['Student', $session['student_name'], $session['student_email'], (int)$session['user_id']],
              ['Volunteer', $session['volunteer_name'], $session['volunteer_email'], (int)$session['volunteer_id']]] as [$role, $name, $email, $id]) {
        $you   = $myId !== null && $id === $myId;
        $html .= '<div class="person-card' . ($you ? ' is-you' : '') . '">' . personAvatar($name)
               . '<div class="person-info"><span class="person-role">' . $role . ($you ? '<span class="you-tag">You</span>' : '') . '</span>'
               . '<strong>' . htmlspecialchars($name) . '</strong>'
               . '<span class="person-email">' . htmlspecialchars((string)$email) . '</span>'
               . '</div></div>';
    }
    return $html . '</div>';
}

// 90 -> "1 hr 30 min", 60 -> "1 hour", 30 -> "30 min" (same wording as script.js durationLabel).
function formatDuration(int $minutes): string
{
    $hours = intdiv($minutes, 60);
    $rest  = $minutes % 60;
    if ($hours === 0) {
        return $rest . ' min';
    }
    return $rest === 0 ? $hours . ($hours === 1 ? ' hour' : ' hours') : $hours . ' hr ' . $rest . ' min';
}

// One line explaining where the session stands, worded for the person viewing it.
// $onRequestPage: true on Session Request Details, where Accept/Reject are already on the page.
function sessionStatusNote(array $session, string $status, int $myId, bool $onRequestPage = false): string
{
    $isVolunteer = (int)$session['volunteer_id'] === $myId;
    $other       = htmlspecialchars($isVolunteer ? $session['student_name'] : $session['volunteer_name']);
    $start       = $session['start_time'] !== null ? htmlspecialchars(formatTime($session['start_time'])) : '';

    switch ($status) {
        case 'Pending':
            if (!$isVolunteer) {
                $text = 'Waiting for <strong>' . $other . '</strong> to accept this time.';
            } elseif ($onRequestPage) {
                $text = 'Please accept or reject this request.';
            } else {
                $text = 'This session is waiting for your confirmation. '
                      . '<a class="status-note-link" href="session_request_details.php?session_id=' . (int)$session['id'] . '">Session Request</a>';
            }
            $icon = 'clock';
            break;
        case 'Scheduled':
            [$icon, $text] = ['check', 'Confirmed with <strong>' . $other . '</strong>.'];
            break;
        case 'Upcoming':
            [$icon, $text] = ['calendar', 'Starts today at <strong>' . $start . '</strong> with ' . $other . '.'];
            break;
        case 'Unscheduled':
            [$icon, $text] = ['calendar', 'No time booked yet. Use <strong>Schedule Now</strong> to pick one.'];
            break;
        default:
            return '';
    }
    return '<p class="status-note status-note-' . htmlspecialchars($status) . '">' . SESSION_CARD_ICONS[$icon] . '<span>' . $text . '</span></p>';
}

// Read-only overview for Session Details and Session Request Details. Needs general/format.php.
function sessionOverviewHtml(array $session, string $status, int $myId, bool $onRequestPage = false): string
{
    $isBooked = $session['session_date'] !== null;
    $isOnline = $session['support_mode'] === 'Online';
    $minutes  = $isBooked && $session['end_time'] !== null
              ? (int)((strtotime($session['end_time']) - strtotime($session['start_time'])) / 60) : null;

    // "Today" or "Tomorrow" next to the date; any other day gets no badge.
    $relative = '';
    if ($isBooked) {
        $days     = (int)round((strtotime($session['session_date']) - strtotime(date('Y-m-d'))) / 86400);
        $relative = match ($days) {
            0       => 'Today',
            1       => 'Tomorrow',
            default => '',
        };
    }

    $tiles = [
        ['Date', 'calendar', $isBooked ? date('D, j M Y', strtotime($session['session_date'])) : null],
        ['Time', 'clock', $isBooked ? str_replace(' - ', ' – ', formatTimeRange($session['start_time'], $session['end_time'])) : null],
        ['Duration', 'duration', $minutes !== null ? formatDuration($minutes) : null],
        ['Support mode', $isOnline ? 'online' : 'inperson', $isOnline ? 'Virtual' : 'Face-to-face'],
    ];
    $tileHtml = '';
    foreach ($tiles as [$label, $icon, $value]) {
        $tileHtml .= '<div class="summary-item"><span class="tile-label">' . $label
                   . ($label === 'Date' && $relative !== '' ? '<small class="tile-sub">' . $relative . '</small>' : '') . '</span>'
                   . '<strong class="tile-value' . ($value === null ? ' is-empty' : '') . '">' . SESSION_CARD_ICONS[$icon]
                   . htmlspecialchars($value ?? 'Not booked yet') . '</strong>'
                   . '</div>';
    }

    // Order: people, then the session (status, category, topic, note), then when.
    return bookingPeopleHtml($session, $myId)
         . '<section class="view-head">'
         . '<div class="view-tags"><span class="pill pill-' . htmlspecialchars($status) . '">' . htmlspecialchars($status) . '</span></div>'
         . '<h2 class="view-title">' . htmlspecialchars($session['category']) . '</h2>'
         . '<p class="view-desc">' . nl2br(htmlspecialchars($session['description'])) . '</p>'
         . sessionStatusNote($session, $status, $myId, $onRequestPage)
         . '</section>'
         . '<section class="view-when"><h3 class="view-label">When</h3><div class="summary-grid">' . $tileHtml . '</div></section>';
}

// Calendar (step 1), time grid (step 2), support mode (step 3) and the review summary (step 4).
function bookingPickerHtml(string $mode): string
{
    $online  = $mode !== 'Face-to-face';
    $options = [
        ['Online', 'Virtual', 'Meet online', 'online'],
        ['Face-to-face', 'Face-to-face', 'Meet on campus', 'inperson'],
    ];
    $modeCards = '';
    foreach ($options as [$value, $label, $hint, $icon]) {
        $checked    = ($value === 'Online') === $online;
        $modeCards .= '<label class="mode-card"><input type="radio" name="mode" value="' . $value . '"' . ($checked ? ' checked' : '') . '>'
                    . '<span class="mode-card-body">' . SESSION_CARD_ICONS[$icon]
                    . '<span><strong>' . $label . '</strong><small>' . $hint . '</small></span></span></label>';
    }

    return '<div class="schedule-picker">'
         . '<section class="calendar">' . bookingStepTitle(1, 'Choose a date')
         . '<div class="calendar-head">'
         . '<button type="button" class="calendar-nav" data-step="-1" aria-label="Previous month">&lsaquo;</button>'
         . '<span id="calendarMonth"></span>'
         . '<button type="button" class="calendar-nav" data-step="1" aria-label="Next month">&rsaquo;</button>'
         . '</div><div class="calendar-grid" id="calendarGrid"></div></section>'
         . '<section class="slots">' . bookingStepTitle(2, 'Choose a time')
         . '<div class="slot-grid" id="slotGrid"></div>'
         . '<p class="schedule-help" id="slotHelp" aria-live="polite">Pick a green date to see the volunteer\'s times.</p>'
         . '<div class="schedule-legend">'
         . '<span><i class="legend-dot is-open"></i>Available</span>'
         . '<span><i class="legend-dot is-pref"></i>Volunteer\'s preference</span>'
         . '<span><i class="legend-dot is-selected"></i>Selected</span>'
         . '<span><i class="legend-dot is-busy"></i>Unavailable</span>'
         . '</div></section>'
         . '</div>'
         . '<section class="booking-mode">' . bookingStepTitle(3, 'Choose support mode')
         . '<div class="mode-cards" role="radiogroup" aria-label="Support mode">' . $modeCards . '</div>'
         . '</section>'
         . '<section class="booking-review">' . bookingStepTitle(4, 'Review')
         . '<div class="summary-grid">'
         . '<label class="summary-item" for="selectedDate"><span>Date</span><input type="text" id="selectedDate" placeholder="Not chosen" readonly tabindex="-1"></label>'
         . '<label class="summary-item" for="timeDisplay"><span>Time</span><input type="text" id="timeDisplay" placeholder="Not chosen" readonly tabindex="-1"></label>'
         . '<label class="summary-item" for="durationDisplay"><span>Duration</span><input type="text" id="durationDisplay" placeholder="--" readonly tabindex="-1"></label>'
         . '<label class="summary-item" for="modeDisplay"><span>Support mode</span><input type="text" id="modeDisplay" value="' . ($online ? 'Virtual' : 'Face-to-face') . '" readonly tabindex="-1"></label>'
         . '</div>'
         . '</section>';
}
