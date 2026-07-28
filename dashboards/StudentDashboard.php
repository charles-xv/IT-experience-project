<?php
require_once __DIR__ . '/../php/SessionGuard.php';
require_role('student');
$name = htmlspecialchars($_SESSION['full_name'] ?? 'Student');
$email = htmlspecialchars($_SESSION['email'] ?? 'student@example.com');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Dashboard - Mech Spec LMS</title>
  <link rel="stylesheet" href="../Index.css?v=<?= time() ?>">
  <link rel="stylesheet" href="Dashboard.css?v=<?= time() ?>">
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
        <a href="#" class="nav-item active">📚 My Learning</a>
        <a href="#" class="nav-item">🏆 Certificates</a>
        <a href="#" class="nav-item">💬 Discussions</a>
        <a href="#" class="nav-item">⚙️ Settings</a>
      </nav>
      <div class="sidebar-footer">
        <a href="#" class="logout-btn" onclick="document.getElementById('logoutModal').classList.add('open')">
          🚪 Log Out
        </a>
      </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-wrapper">
      
      <!-- TOP HEADER -->
      <header class="top-header">
        <div class="breadcrumbs">
          Dashboard <span>/ My Learning</span>
        </div>
        <div class="header-actions">
          <span class="dash-role-pill dash-role-student">Student Mode</span>
          <div class="user-profile">
            <div class="user-info" style="text-align: right;">
              <span class="user-name"><?= $name ?></span>
              <span class="user-email"><?= $email ?></span>
            </div>
            <div class="avatar"><?= substr($name, 0, 1) ?></div>
          </div>
        </div>
      </header>

      <!-- SCROLLABLE DASHBOARD -->
      <div class="dashboard-content">
        <h1 class="page-title">Welcome back, <?= explode(' ', $name)[0] ?>! 👋</h1>
        <p class="page-sub">You have completed 3 lessons this week. Keep up the great work.</p>

        <!-- TOP METRICS -->
        <div class="metrics-row">
          <div class="metric-card cyan">
            <span class="metric-label">Overall Progress</span>
            <span class="metric-value">75%</span>
          </div>
          <div class="metric-card cyan">
            <span class="metric-label">Completed Courses</span>
            <span class="metric-value">2</span>
          </div>
          <div class="metric-card cyan">
            <span class="metric-label">Certificates Earned</span>
            <span class="metric-value">1</span>
          </div>
        </div>

        <!-- ACTIVE COURSES GRID -->
        <h2 style="margin-bottom: 24px; font-size: 1.3rem;">Continue Learning</h2>
        <div class="course-grid">
          
          <div class="dash-course-card">
            <div class="course-img" style="background-image: url('https://images.unsplash.com/photo-1550751827-4bd374c3f58b?auto=format&fit=crop&q=80&w=800');">
              <div class="course-progress-overlay">
                <div class="course-progress-fill" style="width: 78%;"></div>
              </div>
            </div>
            <div class="course-body">
              <h3>Web Security Basics</h3>
              <p>Learn how to identify and patch common vulnerabilities like XSS and SQLi.</p>
              <a href="../Index.html" class="btn-block-cyan">Resume Lesson</a>
            </div>
          </div>

          <div class="dash-course-card">
            <div class="course-img" style="background-image: url('https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?auto=format&fit=crop&q=80&w=800'); filter: grayscale(100%);">
              <div class="course-progress-overlay">
                <div class="course-progress-fill" style="width: 15%;"></div>
              </div>
            </div>
            <div class="course-body">
              <h3>Advanced Cryptography</h3>
              <p>Deep dive into hashing algorithms and modern encryption protocols.</p>
              <a href="#" class="btn-block-cyan">Resume Lesson</a>
            </div>
          </div>

        </div>

      </div>
    </main>

  </div>

  <!-- LOGOUT MODAL -->
  <div class="logout-modal" id="logoutModal">
    <div class="logout-card">
      <h3>Log out?</h3>
      <p>You'll need to sign in again to get back into your dashboard.</p>
      <div class="logout-actions">
        <button class="logout-cancel" onclick="document.getElementById('logoutModal').classList.remove('open')">Cancel</button>
        <button class="logout-confirm" onclick="window.location.href='../php/Logout.php'">Log out</button>
      </div>
    </div>
  </div>

</body>
</html>
