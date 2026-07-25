<?php
// Include at the top of every protected page. Ensures a user is logged in,
// enforces an idle timeout, and stops the browser caching the page so the
// back button can't show a dashboard after logout. Also handles role checks.

// --- Stop the browser (and back button) from caching protected pages ---
// Without these, Chrome shows a saved copy of the dashboard after logout when
// you press Back — the session is dead, but the stale page is served from cache.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Not logged in at all -> back to login.
if (empty($_SESSION['user_id'])) {
    header('Location: ../LoginPage.php');
    exit;
}

// --- Idle timeout ---
// A session is considered expired after this many seconds of inactivity, so a
// bookmarked dashboard opened days later won't still be logged in. Each page
// load refreshes the timer; go idle past the limit and the session is killed.
const SESSION_IDLE_LIMIT = 1800; // 30 minutes

if (isset($_SESSION['last_activity'])
    && (time() - $_SESSION['last_activity']) > SESSION_IDLE_LIMIT) {
    // Expired — clear everything and send them to log in with a notice.
    $_SESSION = [];
    session_destroy();
    session_start();
    $_SESSION['login_error'] = 'Your session expired. Please log in again.';
    header('Location: ../LoginPage.php');
    exit;
}
$_SESSION['last_activity'] = time();

// require_role('admin') on a page means only admins get in. A logged-in student
// who types the admin URL is bounced to their own dashboard, not shown the page.
function require_role(string $role): void {
    if (($_SESSION['role'] ?? '') !== $role) {
        switch ($_SESSION['role'] ?? '') {
            case 'admin':
                header('Location: AdminDashboard.php');
                break;
            case 'instructor':
                header('Location: InstructorDashboard.php');
                break;
            default:
                header('Location: StudentDashboard.php');
        }
        exit;
    }
}
