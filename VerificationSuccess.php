<?php
session_start();
if (empty($_SESSION['user_id']) || empty($_SESSION['verification_success'])) {
    header('Location: LoginPage.php');
    exit;
}
unset($_SESSION['verification_success']);
$role = $_SESSION['role'] ?? 'student';
$destinations = [
    'admin' => 'dashboards/AdminDashboard.php',
    'instructor' => 'dashboards/InstructorDashboard.php',
    'student' => 'dashboards/StudentDashboard.php',
];
$destination = $destinations[$role] ?? 'dashboards/StudentDashboard.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="refresh" content="3;url=<?= htmlspecialchars($destination, ENT_QUOTES, 'UTF-8') ?>">
  <script>(function(){try{var t=localStorage.getItem('mechspec-theme');document.documentElement.setAttribute('data-theme',t==='light'?'light':'dark');}catch(e){document.documentElement.setAttribute('data-theme','dark');}})();</script>
  <title>Email Verified - Mech Spec LMS</title>
  <link rel="stylesheet" href="Index.css">
  <link rel="stylesheet" href="AuthPage.css">
</head>
<body>
  <main class="auth-card verification-card">
    <div class="auth-form-panel verification-panel">
      <div class="verification-icon" aria-hidden="true">✓</div>
      <h2>Email verified successfully</h2>
      <p class="auth-subtitle">Your account is verified and you are now signed in.</p>
      <p class="verification-copy">Taking you to your dashboard...</p>
      <a class="auth-btn verification-link" href="<?= htmlspecialchars($destination, ENT_QUOTES, 'UTF-8') ?>">Continue to dashboard</a>
    </div>
  </main>
</body>
</html>
