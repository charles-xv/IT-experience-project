<?php
session_start();

$error  = $_SESSION['reset_error'] ?? '';
$notice = $_SESSION['reset_notice'] ?? '';
unset($_SESSION['reset_error'], $_SESSION['reset_notice']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script>
    (function(){try{var t=localStorage.getItem('mechspec-theme');document.documentElement.setAttribute('data-theme',t==='light'?'light':'dark');}catch(e){document.documentElement.setAttribute('data-theme','dark');}})();
  </script>
  <title>Reset Password - Mech Spec LMS</title>
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
      <p>We'll send you a link to set a new password.</p>
    </aside>

    <div class="auth-form-panel">
      <a href="LoginPage.php" class="auth-back">&larr; Back to login</a>
      <h2>Forgot password?</h2>
      <p class="auth-subtitle">Enter your email and we'll send a secure reset link. The link expires in <strong>15 minutes</strong>.</p>

      <?php if ($error): ?>
        <div class="form-message error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <?php if ($notice): ?>
        <div class="form-message success"><?= htmlspecialchars($notice) ?></div>
      <?php endif; ?>



      <form method="POST" action="php/RequestReset.php">
        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" placeholder="you@example.com" required>
        </div>
        <button type="submit" class="auth-btn">Send reset link</button>
      </form>

      <div class="auth-switch">
        Remembered it? <a href="LoginPage.php">Log in</a>
      </div>
    </div>
  </div>

  <script src="LoadingBar.js"></script>
</body>
</html>