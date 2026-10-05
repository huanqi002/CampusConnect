<?php

function formatTime(?string $time): string
{
    return $time === null ? '' : date('g:i A', strtotime($time));
}

function formatTimeRange(?string $start, ?string $end = null): string
{
    return formatTime($start) . ($end !== null ? ' - ' . formatTime($end) : '');
}

function formatDateTime(?string $dateTime): string
{
    return $dateTime === null ? '' : date('j M Y, g:i A', strtotime($dateTime));
}
