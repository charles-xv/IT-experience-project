<?php
// Completes a password reset against a valid, unexpired, single-use token.

session_start();
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../LoginPage.php');
    exit;
}

$token   = $_POST['token'] ?? '';
$new     = $_POST['new_password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

function back(string $message, string $token): void {
    $_SESSION['reset_error'] = $message;
    header('Location: ../ResetPassword.php?token=' . urlencode($token));
    exit;
}

if ($token === '') {
    $_SESSION['login_error'] = 'That reset link is not valid.';
    header('Location: ../LoginPage.php');
    exit;
}

if ($new === '' || $confirm === '') {
    back('Please fill in both password fields.', $token);
}
if (strlen($new) < 8) {
    back('Your new password must be at least 8 characters.', $token);
}
if ($new !== $confirm) {
    back('The passwords do not match.', $token);
}

try {
    // The stored value is a hash, so the incoming token is hashed and compared.
    // Expiry is checked in SQL so an expired token cannot match at all.
    $stmt = $pdo->prepare(
        'SELECT user_id, email FROM Users
         WHERE reset_token = ? AND reset_expires > NOW()'
    );
    $stmt->execute([hash('sha256', $token)]);
    $user = $stmt->fetch();

    if (!$user) {
        log_security_event($pdo, 'access_denied', null, 'Invalid or expired password reset token used');
        $_SESSION['login_error'] = 'That reset link has expired or already been used. Please request a new one.';
        header('Location: ../LoginPage.php');
        exit;
    }

    // The token is cleared in the same statement that sets the password, so it
    // is single-use — the same link cannot reset the account twice.
    $pdo->prepare(
        'UPDATE Users
         SET password_hash = ?, reset_token = NULL, reset_expires = NULL
         WHERE user_id = ?'
    )->execute([password_hash($new, PASSWORD_BCRYPT), (int) $user['user_id']]);

    // Someone locked out by failed attempts can recover this way, so the
    // failure history is cleared too. Proving control of the reset link is
    // stronger evidence of ownership than waiting out a lockout.
    $pdo->prepare('DELETE FROM LoginAttempts WHERE email = ? AND successful = 0')
        ->execute([$user['email']]);

    log_security_event($pdo, 'password_reset', (int) $user['user_id'], 'Password reset completed');

} catch (PDOException $e) {
    error_log('Password reset failed: ' . $e->getMessage());
    back('Something went wrong. Please try again.', $token);
}

// Deliberately does not log them in — they sign in with the new password,
// which confirms it works and matches the normal login path.
$_SESSION['login_success'] = 'Your password has been reset. Please log in.';
header('Location: ../LoginPage.php');
exit;