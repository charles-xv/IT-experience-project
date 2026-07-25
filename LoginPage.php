<?php
// Shows the login form and any message Login.php left in the session
// (a wrong-password error, or a rate-limit lockout notice).
session_start();

$error   = $_SESSION['login_error']   ?? '';
$success = $_SESSION['login_success'] ?? '';
unset($_SESSION['login_error'], $_SESSION['login_success']);

$old_email = $_SESSION['login_old']['email'] ?? '';
unset($_SESSION['login_old']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Log In - Mech Spec LMS</title>
  <link rel="stylesheet" href="Index.css">
  <link rel="stylesheet" href="LoginPage.css">
</head>
<body>
  <div class="auth-container">
    <h2>Log In</h2>

    <?php if ($error): ?>
      <div class="form-message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="form-message success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" action="php/Login.php">
      <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" placeholder="you@example.com"
               value="<?= htmlspecialchars($old_email) ?>" required>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="••••••••" required>
      </div>

      <button type="submit">Log In</button>
    </form>

    <div class="switch-link">
      <a href="Index.html">← Back to Home</a><br><br>
      Don't have an account? <a href="SignupPage.php">Sign up</a>
    </div>
  </div>
</body>
</html>
