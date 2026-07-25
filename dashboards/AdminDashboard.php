<?php
require_once __DIR__ . '/../php/SessionGuard.php';
require_role('admin');
$name = htmlspecialchars($_SESSION['full_name'] ?? 'Admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - Mech Spec LMS</title>
  <link rel="stylesheet" href="../Index.css">
  <link rel="stylesheet" href="Dashboard.css">
</head>
<body class="dash-body">
  <header class="dash-header">
    <div class="dash-brand">
      <span class="brand-mark">M</span>
      <span>Mech Spec <span class="dash-gold">LMS</span></span>
    </div>
    <div class="dash-user">
      <span class="dash-role-pill dash-role-admin">Admin</span>
      <span class="dash-name"><?= $name ?></span>
      <a href="../php/Logout.php" class="dash-logout">Log out</a>
    </div>
  </header>

  <main class="dash-main">
    <h1>Welcome, <?= $name ?></h1>
    <p class="dash-sub">You are logged in as a <strong>Admin</strong>.</p>

    <div class="dash-cards">
      <div class="dash-card"><h3>User Management</h3><p>View all users and promote roles.</p></div>
      <div class="dash-card"><h3>Security Logs</h3><p>Review login attempts and lockouts.</p></div>
      <div class="dash-card"><h3>Platform Settings</h3><p>Configure the learning platform.</p></div>
    </div>
  </main>

  <div class="logout-modal" id="logoutModal">
    <div class="logout-card">
      <h3>Log out?</h3>
      <p>You'll need to sign in again to get back into your dashboard.</p>
      <div class="logout-actions">
        <button class="logout-cancel" id="logoutCancel">Cancel</button>
        <button class="logout-confirm" id="logoutConfirm">Log out</button>
      </div>
    </div>
  </div>

  <script src="Dashboard.js"></script>
</body>
</html>
