<?php
require_once __DIR__ . '/../php/SessionGuard.php';
require_once __DIR__ . '/../php/Database.php';
require_once __DIR__ . '/../php/Helpers.php';
require_role('instructor');

log_page_visit($pdo, 'CreateCourse');

$name  = $_SESSION['full_name'] ?? 'Instructor';
$email = $_SESSION['email'] ?? '';

$error = $_SESSION['course_error'] ?? '';
unset($_SESSION['course_error']);

// Repopulate the form after a failed submit so nothing has to be retyped.
$old = $_SESSION['course_old'] ?? [];
unset($_SESSION['course_old']);

// The Published option is disabled until an admin approves this instructor —
// SaveCourse.php enforces the same rule server-side, this is just so the
// form doesn't offer a choice that will be rejected on submit.
$canPublish = instructor_can_publish($pdo, (int) $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Course - Mech Spec LMS</title>
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
        <a href="InstructorDashboard.php" class="nav-item"><?= ui_icon('chart') ?><span class="nav-label">Overview</span></a>
        <a href="CreateCourse.php" class="nav-item active"><?= ui_icon('plus') ?><span class="nav-label">Create Course</span></a>
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
          Dashboard <span>/ Create Course</span>
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
        <h1 class="page-title">Create a Course</h1>
        <p class="page-sub">Paste a YouTube link the video and its thumbnail are pulled in automatically.</p>

        <?php if ($error): ?>
          <div class="form-notice error"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="form-panel">
          <form method="POST" action="../php/SaveCourse.php">

            <div class="form-row">
              <label for="title">Course Title</label>
              <input type="text" id="title" name="title" maxlength="180"
                     placeholder="e.g. Web Security Basics"
                     value="<?= e($old['title'] ?? '') ?>" required>
            </div>

            <div class="form-row">
              <label for="description">Description</label>
              <textarea id="description" name="description" rows="4"
                        placeholder="What will students learn in this course?"><?= e($old['description'] ?? '') ?></textarea>
            </div>

            <div class="form-row">
              <label for="category">Category</label>
              <select id="category" name="category">
                <?php
                $categories = ['Web Security', 'Development', 'Artificial Intelligence',
                               'Networking', 'Data Analysis', 'Cloud Computing'];
                $chosen = $old['category'] ?? '';
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
                     placeholder="0.00" value="<?= e($old['price'] ?? '0.00') ?>" required>
              <span class="form-hint">
                Enter 0 to make the course free students enrol without checkout.
                Any amount above 0 sends them through the purchase flow.
              </span>
            </div>
            <div class="form-row">
              <label for="youtube_url">YouTube Link</label>
              <input type="url" id="youtube_url" name="youtube_url"
                     placeholder="https://www.youtube.com/watch?v=..."
                     value="<?= e($old['youtube_url'] ?? '') ?>" required>
              <span class="form-hint">
                Paste the full link. Any YouTube format works watch, youtu.be, or embed.
                The thumbnail is generated from the video, so you don't upload one.
              </span>
            </div>

            <div class="form-row">
              <label for="status">Publish Status</label>
              <select id="status" name="status">
                <option value="draft" <?= ($old['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>
                  Draft only you can see it
                </option>
                <option value="published" <?= ($old['status'] ?? '') === 'published' ? 'selected' : '' ?> <?= $canPublish ? '' : 'disabled' ?>>
                  Published students can enrol<?= $canPublish ? '' : ' (awaiting admin approval)' ?>
                </option>
              </select>
            </div>

            <div class="form-actions">
              <a href="InstructorDashboard.php" class="btn-cancel">Cancel</a>
              <button type="submit" class="btn-submit">Create Course</button>
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