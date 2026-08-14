<?php
// Starts a password reset. Generates a single-use token, stores only its hash,
// and emails the raw reset link. The reset token expires after 15 minutes.

session_start();
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Helpers.php';
require_once __DIR__ . '/Mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../ForgotPassword.php');
    exit;
}

$email = trim($_POST['email'] ?? '');

function back(string $key, string $message): void {
    $_SESSION[$key] = $message;
    header('Location: ../ForgotPassword.php');
    exit;
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    back('reset_error', 'Please enter a valid email address.');
}

// The response below is identical whether or not the account exists. Saying
// "no account with that email" would turn this form into a way to discover
// which addresses are registered.
$genericMessage = 'If an account exists for that address, a reset link has been created.';

try {
    $stmt = $pdo->prepare('SELECT user_id, full_name FROM Users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        log_security_event($pdo, 'password_reset_requested', null, "Reset requested for unknown address");
        back('reset_notice', $genericMessage);
    }

    // 64 hex characters from a cryptographically secure source.
    $rawToken = bin2hex(random_bytes(32));

    // Fifteen minutes: a reset link is a temporary credential to the account.
    $pdo->prepare(
        'UPDATE Users
         SET reset_token = ?, reset_expires = DATE_ADD(NOW(), INTERVAL 15 MINUTE)
         WHERE user_id = ?'
    )->execute([hash('sha256', $rawToken), (int) $user['user_id']]);

    log_security_event($pdo, 'password_reset_requested', (int) $user['user_id'], "Reset link issued for $email");

    $resetLink = app_url('ResetPassword.php?token=' . urlencode($rawToken));
    $html = '<div style="font-family:Arial,sans-serif;max-width:600px;margin:auto;color:#14233a">'
        . '<h2>Reset your Mech Spec LMS password</h2>'
        . '<p>Hello ' . e($user['full_name']) . ',</p>'
        . '<p>We received a request to reset your password. This link expires in <strong>15 minutes</strong> and can only be used once.</p>'
        . '<p><a href="' . e($resetLink) . '" style="display:inline-block;background:#f7c331;color:#07111f;padding:12px 20px;border-radius:8px;text-decoration:none;font-weight:700">Create a new password</a></p>'
        . '<p>If you did not request this, you can safely ignore this email.</p></div>';
    if (!send_html_email($email, 'Reset your Mech Spec LMS password', $html, 'Reset your Mech Spec LMS password: ' . $resetLink)) {
        $pdo->prepare('UPDATE Users SET reset_token = NULL, reset_expires = NULL WHERE user_id = ?')
            ->execute([(int) $user['user_id']]);
        log_security_event($pdo, 'email_delivery_failed', (int) $user['user_id'], 'Password reset email could not be sent');
        back('reset_error', 'We could not send the reset email right now. Please try again later.');
    }

} catch (PDOException $e) {
    error_log('Password reset request failed: ' . $e->getMessage());
    back('reset_error', 'Something went wrong. Please try again.');
}

back('reset_notice', $genericMessage);