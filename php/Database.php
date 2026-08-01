<?php
// Central database connection. Every PHP file that touches the database
// includes this one, so the credentials and connection settings live in a
// single place instead of being repeated (and drifting) across files.

$DB_HOST = 'localhost';
$DB_NAME = 'itexperience_db';

// ---------------------------------------------------------------------
//  SECURITY TO-DO before hosting: stop using root.
//
//  root can create and drop any database on the server, so a single SQL
//  injection anywhere becomes a full-server compromise instead of a
//  contained one. The fix is a user that can only touch this database.
//
//  Run this once in phpMyAdmin -> SQL, then swap the two lines below:
//
//    CREATE USER 'itexp_app'@'localhost' IDENTIFIED BY 'ChooseAStrongPassword';
//    GRANT SELECT, INSERT, UPDATE, DELETE ON itexperience_db.*
//      TO 'itexp_app'@'localhost';
//    FLUSH PRIVILEGES;
//
//  Then:  $DB_USER = 'itexp_app';  $DB_PASS = 'ChooseAStrongPassword';
//
//  Note the grant deliberately excludes DROP and ALTER — the application
//  never needs to change the schema at runtime.
// ---------------------------------------------------------------------
$DB_USER = 'root';
$DB_PASS = '';

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            // Throw on error instead of failing silently. A silent database
            // failure is how you end up "logged in" against a query that
            // never actually ran.
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

            // Rows come back as $row['email'] rather than $row[0].
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

            // Force REAL prepared statements. With emulation on, PDO builds
            // the query string itself and the injection defence is weaker.
            // This single line is the most important one in the file.
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // Never print the raw database error to the page — it leaks table names,
    // file paths and sometimes credentials. Log it, show the user nothing.
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('A server error occurred. Please try again later.');
}
