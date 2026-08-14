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
// Everything they are enrolled in — finished courses included. Filtering
// completed ones out meant a student who finished their only course saw an
// empty "no courses" panel while the metrics above said otherwise.
$stmt = $pdo->prepare(
    'SELECT c.course_id, c.title, c.description, c.thumbnail_url,
            c.youtube_video_id, e.progress_percent, e.completed_at
     FROM Enrollments e
     JOIN Courses c ON c.course_id = e.course_id
     WHERE e.student_id = ?
     ORDER BY e.completed_at IS NOT NULL, e.enrolled_at DESC'
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
  <link rel="stylesheet" href="../LoadingBar.css">
</head>
<body class="role-student">
  <div class="app-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <span class="brand-mark">M</span>
        <span>Mech Spec <span class="dash-gold">LMS</span></span>
      </div>
      <nav class="sidebar-nav">
        <a href="StudentDashboard.php" class="nav-item active"><?= ui_icon('book') ?><span class="nav-label">My Learning</span></a>
        <a href="BrowseCourses.php" class="nav-item"><?= ui_icon('search') ?><span class="nav-label">Browse Courses</span></a>
        <a href="Cart.php" class="nav-item"><?= ui_icon('cart') ?><span class="nav-label">Cart</span></a>
        <a href="Certificates.php" class="nav-item"><?= ui_icon('award') ?><span class="nav-label">Certificates</span></a>
        <a href="Settings.php" class="nav-item"><?= ui_icon('settings') ?><span class="nav-label">Settings</span></a>
      </nav>
      <div class="sidebar-footer">
        <a href="#" class="logout-btn" id="logoutTrigger"><?= ui_icon('logout') ?><span class="logout-label">Log Out</span></a>
      </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-wrapper">

      <header class="top-header">
        <div class="breadcrumbs">
          Dashboard <span>/ My Learning</span>
        </div>
        <div class="header-actions">
          <button type="button" class="header-logout-btn" id="logoutTriggerMobile" aria-label="Log out" title="Log out">
            <?= ui_icon('logout') ?>
          </button>
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
        <section class="role-hero">
          <div class="role-hero-copy">
            <div class="role-hero-kicker"><?= ui_icon('book') ?> Student Learning Hub</div>
            <h1>Welcome back, <?= e(explode(' ', $name)[0]) ?>.</h1>
            <p>
              <?php if ($enrolledCount === 0): ?>
                Your learning space is ready. Explore the catalogue and choose your first course.
              <?php elseif ((int) $metrics['completed_count'] > 0): ?>
                You've completed <?= (int) $metrics['completed_count'] ?> of your <?= $enrolledCount ?> course<?= $enrolledCount === 1 ? '' : 's' ?>. Keep building your progress.
              <?php else: ?>
                You're enrolled in <?= $enrolledCount ?> course<?= $enrolledCount === 1 ? '' : 's' ?>. Continue from where you left off.
              <?php endif; ?>
            </p>
          </div>
          <div class="role-hero-action">
            <a href="BrowseCourses.php" class="btn-primary"><?= ui_icon('search') ?> Browse Courses</a>
          </div>
        </section>

        <div class="quick-grid">
          <a class="quick-card" href="BrowseCourses.php">
            <span class="quick-icon"><?= ui_icon('search') ?></span>
            <span><strong>Explore Courses</strong><span>Find something new to learn</span></span>
          </a>
          <a class="quick-card" href="Cart.php">
            <span class="quick-icon"><?= ui_icon('cart') ?></span>
            <span><strong>Review Cart</strong><span>Check saved course purchases</span></span>
          </a>
          <a class="quick-card" href="Certificates.php">
            <span class="quick-icon"><?= ui_icon('award') ?></span>
            <span><strong>Certificates</strong><span>View your earned certificates</span></span>
          </a>
        </div>

        <!-- METRICS — all three read from the database -->
        <div class="metrics-row">
          <div class="metric-card cyan">
            <div class="metric-icon"><?= ui_icon('chart') ?></div>
            <span class="metric-label">Overall Progress</span>
            <span class="metric-value"><?= (int) $metrics['overall_progress'] ?>%</span>
          </div>
          <div class="metric-card emerald">
            <div class="metric-icon"><?= ui_icon('award') ?></div>
            <span class="metric-label">Completed Courses</span>
            <span class="metric-value"><?= (int) $metrics['completed_count'] ?></span>
          </div>
          <div class="metric-card gold">
            <div class="metric-icon"><?= ui_icon('award') ?></div>
            <span class="metric-label">Certificates Earned</span>
            <span class="metric-value"><?= $certificateCount ?></span>
          </div>
        </div>

        <h2 class="section-heading">Continue Learning</h2>

        <?php if (empty($activeCourses)): ?>
          <!-- Empty state: shown until the student enrols in something -->
          <div class="empty-state">
            <span class="empty-icon"><?= ui_icon('book') ?></span>
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
                    <div class="course-thumb-placeholder"><?= ui_icon('book') ?></div>
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
                  <?php if ($course['completed_at']): ?>
                    <span class="course-tag tag-done">✓ Completed</span>
                  <?php endif; ?>
                  <p><?= e($course['description']) ?></p>
                  <a href="WatchCourse.php?id=<?= (int) $course['course_id'] ?>"
                     class="btn-block-cyan">
                    <?php if ($course['completed_at']): ?>
                      Rewatch Course
                    <?php elseif ((int) $course['progress_percent'] > 0): ?>
                      Resume Course
                    <?php else: ?>
                      Start Course
                    <?php endif; ?>
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

  <script src="../LoadingBar.js"></script>
  <script src="Dashboard.js"></script>
</body>
</html>