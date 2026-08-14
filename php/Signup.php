<?php
// Creates an email/password account, sends a real SMTP verification email,
// then sends the user to the verification-pending page. Verification links
// are single-use and expire after 15 minutes.
session_start();
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Helpers.php';
require_once __DIR__ . '/Mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../SignupPage.php');
    exit;
}

$fullName = trim((string) ($_POST['full_name'] ?? ''));
$email    = strtolower(trim((string) ($_POST['email'] ?? '')));
$password = (string) ($_POST['password'] ?? '');
$role     = (string) ($_POST['role'] ?? '');

$_SESSION['signup_old'] = [
    'full_name' => $fullName,
    'email' => $email,
    'role' => $role,
];

function fail_signup(string $message): void {
    $_SESSION['signup_error'] = $message;
    header('Location: ../SignupPage.php');
    exit;
}

if ($fullName === '' || $email === '' || $password === '') {
    fail_signup('Please fill in all fields.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fail_signup('Please enter a valid email address.');
}
if (strlen($password) < 8) {
    fail_signup('Password must be at least 8 characters.');
}
if (!in_array($role, ['student', 'instructor'], true)) {
    fail_signup('Please choose a valid role.');
}

try {
    $check = $pdo->prepare('SELECT user_id, email_verified FROM Users WHERE email = ?');
    $check->execute([$email]);
    $existing = $check->fetch();
    if ($existing) {
        fail_signup('An account with that email already exists. Please log in or use Forgot password.');
    }

    $pdo->beginTransaction();

    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    $rawToken = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $rawToken);

    // Instructors start 'pending' and can't publish until an admin approves
    // them (Option C from the mentor review). Students stay at the column
    // default ('approved') — the value is meaningless for their role anyway.
    $approvalStatus = $role === 'instructor' ? 'pending' : 'approved';

    $insert = $pdo->prepare(
        'INSERT INTO Users
            (full_name, email, password_hash, role, email_verified, email_verify_token, email_verify_expires, instructor_approval_status)
         VALUES (?, ?, ?, ?, 0, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE), ?)'
    );
    $insert->execute([$fullName, $email, $passwordHash, $role, $tokenHash, $approvalStatus]);
    $userId = (int) $pdo->lastInsertId();

    $verifyLink = app_url('php/VerifyEmail.php?token=' . rawurlencode($rawToken));
    $safeName = e($fullName);
    $html = '<!doctype html><html><body style="margin:0;background:#f5f7fa;font-family:Arial,sans-serif;color:#14233a">'
        . '<div style="max-width:600px;margin:30px auto;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:32px">'
        . '<h2 style="margin-top:0">Verify your Mech Spec LMS email</h2>'
        . '<p>Hello ' . $safeName . ',</p>'
        . '<p>Thanks for creating your Mech Spec LMS account. Click the button below to verify your email address.</p>'
        . ($role === 'instructor'
            ? '<p>As an instructor, your account also needs a quick review by our team before you can publish courses. You can log in and start building drafts right away — publishing unlocks once you\'re approved.</p>'
            : '')
        . '<p><a href="' . e($verifyLink) . '" style="display:inline-block;background:#f7c331;color:#07111f;padding:13px 22px;border-radius:8px;text-decoration:none;font-weight:700">Verify my email</a></p>'
        . '<p style="color:#64748b"><strong>This link expires in 15 minutes</strong> and can only be used once.</p>'
        . '<p style="color:#64748b">If you did not create this account, you can safely ignore this email.</p>'
        . '</div></body></html>';

    if (!send_html_email($email, 'Verify your Mech Spec LMS email', $html, 'Verify your Mech Spec LMS email: ' . $verifyLink)) {
        $pdo->rollBack();
        log_security_event($pdo, 'email_delivery_failed', null, 'Verification email could not be sent during signup');
        fail_signup('We could not send the verification email. Please check the email address and try again.');
    }

    log_security_event($pdo, 'signup', $userId, 'Account created; verification email sent');
    $pdo->commit();

    unset($_SESSION['signup_old']);
    $_SESSION['pending_verification_email'] = $email;
    $_SESSION['pending_verification_name'] = $fullName;
    $_SESSION['verification_last_sent'] = time();

    header('Location: ../VerificationPending.php');
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Signup failed: ' . $e->getMessage());
    fail_signup('Something went wrong creating your account. Please try again.');
}
