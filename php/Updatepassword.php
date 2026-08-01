<?php
// Changes the logged-in user's password.
// The current password is required: without it, anyone who found an unattended
// logged-in browser could lock the real owner out of their own account.

require_once __DIR__ . '/SessionGuard.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboards/Settings.php');
    exit;
}

$userId  = (int) $_SESSION['user_id'];
$current = $_POST['current_password'] ?? '';
$new     = $_POST['new_password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

function back(string $key, string $message): void {
    $_SESSION[$key] = $message;
    header('Location: ../dashboards/Settings.php');
    exit;
}

if ($current === '' || $new === '' || $confirm === '') {
    back('settings_error', 'Please fill in all three fields.');
}

if (strlen($new) < 8) {
    back('settings_error', 'Your new password must be at least 8 characters.');
}

if ($new !== $confirm) {
    back('settings_error', 'The new passwords do not match.');
}

if ($new === $current) {
    back('settings_error', 'Your new password must be different from the current one.');
}

try {
    $stmt = $pdo->prepare('SELECT password_hash FROM Users WHERE user_id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($current, $user['password_hash'])) {
        log_security_event($pdo, 'access_denied', $userId, 'Password change with wrong current password');
        back('settings_error', 'Your current password is not correct.');
    }

    $pdo->prepare('UPDATE Users SET password_hash = ? WHERE user_id = ?')
        ->execute([password_hash($new, PASSWORD_BCRYPT), $userId]);

    // Any failed attempts on this account are cleared — proving ownership of
    // the current password is stronger evidence than waiting out a lockout.
    $stmt = $pdo->prepare('SELECT email FROM Users WHERE user_id = ?');
    $stmt->execute([$userId]);
    $email = $stmt->fetch()['email'] ?? '';
    if ($email !== '') {
        $pdo->prepare('DELETE FROM LoginAttempts WHERE email = ? AND successful = 0')
            ->execute([$email]);
    }

    log_security_event($pdo, 'password_changed', $userId, 'Password updated from Settings');

    // The session ID is rotated so any copy of the old one is useless.
    session_regenerate_id(true);

} catch (PDOException $e) {
    error_log('Password change failed: ' . $e->getMessage());
    back('settings_error', 'Something went wrong. Please try again.');
}

back('settings_success', 'Your password has been updated.');