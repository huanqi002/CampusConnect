<?php

const CARD_ICONS = [
    'edit'     => '<svg class="icon-fill" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>',
    'cancel'   => '<svg class="icon-stroke" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 5l14 14M19 5L5 19"/></svg>',
    'complete' => '<svg class="icon-stroke" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12.5l5 5L20 6.5"/></svg>',
];

function cardIcon(string $name): string
{
    return CARD_ICONS[$name] ?? '';
}

function cardIconLink(string $href, string $icon, string $label): string
{
    $label = htmlspecialchars($label);
    return '<a class="icon-btn" href="' . htmlspecialchars($href) . '" title="' . $label . '" aria-label="' . $label . '">'
         . cardIcon($icon) . '</a>';
}

function cardIconForm(string $action, array $hidden, string $icon, string $label, string $confirm = ''): string
{
    $label  = htmlspecialchars($label);
    $submit = $confirm !== '' ? ' onsubmit="return confirm(' . htmlspecialchars(json_encode($confirm)) . ');"' : '';
    $html   = '<form method="post" action="' . htmlspecialchars($action) . '"' . $submit . '>';
    foreach ($hidden as $name => $value) {
        $html .= '<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars((string)$value) . '">';
    }
    return $html . '<button type="submit" class="icon-btn" title="' . $label . '" aria-label="' . $label . '">'
                 . cardIcon($icon) . '</button></form>';
}

function renderCard(array $card): string
{
    $text = '';
    foreach ($card['fields'] ?? [] as $label => $value) {
        $text .= '<p><span class="card-label">' . htmlspecialchars($label) . ' :</span> '
               . htmlspecialchars((string)($value ?? '-')) . '</p>';
    }

    $html  = '<div class="info-card' . (!empty($card['href']) ? ' is-clickable' : '') . '">';
    $html .= !empty($card['href'])
        ? '<a class="card-text card-link" href="' . htmlspecialchars($card['href']) . '">' . $text . '</a>'
        : '<div class="card-text">' . $text . '</div>';

    if (!empty($card['status'])) {
        $status = htmlspecialchars($card['status']);
        $html  .= '<span class="pill pill-' . $status . '">' . $status . '</span>';
    }
    if (array_key_exists('actions', $card)) {
        $html .= '<div class="card-actions">' . $card['actions'] . '</div>';
    }
    return $html . '</div>';
}

function renderDetails(array $fields): string
{
    $html = '<dl class="detail-list">';
    foreach ($fields as $label => $value) {
        $html .= '<div class="detail-row"><dt>' . htmlspecialchars($label) . '</dt><dd>'
               . nl2br(htmlspecialchars((string)($value ?? '-'))) . '</dd></div>';
    }
    return $html . '</dl>';
}
