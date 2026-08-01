<?php
require_once __DIR__ . '/../php/SessionGuard.php';
require_once __DIR__ . '/../php/Database.php';
require_once __DIR__ . '/../php/Helpers.php';
require_role('instructor');

log_page_visit($pdo, 'Students');

$instructorId = (int) $_SESSION['user_id'];
$name  = $_SESSION['full_name'] ?? 'Instructor';
$email = $_SESSION['email'] ?? '';

// Every enrolment across this instructor's courses. The instructor_id filter
// is the important part: without it an instructor would see the whole
// platform's students, not just their own.
$stmt = $pdo->prepare(
    'SELECT u.full_name, u.email,
            c.title AS course_title,
            e.progress_percent, e.enrolled_at, e.completed_at
     FROM Enrollments e
     JOIN Courses c ON c.course_id = e.course_id
     JOIN Users   u ON u.user_id   = e.student_id
     WHERE c.instructor_id = ?
     ORDER BY e.enrolled_at DESC'
);
$stmt->execute([$instructorId]);
$rows = $stmt->fetchAll();

// Distinct people, since one student may appear on several courses.
$stmt = $pdo->prepare(
    'SELECT COUNT(DISTINCT e.student_id) AS c
     FROM Enrollments e
     JOIN Courses co ON co.course_id = e.course_id
     WHERE co.instructor_id = ?'
);
$stmt->execute([$instructorId]);
$uniqueStudents = (int) $stmt->fetch()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Students - Mech Spec LMS</title>
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
        <a href="InstructorDashboard.php" class="nav-item">📊 Overview</a>
        <a href="CreateCourse.php" class="nav-item">➕ Create Course</a>
        <a href="Students.php" class="nav-item active">👥 Students</a>
        <a href="Settings.php" class="nav-item">⚙️ Settings</a>
      </nav>
      <div class="sidebar-footer">
        <a href="#" class="logout-btn" id="logoutTrigger">🚪 Log Out</a>
      </div>
    </aside>

    <main class="main-wrapper">

      <header class="top-header">
        <div class="breadcrumbs">Dashboard <span>/ Students</span></div>
        <div class="header-actions">
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
        <h1 class="page-title">Your Students</h1>
        <p class="page-sub">
          <?= $uniqueStudents ?> student<?= $uniqueStudents === 1 ? '' : 's' ?>
          across <?= count($rows) ?> enrolment<?= count($rows) === 1 ? '' : 's' ?>.
        </p>

        <div class="table-widget">
          <div class="table-header"><h3>Enrolments</h3></div>

          <?php if (empty($rows)): ?>
            <div class="empty-state">
              <span class="empty-icon">👥</span>
              <h3>No students yet</h3>
              <p>Students appear here once they enrol in one of your published courses.</p>
              <a href="CreateCourse.php" class="btn-block-cyan empty-action">Create a Course</a>
            </div>
          <?php else: ?>
            <table>
              <thead>
                <tr>
                  <th>Student</th><th>Email</th><th>Course</th>
                  <th>Progress</th><th>Status</th><th>Enrolled</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $r): ?>
                  <tr>
                    <td><strong><?= e($r['full_name']) ?></strong></td>
                    <td><?= e($r['email']) ?></td>
                    <td><?= e($r['course_title']) ?></td>
                    <td class="cell-nowrap"><?= (int) $r['progress_percent'] ?>%</td>
                    <td>
                      <?php if ($r['completed_at']): ?>
                        <span class="status-badge status-active">Completed</span>
                      <?php else: ?>
                        <span class="status-badge status-draft">In progress</span>
                      <?php endif; ?>
                    </td>
                    <td class="cell-nowrap"><?= e(date('d M Y', strtotime($r['enrolled_at']))) ?></td>
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

  <script src="Dashboard.js"></script>
</body>
</html>