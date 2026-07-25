<?php
// Receives the login form. Enforces rate limiting (3 failed tries per 15 minutes),
// verifies the password against the stored bcrypt hash, and on success routes the
// user to the dashboard for their role.

// Make the session cookie last only until the browser closes (lifetime 0) and
// harden it: HttpOnly keeps JavaScript from reading it, SameSite blocks it being
// sent from other sites. Combined with the idle timeout in SessionGuard, this is
// what stops a bookmarked dashboard from still being logged in days later.
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
require_once __DIR__ . '/Database.php';

// Tunable rate-limit settings — change in one place.
const MAX_ATTEMPTS  = 3;    // failed tries allowed
const LOCKOUT_MINS  = 15;   // window they're counted over, and how long a lock lasts

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../LoginPage.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

$_SESSION['login_old'] = ['email' => $email];

function fail(string $message): void {
    $_SESSION['login_error'] = $message;
    header('Location: ../LoginPage.php');
    exit;
}

if ($email === '' || $password === '') {
    fail('Please enter your email and password.');
}

try {
    // --- Rate limit check (before we even look at the password) ---
    // Count recent FAILED attempts for THIS email inside the window. We key on
    // the email alone, not the IP: keying on IP too would lock every account
    // that shares an IP (a whole school or office behind one address, or one
    // person testing several accounts) the moment any single account fails.
    $limitCheck = $pdo->prepare(
        'SELECT COUNT(*) AS fails
         FROM LoginAttempts
         WHERE successful = 0
           AND email = ?
           AND attempted_at > (NOW() - INTERVAL ? MINUTE)'
    );
    $limitCheck->execute([$email, LOCKOUT_MINS]);
    $recentFails = (int) $limitCheck->fetch()['fails'];

    if ($recentFails >= MAX_ATTEMPTS) {
        // Locked. We do NOT check the password at all while locked — that's what
        // makes the lock meaningful against a brute-force script.
        fail('Too many failed attempts. Please try again in ' . LOCKOUT_MINS . ' minutes.');
    }

    // --- Look up the user ---
    $stmt = $pdo->prepare('SELECT user_id, full_name, password_hash, role FROM Users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Verify the password. password_verify re-hashes the input with the stored
    // salt and compares — it never decrypts anything, because bcrypt is one-way.
    // We use one identical error message whether the email is unknown or the
    // password is wrong, so an attacker can't tell which emails are registered.
    $passwordOk = $user && password_verify($password, $user['password_hash']);

    // Log this attempt either way — success feeds the audit trail, failure feeds
    // the rate limiter above on the next try.
    $log = $pdo->prepare(
        'INSERT INTO LoginAttempts (email, ip_address, successful) VALUES (?, ?, ?)'
    );
    $log->execute([$email, $ip, $passwordOk ? 1 : 0]);

    if (!$passwordOk) {
        $remaining = MAX_ATTEMPTS - ($recentFails + 1);
        if ($remaining > 0) {
            fail("Incorrect email or password. $remaining attempt(s) left.");
        }
        fail('Too many failed attempts. Please try again in ' . LOCKOUT_MINS . ' minutes.');
    }

    // --- Success: establish the session ---
    // Clear this account's recent failures so a correct login wipes the slate —
    // otherwise earlier fails would keep counting toward a lockout for 15 minutes.
    $clear = $pdo->prepare('DELETE FROM LoginAttempts WHERE email = ? AND successful = 0');
    $clear->execute([$email]);

    // session_regenerate_id prevents session fixation — a fresh ID is issued the
    // moment privilege changes (anonymous visitor becomes logged-in user).
    session_regenerate_id(true);
    $_SESSION['user_id']   = $user['user_id'];
    $_SESSION['last_activity'] = time();
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role']      = $user['role'];
    unset($_SESSION['login_old']);

    // Route by role. This is the server-side decision — the browser never gets to
    // choose which dashboard it lands on, which is the whole point of doing it here.
    switch ($user['role']) {
        case 'admin':
            header('Location: ../dashboards/AdminDashboard.php');
            break;
        case 'instructor':
            header('Location: ../dashboards/InstructorDashboard.php');
            break;
        default:
            header('Location: ../dashboards/StudentDashboard.php');
    }
    exit;

} catch (PDOException $e) {
    error_log('Login failed: ' . $e->getMessage());
    fail('Something went wrong. Please try again.');
}
