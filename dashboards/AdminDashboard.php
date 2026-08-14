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
if (!in_array($tab, ['overview', 'users', 'pending', 'courses', 'purchases', 'visitors', 'security'], true)) {
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
        (SELECT COUNT(*) FROM Users WHERE role = "instructor" AND instructor_approval_status = "pending") AS pending_instructors,
        (SELECT COUNT(*) FROM Courses)                           AS courses,
        (SELECT COUNT(*) FROM Enrollments)                       AS enrollments,
        (SELECT COUNT(DISTINCT ip_address) FROM PageVisits)      AS unique_visitors,
        (SELECT COUNT(*) FROM Payments WHERE status = "successful") AS sales,
        (SELECT COALESCE(SUM(amount), 0) FROM Payments WHERE status = "successful") AS revenue,
        (SELECT COUNT(*) FROM Payments WHERE status = "failed")     AS failed_payments'
)->fetch();

// Data for whichever tab is open. Loaded per-tab so the page stays quick.
$users = $coursesList = $visitors = $securityLog = $purchases = $pendingInstructors = [];

if ($tab === 'users') {
    $users = $pdo->query(
        'SELECT user_id, full_name, email, role, status, last_login, created_at
         FROM Users
         ORDER BY created_at DESC'
    )->fetchAll();
}

if ($tab === 'pending') {
    $pendingInstructors = $pdo->query(
        'SELECT user_id, full_name, email, created_at
         FROM Users
         WHERE role = "instructor" AND instructor_approval_status = "pending"
         ORDER BY created_at ASC'
    )->fetchAll();
}

if ($tab === 'courses') {
    $coursesList = $pdo->query(
        'SELECT c.course_id, c.title, c.category, c.price, c.status, c.created_at,
                u.full_name AS instructor_name,
                (SELECT COUNT(*) FROM Enrollments e WHERE e.course_id = c.course_id) AS student_count
         FROM Courses c
         JOIN Users u ON u.user_id = c.instructor_id
         ORDER BY c.created_at DESC'
    )->fetchAll();
}

if ($tab === 'purchases') {
    // Every attempt, not just the successful ones. A payments view showing
    // only successes hides the failure rate, which is the number that
    // actually tells you whether checkout is working.
    $purchases = $pdo->query(
        'SELECT pay.order_reference, pay.amount, pay.status, pay.email,
                pay.card_last_four, pay.failure_reason, pay.created_at,
                u.full_name AS student_name,
                (SELECT COUNT(*) FROM Purchases pu WHERE pu.payment_id = pay.payment_id) AS items
         FROM Payments pay
         JOIN Users u ON u.user_id = pay.student_id
         ORDER BY pay.created_at DESC
         LIMIT 100'
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
  <link rel="stylesheet" href="../LoadingBar.css">
</head>
<body class="role-admin">
  <div class="app-layout">

    <aside class="sidebar">
      <div class="sidebar-header">
        <span class="brand-mark">M</span>
        <span>Mech Spec <span class="dash-gold">LMS</span></span>
      </div>
      <nav class="sidebar-nav">
        <a href="?tab=overview" class="nav-item <?= $tab === 'overview' ? 'active' : '' ?>"><?= ui_icon('chart') ?><span class="nav-label">Overview</span></a>
        <a href="?tab=users"    class="nav-item <?= $tab === 'users' ? 'active' : '' ?>"><?= ui_icon('users') ?><span class="nav-label">Users</span></a>
        <a href="?tab=pending"  class="nav-item <?= $tab === 'pending' ? 'active' : '' ?>"><?= ui_icon('shield') ?><span class="nav-label">Pending Instructors<?php if ((int) $counts['pending_instructors'] > 0): ?> (<?= (int) $counts['pending_instructors'] ?>)<?php endif; ?></span></a>
        <a href="?tab=courses"  class="nav-item <?= $tab === 'courses' ? 'active' : '' ?>"><?= ui_icon('folder') ?><span class="nav-label">Courses</span></a>
        <a href="?tab=purchases" class="nav-item <?= $tab === 'purchases' ? 'active' : '' ?>"><?= ui_icon('card') ?><span class="nav-label">Purchases</span></a>
        <a href="?tab=visitors" class="nav-item <?= $tab === 'visitors' ? 'active' : '' ?>"><?= ui_icon('globe') ?><span class="nav-label">Visitor IPs</span></a>
        <a href="?tab=security" class="nav-item <?= $tab === 'security' ? 'active' : '' ?>"><?= ui_icon('shield') ?><span class="nav-label">Security Log</span></a>
        <a href="Settings.php" class="nav-item"><?= ui_icon('settings') ?><span class="nav-label">Settings</span></a>
      </nav>
      <div class="sidebar-footer">
        <a href="#" class="logout-btn" id="logoutTrigger"><?= ui_icon('logout') ?><span class="logout-label">Log Out</span></a>
      </div>
    </aside>

    <main class="main-wrapper">

      <header class="top-header">
        <div class="breadcrumbs">
          Admin <span>/ <?= ucfirst($tab === 'visitors' ? 'Visitor IPs' : $tab) ?></span>
        </div>
        <div class="header-actions">
          <button type="button" class="header-logout-btn" id="logoutTriggerMobile" aria-label="Log out" title="Log out">
            <?= ui_icon('logout') ?>
          </button>
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
          <section class="role-hero">
            <div class="role-hero-copy">
              <div class="role-hero-kicker"><?= ui_icon('shield') ?> Administration & Control</div>
              <h1>Platform Overview</h1>
              <p>Monitor users, learning activity, commerce and security from one control centre.</p>
            </div>
            <div class="role-hero-action">
              <a href="?tab=security" class="btn-primary"><?= ui_icon('shield') ?> Security Log</a>
            </div>
          </section>

          <div class="quick-grid">
            <a class="quick-card" href="?tab=users">
              <span class="quick-icon"><?= ui_icon('users') ?></span>
              <span><strong>User Management</strong><span>Accounts, roles and status</span></span>
            </a>
            <a class="quick-card" href="?tab=pending">
              <span class="quick-icon"><?= ui_icon('shield') ?></span>
              <span><strong>Pending Instructors</strong><span><?= (int) $counts['pending_instructors'] ?> awaiting review</span></span>
            </a>
            <a class="quick-card" href="?tab=courses">
              <span class="quick-icon"><?= ui_icon('folder') ?></span>
              <span><strong>Course Oversight</strong><span>Review the course catalogue</span></span>
            </a>
            <a class="quick-card" href="?tab=purchases">
              <span class="quick-icon"><?= ui_icon('card') ?></span>
              <span><strong>Commerce</strong><span>Payments and purchase activity</span></span>
            </a>
          </div>

          <div class="metrics-row">
            <div class="metric-card cyan">
              <div class="metric-icon"><?= ui_icon('users') ?></div>
            <span class="metric-label">Students</span>
              <span class="metric-value"><?= (int) $counts['students'] ?></span>
            </div>
            <div class="metric-card gold">
              <div class="metric-icon"><?= ui_icon('users') ?></div>
            <span class="metric-label">Instructors</span>
              <span class="metric-value"><?= (int) $counts['instructors'] ?></span>
            </div>
            <div class="metric-card emerald">
              <div class="metric-icon"><?= ui_icon('book') ?></div>
            <span class="metric-label">Courses</span>
              <span class="metric-value"><?= (int) $counts['courses'] ?></span>
            </div>
            <div class="metric-card cyan">
              <div class="metric-icon"><?= ui_icon('users') ?></div>
            <span class="metric-label">Enrolments</span>
              <span class="metric-value"><?= (int) $counts['enrollments'] ?></span>
            </div>
            <div class="metric-card gold">
              <div class="metric-icon"><?= ui_icon('globe') ?></div>
            <span class="metric-label">Unique Visitor IPs</span>
              <span class="metric-value"><?= (int) $counts['unique_visitors'] ?></span>
            </div>
            <div class="metric-card emerald">
              <div class="metric-icon"><?= ui_icon('shield') ?></div>
            <span class="metric-label">Suspended Accounts</span>
              <span class="metric-value"><?= (int) $counts['suspended'] ?></span>
            </div>
            <div class="metric-card gold">
              <div class="metric-icon"><?= ui_icon('shield') ?></div>
            <span class="metric-label">Pending Instructors</span>
              <span class="metric-value"><?= (int) $counts['pending_instructors'] ?></span>
            </div>
            <div class="metric-card cyan">
              <span class="metric-label">Sales</span>
              <span class="metric-value"><?= (int) $counts['sales'] ?></span>
            </div>
            <div class="metric-card gold">
              <span class="metric-label">Revenue (simulated)</span>
              <span class="metric-value">$<?= number_format((float) $counts['revenue'], 2) ?></span>
            </div>
            <div class="metric-card emerald">
              <span class="metric-label">Failed Payments</span>
              <span class="metric-value"><?= (int) $counts['failed_payments'] ?></span>
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
                <span class="empty-icon"><?= ui_icon('users') ?></span>
                <h3>No users yet</h3>
                <p>Accounts will appear here as people sign up.</p>
              </div>
            <?php else: ?>
              <table>
                <thead>
                  <tr>
                    <th colspan="3">Account (editable)</th><th>Status</th>
                    <th>Last Login</th><th>Joined</th><th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($users as $u): ?>
                    <tr>
                      <td colspan="3">
                        <form method="POST" action="../php/AdminAction.php" class="edit-form">
                          <input type="hidden" name="user_id" value="<?= (int) $u['user_id'] ?>">
                          <input type="text" name="full_name" value="<?= e($u['full_name']) ?>"
                                 class="edit-input" aria-label="Full name" required>
                          <input type="email" name="email" value="<?= e($u['email']) ?>"
                                 class="edit-input edit-input-wide" aria-label="Email" required>
                          <select name="role" class="edit-input edit-select" aria-label="Role"
                                  <?= (int) $u['user_id'] === $adminId ? 'disabled' : '' ?>>
                            <?php foreach (['student', 'instructor', 'admin'] as $r): ?>
                              <option value="<?= $r ?>" <?= $u['role'] === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                            <?php endforeach; ?>
                          </select>
                          <?php if ((int) $u['user_id'] === $adminId): ?>
                            <input type="hidden" name="role" value="admin">
                          <?php endif; ?>
                          <button type="submit" name="action" value="update_user" class="row-btn ok">Save</button>
                        </form>
                      </td>
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

        <!-- ================= PENDING INSTRUCTORS ================= -->
        <?php elseif ($tab === 'pending'): ?>
          <h1 class="page-title">Pending Instructors</h1>
          <p class="page-sub">New instructor signups wait here until approved. They can log in and build drafts, but can't publish until you approve them.</p>

          <div class="table-widget">
            <div class="table-header"><h3>Awaiting Review</h3></div>
            <?php if (empty($pendingInstructors)): ?>
              <div class="empty-state">
                <span class="empty-icon"><?= ui_icon('shield') ?></span>
                <h3>Nothing to review</h3>
                <p>New instructor signups will appear here.</p>
              </div>
            <?php else: ?>
              <table>
                <thead>
                  <tr><th>Name</th><th>Email</th><th>Signed Up</th><th>Actions</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($pendingInstructors as $p): ?>
                    <tr>
                      <td><strong><?= e($p['full_name']) ?></strong></td>
                      <td><?= e($p['email']) ?></td>
                      <td class="cell-nowrap"><?= e(date('d M Y, H:i', strtotime($p['created_at']))) ?></td>
                      <td>
                        <form method="POST" action="../php/AdminAction.php" class="row-form">
                          <input type="hidden" name="user_id" value="<?= (int) $p['user_id'] ?>">
                          <button type="submit" name="action" value="approve_instructor" class="row-btn ok">Approve</button>
                        </form>
                        <form method="POST" action="../php/AdminAction.php" class="row-form">
                          <input type="hidden" name="user_id" value="<?= (int) $p['user_id'] ?>">
                          <input type="text" name="reason" placeholder="Reason (optional)" class="edit-input" aria-label="Rejection reason">
                          <button type="submit" name="action" value="reject_instructor" class="row-btn danger"
                                  data-confirm="Reject <?= e($p['full_name']) ?>'s instructor application?">Reject</button>
                        </form>
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
                <span class="empty-icon"><?= ui_icon('folder') ?></span>
                <h3>No courses yet</h3>
                <p>Courses appear here once an instructor creates one.</p>
              </div>
            <?php else: ?>
              <table>
                <thead>
                  <tr><th>Title</th><th>Instructor</th><th>Category</th><th>Price</th><th>Status</th><th>Students</th><th>Created</th><th>Actions</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($coursesList as $c): ?>
                    <tr>
                      <td><strong><?= e($c['title']) ?></strong></td>
                      <td><?= e($c['instructor_name']) ?></td>
                      <td><?= $c['category'] ? e($c['category']) : '—' ?></td>
                      <td class="cell-nowrap">
                        <?= (float) $c['price'] <= 0 ? 'Free' : '$' . number_format((float) $c['price'], 2) ?>
                      </td>
                      <td><span class="status-badge status-<?= e($c['status']) ?>"><?= ucfirst(e($c['status'])) ?></span></td>
                      <td><?= (int) $c['student_count'] ?></td>
                      <td class="cell-nowrap"><?= e(date('d M Y', strtotime($c['created_at']))) ?></td>
                      <td>
                        <form method="POST" action="../php/AdminAction.php" class="row-form">
                          <input type="hidden" name="course_id" value="<?= (int) $c['course_id'] ?>">
                          <button type="submit" name="action" value="delete_course" class="row-btn danger"
                                  data-confirm="Remove &quot;<?= e($c['title']) ?>&quot;? Enrolled students will lose access. This cannot be undone.">
                            Remove
                          </button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>
          </div>

        <!-- ================= PURCHASES ================= -->
        <?php elseif ($tab === 'purchases'): ?>
          <h1 class="page-title">Purchases</h1>
          <p class="page-sub">Every checkout attempt across the platform. successful, failed and abandoned. Latest 100.</p>

          <div class="table-widget">
            <div class="table-header"><h3>Transactions</h3></div>
            <?php if (empty($purchases)): ?>
              <div class="empty-state">
                <span class="empty-icon"><?= ui_icon('card') ?></span>
                <h3>No purchases yet</h3>
                <p>Transactions appear here once a student checks out a paid course.</p>
              </div>
            <?php else: ?>
              <table>
                <thead>
                  <tr><th>Reference</th><th>Student</th><th>Email</th><th>Items</th>
                      <th>Amount</th><th>Status</th><th>Card</th><th>When</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($purchases as $p): ?>
                    <tr>
                      <td><code><?= e($p['order_reference']) ?></code></td>
                      <td><strong><?= e($p['student_name']) ?></strong></td>
                      <td><?= e($p['email']) ?></td>
                      <td><?= (int) $p['items'] ?></td>
                      <td class="cell-nowrap">$<?= number_format((float) $p['amount'], 2) ?></td>
                      <td>
                        <span class="status-badge pay-<?= e($p['status']) ?>"><?= ucfirst(e($p['status'])) ?></span>
                        <?php if (!empty($p['failure_reason'])): ?>
                          <span class="row-note"><?= e($p['failure_reason']) ?></span>
                        <?php endif; ?>
                      </td>
                      <td class="cell-nowrap">
                        <?= $p['card_last_four'] ? '•••• ' . e($p['card_last_four']) : '—' ?>
                      </td>
                      <td class="cell-nowrap"><?= e(date('d M Y, H:i', strtotime($p['created_at']))) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>
          </div>

        <!-- ================= VISITOR IPs ================= -->
        <?php elseif ($tab === 'visitors'): ?>
          <h1 class="page-title">Visitor IP Addresses</h1>
          <p class="page-sub">Grouped by address one row per visitor, not per page load. Most recent first, latest 100.</p>

          <div class="table-widget">
            <div class="table-header"><h3>Visitors</h3></div>
            <?php if (empty($visitors)): ?>
              <div class="empty-state">
                <span class="empty-icon"><?= ui_icon('globe') ?></span>
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
                <span class="empty-icon"><?= ui_icon('shield') ?></span>
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

  <script src="../LoadingBar.js"></script>
  <script src="Dashboard.js"></script>
</body>
</html>