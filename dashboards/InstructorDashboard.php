<?php
require_once __DIR__ . '/../php/SessionGuard.php';
require_role('instructor');
$name = htmlspecialchars($_SESSION['full_name'] ?? 'Instructor');
$email = htmlspecialchars($_SESSION['email'] ?? 'instructor@example.com');
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
    
    <!-- SIDEBAR -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <span class="brand-mark">M</span>
        <span>Mech Spec <span class="dash-gold">LMS</span></span>
      </div>
      <nav class="sidebar-nav">
        <a href="#" class="nav-item active">📊 Overview</a>
        <a href="#" class="nav-item">📁 Course Manager</a>
        <a href="#" class="nav-item">👥 Students</a>
        <a href="#" class="nav-item">💳 Payouts</a>
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
          Dashboard <span>/ Overview</span>
        </div>
        <div class="header-actions">
          <span class="dash-role-pill dash-role-instructor">Instructor Mode</span>
          <div class="user-profile">
            <div class="user-info" style="text-align: right;">
              <span class="user-name"><?= $name ?></span>
              <span class="user-email"><?= $email ?></span>
            </div>
            <div class="avatar" style="background: linear-gradient(135deg, var(--gold) 0%, #ca8a04 100%);"><?= substr($name, 0, 1) ?></div>
          </div>
        </div>
      </header>

      <!-- SCROLLABLE DASHBOARD -->
      <div class="dashboard-content">
        <h1 class="page-title">Welcome back, <?= explode(' ', $name)[0] ?>! 📈</h1>
        <p class="page-sub">Here is what's happening with your courses today.</p>

        <!-- TOP METRICS (Analytics Row) -->
        <div class="metrics-row">
          <div class="metric-card gold">
            <span class="metric-label">Total Students</span>
            <span class="metric-value">1,248</span>
          </div>
          <div class="metric-card gold">
            <span class="metric-label">Active Courses</span>
            <span class="metric-value">4</span>
          </div>
          <div class="metric-card gold">
            <span class="metric-label">Average Rating</span>
            <span class="metric-value">4.8 <span style="font-size:1rem; color:var(--gold);">★</span></span>
          </div>
          <div class="metric-card emerald">
            <span class="metric-label">Simulated Revenue</span>
            <span class="metric-value" style="color: var(--emerald-accent);">$8,450</span>
          </div>
        </div>

        <!-- COURSE MANAGER TABLE -->
        <h2 style="margin-bottom: 24px; font-size: 1.3rem;">Course Manager</h2>
        
        <div class="table-widget">
          <div class="table-header">
            <h3>Your Courses</h3>
            <button class="btn-small">+ Create New Course</button>
          </div>
          <table>
            <thead>
              <tr>
                <th>Course Name</th>
                <th>Status</th>
                <th>Students</th>
                <th>Price</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><strong>Web Security Basics</strong></td>
                <td><span class="status-badge status-published">Published</span></td>
                <td>842</td>
                <td>Free</td>
                <td><a href="#" style="color:var(--cyan); text-decoration:none; font-size:0.9rem;">Edit</a></td>
              </tr>
              <tr>
                <td><strong>Advanced Cryptography</strong></td>
                <td><span class="status-badge status-published">Published</span></td>
                <td>406</td>
                <td>$49.99</td>
                <td><a href="#" style="color:var(--cyan); text-decoration:none; font-size:0.9rem;">Edit</a></td>
              </tr>
              <tr>
                <td><strong>Network Penetration Testing</strong></td>
                <td><span class="status-badge status-draft">Draft</span></td>
                <td>0</td>
                <td>$89.99</td>
                <td><a href="#" style="color:var(--cyan); text-decoration:none; font-size:0.9rem;">Edit</a></td>
              </tr>
            </tbody>
          </table>
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
