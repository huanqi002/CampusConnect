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
    'check' => '<svg class="modal-icon" viewBox="0 0 40 40" aria-hidden="true">'
        . '<circle cx="20" cy="20" r="17" fill="#1aa33a"/>'
        . '<path d="M12 20.5l5.5 5.5L28.5 14" fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>'
        . '</svg>',
    'cancelled' => '<svg class="modal-icon" viewBox="0 0 40 40" aria-hidden="true">'
        . '<circle cx="20" cy="20" r="15" fill="none" stroke="#d32f2f" stroke-width="4"/>'
        . '<path d="M9.5 9.5l21 21" stroke="#d32f2f" stroke-width="4"/>'
        . '</svg>',
    'rejected' => '<svg class="modal-icon" viewBox="0 0 40 40" aria-hidden="true">'
        . '<circle cx="20" cy="20" r="17" fill="#d32f2f"/>'
        . '<path d="M14 14l12 12M26 14L14 26" fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round"/>'
        . '</svg>',
    'warning' => '<svg class="modal-icon" viewBox="0 0 40 40" aria-hidden="true">'
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
    'accept_request' => ['check', 'Accept', 'Are you sure you want to accept this?', 'Yes, Accept', 'success'],
    'reject_request' => ['warning', 'Reject', 'Are you sure you want to reject this?', 'Yes, Reject', 'danger'],
    'mark_completed.php' => ['check', 'Mark as Complete', 'Are you sure you want to mark this session as complete?', 'Yes, Complete', 'success'],
];

function renderResultModal(string $icon, string $title, string $message, string $tone = 'success'): string
{
    return '<div class="modal-backdrop" id="resultModal" role="dialog" aria-modal="true" aria-labelledby="resultModalTitle">'
        . '<div class="modal-box">'
        . '<h2 class="modal-title is-' . htmlspecialchars($tone) . '" id="resultModalTitle">' . (RESULT_MODAL_ICONS[$icon] ?? '') . htmlspecialchars($title) . '</h2>'
        . '<p>' . htmlspecialchars($message) . '</p>'
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
        . '  var action = e.target.getAttribute("action");'
        . '  var cfg = configs[action];'
        . '  if (!cfg && action === "respond_to_request.php") {'
        . '    var response = e.target.querySelector("input[name=response]");'
        . '    cfg = configs[response && response.value === "Accepted" ? "accept_request" : "reject_request"];'
        . '  }'
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
