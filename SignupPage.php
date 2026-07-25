<?php
// Shows the signup form. When Signup.php redirects back here with an error or a
// success flag, this page reads it from the session and shows the message.
session_start();

$error   = $_SESSION['signup_error']   ?? '';
$success = $_SESSION['signup_success'] ?? '';
// Read once, then clear — so a refresh doesn't show a stale message.
unset($_SESSION['signup_error'], $_SESSION['signup_success']);

// If the submission failed, we repopulate the fields the user already typed
// (everything except the password) so they don't retype everything.
$old_name  = $_SESSION['signup_old']['full_name'] ?? '';
$old_email = $_SESSION['signup_old']['email']     ?? '';
$old_role  = $_SESSION['signup_old']['role']      ?? 'student';
unset($_SESSION['signup_old']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Account - Mech Spec LMS</title>
  <link rel="stylesheet" href="Index.css">
  <link rel="stylesheet" href="SignupPage.css">
</head>
<body>
  <div class="auth-container">
    <h2>Create Account</h2>

    <?php if ($error): ?>
      <div class="form-message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="form-message success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" action="php/Signup.php">
      <div class="form-group">
        <label for="fullName">Full Name</label>
        <input type="text" id="fullName" name="full_name" placeholder="John Doe"
               value="<?= htmlspecialchars($old_name) ?>" required>
      </div>

      <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" placeholder="you@example.com"
               value="<?= htmlspecialchars($old_email) ?>" required>
      </div>

      <div class="form-group">
        <label for="role">I am a</label>
        <select id="role" name="role" required>
          <option value="student"    <?= $old_role === 'student'    ? 'selected' : '' ?>>Student</option>
          <option value="instructor" <?= $old_role === 'instructor' ? 'selected' : '' ?>>Instructor</option>
        </select>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="At least 8 characters" required>
      </div>

      <button type="submit">Create Account</button>
    </form>

    <div class="switch-link">
      <a href="Index.html">← Back to Home</a><br><br>
      Already have an account? <a href="LoginPage.php">Log in</a>
    </div>
  </div>
</body>
</html>
