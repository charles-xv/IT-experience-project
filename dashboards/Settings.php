<?php
// ============================================================
//  Settings — shared by student, instructor and admin.
//  No require_role() here on purpose: SessionGuard has already
//  confirmed the person is logged in, and all three roles use
//  this same page. The sidebar is built from their role below.
// ============================================================

require_once __DIR__ . '/../php/SessionGuard.php';
require_once __DIR__ . '/../php/Database.php';
require_once __DIR__ . '/../php/Helpers.php';

log_page_visit($pdo, 'Settings');

$userId = (int) $_SESSION['user_id'];
$name   = $_SESSION['full_name'] ?? '';
$email  = $_SESSION['email'] ?? '';
$role   = $_SESSION['role'] ?? 'student';

$notice      = $_SESSION['settings_success'] ?? '';
$noticeError = $_SESSION['settings_error'] ?? '';
unset($_SESSION['settings_success'], $_SESSION['settings_error']);

// Read from the database rather than the session, so the page reflects
// reality even if the session is holding stale values.
$stmt = $pdo->prepare('SELECT full_name, email, created_at, last_login FROM Users WHERE user_id = ?');
$stmt->execute([$userId]);
$account = $stmt->fetch();

// Fall back to the session if the row somehow can't be read.
$displayName  = $account['full_name'] ?? $name;
$displayEmail = $account['email'] ?? $email;

// Each role gets its own sidebar. Built here rather than duplicated into
// three near-identical settings pages.
// These must mirror the sidebars on the real dashboards exactly. This page
// builds its own nav rather than sharing theirs, which is why entries added
// to a dashboard have to be added here too — Cart and Purchases were both
// missed that way. Anything added to a dashboard sidebar belongs here as well.
$navByRole = [
    'student' => [
        ['StudentDashboard.php', 'book', 'My Learning'],
        ['BrowseCourses.php',    'search', 'Browse Courses'],
        ['Cart.php',             'cart', 'Cart'],
        ['Certificates.php',     'award', 'Certificates'],
    ],
    'instructor' => [
        ['InstructorDashboard.php', 'chart', 'Overview'],
        ['CreateCourse.php',        'plus', 'Create Course'],
        ['Students.php',            'users', 'Students'],
    ],
    'admin' => [
        ['AdminDashboard.php?tab=overview',  'chart', 'Overview'],
        ['AdminDashboard.php?tab=users',     'users', 'Users'],
        ['AdminDashboard.php?tab=courses',   'folder', 'Courses'],
        ['AdminDashboard.php?tab=purchases', 'card', 'Purchases'],
        ['AdminDashboard.php?tab=visitors',  'globe', 'Visitor IPs'],
        ['AdminDashboard.php?tab=security',  'shield', 'Security Log'],
    ],
];
$nav = $navByRole[$role] ?? $navByRole['student'];

$avatarClass = 'avatar';
if ($role === 'instructor') $avatarClass = 'avatar avatar-instructor';
if ($role === 'admin')      $avatarClass = 'avatar avatar-admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Settings - Mech Spec LMS</title>
  <link rel="stylesheet" href="../Index.css">
  <link rel="stylesheet" href="Dashboard.css">
  <link rel="stylesheet" href="../LoadingBar.css">
</head>
<body>
  <div class="app-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <span class="brand-mark">M</span>
        <span>Mech Spec <span class="dash-gold">LMS</span></span>
      </div>
      <nav class="sidebar-nav">
        <?php foreach ($nav as $item): ?>
          <a href="<?= e($item[0]) ?>" class="nav-item"><?= ui_icon($item[1]) ?><span class="nav-label"><?= e($item[2]) ?></span></a>
        <?php endforeach; ?>
        <a href="Settings.php" class="nav-item active"><?= ui_icon('settings') ?><span class="nav-label">Settings</span></a>
      </nav>
      <div class="sidebar-footer">
        <a href="#" class="logout-btn" id="logoutTrigger"><?= ui_icon('logout') ?><span class="logout-label">Log Out</span></a>
      </div>
    </aside>

    <!-- MAIN -->
    <main class="main-wrapper">

      <header class="top-header">
        <div class="breadcrumbs">
          Dashboard <span>/ Settings</span>
        </div>
        <div class="header-actions">
          <button type="button" class="header-logout-btn" id="logoutTriggerMobile" aria-label="Log out" title="Log out">
            <?= ui_icon('logout') ?>
          </button>
          <span class="dash-role-pill dash-role-<?= e($role) ?>"><?= ucfirst(e($role)) ?> Mode</span>
          <div class="user-profile">
            <div class="user-info">
              <span class="user-name"><?= e($displayName) ?></span>
              <span class="user-email"><?= e($displayEmail) ?></span>
            </div>
            <div class="<?= $avatarClass ?>"><?= e(strtoupper(substr($displayName, 0, 1))) ?></div>
          </div>
        </div>
      </header>

      <div class="dashboard-content">
        <h1 class="page-title">Settings</h1>
        <p class="page-sub">Your account details and password.</p>

        <?php if ($notice): ?>
          <div class="form-notice success"><?= e($notice) ?></div>
        <?php endif; ?>
        <?php if ($noticeError): ?>
          <div class="form-notice error"><?= e($noticeError) ?></div>
        <?php endif; ?>

        <div class="settings-grid">

          <!-- LEFT: editable forms -->
          <div class="settings-main">

            <!-- ACCOUNT DETAILS -->
            <div class="form-panel">
              <h2 class="section-heading">Account</h2>

              <form method="POST" action="../php/UpdateProfile.php">

                <div class="form-row">
                  <label for="full_name">Display Name</label>
                  <input type="text" id="full_name" name="full_name"
                         value="<?= e($displayName) ?>" maxlength="120" required>
                </div>

                <div class="form-row">
                  <label for="new_email">Email Address</label>
                  <input type="email" id="new_email" name="email"
                         value="<?= e($displayEmail) ?>" maxlength="190" required>
                </div>

                <div class="form-row">
                  <label for="confirm_pw">Confirm with your password</label>
                  <input type="password" id="confirm_pw" name="password"
                         placeholder="Required to save changes" required>
                  <span class="form-hint">
                    Your password is required because changing the email changes how
                    you sign in. Without it, an unattended logged-in browser could move
                    the account to a different address.
                  </span>
                </div>

                <div class="form-actions">
                  <button type="submit" class="btn-submit">Save Changes</button>
                </div>
              </form>

              <p class="form-hint">
                Your role is set by an administrator and can't be changed here.
              </p>
            </div>

            <!-- PASSWORD -->
            <div class="form-panel">
              <h2 class="section-heading">Change Password</h2>

              <form method="POST" action="../php/UpdatePassword.php">

                <div class="form-row">
                  <label for="current_password">Current Password</label>
                  <input type="password" id="current_password" name="current_password" required>
                </div>

                <div class="form-row">
                  <label for="new_password">New Password</label>
                  <input type="password" id="new_password" name="new_password"
                         minlength="8" placeholder="At least 8 characters" required>
                </div>

                <div class="form-row">
                  <label for="confirm_password">Confirm New Password</label>
                  <input type="password" id="confirm_password" name="confirm_password"
                         minlength="8" required>
                  <span class="form-hint">
                    Changing your password also clears any failed login attempts on
                    the account and refreshes your session.
                  </span>
                </div>

                <div class="form-actions">
                  <button type="submit" class="btn-submit">Update Password</button>
                </div>
              </form>
            </div>

          </div>

          <!-- RIGHT: profile summary -->
          <aside class="settings-profile">
            <div class="<?= $avatarClass ?> settings-profile-avatar"><?= e(strtoupper(substr($displayName, 0, 1))) ?></div>
            <h3 class="settings-profile-name"><?= e($displayName) ?></h3>
            <span class="settings-profile-email"><?= ui_icon('card') ?><?= e($displayEmail) ?></span>

            <a href="#" class="settings-logout-btn" id="logoutTriggerProfile"><?= ui_icon('logout') ?> Log Out</a>

            <div class="settings-profile-stats">
              <div class="settings-profile-stat">
                <strong><?= ucfirst(e($role)) ?></strong>
                <span>Role</span>
              </div>
              <div class="settings-profile-stat">
                <strong><?= e(date('d M Y', strtotime($account['created_at']))) ?></strong>
                <span>Joined</span>
              </div>
            </div>

            <dl class="detail-list detail-readonly">
              <dt>Last login</dt>
              <dd>
                <?= $account['last_login']
                      ? e(date('d M Y, H:i', strtotime($account['last_login'])))
                      : 'This is your first session' ?>
              </dd>
            </dl>
          </aside>

        </div>
      </div>
    </main>
  </div>

  <!-- LOGOUT MODAL -->
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

  <script src="../LoadingBar.js"></script>
  <script src="Dashboard.js"></script>
</body>
</html> 