<?php
require_once __DIR__ . '/../php/SessionGuard.php';
require_once __DIR__ . '/../php/Database.php';
require_once __DIR__ . '/../php/Helpers.php';
require_role('student');

log_page_visit($pdo, 'PurchaseSuccess');

$studentId = (int) $_SESSION['user_id'];
$name  = $_SESSION['full_name'] ?? 'Student';
$email = $_SESSION['email'] ?? '';

$reference = $_SESSION['purchase_reference'] ?? '';
unset($_SESSION['purchase_reference']);

// Reached directly rather than through a completed payment.
if ($reference === '') {
    header('Location: StudentDashboard.php');
    exit;
}

// The receipt is built from the Payments record, not from the session, so
// it always reflects what was actually stored and verified.
$stmt = $pdo->prepare(
    'SELECT order_reference, amount, email, card_last_four, status, verified_at
     FROM Payments
     WHERE order_reference = ? AND student_id = ? AND status = "successful"'
);
$stmt->execute([$reference, $studentId]);
$payment = $stmt->fetch();

if (!$payment) {
    header('Location: StudentDashboard.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT c.title, p.amount_paid
     FROM Purchases p
     JOIN Courses c ON c.course_id = p.course_id
     WHERE p.student_id = ? AND p.reference LIKE ?
     ORDER BY p.purchase_id'
);
$stmt->execute([$studentId, $reference . '%']);
$lines = $stmt->fetchAll();

$totalPaid = number_format((float) $payment['amount'], 2);
$count     = count($lines);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Purchase Complete - Mech Spec LMS</title>
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

    <main class="main-wrapper">

      <header class="top-header">
        <div class="breadcrumbs">Dashboard <span>/ Purchase Complete</span></div>
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
          <span class="receipt-tick">✓</span>
          <h1>Payment successful</h1>
          <p class="receipt-sub">
            You're enrolled in <?= $count ?> course<?= $count === 1 ? '' : 's' ?>. Start whenever you're ready.
          </p>

          <div class="receipt-card">
            <div class="receipt-head">
              <span>Receipt</span>
              <code><?= e($reference) ?></code>
            </div>

            <?php foreach ($lines as $l): ?>
              <div class="receipt-row">
                <span><?= e($l['title']) ?></span>
                <span>$<?= number_format((float) $l['amount_paid'], 2) ?></span>
              </div>
            <?php endforeach; ?>

            <div class="receipt-row total">
              <span>Total paid</span>
              <span>$<?= e($totalPaid) ?></span>
            </div>

            <div class="receipt-row">
              <span>Paid with</span>
              <span>Card ending <?= e($payment['card_last_four'] ?? '****') ?></span>
            </div>
            <div class="receipt-row">
              <span>Receipt sent to</span>
              <span><?= e($payment['email']) ?></span>
            </div>

            <?php if (!empty($lines)): ?>
              <div class="receipt-date">
                <?= e(date('d M Y, H:i', strtotime($payment['verified_at']))) ?>
              </div>
            <?php endif; ?>
          </div>

          <div class="receipt-actions">
            <a href="StudentDashboard.php" class="btn-submit">Go to My Learning</a>
            <a href="BrowseCourses.php" class="btn-cancel">Browse more courses</a>
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