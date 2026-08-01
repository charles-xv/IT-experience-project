<?php
// Updates the logged-in user's display name and email address.
//
// The current password is required. Changing the email changes how the account
// signs in, so without that check anyone at an unattended logged-in browser
// could move the account to an address they control and lock the owner out.

require_once __DIR__ . '/SessionGuard.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboards/Settings.php');
    exit;
}

$userId   = (int) $_SESSION['user_id'];
$newName  = trim($_POST['full_name'] ?? '');
$newEmail = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

function back(string $key, string $message): void {
    $_SESSION[$key] = $message;
    header('Location: ../dashboards/Settings.php');
    exit;
}

if ($newName === '' || $newEmail === '' || $password === '') {
    back('settings_error', 'Please fill in all fields, including your password.');
}

if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
    back('settings_error', 'That is not a valid email address.');
}

try {
    $stmt = $pdo->prepare('SELECT full_name, email, password_hash FROM Users WHERE user_id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        log_security_event($pdo, 'access_denied', $userId, 'Profile change with wrong password');
        back('settings_error', 'That password is not correct.');
    }

    // Nothing actually changed — say so rather than reporting a false success.
    if ($newName === $user['full_name'] && $newEmail === $user['email']) {
        back('settings_error', 'Nothing to update — those are your current details.');
    }

    // email is UNIQUE in the database; check first for a readable message
    // instead of a raw constraint error.
    if ($newEmail !== $user['email']) {
        $dupe = $pdo->prepare('SELECT user_id FROM Users WHERE email = ? AND user_id != ?');
        $dupe->execute([$newEmail, $userId]);
        if ($dupe->fetch()) {
            back('settings_error', 'Another account already uses that email address.');
        }
    }

    $pdo->prepare('UPDATE Users SET full_name = ?, email = ? WHERE user_id = ?')
        ->execute([$newName, $newEmail, $userId]);

    // The session carries these for the header, so refresh them or the page
    // keeps showing the old values until the next login.
    $_SESSION['full_name'] = $newName;
    $_SESSION['email']     = $newEmail;

    $detail = $newEmail !== $user['email']
        ? "Email {$user['email']} -> $newEmail"
        : 'Display name updated';
    log_security_event($pdo, 'profile_updated', $userId, $detail);

} catch (PDOException $e) {
    error_log('Profile update failed: ' . $e->getMessage());
    back('settings_error', 'Something went wrong. Please try again.');
}

back('settings_success', 'Your details have been updated.');