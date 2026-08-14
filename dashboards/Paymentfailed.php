<?php
require_once __DIR__ . '/../php/SessionGuard.php';
require_once __DIR__ . '/../php/Database.php';
require_once __DIR__ . '/../php/Helpers.php';
require_role('student');

log_page_visit($pdo, 'PaymentFailed');

$studentId = (int) $_SESSION['user_id'];
$name  = $_SESSION['full_name'] ?? 'Student';
$email = $_SESSION['email'] ?? '';

$reference = $_SESSION['payment_reference'] ?? '';
$reason    = $_SESSION['payment_reason'] ?? 'The payment could not be completed.';
unset($_SESSION['payment_reference'], $_SESSION['payment_reason']);

// Reached directly rather than through a failed payment — nothing to show.
if ($reference === '') {
    header('Location: Cart.php');
    exit;
}

// Read back from the record rather than the session, so the page reflects
// what was actually stored.
$stmt = $pdo->prepare(
    'SELECT order_reference, amount, status, failure_reason, created_at
     FROM Payments WHERE order_reference = ? AND student_id = ?'
);
$stmt->execute([$reference, $studentId]);
$payment = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment Failed - Mech Spec LMS</title>
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
        <a href="BrowseCourses.php" class="nav-item"><?= ui_icon('search') ?><span class="nav-label">Browse Courses</span></a>
        <a href="Cart.php" class="nav-item active"><?= ui_icon('cart') ?><span class="nav-label">Cart</span></a>
        <a href="Certificates.php" class="nav-item"><?= ui_icon('award') ?><span class="nav-label">Certificates</span></a>
        <a href="Settings.php" class="nav-item"><?= ui_icon('settings') ?><span class="nav-label">Settings</span></a>
      </nav>
      <div class="sidebar-footer">
        <a href="#" class="logout-btn" id="logoutTrigger"><?= ui_icon('logout') ?><span class="logout-label">Log Out</span></a>
      </div>
    </aside>

    <main class="main-wrapper">

      <header class="top-header">
        <div class="breadcrumbs">Dashboard <span>/ Payment Failed</span></div>
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
        <div class="receipt">
          <span class="receipt-cross">&#10005;</span>
          <h1>Payment not completed</h1>
          <p class="receipt-sub"><?= e($reason) ?></p>

          <?php if ($payment): ?>
            <div class="receipt-card">
              <div class="receipt-head">
                <span>Transaction</span>
                <code><?= e($payment['order_reference']) ?></code>
              </div>
              <div class="receipt-row">
                <span>Amount</span>
                <span>$<?= number_format((float) $payment['amount'], 2) ?></span>
              </div>
              <div class="receipt-row">
                <span>Status</span>
                <span class="status-badge status-suspended"><?= ucfirst(e($payment['status'])) ?></span>
              </div>
              <?php if (!empty($payment['failure_reason'])): ?>
                <div class="receipt-row">
                  <span>Reason</span>
                  <span><?= e($payment['failure_reason']) ?></span>
                </div>
              <?php endif; ?>
              <div class="receipt-date">
                <?= e(date('d M Y, H:i', strtotime($payment['created_at']))) ?>
              </div>
            </div>
          <?php endif; ?>

          <p class="receipt-note">
            Your cart has not been changed and nothing has been charged. You can
            try again with the same or a different card.
          </p>

          <div class="receipt-actions">
            <a href="Checkout.php" class="btn-submit">Try again</a>
            <a href="Cart.php" class="btn-cancel">Back to cart</a>
          </div>
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