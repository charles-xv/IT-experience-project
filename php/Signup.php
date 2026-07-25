<?php
// Receives the signup form, validates it, and creates the account.
// On any problem it stores a message + the typed values in the session and
// sends the user back to the signup page. On success it sends them to login.

session_start();
require_once __DIR__ . '/Database.php';

// Only accept POST. Someone typing this URL directly gets bounced.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../SignupPage.php');
    exit;
}

// Trim whitespace so " a@b.com " doesn't sneak through as different from "a@b.com".
$fullName = trim($_POST['full_name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role     = $_POST['role'] ?? '';

// Keep what they typed (never the password) so the form can repopulate on error.
$_SESSION['signup_old'] = ['full_name' => $fullName, 'email' => $email, 'role' => $role];

// Helper: stash an error and return to the form.
function fail(string $message): void {
    $_SESSION['signup_error'] = $message;
    header('Location: ../SignupPage.php');
    exit;
}

// --- Validation ---

if ($fullName === '' || $email === '' || $password === '') {
    fail('Please fill in all fields.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fail('Please enter a valid email address.');
}

if (strlen($password) < 8) {
    fail('Password must be at least 8 characters.');
}

// The public form only offers these two. Anyone tampering with the request to
// send 'admin' is rejected here — admin accounts are never created via signup.
if (!in_array($role, ['student', 'instructor'], true)) {
    fail('Please choose a valid role.');
}

// --- Create the account ---

try {
    // Is the email already taken? Prepared statement, so the email can never be
    // read as SQL — this is the SQL-injection defence in practice.
    $check = $pdo->prepare('SELECT user_id FROM Users WHERE email = ?');
    $check->execute([$email]);
    if ($check->fetch()) {
        fail('An account with that email already exists.');
    }

    // Hash with bcrypt. The raw password is never stored, and password_hash
    // generates a unique salt automatically, so identical passwords hash differently.
    $hash = password_hash($password, PASSWORD_BCRYPT);

    $insert = $pdo->prepare(
        'INSERT INTO Users (full_name, email, password_hash, role) VALUES (?, ?, ?, ?)'
    );
    $insert->execute([$fullName, $email, $hash, $role]);

} catch (PDOException $e) {
    error_log('Signup failed: ' . $e->getMessage());
    fail('Something went wrong creating your account. Please try again.');
}

// Success — clear the remembered form values and send them to log in.
unset($_SESSION['signup_old']);
$_SESSION['login_success'] = 'Account created. Please log in.';
header('Location: ../LoginPage.php');
exit;
