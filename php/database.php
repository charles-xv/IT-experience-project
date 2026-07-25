<?php
// Central database connection. Every PHP file that touches the database
// includes this one, so credentials and connection settings live in a single place.

// XAMPP defaults: user 'root', empty password, MySQL on localhost.
// On a real server these would move to environment variables, never hard-coded.
$DB_HOST = 'localhost';
$DB_NAME = 'itexperience_db';
$DB_USER = 'root';
$DB_PASS = '';

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            // Throw exceptions on error instead of failing silently — a silent DB
            // failure is how you end up "logged in" against a query that never ran.
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

            // Return rows as associative arrays ($row['email'], not $row[0]).
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

            // Force REAL prepared statements. Without this, PDO emulates them and
            // the defence against SQL injection is weaker. This is the line that
            // matters most for the security grade.
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // Never echo the raw database error to the page — it can leak table names,
    // paths, and credentials to an attacker. Log it, show the user nothing useful.
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('A server error occurred. Please try again later.');
}
