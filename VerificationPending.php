<?php
session_start();
$email = (string) ($_SESSION['pending_verification_email'] ?? '');
$name  = (string) ($_SESSION['pending_verification_name'] ?? '');
if ($email === '') {
    header('Location: SignupPage.php');
    exit;
}
$error = $_SESSION['verification_error'] ?? '';
$notice = $_SESSION['verification_notice'] ?? '';
unset($_SESSION['verification_error'], $_SESSION['verification_notice']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script>(function(){try{var t=localStorage.getItem('mechspec-theme');document.documentElement.setAttribute('data-theme',t==='light'?'light':'dark');}catch(e){document.documentElement.setAttribute('data-theme','dark');}})();</script>
  <title>Check Your Email - Mech Spec LMS</title>
  <link rel="stylesheet" href="Index.css">
  <link rel="stylesheet" href="AuthPage.css">
  <link rel="stylesheet" href="LoadingBar.css">
</head>
<body>
  <main class="auth-card verification-card">
    <div class="auth-form-panel verification-panel">
      <div class="verification-icon" aria-hidden="true">✓</div>
      <h2>Check your email</h2>
      <p class="auth-subtitle">We've sent a verification link to</p>
      <p class="verification-email"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></p>
      <p class="verification-copy">Click the link in the email to verify your account. The link expires in <strong>15 minutes</strong>.</p>

      <?php if ($error): ?><div class="form-message error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
      <?php if ($notice): ?><div class="form-message success"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

      <div class="verification-spam">Can't find the email? Check your <strong>Spam</strong> or <strong>Junk</strong> folder.</div>

      <form method="POST" action="php/ResendVerification.php" class="verification-resend-form">
        <input type="hidden" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit" class="auth-btn">Resend verification email</button>
      </form>

      <div class="auth-switch"><a href="LoginPage.php">Back to login</a></div>
    </div>
  </main>
  <script src="LoadingBar.js"></script>
</body>
</html>
