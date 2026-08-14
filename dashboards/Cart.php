<?php
require_once __DIR__ . '/../php/SessionGuard.php';
require_once __DIR__ . '/../php/Database.php';
require_once __DIR__ . '/../php/Helpers.php';
require_role('student');

log_page_visit($pdo, 'Cart');

$studentId = (int) $_SESSION['user_id'];
$name  = $_SESSION['full_name'] ?? 'Student';
$email = $_SESSION['email'] ?? '';

$notice      = $_SESSION['cart_success'] ?? '';
$noticeError = $_SESSION['cart_error'] ?? '';
$fromBrowse  = $_SESSION['enrol_success'] ?? '';
unset($_SESSION['cart_success'], $_SESSION['cart_error'], $_SESSION['enrol_success']);

// Prices are read live so the cart always reflects the current price, and
// only published courses are billable.
$stmt = $pdo->prepare(
    'SELECT c.course_id, c.title, c.category, c.thumbnail_url, c.price,
            u.full_name AS instructor_name
     FROM CartItems ci
     JOIN Courses c ON c.course_id = ci.course_id
     JOIN Users   u ON u.user_id   = c.instructor_id
     WHERE ci.student_id = ? AND c.status = "published"
     ORDER BY ci.added_at DESC'
);
$stmt->execute([$studentId]);
$items = $stmt->fetchAll();

$total = 0.0;
foreach ($items as $it) { $total += (float) $it['price']; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cart - Mech Spec LMS</title>
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
        <div class="breadcrumbs">Dashboard <span>/ Cart</span></div>
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
        <h1 class="page-title">Your Cart</h1>
        <p class="page-sub">
          <?php if (empty($items)): ?>
            Your cart is empty.
          <?php else: ?>
            <?= count($items) ?> course<?= count($items) === 1 ? '' : 's' ?> ready for checkout.
          <?php endif; ?>
        </p>

        <?php if ($fromBrowse): ?>
          <div class="form-notice success"><?= e($fromBrowse) ?></div>
        <?php endif; ?>
        <?php if ($notice): ?>
          <div class="form-notice success"><?= e($notice) ?></div>
        <?php endif; ?>
        <?php if ($noticeError): ?>
          <div class="form-notice error"><?= e($noticeError) ?></div>
        <?php endif; ?>

        <?php if (empty($items)): ?>
          <div class="empty-state">
            <span class="empty-icon"><?= ui_icon('cart') ?></span>
            <h3>Nothing in your cart</h3>
            <p>Browse the catalogue and add a course to get started.</p>
            <a href="BrowseCourses.php" class="btn-block-cyan empty-action">Browse Courses</a>
          </div>
        <?php else: ?>
          <div class="cart-layout">

            <div class="cart-items">
              <?php foreach ($items as $it): ?>
                <div class="cart-row">
                  <div class="cart-thumb">
                    <?php if (!empty($it['thumbnail_url'])): ?>
                      <img src="<?= e($it['thumbnail_url']) ?>" alt="<?= e($it['title']) ?>">
                    <?php else: ?>
                      <span class="course-thumb-placeholder"><?= ui_icon('book') ?></span>
                    <?php endif; ?>
                  </div>
                  <div class="cart-info">
                    <h3><?= e($it['title']) ?></h3>
                    <span class="cart-meta"><?= e($it['instructor_name']) ?><?= $it['category'] ? ' · ' . e($it['category']) : '' ?></span>
                  </div>
                  <div class="cart-price">$<?= number_format((float) $it['price'], 2) ?></div>
                  <form method="POST" action="../php/RemoveFromCart.php" class="row-form">
                    <input type="hidden" name="course_id" value="<?= (int) $it['course_id'] ?>">
                    <button type="submit" class="row-btn danger">Remove</button>
                  </form>
                </div>
              <?php endforeach; ?>
            </div>

            <aside class="cart-summary">
              <h3>Order Summary</h3>
              <div class="cart-summary-row">
                <span>Courses</span><span><?= count($items) ?></span>
              </div>
              <div class="cart-summary-row">
                <span>Subtotal</span><span>$<?= number_format($total, 2) ?></span>
              </div>
              <div class="cart-summary-row total">
                <span>Total</span><span>$<?= number_format($total, 2) ?></span>
              </div>
              <a href="Checkout.php" class="btn-block-cyan cart-checkout">Proceed to Checkout</a>
              <a href="BrowseCourses.php" class="cart-continue">Continue browsing</a>
            </aside>

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