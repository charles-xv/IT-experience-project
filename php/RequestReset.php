<?php
// Starts a password reset. Generates a single-use token, stores a hash of it,
// and produces the reset link.
//
// In production this link would be emailed. There is no mail server configured
// here, so the link is handed back to the page instead — clearly labelled as a
// development shortcut, not a design choice.

session_start();
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Helpers.php';

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

    // One hour, deliberately shorter than a verification token would be: a
    // reset link is a temporary key to the account.
    $pdo->prepare(
        'UPDATE Users
         SET reset_token = ?, reset_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR)
         WHERE user_id = ?'
    )->execute([hash('sha256', $rawToken), (int) $user['user_id']]);

    log_security_event($pdo, 'password_reset_requested', (int) $user['user_id'], "Reset link issued for $email");

    // DEVELOPMENT ONLY — production sends this by email and never displays it.
    $_SESSION['reset_dev_link'] = 'ResetPassword.php?token=' . $rawToken;

} catch (PDOException $e) {
    error_log('Password reset request failed: ' . $e->getMessage());
    back('reset_error', 'Something went wrong. Please try again.');
}

back('reset_notice', $genericMessage);