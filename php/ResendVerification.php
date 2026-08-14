<?php
session_start();
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Helpers.php';
require_once __DIR__ . '/Mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../LoginPage.php');
    exit;
}

$sessionEmail = strtolower(trim((string) ($_SESSION['pending_verification_email'] ?? '')));
$postedEmail  = strtolower(trim((string) ($_POST['email'] ?? '')));
$email = $sessionEmail !== '' ? $sessionEmail : $postedEmail;

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['login_error'] = 'Please start the registration process again.';
    header('Location: ../LoginPage.php');
    exit;
}

$lastSent = (int) ($_SESSION['verification_last_sent'] ?? 0);
if ($lastSent && time() - $lastSent < 60) {
    $wait = 60 - (time() - $lastSent);
    $_SESSION['verification_error'] = "Please wait {$wait} seconds before requesting another email.";
    header('Location: ../VerificationPending.php');
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT user_id, full_name, email_verified FROM Users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        $_SESSION['verification_error'] = 'That verification request is no longer available. Please register again.';
        header('Location: ../VerificationPending.php');
        exit;
    }

    if ((int) $user['email_verified']) {
        unset($_SESSION['pending_verification_email'], $_SESSION['pending_verification_name'], $_SESSION['verification_last_sent']);
        $_SESSION['login_success'] = 'Your email is already verified. Please log in.';
        header('Location: ../LoginPage.php');
        exit;
    }

    $rawToken = bin2hex(random_bytes(32));
    $pdo->prepare(
        'UPDATE Users SET email_verify_token = ?, email_verify_expires = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE user_id = ?'
    )->execute([hash('sha256', $rawToken), (int) $user['user_id']]);

    $link = app_url('php/VerifyEmail.php?token=' . rawurlencode($rawToken));
    $html = '<!doctype html><html><body style="margin:0;background:#f5f7fa;font-family:Arial,sans-serif;color:#14233a">'
        . '<div style="max-width:600px;margin:30px auto;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:32px">'
        . '<h2>Verify your Mech Spec LMS email</h2>'
        . '<p>Hello ' . e($user['full_name']) . ',</p>'
        . '<p>Here is your new verification link.</p>'
        . '<p><a href="' . e($link) . '" style="display:inline-block;background:#f7c331;color:#07111f;padding:13px 22px;border-radius:8px;text-decoration:none;font-weight:700">Verify my email</a></p>'
        . '<p style="color:#64748b"><strong>This link expires in 15 minutes.</strong></p>'
        . '</div></body></html>';

    if (!send_html_email($email, 'Your Mech Spec LMS verification link', $html, 'Verify your Mech Spec LMS email: ' . $link)) {
        $_SESSION['verification_error'] = 'We could not send the email right now. Please try again later.';
        header('Location: ../VerificationPending.php');
        exit;
    }

    $_SESSION['verification_last_sent'] = time();
    $_SESSION['verification_notice'] = 'A new verification email has been sent. Please check your inbox and spam folder.';
    log_security_event($pdo, 'verification_email_resent', (int) $user['user_id'], 'Verification email resent');
    header('Location: ../VerificationPending.php');
    exit;
} catch (Throwable $e) {
    error_log('Verification resend failed: ' . $e->getMessage());
    $_SESSION['verification_error'] = 'We could not send the email right now. Please try again later.';
    header('Location: ../VerificationPending.php');
    exit;
}
