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
</head>
<body>
  <div class="app-layout">

    <aside class="sidebar">
      <div class="sidebar-header">
        <span class="brand-mark">M</span>
        <span>Mech Spec <span class="dash-gold">LMS</span></span>
      </div>
      <nav class="sidebar-nav">
        <a href="InstructorDashboard.php" class="nav-item active">📊 Overview</a>
        <a href="CreateCourse.php" class="nav-item">➕ Create Course</a>
        <a href="#" class="nav-item">👥 Students</a>
        <a href="#" class="nav-item">⚙️ Settings</a>
      </nav>
      <div class="sidebar-footer">
        <a href="#" class="logout-btn" id="logoutTrigger">🚪 Log Out</a>
      </div>
    </aside>

    <main class="main-wrapper">

      <header class="top-header">
        <div class="breadcrumbs">
          Dashboard <span>/ Overview</span>
        </div>
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
        <h1 class="page-title">Welcome back, <?= e(explode(' ', $name)[0]) ?>! 📈</h1>

        <p class="page-sub">
          <?php if ((int) $courseStats['total_courses'] === 0): ?>
            You haven't created any courses yet. Create your first one to start teaching.
          <?php else: ?>
            You have <?= (int) $courseStats['total_courses'] ?>
            course<?= (int) $courseStats['total_courses'] === 1 ? '' : 's' ?>
            and <?= $totalStudents ?> student<?= $totalStudents === 1 ? '' : 's' ?> enrolled.
          <?php endif; ?>
        </p>

        <?php if ($notice): ?>
          <div class="form-notice success"><?= e($notice) ?></div>
        <?php endif; ?>
        <?php if ($noticeError): ?>
          <div class="form-notice error"><?= e($noticeError) ?></div>
        <?php endif; ?>

        <div class="metrics-row">
          <div class="metric-card gold">
            <span class="metric-label">Total Students</span>
            <span class="metric-value"><?= $totalStudents ?></span>
          </div>
          <div class="metric-card cyan">
            <span class="metric-label">Published Courses</span>
            <span class="metric-value"><?= (int) $courseStats['published_courses'] ?></span>
          </div>
          <div class="metric-card gold">
            <span class="metric-label">Drafts</span>
            <span class="metric-value"><?= (int) $courseStats['draft_courses'] ?></span>
          </div>
          <div class="metric-card emerald">
            <span class="metric-label">Total Courses</span>
            <span class="metric-value"><?= (int) $courseStats['total_courses'] ?></span>
          </div>
        </div>

        <h2 class="section-heading">Course Manager</h2>

        <div class="table-widget">
          <div class="table-header">
            <h3>Your Courses</h3>
            <a href="CreateCourse.php" class="btn-small">+ Create New Course</a>
          </div>

          <?php if (empty($courses)): ?>
            <div class="empty-state">
              <span class="empty-icon">🎬</span>
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
                    <td><?= e(date('d M Y', strtotime($c['created_at']))) ?></td>
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
