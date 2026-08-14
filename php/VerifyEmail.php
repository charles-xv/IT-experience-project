<?php
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Helpers.php';

$token = (string) ($_GET['token'] ?? '');
if ($token === '') {
    $_SESSION['verification_error'] = 'That verification link is not valid.';
    header('Location: ../LoginPage.php');
    exit;
}

try {
    $stmt = $pdo->prepare(
        'SELECT user_id, full_name, email, role, status FROM Users
         WHERE email_verify_token = ? AND email_verify_expires > NOW() AND email_verified = 0
         LIMIT 1'
    );
    $stmt->execute([hash('sha256', $token)]);
    $user = $stmt->fetch();

    if (!$user) {
        $_SESSION['verification_error'] = 'That verification link has expired or has already been used. Please request a new one.';
        header('Location: ../LoginPage.php');
        exit;
    }

    if (($user['status'] ?? 'active') === 'suspended') {
        $_SESSION['login_error'] = 'This account has been suspended. Please contact an administrator.';
        header('Location: ../LoginPage.php');
        exit;
    }

    $update = $pdo->prepare(
        'UPDATE Users
         SET email_verified = 1, email_verify_token = NULL, email_verify_expires = NULL, last_login = NOW()
         WHERE user_id = ? AND email_verified = 0'
    );
    $update->execute([(int) $user['user_id']]);

    if ($update->rowCount() !== 1) {
        $_SESSION['verification_error'] = 'That verification link has already been used.';
        header('Location: ../LoginPage.php');
        exit;
    }

    log_security_event($pdo, 'email_verified', (int) $user['user_id'], 'Email address verified; account session created');
    log_security_event($pdo, 'login_success', (int) $user['user_id'], 'Automatic login after email verification');

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['user_id'];
    $_SESSION['last_activity'] = time();
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['verification_success'] = true;
    unset($_SESSION['pending_verification_email'], $_SESSION['pending_verification_name'], $_SESSION['verification_last_sent']);

    header('Location: ../VerificationSuccess.php');
    exit;
} catch (Throwable $e) {
    error_log('Email verification failed: ' . $e->getMessage());
    $_SESSION['verification_error'] = 'We could not verify that email right now. Please try again.';
    header('Location: ../LoginPage.php');
    exit;
}
