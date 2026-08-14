<?php
require_once __DIR__ . '/../php/SessionGuard.php';
require_once __DIR__ . '/../php/Database.php';
require_once __DIR__ . '/../php/Helpers.php';
require_role('student');

log_page_visit($pdo, 'BrowseCourses');

$studentId = (int) $_SESSION['user_id'];
$name  = $_SESSION['full_name'] ?? 'Student';
$email = $_SESSION['email'] ?? '';

$notice      = $_SESSION['enrol_success'] ?? '';
$noticeError = $_SESSION['enrol_error'] ?? '';
unset($_SESSION['enrol_success'], $_SESSION['enrol_error']);

// Published courses only — drafts belong to their instructor alone.
// The LEFT JOIN tells us whether this student is already enrolled, so the
// button can say Enrol or Continue without a second query per card.
$stmt = $pdo->prepare(
    'SELECT c.course_id, c.title, c.description, c.category, c.thumbnail_url, c.price,
            u.full_name AS instructor_name,
            e.enrollment_id,
            (SELECT COUNT(*) FROM Enrollments x WHERE x.course_id = c.course_id) AS student_count
     FROM Courses c
     JOIN Users u ON u.user_id = c.instructor_id
     LEFT JOIN Enrollments e ON e.course_id = c.course_id AND e.student_id = ?
     WHERE c.status = "published"
     ORDER BY c.created_at DESC'
);
$stmt->execute([$studentId]);
$courses = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Browse Courses - Mech Spec LMS</title>
  <link rel="stylesheet" href="../Index.css">
  <link rel="stylesheet" href="Dashboard.css">
  <link rel="stylesheet" href="../LoadingBar.css">
</head>
<body>
  <div class="app-layout">

    <aside class="sidebar">
      <div class="sidebar-header">
        <span class="brand-mark">M</span>
        <span>Mech Spec <span class="dash-gold">LMS</span></span>
      </div>
      <nav class="sidebar-nav">
        <a href="StudentDashboard.php" class="nav-item"><?= ui_icon('book') ?><span class="nav-label">My Learning</span></a>
        <a href="BrowseCourses.php" class="nav-item active"><?= ui_icon('search') ?><span class="nav-label">Browse Courses</span></a>
        <a href="Cart.php" class="nav-item"><?= ui_icon('cart') ?><span class="nav-label">Cart</span></a>
        <a href="Certificates.php" class="nav-item"><?= ui_icon('award') ?><span class="nav-label">Certificates</span></a>
        <a href="Settings.php" class="nav-item"><?= ui_icon('settings') ?><span class="nav-label">Settings</span></a>
      </nav>
      <div class="sidebar-footer">
        <a href="#" class="logout-btn" id="logoutTrigger"><?= ui_icon('logout') ?><span class="logout-label">Log Out</span></a>
      </div>
    </aside>

    <main class="main-wrapper">

      <header class="top-header">
        <div class="breadcrumbs">Dashboard <span>/ Browse Courses</span></div>
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
        <h1 class="page-title">Browse Courses</h1>
        <p class="page-sub">
          <?= count($courses) ?> course<?= count($courses) === 1 ? '' : 's' ?> available. Enrol to start learning.
        </p>

        <?php if ($notice): ?>
          <div class="form-notice success"><?= e($notice) ?></div>
        <?php endif; ?>
        <?php if ($noticeError): ?>
          <div class="form-notice error"><?= e($noticeError) ?></div>
        <?php endif; ?>

        <?php if (empty($courses)): ?>
          <div class="empty-state">
            <span class="empty-icon"><?= ui_icon('search') ?></span>
            <h3>No courses available yet</h3>
            <p>There are no published courses right now. Check back once an instructor publishes one.</p>
          </div>
        <?php else: ?>
          <div class="search-row">
            <span class="search-icon"><?= ui_icon('search') ?></span>
            <input type="text" id="courseSearch" placeholder="Search courses, categories or instructors...">
          </div>

          <div class="empty-state is-hidden" id="searchEmpty">
            <span class="empty-icon"><?= ui_icon('search') ?></span>
            <h3>No matches</h3>
            <p>No courses match that search. Try a different term.</p>
          </div>

          <div class="course-grid">
            <?php foreach ($courses as $c): ?>
              <div class="dash-course-card">
                <div class="course-img">
                  <?php if (!empty($c['thumbnail_url'])): ?>
                    <img class="course-thumb" src="<?= e($c['thumbnail_url']) ?>" alt="<?= e($c['title']) ?>">
                  <?php else: ?>
                    <div class="course-thumb-placeholder"><?= ui_icon('book') ?></div>
                  <?php endif; ?>
                </div>
                <div class="course-body">
                  <?php if ($c['category']): ?>
                    <span class="course-tag"><?= e($c['category']) ?></span>
                  <?php endif; ?>
                  <h3><?= e($c['title']) ?></h3>
                  <p><?= e($c['description']) ?></p>
                  <span class="course-meta">
                    By <?= e($c['instructor_name']) ?> ·
                    <?= (int) $c['student_count'] ?> enrolled
                  </span>

                  <div class="price-row">
                    <?php if ((float) $c['price'] <= 0): ?>
                      <span class="price-free">Free</span>
                    <?php else: ?>
                      <span class="price-tag">$<?= number_format((float) $c['price'], 2) ?></span>
                    <?php endif; ?>
                  </div>

                  <?php if ($c['enrollment_id']): ?>
                    <a href="WatchCourse.php?id=<?= (int) $c['course_id'] ?>" class="btn-block-cyan">Continue Course</a>
                  <?php else: ?>
                    <form method="POST" action="../php/AddToCart.php" class="enrol-form">
                      <input type="hidden" name="course_id" value="<?= (int) $c['course_id'] ?>">
                      <button type="submit" class="btn-block-cyan">
                        <?= (float) $c['price'] <= 0 ? 'Enrol Free' : 'Add to Cart' ?>
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
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