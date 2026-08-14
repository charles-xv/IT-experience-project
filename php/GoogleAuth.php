<?php
// Google Identity Services backend verifier.
// The browser sends Google's signed ID token here. We validate it with
// Google's tokeninfo endpoint, then verify the issuer, audience and expiry
// before using any identity fields.

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Helpers.php';
require_once __DIR__ . '/Mailer.php';

function google_client_id(): string
{
    return app_secret('GOOGLE_CLIENT_ID', '');
}

function verify_google_id_token(string $idToken): ?array
{
    $clientId = google_client_id();
    if ($clientId === '' || $idToken === '') {
        return null;
    }

    $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . rawurlencode($idToken);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false || $status !== 200) {
        return null;
    }

    $payload = json_decode($body, true);
    if (!is_array($payload)) {
        return null;
    }

    $issuerOk = in_array($payload['iss'] ?? '', ['https://accounts.google.com', 'accounts.google.com'], true);
    $audienceOk = hash_equals($clientId, (string) ($payload['aud'] ?? ''));
    $expiresOk = isset($payload['exp']) && (int) $payload['exp'] > time();
    $emailOk = filter_var($payload['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $verifiedOk = filter_var($payload['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $subject = (string) ($payload['sub'] ?? '');

    if (!$issuerOk || !$audienceOk || !$expiresOk || !$emailOk || !$verifiedOk || $subject === '') {
        return null;
    }

    return [
        'sub' => $subject,
        'email' => strtolower(trim((string) $payload['email'])),
        'name' => trim((string) ($payload['name'] ?? 'Google User')),
        'picture' => (string) ($payload['picture'] ?? ''),
        'email_verified' => true,
    ];
}

function finish_google_login(PDO $pdo, array $google): void
{
    $email = $google['email'];
    $sub = $google['sub'];

    $stmt = $pdo->prepare('SELECT user_id, full_name, email, role, status, google_sub FROM Users WHERE email = ? OR google_sub = ? LIMIT 1');
    $stmt->execute([$email, $sub]);
    $user = $stmt->fetch();

    if ($user && ($user['status'] ?? 'active') === 'suspended') {
        log_security_event($pdo, 'access_denied', (int) $user['user_id'], 'Suspended account attempted Google login');
        $_SESSION['login_error'] = 'This account has been suspended. Please contact an administrator.';
        header('Location: ../LoginPage.php');
        exit;
    }

    if (!$user) {
        $hash = password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT);
        $insert = $pdo->prepare(
            'INSERT INTO Users (full_name, email, password_hash, role, email_verified, google_sub)
             VALUES (?, ?, ?, "student", 1, ?)'
        );
        $insert->execute([$google['name'] ?: 'Google User', $email, $hash, $sub]);
        $userId = (int) $pdo->lastInsertId();
        $role = 'student';
        $fullName = $google['name'] ?: 'Google User';
        log_security_event($pdo, 'signup', $userId, 'Account created through Google Sign-In');
    } else {
        $userId = (int) $user['user_id'];
        $role = $user['role'];
        $fullName = $user['full_name'];
        $pdo->prepare('UPDATE Users SET google_sub = ?, email_verified = 1, last_login = NOW() WHERE user_id = ?')
            ->execute([$sub, $userId]);
    }

    $pdo->prepare('UPDATE Users SET last_login = NOW(), email_verified = 1 WHERE user_id = ?')->execute([$userId]);
    log_security_event($pdo, 'login_success', $userId, 'Google Sign-In');

    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['last_activity'] = time();
    $_SESSION['full_name'] = $fullName;
    $_SESSION['role'] = $role;
    $_SESSION['email'] = $email;
    unset($_SESSION['login_old']);

    $dest = $role === 'admin' ? 'AdminDashboard.php' : ($role === 'instructor' ? 'InstructorDashboard.php' : 'StudentDashboard.php');
    header('Location: ../dashboards/' . $dest);
    exit;
}
