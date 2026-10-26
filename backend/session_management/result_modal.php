<?php

// Popups for session actions: a "Are you sure?" confirmation before the action and a result popup after it.
const RESULT_MODAL_ICONS = [
    'scheduled' => '<svg class="modal-icon" viewBox="0 0 40 40" aria-hidden="true">'
                 . '<rect x="3" y="7" width="28" height="26" rx="3" fill="#dbe7f7" stroke="#3d5a8a" stroke-width="2"/>'
                 . '<rect x="3" y="7" width="28" height="7" rx="3" fill="#5b7fb8"/>'
                 . '<path d="M10 4v6M24 4v6" stroke="#3d5a8a" stroke-width="2.5" stroke-linecap="round"/>'
                 . '<path d="M8 19h4M15 19h4M22 19h4M8 25h4M15 25h4" stroke="#7f95b8" stroke-width="2.5" stroke-linecap="round"/>'
                 . '<circle cx="30" cy="30" r="8" fill="#2ebd59" stroke="#fff" stroke-width="2"/>'
                 . '<path d="M26.5 30l2.5 2.5 4.5-5" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>'
                 . '</svg>',
    'check'     => '<svg class="modal-icon" viewBox="0 0 40 40" aria-hidden="true">'
                 . '<circle cx="20" cy="20" r="17" fill="#1aa33a"/>'
                 . '<path d="M12 20.5l5.5 5.5L28.5 14" fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>'
                 . '</svg>',
    'cancelled' => '<svg class="modal-icon" viewBox="0 0 40 40" aria-hidden="true">'
                 . '<circle cx="20" cy="20" r="15" fill="none" stroke="#d32f2f" stroke-width="4"/>'
                 . '<path d="M9.5 9.5l21 21" stroke="#d32f2f" stroke-width="4"/>'
                 . '</svg>',
    'rejected'  => '<svg class="modal-icon" viewBox="0 0 40 40" aria-hidden="true">'
                 . '<circle cx="20" cy="20" r="17" fill="#d32f2f"/>'
                 . '<path d="M14 14l12 12M26 14L14 26" fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round"/>'
                 . '</svg>',
    'warning'   => '<svg class="modal-icon" viewBox="0 0 40 40" aria-hidden="true">'
                 . '<path d="M20 3.5L37.5 35h-35z" fill="#ef3b3b" stroke="#ef3b3b" stroke-width="3" stroke-linejoin="round"/>'
                 . '<path d="M20 15v10" stroke="#fff" stroke-width="4" stroke-linecap="round"/>'
                 . '<circle cx="20" cy="30" r="2.3" fill="#fff"/>'
                 . '</svg>',
];

// Confirmation shown before submitting each action form, keyed by the form's action.
const CONFIRM_MODALS = [
    'cancel_session.php' => ['warning', 'Cancel Session', 'Are you sure you want to cancel this session?', 'Yes, Cancel Session', 'danger'],
    'accept_session.php' => ['check', 'Accept', 'Are you sure you want to accept this?', 'Yes, Accept', 'success'],
    'reject_session.php' => ['warning', 'Reject', 'Are you sure you want to reject this?', 'Yes, Reject', 'danger'],
    'mark_completed.php' => ['check', 'Mark as Complete', 'Are you sure you want to mark this session as complete?', 'Yes, Complete', 'success'],
];

// Card shown under the message of a result popup, e.g. the Google Calendar confirmation.
// $note: heading, detail, and optionally when ("date · time", shown as two chips) and link (opens the event; Google URLs only).
function renderResultNote(array $note): string
{
    $link = (string)($note['link'] ?? '');
    $when = (string)($note['when'] ?? '');

    $html = '<style>'
          . '.modal-note { margin: 0 0 22px; padding: 16px; text-align: left; border: 1px solid #e3e6ea; border-radius: 16px; background: #fff; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08); }'
          . '.modal-note-top { display: flex; align-items: center; gap: 14px; }'
          . '.modal-note-badge { flex: none; display: grid; place-items: center; width: 48px; height: 48px; border-radius: 12px; background: #eaf7ee; }'
          . '.modal-note-badge .modal-icon { width: 34px; height: 34px; }'
          . '.modal-note strong { display: block; color: #1f2933; font-size: 15px; }'
          . '.modal-note-detail { display: block; margin-top: 2px; color: #5f6b76; font-size: 12.5px; line-height: 1.4; }'
          . '.modal-note-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 14px; }'
          . '.modal-note-chip { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 999px; background: #f1f3f4; color: #3c4043; font-size: 12.5px; font-weight: bold; }'
          . '.modal-note-chip svg { width: 14px; height: 14px; fill: none; stroke: #2e7d32; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }'
          . '.modal-note-link { display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 14px; padding: 9px 16px; border: 1.5px solid #1565c0; border-radius: 999px; background: #fff; color: #1565c0; font-size: 13px; font-weight: bold; text-decoration: none; transition: background 0.15s; }'
          . '.modal-note-link:hover { background: #e8f0fb; }'
          . '.modal-note-link:focus-visible { outline: 3px solid rgba(21, 101, 192, 0.35); outline-offset: 2px; }'
          . '.modal-note-link svg { width: 14px; height: 14px; fill: none; stroke: currentColor; stroke-width: 2.2; stroke-linecap: round; stroke-linejoin: round; }'
          . '</style>'
          . '<div class="modal-note">'
          . '<div class="modal-note-top"><span class="modal-note-badge">' . RESULT_MODAL_ICONS['scheduled'] . '</span>'
          . '<div><strong>' . htmlspecialchars($note['heading']) . '</strong>'
          . '<span class="modal-note-detail">' . htmlspecialchars($note['detail']) . '</span></div></div>';

    if ($when !== '') {
        $icons = [
            '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg>',
            '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
        ];
        $html .= '<div class="modal-note-chips">';
        foreach (explode(' · ', $when, 2) as $i => $part) {
            $html .= '<span class="modal-note-chip">' . $icons[$i] . htmlspecialchars($part) . '</span>';
        }
        $html .= '</div>';
    }
    if (str_starts_with($link, 'https://www.google.com/calendar/')) {
        $html .= '<a class="modal-note-link" href="' . htmlspecialchars($link) . '" target="_blank" rel="noopener noreferrer">'
               . 'View event in Google Calendar'
               . '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/></svg></a>';
    }
    return $html . '</div>';
}
function renderResultModal(string $icon, string $title, string $message, string $tone = 'success', ?array $note = null): string
{
    $noteHtml = $note !== null ? renderResultNote($note) : '';
    return '<div class="modal-backdrop" id="resultModal" role="dialog" aria-modal="true" aria-labelledby="resultModalTitle">'
         . '<div class="modal-box">'
         . '<h2 class="modal-title is-' . htmlspecialchars($tone) . '" id="resultModalTitle">' . (RESULT_MODAL_ICONS[$icon] ?? '') . htmlspecialchars($title) . '</h2>'
         . '<p>' . htmlspecialchars($message) . '</p>'
         . $noteHtml
         . '<button type="button" class="modal-close" id="resultModalClose">CLOSE</button>'
         . '</div></div>'
         . '<script>(function () {'
         . 'var modal = document.getElementById("resultModal");'
         . 'function dismiss() { modal.remove(); history.replaceState(null, "", location.pathname); }'
         . 'document.getElementById("resultModalClose").addEventListener("click", dismiss);'
         . 'document.addEventListener("keydown", function (e) { if (e.key === "Escape" && document.body.contains(modal)) { dismiss(); } });'
         . '})();</script>';
}

// Asks "Are you sure?" before any form posting to an action in CONFIRM_MODALS, then submits it on "Yes".
function renderConfirmModal(): string
{
    $configs = [];
    foreach (CONFIRM_MODALS as $action => [$icon, $title, $message, $yes, $tone]) {
        $configs[$action] = ['icon' => RESULT_MODAL_ICONS[$icon], 'title' => $title, 'message' => $message, 'yes' => $yes, 'tone' => $tone];
    }

    return '<div class="modal-backdrop" id="confirmModal" role="dialog" aria-modal="true" aria-labelledby="confirmModalTitle" hidden>'
         . '<div class="modal-box">'
         . '<div class="modal-icon-top" id="confirmModalIcon"></div>'
         . '<h2 class="modal-confirm-title" id="confirmModalTitle"></h2>'
         . '<p id="confirmModalMessage"></p>'
         . '<div class="modal-buttons">'
         . '<button type="button" class="modal-close" id="confirmModalNo">No</button>'
         . '<button type="button" class="modal-yes" id="confirmModalYes"></button>'
         . '</div></div></div>'
         . '<script>(function () {'
         . 'var configs = ' . json_encode($configs) . ';'
         . 'var modal = document.getElementById("confirmModal");'
         . 'var yes = document.getElementById("confirmModalYes");'
         . 'var pending = null;'
         . 'function close() { modal.hidden = true; pending = null; }'
         . 'document.addEventListener("submit", function (e) {'
         . '  var cfg = configs[e.target.getAttribute("action")];'
         . '  if (!cfg) { return; }'
         . '  e.preventDefault();'
         . '  pending = e.target;'
         . '  document.getElementById("confirmModalIcon").innerHTML = cfg.icon;'
         . '  document.getElementById("confirmModalTitle").textContent = cfg.title;'
         . '  document.getElementById("confirmModalMessage").textContent = cfg.message;'
         . '  yes.textContent = cfg.yes;'
         . '  yes.className = "modal-yes is-" + cfg.tone;'
         . '  modal.hidden = false;'
         . '});'
         . 'yes.addEventListener("click", function () { var form = pending; close(); if (form) { form.submit(); } });'
         . 'document.getElementById("confirmModalNo").addEventListener("click", close);'
         . 'modal.addEventListener("click", function (e) { if (e.target === modal) { close(); } });'
         . 'document.addEventListener("keydown", function (e) { if (e.key === "Escape" && !modal.hidden) { close(); } });'
         . '})();</script>';
}
