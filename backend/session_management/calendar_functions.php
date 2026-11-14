<?php

// Google Calendar sync over plain curl (no Composer). There is one shared Google Calendar connection for the
// whole app: any logged-in user can click Connect on the Session Dashboard to set it up, and once it's
// connected every other user just sees "Connected" with a Disconnect button - nobody else needs to connect.
// Every accepted session becomes one event on that shared calendar, and both the student and volunteer are
// invited by email either way.
// Needs functions.php (sessionCalendarDetails). Credentials live in general/google_config.php (not in Git).
// The refresh token lives in general/calendar_tokens.php (not in Git, not in the database) - see loadCalendarToken().
// Calendar problems are logged and never stop a booking, accept or cancel.

const GOOGLE_AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
const GOOGLE_TOKEN_URL = 'https://oauth2.googleapis.com/token';
const GOOGLE_REVOKE_URL = 'https://oauth2.googleapis.com/revoke';
const GOOGLE_EVENTS_URL = 'https://www.googleapis.com/calendar/v3/calendars/primary/events';
const GOOGLE_SCOPE = 'https://www.googleapis.com/auth/calendar.events';

// ['client_id', 'client_secret', 'redirect_uri'], or null when google_config.php is missing or incomplete.
function googleCalendarConfig(): ?array
{
    static $config = false;
    if ($config === false) {
        $file = __DIR__ . '/../general/google_config.php';
        $config = is_file($file) ? require $file : null;
        if (
            !is_array($config) || empty($config['client_id']) || empty($config['client_secret']) || empty($config['redirect_uri'])
            || str_starts_with((string) $config['client_secret'], 'PASTE')
        ) {
            $config = null;
        }
    }
    return $config;
}

// Why calendar sync is not available, in plain words, or null when the server-side setup is complete.
function calendarSetupHint(): ?string
{
    $file = __DIR__ . '/../general/google_config.php';
    if (!is_file($file)) {
        return 'Google Calendar sync is off: backend/general/google_config.php was not found.';
    }
    $config = require $file;
    $missing = [];
    foreach (['client_id', 'client_secret', 'redirect_uri'] as $key) {
        $value = is_array($config) ? ($config[$key] ?? '') : '';
        if (empty($value) || (is_string($value) && str_starts_with($value, 'PASTE'))) {
            $missing[] = $key;
        }
    }
    return $missing !== [] ? 'Google Calendar sync is off: google_config.php is missing ' . implode(', ', $missing) . '.' : null;
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

// The one shared connection: ['refresh_token' => ..., 'connected_at' => 'Y-m-d H:i:s', 'connected_by' => userId],
// or null when nobody has connected. Lives in a file, never in Git, never in the database.
const CALENDAR_TOKEN_FILE = __DIR__ . '/../general/calendar_tokens.php';

function loadCalendarToken(): ?array
{
    $token = is_file(CALENDAR_TOKEN_FILE) ? (require CALENDAR_TOKEN_FILE) : null;
    return is_array($token) ? $token : null;
}

function writeCalendarToken(?array $token): void
{
    file_put_contents(CALENDAR_TOKEN_FILE, "<?php\nreturn " . var_export($token, true) . ";\n", LOCK_EX);
}

function saveCalendarToken(string $refreshToken, int $connectedBy): void
{
    writeCalendarToken(['refresh_token' => $refreshToken, 'connected_at' => date('Y-m-d H:i:s'), 'connected_by' => $connectedBy]);
}

function findCalendarToken(): ?string
{
    return loadCalendarToken()['refresh_token'] ?? null;
}

function deleteCalendarToken(): void
{
    writeCalendarToken(null);
}

function isCalendarConnected(): bool
{
    return googleCalendarConfig() !== null && findCalendarToken() !== null;
}

function revokeCalendarToken(string $refreshToken): void
{
    googleFormPost(GOOGLE_REVOKE_URL, ['token' => $refreshToken]);
}

// Short-lived access token for the shared calendar, or null when nobody is connected or Google refuses.
// A revoked/expired refresh token (invalid_grant) is deleted here, so isCalendarConnected() goes back to false
// on its own and the Session Dashboard offers the Connect button again - no separate "expiry check" needed.
function googleAccessToken(): ?string
{
    $config = googleCalendarConfig();
    $refresh = $config !== null ? findCalendarToken() : null;
    if ($refresh === null) {
        return null;
    }
    [$status, $data] = googleFormPost(GOOGLE_TOKEN_URL, [
        'client_id' => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'refresh_token' => $refresh,
        'grant_type' => 'refresh_token',
    ]);
    if ($status === 400 && ($data['error'] ?? '') === 'invalid_grant') {   // whoever connected revoked access in their Google account
        deleteCalendarToken();
    }
    return $status === 200 ? ($data['access_token'] ?? null) : null;
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

// Creates the event on the shared calendar and invites both people. Returns the event's Google Calendar link,
// or null when nobody is connected or the call failed. Event ids are built from the session id, so after a
// database reset (session ids start again at 1) the id may already exist on the calendar, even as a
// deleted/cancelled event. Google answers 409 then, and the event is brought back and updated instead.
function createCalendarEvent(array $session): ?string
{
    $event = sessionCalendarDetails($session);
    $token = $event !== null ? googleAccessToken() : null;
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
function updateCalendarEvent(array $session): bool
{
    $event = sessionCalendarDetails($session);
    $token = $event !== null ? googleAccessToken() : null;
    if ($token === null) {
        return false;
    }
    [$status] = googleEventRequest('PATCH', $token, calendarEventId($session), googleEventPayload($event, $session));
    return $status === 200;
}

// Deletes the event. An event that is already gone (404/410) counts as deleted.
function deleteCalendarEvent(array $session): bool
{
    $token = googleAccessToken();
    if ($token === null) {
        return false;
    }
    [$status] = googleEventRequest('DELETE', $token, calendarEventId($session));
    return in_array($status, [200, 204, 404, 410], true);
}

// Runs one calendar action and returns its result, or $failed when it throws.
// A Google problem is logged and never stops the booking.
function runCalendarSync(array $session, callable $action, string $label, mixed $failed): mixed
{
    try {
        return $action($session);
    } catch (Throwable $e) {
        error_log("Calendar sync ($label) failed: " . $e->getMessage());
        return $failed;
    }
}

// Call after a session becomes Scheduled. Returns the event link, '' if it already existed, null on failure.
function syncCalendarOnAccept(array $session): ?string
{
    return runCalendarSync($session, 'createCalendarEvent', 'create', null);
}

// Call after a Scheduled session's time or details changed. $session must hold the new values.
function syncCalendarOnUpdate(array $session): bool
{
    return runCalendarSync($session, 'updateCalendarEvent', 'update', false);
}

// Call after a session is cancelled.
function syncCalendarOnCancel(array $session): bool
{
    return runCalendarSync($session, 'deleteCalendarEvent', 'delete', false);
}
