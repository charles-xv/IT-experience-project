<?php
// Ends the session completely: clears the data, deletes the session cookie from
// the browser, and destroys the server-side session. Deleting the cookie is what
// stops a leftover session identifier from being reused.
session_start();

// Logged before the session is cleared, while the user id is still known.
if (!empty($_SESSION['user_id'])) {
    require_once __DIR__ . '/Database.php';
    require_once __DIR__ . '/Helpers.php';
    log_security_event($pdo, 'logout', (int) $_SESSION['user_id']);
}

$_SESSION = [];

// Expire the session cookie in the browser by re-issuing it with a past date.
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();
header('Location: ../Index.php');
exit;