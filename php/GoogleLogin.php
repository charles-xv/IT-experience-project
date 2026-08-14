<?php

declare(strict_types=1);

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();

require_once __DIR__ . '/GoogleAuth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../LoginPage.php');
    exit;
}

$credential = trim((string) ($_POST['credential'] ?? ''));

if ($credential === '') {
    $_SESSION['login_error'] =
        'Google sign-in did not return a valid credential. Please try again.';

    header('Location: ../LoginPage.php');
    exit;
}

$google = verify_google_id_token($credential);

if (!$google) {
    log_security_event(
        $pdo,
        'access_denied',
        null,
        'Invalid Google ID token'
    );

    $_SESSION['login_error'] =
        'Google sign-in could not be verified. Please try again or use your password.';

    header('Location: ../LoginPage.php');
    exit;
}

finish_google_login($pdo, $google);