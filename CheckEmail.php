<?php
// Shown right after signup, before the account is usable. Mirrors the
// "email a link" pattern used for password reset — the difference here is
// this screen also has to tell an instructor what happens next, since for
// them verifying is only step one.

session_start();

$email = $_SESSION['signup_email'] ?? '';
$role  = $_SESSION['signup_role'] ?? 'student';
// DEVELOPMENT ONLY — no mail server is configured, so the link is shown
// here instead of being emailed. A real deployment removes this block
// entirely; the token logic behind it is unaffected either way.
$devLink = $_SESSION['verify_dev_link'] ?? '';
unset($_SESSION['signup_email'], $_SESSION['signup_role'], $_SESSION['verify_dev_link']);

if ($email === '') {
    header('Location: LoginPage.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Check Your Email - Mech Spec LMS</title>
  <link rel="stylesheet" href="Index.css">
  <link rel="stylesheet" href="AuthPage.css">
  <link rel="stylesheet" href="LoadingBar.css">
</head>
<body>
  <div class="auth-card">
    <aside class="auth-aside">
      <div class="auth-brand">
        <span class="brand-mark">M</span>
        <span class="auth-brand-name">Mech Spec <span>LMS</span></span>
      </div>
      <p>One more step before you can sign in.</p>
    </aside>

    <div class="auth-form-panel">
      <h2>Check your email</h2>
      <p class="auth-subtitle">
        We've sent a verification link to <strong><?= htmlspecialchars($email) ?></strong>.
      </p>

      <?php if ($role === 'instructor'): ?>
        <div class="form-message dev-link" style="border-color: rgba(247,195,49,0.45); background: rgba(247,195,49,0.08);">
          <strong>One more step for instructors</strong>
          <span>
            After verifying, an administrator still needs to approve your instructor
            account before you can publish courses. You can log in as soon as you
            verify — course creation unlocks once you're approved.
          </span>
        </div>
      <?php endif; ?>

      <?php if ($devLink): ?>
        <div class="form-message dev-link">
          <strong>Development mode</strong>
          <span>No mail server is configured, so the link is shown here instead of being emailed:</span>
          <a href="<?= htmlspecialchars($devLink) ?>"><?= htmlspecialchars($devLink) ?></a>
        </div>
      <?php endif; ?>

      <div class="auth-switch">
        <a href="LoginPage.php">Back to login</a>
      </div>
    </div>
  </div>

  <script src="LoadingBar.js"></script>
</body>
</html>