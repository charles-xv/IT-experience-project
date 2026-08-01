<?php
require_once __DIR__ . '/../php/SessionGuard.php';
require_once __DIR__ . '/../php/Database.php';
require_once __DIR__ . '/../php/Helpers.php';
require_role('admin');

log_page_visit($pdo, 'AdminDashboard');

$name  = $_SESSION['full_name'] ?? 'Admin';
$email = $_SESSION['email'] ?? '';
$adminId = (int) $_SESSION['user_id'];

$notice      = $_SESSION['admin_success'] ?? '';
$noticeError = $_SESSION['admin_error'] ?? '';
unset($_SESSION['admin_success'], $_SESSION['admin_error']);

// Which panel is showing. Kept in the URL so a refresh stays put.
$tab = $_GET['tab'] ?? 'overview';
if (!in_array($tab, ['overview', 'users', 'courses', 'visitors', 'security'], true)) {
    $tab = 'overview';
}

// ---------------------------------------------------------------------
//  Platform-wide counts for the overview cards
// ---------------------------------------------------------------------
$counts = $pdo->query(
    'SELECT
        (SELECT COUNT(*) FROM Users WHERE role = "student")      AS students,
        (SELECT COUNT(*) FROM Users WHERE role = "instructor")   AS instructors,
        (SELECT COUNT(*) FROM Users WHERE status = "suspended")  AS suspended,
        (SELECT COUNT(*) FROM Courses)                           AS courses,
        (SELECT COUNT(*) FROM Enrollments)                       AS enrollments,
        (SELECT COUNT(DISTINCT ip_address) FROM PageVisits)      AS unique_visitors'
)->fetch();

// Data for whichever tab is open. Loaded per-tab so the page stays quick.
$users = $coursesList = $visitors = $securityLog = [];

if ($tab === 'users') {
    $users = $pdo->query(
        'SELECT user_id, full_name, email, role, status, last_login, created_at
         FROM Users
         ORDER BY created_at DESC'
    )->fetchAll();
}

if ($tab === 'courses') {
    $coursesList = $pdo->query(
        'SELECT c.course_id, c.title, c.category, c.status, c.created_at,
                u.full_name AS instructor_name,
                (SELECT COUNT(*) FROM Enrollments e WHERE e.course_id = c.course_id) AS student_count
         FROM Courses c
         JOIN Users u ON u.user_id = c.instructor_id
         ORDER BY c.created_at DESC'
    )->fetchAll();
}

if ($tab === 'visitors') {
    // Grouped by IP so one row is one visitor, not one row per page load.
    $visitors = $pdo->query(
        'SELECT pv.ip_address,
                COUNT(*)            AS visit_count,
                MAX(pv.visited_at)  AS last_seen,
                MAX(pv.page)        AS last_page,
                MAX(u.full_name)    AS known_user
         FROM PageVisits pv
         LEFT JOIN Users u ON u.user_id = pv.user_id
         GROUP BY pv.ip_address
         ORDER BY last_seen DESC
         LIMIT 100'
    )->fetchAll();
}

if ($tab === 'security') {
    $securityLog = $pdo->query(
        'SELECT s.log_id, s.event_type, s.ip_address, s.details, s.created_at,
                u.full_name AS user_name
         FROM SecurityLogs s
         LEFT JOIN Users u ON u.user_id = s.user_id
         ORDER BY s.created_at DESC
         LIMIT 100'
    )->fetchAll();
}
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
<body>
  <div class="app-layout">

    <aside class="sidebar">
      <div class="sidebar-header">
        <span class="brand-mark">M</span>
        <span>Mech Spec <span class="dash-gold">LMS</span></span>
      </div>
      <nav class="sidebar-nav">
        <a href="?tab=overview" class="nav-item <?= $tab === 'overview' ? 'active' : '' ?>">📊 Overview</a>
        <a href="?tab=users"    class="nav-item <?= $tab === 'users' ? 'active' : '' ?>">👥 Users</a>
        <a href="?tab=courses"  class="nav-item <?= $tab === 'courses' ? 'active' : '' ?>">📁 Courses</a>
        <a href="?tab=visitors" class="nav-item <?= $tab === 'visitors' ? 'active' : '' ?>">🌐 Visitor IPs</a>
        <a href="?tab=security" class="nav-item <?= $tab === 'security' ? 'active' : '' ?>">🔐 Security Log</a>
      </nav>
      <div class="sidebar-footer">
        <a href="#" class="logout-btn" id="logoutTrigger">🚪 Log Out</a>
      </div>
    </aside>

    <main class="main-wrapper">

      <header class="top-header">
        <div class="breadcrumbs">
          Admin <span>/ <?= ucfirst($tab === 'visitors' ? 'Visitor IPs' : $tab) ?></span>
        </div>
        <div class="header-actions">
          <span class="dash-role-pill dash-role-admin">Admin Mode</span>
          <div class="user-profile">
            <div class="user-info">
              <span class="user-name"><?= e($name) ?></span>
              <span class="user-email"><?= e($email) ?></span>
            </div>
            <div class="avatar avatar-admin"><?= e(strtoupper(substr($name, 0, 1))) ?></div>
          </div>
        </div>
      </header>

      <div class="dashboard-content">

        <?php if ($notice): ?>
          <div class="form-notice success"><?= e($notice) ?></div>
        <?php endif; ?>
        <?php if ($noticeError): ?>
          <div class="form-notice error"><?= e($noticeError) ?></div>
        <?php endif; ?>

        <!-- ================= OVERVIEW ================= -->
        <?php if ($tab === 'overview'): ?>
          <h1 class="page-title">Platform Overview</h1>
          <p class="page-sub">Live figures across the whole platform.</p>

          <div class="metrics-row">
            <div class="metric-card cyan">
              <span class="metric-label">Students</span>
              <span class="metric-value"><?= (int) $counts['students'] ?></span>
            </div>
            <div class="metric-card gold">
              <span class="metric-label">Instructors</span>
              <span class="metric-value"><?= (int) $counts['instructors'] ?></span>
            </div>
            <div class="metric-card emerald">
              <span class="metric-label">Courses</span>
              <span class="metric-value"><?= (int) $counts['courses'] ?></span>
            </div>
            <div class="metric-card cyan">
              <span class="metric-label">Enrolments</span>
              <span class="metric-value"><?= (int) $counts['enrollments'] ?></span>
            </div>
            <div class="metric-card gold">
              <span class="metric-label">Unique Visitor IPs</span>
              <span class="metric-value"><?= (int) $counts['unique_visitors'] ?></span>
            </div>
            <div class="metric-card emerald">
              <span class="metric-label">Suspended Accounts</span>
              <span class="metric-value"><?= (int) $counts['suspended'] ?></span>
            </div>
          </div>

        <!-- ================= USERS ================= -->
        <?php elseif ($tab === 'users'): ?>
          <h1 class="page-title">User Management</h1>
          <p class="page-sub">Suspend an account to block its login, or delete it permanently.</p>

          <div class="table-widget">
            <div class="table-header"><h3>All Users</h3></div>
            <?php if (empty($users)): ?>
              <div class="empty-state">
                <span class="empty-icon">👥</span>
                <h3>No users yet</h3>
                <p>Accounts will appear here as people sign up.</p>
              </div>
            <?php else: ?>
              <table>
                <thead>
                  <tr>
                    <th>Name</th><th>Email</th><th>Role</th><th>Status</th>
                    <th>Last Login</th><th>Joined</th><th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($users as $u): ?>
                    <tr>
                      <td><strong><?= e($u['full_name']) ?></strong></td>
                      <td><?= e($u['email']) ?></td>
                      <td><span class="status-badge status-<?= e($u['role']) ?>"><?= ucfirst(e($u['role'])) ?></span></td>
                      <td><span class="status-badge status-<?= e($u['status']) ?>"><?= ucfirst(e($u['status'])) ?></span></td>
                      <td><?= $u['last_login'] ? e(date('d M Y, H:i', strtotime($u['last_login']))) : 'Never' ?></td>
                      <td><?= e(date('d M Y', strtotime($u['created_at']))) ?></td>
                      <td>
                        <?php if ((int) $u['user_id'] === $adminId): ?>
                          <span class="row-note">You</span>
                        <?php else: ?>
                          <form method="POST" action="../php/AdminAction.php" class="row-form">
                            <input type="hidden" name="user_id" value="<?= (int) $u['user_id'] ?>">
                            <?php if ($u['status'] === 'active'): ?>
                              <button type="submit" name="action" value="suspend" class="row-btn warn">Suspend</button>
                            <?php else: ?>
                              <button type="submit" name="action" value="reinstate" class="row-btn ok">Reinstate</button>
                            <?php endif; ?>
                            <button type="submit" name="action" value="delete" class="row-btn danger"
                                    data-confirm="Permanently delete <?= e($u['full_name']) ?>? This cannot be undone.">Delete</button>
                          </form>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>
          </div>

        <!-- ================= COURSES ================= -->
        <?php elseif ($tab === 'courses'): ?>
          <h1 class="page-title">All Courses</h1>
          <p class="page-sub">Every course on the platform and who owns it.</p>

          <div class="table-widget">
            <div class="table-header"><h3>Courses</h3></div>
            <?php if (empty($coursesList)): ?>
              <div class="empty-state">
                <span class="empty-icon">📁</span>
                <h3>No courses yet</h3>
                <p>Courses appear here once an instructor creates one.</p>
              </div>
            <?php else: ?>
              <table>
                <thead>
                  <tr><th>Title</th><th>Instructor</th><th>Category</th><th>Status</th><th>Students</th><th>Created</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($coursesList as $c): ?>
                    <tr>
                      <td><strong><?= e($c['title']) ?></strong></td>
                      <td><?= e($c['instructor_name']) ?></td>
                      <td><?= $c['category'] ? e($c['category']) : '—' ?></td>
                      <td><span class="status-badge status-<?= e($c['status']) ?>"><?= ucfirst(e($c['status'])) ?></span></td>
                      <td><?= (int) $c['student_count'] ?></td>
                      <td><?= e(date('d M Y', strtotime($c['created_at']))) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>
          </div>

        <!-- ================= VISITOR IPs ================= -->
        <?php elseif ($tab === 'visitors'): ?>
          <h1 class="page-title">Visitor IP Addresses</h1>
          <p class="page-sub">Grouped by address — one row per visitor, not per page load. Most recent first, latest 100.</p>

          <div class="table-widget">
            <div class="table-header"><h3>Visitors</h3></div>
            <?php if (empty($visitors)): ?>
              <div class="empty-state">
                <span class="empty-icon">🌐</span>
                <h3>No visits recorded yet</h3>
                <p>Page visits are logged as people browse the site.</p>
              </div>
            <?php else: ?>
              <table>
                <thead>
                  <tr><th>IP Address</th><th>Identified As</th><th>Page Views</th><th>Last Page</th><th>Last Seen</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($visitors as $v): ?>
                    <tr>
                      <td>
                        <code><?= e($v['ip_address']) ?></code>
                        <?php if (in_array($v['ip_address'], ['::1', '127.0.0.1'], true)): ?>
                          <span class="row-note">this machine</span>
                        <?php endif; ?>
                      </td>
                      <td><?= $v['known_user'] ? e($v['known_user']) : '<span class="row-note">Anonymous</span>' ?></td>
                      <td><?= (int) $v['visit_count'] ?></td>
                      <td><?= e($v['last_page']) ?></td>
                      <td><?= e(date('d M Y, H:i', strtotime($v['last_seen']))) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>
          </div>

        <!-- ================= SECURITY LOG ================= -->
        <?php else: ?>
          <h1 class="page-title">Security Log</h1>
          <p class="page-sub">Logins, lockouts, suspensions and access denials. Latest 100 events.</p>

          <div class="table-widget">
            <div class="table-header"><h3>Events</h3></div>
            <?php if (empty($securityLog)): ?>
              <div class="empty-state">
                <span class="empty-icon">🔐</span>
                <h3>No events recorded yet</h3>
                <p>Security events appear here as people log in and use the platform.</p>
              </div>
            <?php else: ?>
              <table>
                <thead>
                  <tr><th>Event</th><th>User</th><th>IP Address</th><th>Details</th><th>When</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($securityLog as $log): ?>
                    <tr>
                      <td><span class="status-badge event-<?= e($log['event_type']) ?>"><?= e(str_replace('_', ' ', $log['event_type'])) ?></span></td>
                      <td><?= $log['user_name'] ? e($log['user_name']) : '<span class="row-note">—</span>' ?></td>
                      <td>
                        <code><?= e($log['ip_address'] ?? '—') ?></code>
                        <?php if (in_array($log['ip_address'], ['::1', '127.0.0.1'], true)): ?>
                          <span class="row-note">this machine</span>
                        <?php endif; ?>
                      </td>
                      <td><?= e($log['details'] ?? '') ?></td>
                      <td><?= e(date('d M Y, H:i', strtotime($log['created_at']))) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>
          </div>
        <?php endif; ?>

      </div>
    </main>
  </div>

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