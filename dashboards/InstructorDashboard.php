<?php
require_once __DIR__ . '/../php/SessionGuard.php';
require_once __DIR__ . '/../php/Database.php';
require_once __DIR__ . '/../php/Helpers.php';
require_role('instructor');

log_page_visit($pdo, 'InstructorDashboard');

$instructorId = (int) $_SESSION['user_id'];
$name  = $_SESSION['full_name'] ?? 'Instructor';
$email = $_SESSION['email'] ?? '';

// A message set by SaveCourse.php after a create attempt.
$notice      = $_SESSION['course_success'] ?? '';
$noticeError = $_SESSION['course_error'] ?? '';
unset($_SESSION['course_success'], $_SESSION['course_error']);

// ---------------------------------------------------------------------
//  Metrics — every figure below is counted from this instructor's own
//  rows, so a new instructor account correctly shows zeros.
// ---------------------------------------------------------------------
$stmt = $pdo->prepare(
    'SELECT
        COUNT(*)                                   AS total_courses,
        COALESCE(SUM(status = "published"), 0)     AS published_courses,
        COALESCE(SUM(status = "draft"), 0)         AS draft_courses
     FROM Courses
     WHERE instructor_id = ?'
);
$stmt->execute([$instructorId]);
$courseStats = $stmt->fetch();

// Approval status drives the banner below and whether Publish is offered
// on the course forms. Read fresh from the DB so an admin's decision
// shows up the moment this instructor next loads the page.
$stmt = $pdo->prepare('SELECT instructor_approval_status, instructor_rejection_reason FROM Users WHERE user_id = ?');
$stmt->execute([$instructorId]);
$approval = $stmt->fetch();
$approvalStatus = $approval['instructor_approval_status'] ?? 'approved';

// Distinct students across all of this instructor's courses. DISTINCT matters
// because one student enrolled on three of their courses is still one student.
$stmt = $pdo->prepare(
    'SELECT COUNT(DISTINCT e.student_id) AS total_students
     FROM Enrollments e
     JOIN Courses c ON c.course_id = e.course_id
     WHERE c.instructor_id = ?'
);
$stmt->execute([$instructorId]);
$totalStudents = (int) $stmt->fetch()['total_students'];

// The course table. The enrolment count is a correlated subquery so courses
// with zero students still appear — a JOIN would silently drop them.
$stmt = $pdo->prepare(
    'SELECT c.course_id, c.title, c.status, c.category, c.created_at,
            (SELECT COUNT(*) FROM Enrollments e WHERE e.course_id = c.course_id) AS student_count
     FROM Courses c
     WHERE c.instructor_id = ?
     ORDER BY c.created_at DESC'
);
$stmt->execute([$instructorId]);
$courses = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Instructor Dashboard - Mech Spec LMS</title>
  <link rel="stylesheet" href="../Index.css">
  <link rel="stylesheet" href="Dashboard.css">
  <link rel="stylesheet" href="../LoadingBar.css">
</head>
<body class="role-instructor">
  <div class="app-layout">

    <aside class="sidebar">
      <div class="sidebar-header">
        <span class="brand-mark">M</span>
        <span>Mech Spec <span class="dash-gold">LMS</span></span>
      </div>
      <nav class="sidebar-nav">
        <a href="InstructorDashboard.php" class="nav-item active"><?= ui_icon('chart') ?><span class="nav-label">Overview</span></a>
        <a href="CreateCourse.php" class="nav-item"><?= ui_icon('plus') ?><span class="nav-label">Create Course</span></a>
        <a href="Students.php" class="nav-item"><?= ui_icon('users') ?><span class="nav-label">Students</span></a>
        <a href="Settings.php" class="nav-item"><?= ui_icon('settings') ?><span class="nav-label">Settings</span></a>
      </nav>
      <div class="sidebar-footer">
        <a href="#" class="logout-btn" id="logoutTrigger"><?= ui_icon('logout') ?><span class="logout-label">Log Out</span></a>
      </div>
    </aside>

    <main class="main-wrapper">

      <header class="top-header">
        <div class="breadcrumbs">
          Dashboard <span>/ Overview</span>
        </div>
        <div class="header-actions">
          <button type="button" class="header-logout-btn" id="logoutTriggerMobile" aria-label="Log out" title="Log out">
            <?= ui_icon('logout') ?>
          </button>
          <span class="dash-role-pill dash-role-instructor">Instructor Mode</span>
          <div class="user-profile">
            <div class="user-info">
              <span class="user-name"><?= e($name) ?></span>
              <span class="user-email"><?= e($email) ?></span>
            </div>
            <div class="avatar avatar-instructor"><?= e(strtoupper(substr($name, 0, 1))) ?></div>
          </div>
        </div>
      </header>

      <div class="dashboard-content">
        <section class="role-hero">
          <div class="role-hero-copy">
            <div class="role-hero-kicker"><?= ui_icon('book') ?> Instructor Workspace</div>
            <h1>Welcome back, <?= e(explode(' ', $name)[0]) ?>.</h1>
            <p>
              <?php if ((int) $courseStats['total_courses'] === 0): ?>
                Build your first course and start publishing learning content to students.
              <?php else: ?>
                You manage <?= (int) $courseStats['total_courses'] ?> course<?= (int) $courseStats['total_courses'] === 1 ? '' : 's' ?> for <?= $totalStudents ?> student<?= $totalStudents === 1 ? '' : 's' ?>.
              <?php endif; ?>
            </p>
          </div>
          <div class="role-hero-action">
            <a href="CreateCourse.php" class="btn-primary"><?= ui_icon('plus') ?> Create Course</a>
          </div>
        </section>

        <?php if ($approvalStatus === 'pending'): ?>
          <div class="form-notice">Your instructor account is awaiting admin approval. You can build and save courses as drafts now &mdash; publishing unlocks once you're approved.</div>
        <?php elseif ($approvalStatus === 'rejected'): ?>
          <div class="form-notice error">
            Your instructor account was not approved to publish.
            <?php if (!empty($approval['instructor_rejection_reason'])): ?>
              <strong>Reason:</strong> <?= e($approval['instructor_rejection_reason']) ?>
            <?php endif; ?>
            Contact an administrator if you believe this is a mistake.
          </div>
        <?php endif; ?>

        <?php if ($notice): ?>
          <div class="form-notice success"><?= e($notice) ?></div>
        <?php endif; ?>
        <?php if ($noticeError): ?>
          <div class="form-notice error"><?= e($noticeError) ?></div>
        <?php endif; ?>

        <div class="metrics-row">
          <div class="metric-card gold">
            <div class="metric-icon"><?= ui_icon('users') ?></div>
            <span class="metric-label">Total Students</span>
            <span class="metric-value"><?= $totalStudents ?></span>
          </div>
          <div class="metric-card cyan">
            <div class="metric-icon"><?= ui_icon('book') ?></div>
            <span class="metric-label">Published Courses</span>
            <span class="metric-value"><?= (int) $courseStats['published_courses'] ?></span>
          </div>
          <div class="metric-card gold">
            <div class="metric-icon"><?= ui_icon('folder') ?></div>
            <span class="metric-label">Drafts</span>
            <span class="metric-value"><?= (int) $courseStats['draft_courses'] ?></span>
          </div>
          <div class="metric-card emerald">
            <div class="metric-icon"><?= ui_icon('book') ?></div>
            <span class="metric-label">Total Courses</span>
            <span class="metric-value"><?= (int) $courseStats['total_courses'] ?></span>
          </div>
        </div>

        <div class="quick-grid">
          <a class="quick-card" href="CreateCourse.php">
            <span class="quick-icon"><?= ui_icon('plus') ?></span>
            <span><strong>Create a Course</strong><span>Publish new learning content</span></span>
          </a>
          <a class="quick-card" href="Students.php">
            <span class="quick-icon"><?= ui_icon('users') ?></span>
            <span><strong>View Students</strong><span>Review learners on your courses</span></span>
          </a>
          <a class="quick-card" href="Settings.php">
            <span class="quick-icon"><?= ui_icon('settings') ?></span>
            <span><strong>Account Settings</strong><span>Manage your instructor profile</span></span>
          </a>
        </div>

        <h2 class="section-heading">Course Manager</h2>

        <div class="table-widget">
          <div class="table-header">
            <h3>Your Courses</h3>
            <a href="CreateCourse.php" class="btn-small">+ Create New Course</a>
          </div>

          <?php if (empty($courses)): ?>
            <div class="empty-state">
              <span class="empty-icon"><?= ui_icon('book') ?></span>
              <h3>No courses yet</h3>
              <p>Create your first course by pasting a YouTube link — the video and thumbnail are pulled in automatically.</p>
              <a href="CreateCourse.php" class="btn-block-cyan empty-action">Create a Course</a>
            </div>
          <?php else: ?>
            <table>
              <thead>
                <tr>
                  <th>Course Name</th>
                  <th>Category</th>
                  <th>Status</th>
                  <th>Students</th>
                  <th>Created</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($courses as $c): ?>
                  <tr>
                    <td><strong><?= e($c['title']) ?></strong></td>
                    <td><?= $c['category'] ? e($c['category']) : '—' ?></td>
                    <td>
                      <span class="status-badge status-<?= e($c['status']) ?>">
                        <?= ucfirst(e($c['status'])) ?>
                      </span>
                    </td>
                    <td><?= (int) $c['student_count'] ?></td>
                    <td class="cell-nowrap"><?= e(date('d M Y', strtotime($c['created_at']))) ?></td>
                    <td>
                      <div class="row-actions">
                        <a href="EditCourse.php?id=<?= (int) $c['course_id'] ?>" class="row-btn ok">Edit</a>
                        <form method="POST" action="../php/DeleteCourse.php" class="row-form">
                          <input type="hidden" name="course_id" value="<?= (int) $c['course_id'] ?>">
                          <button type="submit" class="row-btn danger"
                                  data-confirm="Delete &quot;<?= e($c['title']) ?>&quot;? Enrolled students will lose access. This cannot be undone.">
                            Delete
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>

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