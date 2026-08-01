<?php
// Shared helpers used across the app. Include after Database.php, since
// every function here needs the $pdo connection.
//
// These exist so the same logging logic isn't copy-pasted (and slowly
// diverging) across Login.php, Signup.php and the dashboards.


/**
 * Records a security-relevant event to SecurityLogs.
 *
 * Event types in use: login_success, failed_login, account_locked,
 * account_suspended, account_reinstated, account_deleted, role_changed,
 * signup, logout, access_denied.
 *
 * $userId is nullable because some events happen with no known account
 * (a failed login against an email that doesn't exist, for example).
 */
function log_security_event(PDO $pdo, string $eventType, ?int $userId = null, ?string $details = null): void
{
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO SecurityLogs (event_type, user_id, ip_address, details)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $eventType,
            $userId,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $details,
        ]);
    } catch (PDOException $e) {
        // Logging must never break the page it is logging about.
        error_log('Security log write failed: ' . $e->getMessage());
    }
}


/**
 * Records a page view to PageVisits — this is what fills the admin's
 * visitor-IP view. Call it near the top of any page you want tracked.
 *
 * user_id stays null for visitors who are not logged in, which is the
 * point: it captures anonymous traffic as well as known users.
 */
function log_page_visit(PDO $pdo, string $page): void
{
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO PageVisits (page, ip_address, user_id, user_agent)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $page,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SESSION['user_id'] ?? null,
            // Truncated to the column width so an oversized header can't
            // throw and take the page down with it.
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    } catch (PDOException $e) {
        error_log('Page visit log failed: ' . $e->getMessage());
    }
}


/**
 * Escapes a value for safe output in HTML.
 *
 * Every piece of user-supplied data printed to a page must go through
 * this, or a name like <script>alert(1)</script> would execute instead
 * of displaying. Short name because it is used constantly in templates.
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
