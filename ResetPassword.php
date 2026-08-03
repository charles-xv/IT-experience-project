<?php
session_start();

$token = $_GET['token'] ?? '';
$error = $_SESSION['reset_error'] ?? '';
unset($_SESSION['reset_error']);

// No token means the link was mistyped or the page was opened directly.
if ($token === '') {
    $_SESSION['login_error'] = 'That reset link is not valid.';
    header('Location: LoginPage.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Set New Password - Mech Spec LMS</title>
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
      <p>Choose a new password for your account.</p>
    </aside>

    <div class="auth-form-panel">
      <h2>Set a new password</h2>
      <p class="auth-subtitle">This link can only be used once</p>

      <?php if ($error): ?>
        <div class="form-message error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="php/PerformReset.php">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <div class="form-group">
          <label for="new_password">New Password</label>
          <input type="password" id="new_password" name="new_password"
                 minlength="8" placeholder="At least 8 characters" required>
        </div>

        <div class="form-group">
          <label for="confirm_password">Confirm New Password</label>
          <input type="password" id="confirm_password" name="confirm_password"
                 minlength="8" required>
        </div>

        <button type="submit" class="auth-btn">Reset password</button>
      </form>

      <div class="auth-switch">
        <a href="LoginPage.php">Back to login</a>
      </div>
    </div>
  </div>

  <script src="LoadingBar.js"></script>
</body>
</html>