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
require_once __DIR__ . '/Helpers.php';

// Tunable rate-limit settings — change in one place.
const MAX_ATTEMPTS       = 3;   // failed tries before a lock is triggered
const LOCKOUT_MINS       = 15;  // how long the lock itself lasts
const COUNT_WINDOW_HOURS = 24;  // how far back failures are counted

// Two separate windows, deliberately:
//
//   COUNT_WINDOW_HOURS decides which failures still count. Failures older
//   than this are forgotten, so someone who mistyped twice last week starts
//   clean today instead of being one slip away from a lock.
//
//   LOCKOUT_MINS decides how long the lock lasts once triggered.
//
// Using a single window for both is the common mistake: make it short and a
// slow brute-force just waits it out between bursts; make it long and honest
// users stay locked for hours. Splitting them fixes both ends.

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
    // --- Rate limit check (before the password is even looked at) ---
    // Two things are needed: how many failures still count, and when the most
    // recent one happened. A lock is active only if the threshold has been hit
    // AND the newest failure is still inside the lockout period.
    $limitCheck = $pdo->prepare(
        'SELECT COUNT(*) AS fails,
                MAX(attempted_at) AS last_fail,
                TIMESTAMPDIFF(SECOND, MAX(attempted_at), NOW()) AS secs_since_last
         FROM LoginAttempts
         WHERE successful = 0
           AND email = ?
           AND attempted_at > (NOW() - INTERVAL ? HOUR)'
    );
    $limitCheck->execute([$email, COUNT_WINDOW_HOURS]);
    $limitRow    = $limitCheck->fetch();
    $recentFails = (int) $limitRow['fails'];
    $secsSince   = $limitRow['secs_since_last'] === null ? PHP_INT_MAX : (int) $limitRow['secs_since_last'];

    $lockActive = $recentFails >= MAX_ATTEMPTS && $secsSince < (LOCKOUT_MINS * 60);

    if ($lockActive) {
        // While locked the password is not checked at all — that is what makes
        // the lock meaningful against an automated attack.
        $minutesLeft = (int) ceil((LOCKOUT_MINS * 60 - $secsSince) / 60);
        log_security_event($pdo, 'account_locked', null, "Locked login attempt for $email");
        fail("Too many failed attempts. Please try again in $minutesLeft minute(s).");
    }

    // --- Look up the user ---
    $stmt = $pdo->prepare('SELECT user_id, full_name, email, password_hash, role, status, email_verified FROM Users WHERE email = ?');
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
        log_security_event($pdo, 'failed_login', $user ? (int) $user['user_id'] : null, "Failed login for $email");

        // Count THIS failure too. Without the +1 the lock only fired on the
        // next attempt, which gave four tries instead of three.
        $totalFails = $recentFails + 1;

        if ($totalFails >= MAX_ATTEMPTS) {
            log_security_event($pdo, 'account_locked', null, "Locked after $totalFails failures for $email");
            fail('Too many failed attempts. Please try again in ' . LOCKOUT_MINS . ' minutes.');
        }

        // The wording is identical whether the email is unknown or the
        // password is wrong, so it still can't be used to discover which
        // addresses are registered.
        $remaining = MAX_ATTEMPTS - $totalFails;
        fail("Email or password is not correct. $remaining attempt(s) remaining.");
    }

    // A suspended account has a valid password but must not get in.
    // Checked after the password so the message can't be used to discover
    // which emails exist.
    if (($user['status'] ?? 'active') === 'suspended') {
        log_security_event($pdo, 'access_denied', (int) $user['user_id'], 'Suspended account attempted login');
        fail('This account has been suspended. Please contact an administrator.');
    }

    // Unverified accounts are blocked. Checked after the password so the
    // message can't be used to discover which emails exist. A pending
    // instructor approval does NOT block login here — only publishing is
    // restricted, enforced separately where courses are created.
    if ((int) ($user['email_verified'] ?? 1) === 0) {
        log_security_event($pdo, 'access_denied', (int) $user['user_id'], 'Unverified account attempted login');
        fail('Please verify your email before logging in. Check your inbox for the verification link.');
    }

    // --- Success: establish the session ---
    // Clear this account's recent failures so a correct login wipes the slate —
    // otherwise earlier fails would keep counting toward a lockout for 15 minutes.
    $clear = $pdo->prepare('DELETE FROM LoginAttempts WHERE email = ? AND successful = 0');
    $clear->execute([$email]);

    // session_regenerate_id prevents session fixation — a fresh ID is issued the
    // moment privilege changes (anonymous visitor becomes logged-in user).
    // Record when they last signed in — the admin page uses this to spot
    // accounts that have gone inactive.
    $pdo->prepare('UPDATE Users SET last_login = NOW() WHERE user_id = ?')
        ->execute([$user['user_id']]);
    log_security_event($pdo, 'login_success', (int) $user['user_id']);

    session_regenerate_id(true);
    $_SESSION['user_id']   = $user['user_id'];
    $_SESSION['last_activity'] = time();
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role']      = $user['role'];
    $_SESSION['status']    = $user['status'];
    $_SESSION['email']     = $user['email'];
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