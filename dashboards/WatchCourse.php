<?php
require_once __DIR__ . '/../php/SessionGuard.php';
require_once __DIR__ . '/../php/Database.php';
require_once __DIR__ . '/../php/Helpers.php';
require_role('student');

log_page_visit($pdo, 'WatchCourse');

$studentId = (int) $_SESSION['user_id'];
$name  = $_SESSION['full_name'] ?? 'Student';
$email = $_SESSION['email'] ?? '';
$courseId = (int) ($_GET['id'] ?? 0);

$notice = $_SESSION['watch_success'] ?? '';
unset($_SESSION['watch_success']);

// The enrolment is joined in deliberately: a student who is not enrolled gets
// no row back, so they never receive the video ID. Hiding the link on the
// previous page would not be enough — the check has to happen here, server-side.
$stmt = $pdo->prepare(
    'SELECT c.course_id, c.title, c.description, c.category, c.youtube_video_id,
            u.full_name AS instructor_name,
            e.progress_percent, e.completed_at
     FROM Courses c
     JOIN Users u ON u.user_id = c.instructor_id
     JOIN Enrollments e ON e.course_id = c.course_id AND e.student_id = ?
     WHERE c.course_id = ? AND c.status = "published"'
);
$stmt->execute([$studentId, $courseId]);
$course = $stmt->fetch();

if (!$course) {
    log_security_event($pdo, 'access_denied', $studentId, "Tried to watch course #$courseId without enrolment");
    $_SESSION['enrol_error'] = 'You need to enrol in that course before you can watch it.';
    header('Location: BrowseCourses.php');
    exit;
}

$isComplete = $course['completed_at'] !== null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($course['title']) ?> - Mech Spec LMS</title>
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
        <a href="StudentDashboard.php" class="nav-item active">📚 My Learning</a>
        <a href="BrowseCourses.php" class="nav-item">🔍 Browse Courses</a>
        <a href="Certificates.php" class="nav-item">🏆 Certificates</a>
        <a href="Settings.php" class="nav-item">⚙️ Settings</a>
      </nav>
      <div class="sidebar-footer">
        <a href="#" class="logout-btn" id="logoutTrigger">🚪 Log Out</a>
      </div>
    </aside>

    <main class="main-wrapper">

      <header class="top-header">
        <div class="breadcrumbs">Dashboard <span>/ <?= e($course['title']) ?></span></div>
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
        <a href="StudentDashboard.php" class="back-link">← Back to My Learning</a>

        <h1 class="page-title"><?= e($course['title']) ?></h1>
        <p class="page-sub">By <?= e($course['instructor_name']) ?><?= $course['category'] ? ' · ' . e($course['category']) : '' ?></p>

        <?php if ($notice): ?>
          <div class="form-notice success"><?= e($notice) ?></div>
        <?php endif; ?>

        <!-- The player. Wrapped so the iframe keeps a 16:9 shape at any width. -->
        <div class="video-frame">
          <iframe
            src="https://www.youtube.com/embed/<?= e($course['youtube_video_id']) ?>"
            title="<?= e($course['title']) ?>"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowfullscreen></iframe>
        </div>

        <div class="watch-panel">
          <div class="watch-progress">
            <div class="watch-progress-label">
              <span>Your progress</span>
              <strong><?= (int) $course['progress_percent'] ?>%</strong>
            </div>
            <div class="course-progress-overlay">
              <div class="course-progress-fill" data-progress="<?= (int) $course['progress_percent'] ?>"></div>
            </div>
          </div>

          <?php if ($isComplete): ?>
            <div class="watch-done">
              ✅ Completed on <?= e(date('d M Y', strtotime($course['completed_at']))) ?>.
              Your certificate is in <a href="Certificates.php">Certificates</a>.
            </div>
          <?php else: ?>
            <form method="POST" action="../php/UpdateProgress.php" class="watch-actions">
              <input type="hidden" name="course_id" value="<?= (int) $course['course_id'] ?>">
              <button type="submit" name="action" value="halfway" class="btn-cancel">Mark 50% watched</button>
              <button type="submit" name="action" value="complete" class="btn-submit">Mark as Complete</button>
            </form>
          <?php endif; ?>
        </div>

        <?php if ($course['description']): ?>
          <div class="watch-about">
            <h2 class="section-heading">About this course</h2>
            <p><?= nl2br(e($course['description'])) ?></p>
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