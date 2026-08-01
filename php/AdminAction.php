<?php
// Handles the admin's Suspend / Reinstate / Delete buttons.
// Every action here is destructive or privilege-affecting, so the role is
// re-checked server-side rather than trusting that the button was only
// rendered on the admin page.

require_once __DIR__ . '/SessionGuard.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Helpers.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboards/AdminDashboard.php?tab=users');
    exit;
}

$adminId = (int) $_SESSION['user_id'];
$targetId = (int) ($_POST['user_id'] ?? 0);
$action   = $_POST['action'] ?? '';

function back(string $key, string $message): void {
    $_SESSION[$key] = $message;
    header('Location: ../dashboards/AdminDashboard.php?tab=users');
    exit;
}

if ($targetId <= 0 || !in_array($action, ['suspend', 'reinstate', 'delete', 'update_user'], true)) {
    back('admin_error', 'Invalid request.');
}

// An admin must not be able to suspend or delete themselves — that would
// lock the platform out of its own administration.
if ($targetId === $adminId && $action !== 'update_user') {
    back('admin_error', 'You cannot perform that action on your own account.');
}

try {
    $stmt = $pdo->prepare('SELECT full_name, email, role FROM Users WHERE user_id = ?');
    $stmt->execute([$targetId]);
    $target = $stmt->fetch();

    if (!$target) {
        back('admin_error', 'That user no longer exists.');
    }

    // Protect the last remaining admin. Removing or suspending it would leave
    // nobody able to administer the platform.
    if ($target['role'] === 'admin') {
        $adminCount = (int) $pdo->query('SELECT COUNT(*) AS c FROM Users WHERE role = "admin"')->fetch()['c'];
        if ($adminCount <= 1) {
            back('admin_error', 'You cannot remove the only administrator account.');
        }
    }

    if ($action === 'update_user') {
        $newName  = trim($_POST['full_name'] ?? '');
        $newEmail = trim($_POST['email'] ?? '');
        $newRole  = $_POST['role'] ?? $target['role'];

        if ($newName === '' || $newEmail === '') {
            back('admin_error', 'Name and email cannot be empty.');
        }

        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            back('admin_error', 'That is not a valid email address.');
        }

        if (!in_array($newRole, ['student', 'instructor', 'admin'], true)) {
            back('admin_error', 'Invalid role.');
        }

        // An admin must not demote themselves out of the admin role — that
        // would leave them unable to undo it.
        if ($targetId === $adminId && $newRole !== 'admin') {
            back('admin_error', 'You cannot change your own role.');
        }

        // The email is UNIQUE in the database, so check first for a clean
        // message rather than a raw constraint error.
        $dupe = $pdo->prepare('SELECT user_id FROM Users WHERE email = ? AND user_id != ?');
        $dupe->execute([$newEmail, $targetId]);
        if ($dupe->fetch()) {
            back('admin_error', 'Another account already uses that email.');
        }

        $pdo->prepare('UPDATE Users SET full_name = ?, email = ?, role = ? WHERE user_id = ?')
            ->execute([$newName, $newEmail, $newRole, $targetId]);

        $changes = [];
        if ($newEmail !== $target['email']) $changes[] = "email {$target['email']} -> $newEmail";
        if ($newRole  !== $target['role'])  $changes[] = "role {$target['role']} -> $newRole";
        log_security_event($pdo, 'account_updated', $adminId,
            "Updated {$target['full_name']}" . ($changes ? ': ' . implode(', ', $changes) : ''));

        // If the admin edited their own details, refresh the session so the
        // header doesn't keep showing the old values.
        if ($targetId === $adminId) {
            $_SESSION['full_name'] = $newName;
            $_SESSION['email']     = $newEmail;
        }

        back('admin_success', "$newName has been updated.");
    }

    if ($action === 'suspend') {
        $pdo->prepare('UPDATE Users SET status = "suspended" WHERE user_id = ?')->execute([$targetId]);
        log_security_event($pdo, 'account_suspended', $adminId, "Suspended {$target['email']}");
        back('admin_success', "{$target['full_name']} has been suspended.");
    }

    if ($action === 'reinstate') {
        $pdo->prepare('UPDATE Users SET status = "active" WHERE user_id = ?')->execute([$targetId]);
        log_security_event($pdo, 'account_reinstated', $adminId, "Reinstated {$target['email']}");
        back('admin_success', "{$target['full_name']} has been reinstated.");
    }

    // Delete. Foreign keys cascade, so the user's enrolments, progress and
    // certificates go with them. SecurityLogs deliberately does not cascade —
    // the audit trail survives with the user_id set to NULL.
    // The event is logged BEFORE the delete so the details are still readable.
    log_security_event($pdo, 'account_deleted', $adminId, "Deleted {$target['email']} ({$target['role']})");
    $pdo->prepare('DELETE FROM Users WHERE user_id = ?')->execute([$targetId]);
    back('admin_success', "{$target['full_name']} has been permanently deleted.");

} catch (PDOException $e) {
    error_log('Admin action failed: ' . $e->getMessage());
    back('admin_error', 'Something went wrong. Please try again.');
}