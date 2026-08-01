<?php
require_once __DIR__ . '/../php/SessionGuard.php';
require_once __DIR__ . '/../php/Database.php';
require_once __DIR__ . '/../php/Helpers.php';
require_role('student');

log_page_visit($pdo, 'StudentDashboard');

$studentId = (int) $_SESSION['user_id'];
$name      = $_SESSION['full_name'] ?? 'Student';
$email     = $_SESSION['email'] ?? '';

// ---------------------------------------------------------------------
//  Real figures, read from the database. Nothing on this page is fixed
//  text — every number below reflects what this student has actually
//  enrolled in, so a brand-new account correctly shows zeros.
// ---------------------------------------------------------------------

// One query for all three metrics: total enrolled, how many are finished,
// and the average progress across them. COALESCE turns the NULL that AVG
// returns on an empty set into a clean 0.
$stmt = $pdo->prepare(
    'SELECT
        COUNT(*)                                        AS enrolled_count,
        COALESCE(SUM(completed_at IS NOT NULL), 0)      AS completed_count,
        COALESCE(ROUND(AVG(progress_percent)), 0)       AS overall_progress
     FROM Enrollments
     WHERE student_id = ?'
);
$stmt->execute([$studentId]);
$metrics = $stmt->fetch();

$stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM Certificates WHERE student_id = ?');
$stmt->execute([$studentId]);
$certificateCount = (int) $stmt->fetch()['c'];

// The courses shown under "Continue Learning": everything this student is
// enrolled in that isn't finished yet, most recent first.
$stmt = $pdo->prepare(
    'SELECT c.course_id, c.title, c.description, c.thumbnail_url,
            c.youtube_video_id, e.progress_percent
     FROM Enrollments e
     JOIN Courses c ON c.course_id = e.course_id
     WHERE e.student_id = ? AND e.completed_at IS NULL
     ORDER BY e.enrolled_at DESC'
);
$stmt->execute([$studentId]);
$activeCourses = $stmt->fetchAll();

$enrolledCount = (int) $metrics['enrolled_count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Dashboard - Mech Spec LMS</title>
  <link rel="stylesheet" href="../Index.css">
  <link rel="stylesheet" href="Dashboard.css">
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
        <a href="StudentDashboard.php" class="nav-item active">📚 My Learning</a>
        <a href="BrowseCourses.php" class="nav-item">🔍 Browse Courses</a>
        <a href="#" class="nav-item">🏆 Certificates</a>
        <a href="#" class="nav-item">⚙️ Settings</a>
      </nav>
      <div class="sidebar-footer">
        <a href="#" class="logout-btn" id="logoutTrigger">🚪 Log Out</a>
      </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-wrapper">

      <header class="top-header">
        <div class="breadcrumbs">
          Dashboard <span>/ My Learning</span>
        </div>
        <div class="header-actions">
          <span class="dash-role-pill dash-role-student">Student Mode</span>
          <div class="user-profile">
            <div class="user-info">
              <span class="user-name"><?= e($name) ?></span>
              <span class="user-email"><?= e($email) ?></span>
            </div>
            <div class="avatar"><?= e(strtoupper(substr($name, 0, 1))) ?></div>
          </div>
        </div>
      </header>

      <div class="dashboard-content">
        <h1 class="page-title">Welcome back, <?= e(explode(' ', $name)[0]) ?>! 👋</h1>

        <p class="page-sub">
          <?php if ($enrolledCount === 0): ?>
            You haven't enrolled in any courses yet. Browse the catalogue to get started.
          <?php elseif ((int) $metrics['completed_count'] > 0): ?>
            You've completed <?= (int) $metrics['completed_count'] ?>
            of your <?= $enrolledCount ?> course<?= $enrolledCount === 1 ? '' : 's' ?>. Keep going.
          <?php else: ?>
            You're enrolled in <?= $enrolledCount ?> course<?= $enrolledCount === 1 ? '' : 's' ?>. Pick up where you left off.
          <?php endif; ?>
        </p>

        <!-- METRICS — all three read from the database -->
        <div class="metrics-row">
          <div class="metric-card cyan">
            <span class="metric-label">Overall Progress</span>
            <span class="metric-value"><?= (int) $metrics['overall_progress'] ?>%</span>
          </div>
          <div class="metric-card emerald">
            <span class="metric-label">Completed Courses</span>
            <span class="metric-value"><?= (int) $metrics['completed_count'] ?></span>
          </div>
          <div class="metric-card gold">
            <span class="metric-label">Certificates Earned</span>
            <span class="metric-value"><?= $certificateCount ?></span>
          </div>
        </div>

        <h2 class="section-heading">Continue Learning</h2>

        <?php if (empty($activeCourses)): ?>
          <!-- Empty state: shown until the student enrols in something -->
          <div class="empty-state">
            <span class="empty-icon">📚</span>
            <h3>No courses yet</h3>
            <p>You haven't enrolled in any courses. Browse the catalogue and enrol to start learning.</p>
            <a href="BrowseCourses.php" class="btn-block-cyan empty-action">Browse Courses</a>
          </div>
        <?php else: ?>
          <div class="course-grid">
            <?php foreach ($activeCourses as $course): ?>
              <div class="dash-course-card">
                <div class="course-img">
                  <?php if (!empty($course['thumbnail_url'])): ?>
                    <img class="course-thumb" src="<?= e($course['thumbnail_url']) ?>"
                         alt="<?= e($course['title']) ?>">
                  <?php else: ?>
                    <div class="course-thumb-placeholder">🎓</div>
                  <?php endif; ?>
                  <div class="course-progress-overlay">
                    <!-- Width is set from data-progress by Dashboard.js, so the
                         per-course value stays out of a style attribute. -->
                    <div class="course-progress-fill"
                         data-progress="<?= (int) $course['progress_percent'] ?>"></div>
                  </div>
                </div>
                <div class="course-body">
                  <h3><?= e($course['title']) ?></h3>
                  <p><?= e($course['description']) ?></p>
                  <a href="WatchCourse.php?id=<?= (int) $course['course_id'] ?>"
                     class="btn-block-cyan">
                    <?= (int) $course['progress_percent'] > 0 ? 'Resume Course' : 'Start Course' ?>
                  </a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

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

  <script src="Dashboard.js"></script>
</body>
</html>
