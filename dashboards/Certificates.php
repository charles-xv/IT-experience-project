<?php
require_once __DIR__ . '/../php/SessionGuard.php';
require_once __DIR__ . '/../php/Database.php';
require_once __DIR__ . '/../php/Helpers.php';
require_role('student');

log_page_visit($pdo, 'Certificates');

$studentId = (int) $_SESSION['user_id'];
$name  = $_SESSION['full_name'] ?? 'Student';
$email = $_SESSION['email'] ?? '';

$stmt = $pdo->prepare(
    'SELECT cert.certificate_id, cert.issued_at,
            c.title, c.category, c.thumbnail_url,
            u.full_name AS instructor_name
     FROM Certificates cert
     JOIN Courses c ON c.course_id = cert.course_id
     JOIN Users u   ON u.user_id  = c.instructor_id
     WHERE cert.student_id = ?
     ORDER BY cert.issued_at DESC'
);
$stmt->execute([$studentId]);
$certificates = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Certificates - Mech Spec LMS</title>
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
        <a href="StudentDashboard.php" class="nav-item">📚 My Learning</a>
        <a href="BrowseCourses.php" class="nav-item">🔍 Browse Courses</a>
        <a href="Certificates.php" class="nav-item active">🏆 Certificates</a>
        <a href="Settings.php" class="nav-item">⚙️ Settings</a>
      </nav>
      <div class="sidebar-footer">
        <a href="#" class="logout-btn" id="logoutTrigger">🚪 Log Out</a>
      </div>
    </aside>

    <main class="main-wrapper">

      <header class="top-header">
        <div class="breadcrumbs">Dashboard <span>/ Certificates</span></div>
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
        <h1 class="page-title">Certificates</h1>
        <p class="page-sub">
          <?php if (empty($certificates)): ?>
            Complete a course to earn your first certificate.
          <?php else: ?>
            You've earned <?= count($certificates) ?>
            certificate<?= count($certificates) === 1 ? '' : 's' ?>.
          <?php endif; ?>
        </p>

        <?php if (empty($certificates)): ?>
          <div class="empty-state">
            <span class="empty-icon">🏆</span>
            <h3>No certificates yet</h3>
            <p>Certificates are issued automatically when you mark a course as complete.</p>
            <a href="BrowseCourses.php" class="btn-block-cyan empty-action">Browse Courses</a>
          </div>
        <?php else: ?>
          <div class="cert-grid">
            <?php foreach ($certificates as $cert): ?>
              <div class="cert-card">
                <div class="cert-ribbon">Certificate of Completion</div>
                <div class="cert-body">
                  <span class="cert-awarded">This certifies that</span>
                  <h3 class="cert-name"><?= e($name) ?></h3>
                  <span class="cert-awarded">has successfully completed</span>
                  <h4 class="cert-course"><?= e($cert['title']) ?></h4>
                  <?php if ($cert['category']): ?>
                    <span class="course-tag"><?= e($cert['category']) ?></span>
                  <?php endif; ?>
                </div>
                <div class="cert-footer">
                  <span>Instructor: <?= e($cert['instructor_name']) ?></span>
                  <span>Issued <?= e(date('d M Y', strtotime($cert['issued_at']))) ?></span>
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

  <script src="Dashboard.js"></script>
</body>
</html>