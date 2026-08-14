<?php
session_start();
require_once __DIR__ . '/php/GoogleAuth.php';

$error   = $_SESSION['signup_error']   ?? '';
$success = $_SESSION['signup_success'] ?? '';
unset($_SESSION['signup_error'], $_SESSION['signup_success']);

$old_name  = $_SESSION['signup_old']['full_name'] ?? '';
$old_email = $_SESSION['signup_old']['email']     ?? '';
$old_role  = $_SESSION['signup_old']['role']      ?? 'student';
unset($_SESSION['signup_old']);
$googleClientId = google_client_id();
$googleLoginUri = app_url('php/GoogleLogin.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script>
    (function(){try{var t=localStorage.getItem('mechspec-theme');document.documentElement.setAttribute('data-theme',t==='light'?'light':'dark');}catch(e){document.documentElement.setAttribute('data-theme','dark');}})();
  </script>
  <title>Create Account - Mech Spec LMS</title>
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
      <p>Start learning in less than a minute. Free to join, no card required.</p>
    </aside>

    <div class="auth-form-panel">
      <a href="index.php" class="auth-back">← Back to Home</a>
      <h2>Create your account</h2>
      <p class="auth-subtitle">Join the secure learning platform</p>

      <?php if ($error): ?>
        <div class="form-message error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="form-message success"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>

      <form method="POST" action="php/Signup.php">
        <div class="role-toggle">
          <div class="role-option" data-role="student"><span class="role-icon"><?= ui_icon('graduation') ?></span> I'm a Student</div>
          <div class="role-option" data-role="instructor"><span class="role-icon"><?= ui_icon('briefcase') ?></span>I'm an Instructor</div>
        </div>
        <input type="hidden" name="role" id="roleInput" value="<?= htmlspecialchars($old_role) ?>">

        <div class="form-group">
          <label for="fullName">Full Name</label>
          <input type="text" id="fullName" name="full_name" placeholder="Charles Frank"
                 value="<?= htmlspecialchars($old_name) ?>" required>
        </div>

        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" placeholder="you@example.com"
                 value="<?= htmlspecialchars($old_email) ?>" required>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <div class="password-wrap">
            <input type="password" id="password" name="password" placeholder="At least 8 characters" required>
            <button type="button" class="password-toggle" id="passwordToggle" aria-label="Show password">
              <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </div>
        </div>

        <button type="submit" class="auth-btn">Create Account</button>
      </form>

      <?php if ($googleClientId !== '' && !str_contains($googleClientId, 'YOUR_GOOGLE')): ?>
        <div class="google-divider"><span>or</span></div>
        <div class="google-auth-block" aria-label="Google account creation">
          <div class="google-one-tap" id="g_id_onload"
               data-client_id="<?= htmlspecialchars($googleClientId, ENT_QUOTES) ?>"
               data-context="signup"
               data-ux_mode="redirect"
               data-login_uri="<?= htmlspecialchars($googleLoginUri, ENT_QUOTES) ?>"
               data-auto_prompt="false"></div>
          <div class="g_id_signin"
               data-type="standard"
               data-shape="pill"
               data-theme="filled_black"
               data-text="signup_with"
               data-size="large"
               data-logo_alignment="left"></div>
        </div>
      <?php endif; ?>

      <div class="auth-switch">
        Already have an account? <a href="LoginPage.php">Log in</a>
      </div>
    </div>
  </div>

  <?php if ($googleClientId !== '' && !str_contains($googleClientId, 'YOUR_GOOGLE')): ?><script src="https://accounts.google.com/gsi/client" async defer></script><?php endif; ?>
  <script src="AuthPage.js"></script>
  <script src="LoadingBar.js"></script>
</body>
</html>