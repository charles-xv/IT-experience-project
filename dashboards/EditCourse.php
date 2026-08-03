<?php
require_once __DIR__ . '/../php/SessionGuard.php';
require_once __DIR__ . '/../php/Database.php';
require_once __DIR__ . '/../php/Helpers.php';
require_role('instructor');

log_page_visit($pdo, 'EditCourse');

$name  = $_SESSION['full_name'] ?? 'Instructor';
$email = $_SESSION['email'] ?? '';

$error = $_SESSION['course_error'] ?? '';
unset($_SESSION['course_error']);

$courseId = (int) ($_GET['id'] ?? 0);

// The instructor_id filter is the ownership check: an instructor who edits
// the URL to another person's course id simply gets no row back.
$stmt = $pdo->prepare(
    'SELECT course_id, title, description, category, price, youtube_video_id, status
     FROM Courses
     WHERE course_id = ? AND instructor_id = ?'
);
$stmt->execute([$courseId, (int) $_SESSION['user_id']]);
$course = $stmt->fetch();

if (!$course) {
    log_security_event($pdo, 'access_denied', (int) $_SESSION['user_id'],
        "Tried to edit course #$courseId they do not own");
    $_SESSION['course_error'] = 'That course was not found, or it is not yours to edit.';
    header('Location: InstructorDashboard.php');
    exit;
}

// A failed submit wins over the stored values, so nothing typed is lost.
$old = $_SESSION['course_old'] ?? [];
unset($_SESSION['course_old']);
$val = [
    'title'       => $old['title']       ?? $course['title'],
    'description' => $old['description'] ?? $course['description'],
    'category'    => $old['category']    ?? $course['category'],
    'youtube_url' => $old['youtube_url'] ?? 'https://www.youtube.com/watch?v=' . $course['youtube_video_id'],
    'price'       => $old['price']       ?? $course['price'],
    'status'      => $old['status']      ?? $course['status'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Course - Mech Spec LMS</title>
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
        <a href="InstructorDashboard.php" class="nav-item active">📊 Overview</a>
        <a href="CreateCourse.php" class="nav-item">➕ Create Course</a>
        <a href="Students.php" class="nav-item">👥 Students</a>
        <a href="Settings.php" class="nav-item">⚙️ Settings</a>
      </nav>
      <div class="sidebar-footer">
        <a href="#" class="logout-btn" id="logoutTrigger">🚪 Log Out</a>
      </div>
    </aside>

    <main class="main-wrapper">

      <header class="top-header">
        <div class="breadcrumbs">
          Dashboard <span>/ Edit Course</span>
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
        <h1 class="page-title">Edit Course</h1>
        <p class="page-sub">Change any detail below. Replacing the YouTube link also replaces the thumbnail.</p>

        <?php if ($error): ?>
          <div class="form-notice error"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="form-panel">
          <form method="POST" action="../php/UpdateCourse.php">
            <input type="hidden" name="course_id" value="<?= (int) $course['course_id'] ?>">

            <div class="form-row">
              <label for="title">Course Title</label>
              <input type="text" id="title" name="title" maxlength="180"
                     placeholder="e.g. Web Security Basics"
                     value="<?= e($val['title']) ?>" required>
            </div>

            <div class="form-row">
              <label for="description">Description</label>
              <textarea id="description" name="description" rows="4"
                        placeholder="What will students learn in this course?"><?= e($val['description']) ?></textarea>
            </div>

            <div class="form-row">
              <label for="category">Category</label>
              <select id="category" name="category">
                <?php
                $categories = ['Web Security', 'Development', 'Artificial Intelligence',
                               'Networking', 'Data Analysis', 'Cloud Computing'];
                $chosen = $val['category'];
                foreach ($categories as $cat):
                ?>
                  <option value="<?= e($cat) ?>" <?= $chosen === $cat ? 'selected' : '' ?>>
                    <?= e($cat) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>


            <div class="form-row">
              <label for="price">Price (USD)</label>
              <input type="number" id="price" name="price" step="0.01" min="0"
                     placeholder="0.00" value="<?= e($val['price']) ?>" required>
              <span class="form-hint">
                Enter 0 to make the course free — students enrol without checkout.
                Any amount above 0 sends them through the purchase flow.
              </span>
            </div>
            <div class="form-row">
              <label for="youtube_url">YouTube Link</label>
              <input type="url" id="youtube_url" name="youtube_url"
                     placeholder="https://www.youtube.com/watch?v=..."
                     value="<?= e($val['youtube_url']) ?>" required>
              <span class="form-hint">
                Paste the full link. Any YouTube format works — watch, youtu.be, or embed.
                The thumbnail is generated from the video, so you don't upload one.
              </span>
            </div>

            <div class="form-row">
              <label for="status">Publish Status</label>
              <select id="status" name="status">
                <option value="draft" <?= $val['status'] === 'draft' ? 'selected' : '' ?>>
                  Draft — only you can see it
                </option>
                <option value="published" <?= $val['status'] === 'published' ? 'selected' : '' ?>>
                  Published — students can enrol
                </option>
              </select>
            </div>

            <div class="form-actions">
              <a href="InstructorDashboard.php" class="btn-cancel">Cancel</a>
              <button type="submit" class="btn-submit">Save Changes</button>
            </div>

          </form>
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