<?php
require_once __DIR__ . '/db.php';

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never {
    header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
    exit;
}

function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash(): void {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        $class = $f['type'] === 'success' ? 'message-success' : 'message-error';
        echo '<div class="message ' . $class . '">' . e($f['message']) . '</div>';
        unset($_SESSION['flash']);
    }
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function check_csrf(): void {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        exit('Invalid request. Please go back and try again.');
    }
}

function require_login(): void {
    restore_remembered_login();
    if (empty($_SESSION['user_id'])) {
        set_flash('error', 'Please log in first.');
        redirect('login.php');
    }
}

function remember_login(int $userId): void {
    global $pdo;
    $token = bin2hex(random_bytes(32));
    $expires = time() + (14 * 24 * 60 * 60);
    $stmt = $pdo->prepare('UPDATE users SET remember_token_hash = ?, remember_expires = ? WHERE id = ?');
    $stmt->execute([hash('sha256', $token), date('Y-m-d H:i:s', $expires), $userId]);
    setcookie('cc_remember', $userId . ':' . $token, [
        'expires' => $expires,
        'path' => BASE_URL . '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function restore_remembered_login(): void {
    global $pdo;
    if (!empty($_SESSION['user_id']) || empty($_COOKIE['cc_remember'])) return;
    if (!(bool)$pdo->query("SHOW COLUMNS FROM users LIKE 'remember_token_hash'")->fetch()) {
        clear_remember_cookie();
        return;
    }
    if (!preg_match('/^(\d+):([a-f0-9]{64})$/', $_COOKIE['cc_remember'], $matches)) {
        clear_remember_cookie();
        return;
    }

    $stmt = $pdo->prepare('SELECT id, full_name, is_volunteer, remember_token_hash, remember_expires FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int)$matches[1]]);
    $user = $stmt->fetch();
    if (!$user || empty($user['remember_token_hash']) || empty($user['remember_expires']) || strtotime($user['remember_expires']) < time() || !hash_equals((string)$user['remember_token_hash'], hash('sha256', $matches[2]))) {
        clear_remember_cookie();
        return;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['role'] = ($user['is_volunteer'] ?? 'N') === 'Y' ? 'volunteer' : 'student';
    $_SESSION['name'] = $user['full_name'];
    remember_login((int)$user['id']);
}

function clear_remember_cookie(): void {
    setcookie('cc_remember', '', [
        'expires' => time() - 3600,
        'path' => BASE_URL . '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function forget_remembered_login(): void {
    global $pdo;
    $rememberSupported = (bool)$pdo->query("SHOW COLUMNS FROM users LIKE 'remember_token_hash'")->fetch();
    if (!empty($_SESSION['user_id']) && $rememberSupported) {
        $stmt = $pdo->prepare('UPDATE users SET remember_token_hash = NULL, remember_expires = NULL WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
    }
    clear_remember_cookie();
}

function current_user(): ?array {
    global $pdo;
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function send_otp(string $email, string $subject, string $otp): bool {
    global $MAIL_CONFIG;
    if (!extension_loaded('curl') || !is_array($MAIL_CONFIG ?? null)) return false;
    foreach (['tenant_id', 'client_id', 'client_secret', 'sender_email'] as $key) {
        if (empty($MAIL_CONFIG[$key])) return false;
    }

    $tenant = rawurlencode($MAIL_CONFIG['tenant_id']);
    $tokenHandle = curl_init("https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/token");
    curl_setopt_array($tokenHandle, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'client_id' => $MAIL_CONFIG['client_id'],
            'client_secret' => $MAIL_CONFIG['client_secret'],
            'scope' => 'https://graph.microsoft.com/.default',
            'grant_type' => 'client_credentials',
        ]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => 20,
    ]);
    $tokenBody = curl_exec($tokenHandle);
    $tokenStatus = (int)curl_getinfo($tokenHandle, CURLINFO_HTTP_CODE);
    curl_close($tokenHandle);
    $tokenData = is_string($tokenBody) ? json_decode($tokenBody, true) : null;
    if ($tokenStatus < 200 || $tokenStatus >= 300 || empty($tokenData['access_token'])) return false;

    $sender = rawurlencode($MAIL_CONFIG['sender_email']);
    $mailHandle = curl_init("https://graph.microsoft.com/v1.0/users/{$sender}/sendMail");
    $mailData = [
        'message' => [
            'subject' => $subject,
            'body' => [
                'contentType' => 'Text',
                'content' => "Your Campus Connect verification code is: {$otp}\n\nThis code expires in 10 minutes. If you did not request it, you can ignore this email.",
            ],
            'toRecipients' => [['emailAddress' => ['address' => $email]]],
        ],
        'saveToSentItems' => true,
    ];
    curl_setopt_array($mailHandle, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($mailData, JSON_UNESCAPED_SLASHES),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $tokenData['access_token'],
            'Content-Type: application/json',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => 20,
    ]);
    curl_exec($mailHandle);
    $mailStatus = (int)curl_getinfo($mailHandle, CURLINFO_HTTP_CODE);
    curl_close($mailHandle);
    return $mailStatus === 202;
}

function university_email_valid(string $email): bool {
    return (bool)preg_match('/@helplive\.edu\.my$/i', $email);
}

function create_otp(): string {
    return (string)random_int(100000, 999999);
}
?>
