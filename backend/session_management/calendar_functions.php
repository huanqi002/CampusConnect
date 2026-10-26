<?php

// Google Calendar sync over plain curl (no Composer). One organizer account (organizer_user_id in google_config.php)
// connects once; every accepted session becomes one event on its calendar, and the student and volunteer are invited
// by email, so users never connect anything themselves.
// Needs functions.php (sessionCalendarDetails). Credentials live in general/google_config.php (not in Git).
// Calendar problems are logged and never stop a booking, accept or cancel.

const GOOGLE_AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
const GOOGLE_TOKEN_URL = 'https://oauth2.googleapis.com/token';
const GOOGLE_REVOKE_URL = 'https://oauth2.googleapis.com/revoke';
const GOOGLE_EVENTS_URL = 'https://www.googleapis.com/calendar/v3/calendars/primary/events';
const GOOGLE_SCOPE = 'https://www.googleapis.com/auth/calendar.events';

// ['client_id', 'client_secret', 'redirect_uri', 'organizer_user_id'], or null when google_config.php is missing or incomplete.
function googleCalendarConfig(): ?array
{
    static $config = false;
    if ($config === false) {
        $file = __DIR__ . '/../general/google_config.php';
        $config = is_file($file) ? require $file : null;
        if (
            !is_array($config) || empty($config['client_id']) || empty($config['client_secret']) || empty($config['redirect_uri']) || empty($config['organizer_user_id'])
            || str_starts_with((string) $config['client_secret'], 'PASTE')
        ) {
            $config = null;
        }
    }
    return $config;
}

// Why calendar sync is not working on this machine, in plain words, or null when nothing is wrong for this user.
// Shown on the Session Dashboard so a teammate can see what is missing instead of guessing.
function calendarSetupHint(mysqli $conn, int $userId): ?string
{
    $file = __DIR__ . '/../general/google_config.php';
    if (!is_file($file)) {
        return 'Google Calendar sync is off: backend/general/google_config.php was not found.';
    }
    $config = require $file;
    $missing = [];
    foreach (['client_id', 'client_secret', 'redirect_uri', 'organizer_user_id'] as $key) {
        $value = is_array($config) ? ($config[$key] ?? '') : '';
        if (empty($value) || (is_string($value) && str_starts_with($value, 'PASTE'))) {
            $missing[] = $key;
        }
    }
    if ($missing !== []) {
        return 'Google Calendar sync is off: google_config.php is missing ' . implode(', ', $missing) . '.';
    }

    $organizerId = calendarOrganizerId();
    if ($userId !== $organizerId && !isCalendarConnected($conn, $organizerId)) {
        return "Google Calendar sync is waiting for the organizer (user #$organizerId) to log in and click Connect Google Calendar.";
    }
    return null;
}

// One HTTP call. Returns [status code, decoded JSON body or []]; status 0 means the request itself failed.
function googleRequest(string $method, string $url, array $headers = [], ?string $body = null): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    $response = curl_exec($ch);
    if ($response === false) {
        error_log('Google Calendar request failed: ' . curl_error($ch));
        return [0, []];
    }
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    if ($status >= 400) {
        error_log("Google Calendar $method $url returned $status: " . substr($response, 0, 300));
    }
    return [$status, json_decode($response, true) ?: []];
}

function googleFormPost(string $url, array $fields): array
{
    return googleRequest('POST', $url, ['Content-Type: application/x-www-form-urlencoded'], http_build_query($fields));
}

function googleCalendarAuthUrl(string $state): string
{
    $config = googleCalendarConfig();
    return GOOGLE_AUTH_URL . '?' . http_build_query([
        'client_id' => $config['client_id'],
        'redirect_uri' => $config['redirect_uri'],
        'response_type' => 'code',
        'scope' => GOOGLE_SCOPE,
        'access_type' => 'offline',   // we need a refresh token to act later, while the user is away
        'prompt' => 'consent',   // always return the refresh token, even on a reconnect
        'state' => $state,
    ]);
}

// Swaps the one-time code from Google for a refresh token. Returns null on failure.
function googleCalendarExchangeCode(string $code): ?string
{
    $config = googleCalendarConfig();
    [$status, $data] = googleFormPost(GOOGLE_TOKEN_URL, [
        'code' => $code,
        'client_id' => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'redirect_uri' => $config['redirect_uri'],
        'grant_type' => 'authorization_code',
    ]);
    return $status === 200 && !empty($data['refresh_token']) ? $data['refresh_token'] : null;
}

function saveCalendarToken(mysqli $conn, int $userId, string $refreshToken): void
{
    $stmt = $conn->prepare("INSERT INTO calendar_tokens (user_id, refresh_token) VALUES (?, ?)
                             ON DUPLICATE KEY UPDATE refresh_token = VALUES(refresh_token), connected_at = NOW()");
    $stmt->bind_param('is', $userId, $refreshToken);
    $stmt->execute();
}

function findCalendarToken(mysqli $conn, int $userId): ?string
{
    try {
        $stmt = $conn->prepare("SELECT refresh_token FROM calendar_tokens WHERE user_id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_column() ?: null;
    } catch (mysqli_sql_exception $e) {   // table not created yet
        return null;
    }
}

function deleteCalendarToken(mysqli $conn, int $userId): void
{
    $stmt = $conn->prepare("DELETE FROM calendar_tokens WHERE user_id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
}

function isCalendarConnected(mysqli $conn, int $userId): bool
{
    return googleCalendarConfig() !== null && findCalendarToken($conn, $userId) !== null;
}

// True when this user's Google connection was made today (by the database's date). No row at all, or a connection made
// on an earlier day, gives false, and the Connect button is enabled again; today's connection means the calendar is working.
function wasCalendarConnectedToday(mysqli $conn, int $userId): bool
{
    try {
        $stmt = $conn->prepare("SELECT DATE(connected_at) = CURDATE() FROM calendar_tokens WHERE user_id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_row();
        return $row !== null && (int) $row[0] === 1;
    } catch (mysqli_sql_exception $e) {   // table not created yet
        return false;
    }
}

function revokeCalendarToken(string $refreshToken): void
{
    googleFormPost(GOOGLE_REVOKE_URL, ['token' => $refreshToken]);
}

// Short-lived access token for the user's calendar, or null when they are not connected or Google refuses.
function googleAccessToken(mysqli $conn, int $userId): ?string
{
    $config = googleCalendarConfig();
    $refresh = $config !== null ? findCalendarToken($conn, $userId) : null;
    if ($refresh === null) {
        return null;
    }
    [$status, $data] = googleFormPost(GOOGLE_TOKEN_URL, [
        'client_id' => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'refresh_token' => $refresh,
        'grant_type' => 'refresh_token',
    ]);
    if ($status === 400 && ($data['error'] ?? '') === 'invalid_grant') {   // user revoked access in their Google account
        deleteCalendarToken($conn, $userId);
    }
    return $status === 200 ? ($data['access_token'] ?? null) : null;
}

// The user whose Google calendar owns every event, or null when calendar sync is not set up.
function calendarOrganizerId(): ?int
{
    $config = googleCalendarConfig();
    return $config !== null ? (int) $config['organizer_user_id'] : null;
}

function googleEventPayload(array $event, array $session): string
{
    $zone = date_default_timezone_get();
    $attendees = [];
    // Invite the student and the volunteer at the email they registered with.
    foreach ([[$session['student_email'] ?? '', $session['student_name']], [$session['volunteer_email'] ?? '', $session['volunteer_name']]] as [$email, $name]) {
        if ($email !== '') {
            $attendees[] = ['email' => $email, 'displayName' => $name];
        }
    }
    return json_encode([
        'summary' => $event['title'],
        'description' => $event['description'],
        'location' => $event['location'],
        'start' => ['dateTime' => date('Y-m-d\TH:i:s', $event['start']), 'timeZone' => $zone],
        'end' => ['dateTime' => date('Y-m-d\TH:i:s', $event['end']), 'timeZone' => $zone],
        'attendees' => $attendees,
    ]);
}

// sendUpdates=all makes Google email the invitation, change or cancellation to the attendees.
function googleEventRequest(string $method, string $token, string $eventId = '', ?string $body = null): array
{
    $headers = ['Authorization: Bearer ' . $token];
    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
    }
    $url = GOOGLE_EVENTS_URL . ($eventId !== '' ? '/' . rawurlencode($eventId) : '') . '?sendUpdates=all';
    return googleRequest($method, $url, $headers, $body);
}

// Google lets us choose the event id (lowercase a-v and digits only, at least 5 characters), so it is rebuilt
// from the session id instead of stored: session 42 => "ccsession42".
function calendarEventId(array $session): string
{
    return 'ccsession' . (int) $session['id'];
}

// Access token for the organizer's calendar, or null when sync is off or the organizer has not connected.
function organizerAccessToken(mysqli $conn): ?string
{
    $organizerId = calendarOrganizerId();
    return $organizerId !== null ? googleAccessToken($conn, $organizerId) : null;
}

// Creates the event and invites both people. Returns the event's Google Calendar link, or null when it could not be created.
// Event ids are built from the session id, so after a database reset (session ids start again at 1) the id may already exist
// on the calendar, even as a deleted/cancelled event. Google answers 409 then, and the event is brought back and updated instead.
function createCalendarEvent(mysqli $conn, array $session): ?string
{
    $event = sessionCalendarDetails($session);
    $token = $event !== null ? organizerAccessToken($conn) : null;
    if ($token === null) {
        return null;
    }
    $payload = json_decode(googleEventPayload($event, $session), true);
    [$status, $data] = googleEventRequest('POST', $token, '', json_encode($payload + ['id' => calendarEventId($session)]));
    if ($status === 409) {
        [$status, $data] = googleEventRequest('PATCH', $token, calendarEventId($session), json_encode($payload + ['status' => 'confirmed']));
    }
    return $status === 200 ? (string) ($data['htmlLink'] ?? '') : null;
}

// Moves/renames the event after the session changed. Returns true when Google accepted it.
function updateCalendarEvent(mysqli $conn, array $session): bool
{
    $event = sessionCalendarDetails($session);
    $token = $event !== null ? organizerAccessToken($conn) : null;
    if ($token === null) {
        return false;
    }
    [$status] = googleEventRequest('PATCH', $token, calendarEventId($session), googleEventPayload($event, $session));
    return $status === 200;
}

// Deletes the event. An event that is already gone (404/410) counts as deleted.
function deleteCalendarEvent(mysqli $conn, array $session): bool
{
    $token = organizerAccessToken($conn);
    if ($token === null) {
        return false;
    }
    [$status] = googleEventRequest('DELETE', $token, calendarEventId($session));
    return in_array($status, [200, 204, 404, 410], true);
}

// Runs one calendar action and returns its result, or $failed when it throws.
// A Google problem is logged and never stops the booking.
function runCalendarSync(mysqli $conn, array $session, callable $action, string $label, mixed $failed): mixed
{
    try {
        return $action($conn, $session);
    } catch (Throwable $e) {
        error_log("Calendar sync ($label) failed: " . $e->getMessage());
        return $failed;
    }
}

// Call after a session becomes Scheduled. Returns the event link, '' if it already existed, null on failure.
function syncCalendarOnAccept(mysqli $conn, array $session): ?string
{
    return runCalendarSync($conn, $session, 'createCalendarEvent', 'create', null);
}

// Call after a Scheduled session's time or details changed. $session must hold the new values.
function syncCalendarOnUpdate(mysqli $conn, array $session): bool
{
    return runCalendarSync($conn, $session, 'updateCalendarEvent', 'update', false);
}

// Call after a session is cancelled.
function syncCalendarOnCancel(mysqli $conn, array $session): bool
{
    return runCalendarSync($conn, $session, 'deleteCalendarEvent', 'delete', false);
}