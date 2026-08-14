<?php
require_once __DIR__ . '/../php/SessionGuard.php';
require_once __DIR__ . '/../php/Database.php';
require_once __DIR__ . '/../php/Helpers.php';
require_role('student');

log_page_visit($pdo, 'Checkout');

$studentId = (int) $_SESSION['user_id'];
$name  = $_SESSION['full_name'] ?? 'Student';
$email = $_SESSION['email'] ?? '';

$error = $_SESSION['checkout_error'] ?? '';
unset($_SESSION['checkout_error']);

$stmt = $pdo->prepare(
    'SELECT c.course_id, c.title, c.price
     FROM CartItems ci
     JOIN Courses c ON c.course_id = ci.course_id
     WHERE ci.student_id = ? AND c.status = "published"
     ORDER BY ci.added_at DESC'
);
$stmt->execute([$studentId]);
$items = $stmt->fetchAll();

// Nothing to pay for — send them back rather than showing an empty form.
if (empty($items)) {
    $_SESSION['cart_error'] = 'Your cart is empty.';
    header('Location: Cart.php');
    exit;
}

$total = 0.0;
foreach ($items as $it) { $total += (float) $it['price']; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Checkout - Mech Spec LMS</title>
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
        <div class="breadcrumbs">Dashboard <span>/ Checkout</span></div>
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
        <a href="Cart.php" class="back-link">← Back to cart</a>
        <h1 class="page-title">Checkout</h1>
        <p class="page-sub">Review your order and complete the payment.</p>

        <?php if ($error): ?>
          <div class="form-notice error"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="checkout-banner">
          <span class="checkout-banner-ico">ℹ</span>
          <div>
            <strong>Simulated payment</strong>
            <p>You will be taken to a payment page to enter your card. No real payment is taken, and no card details are stored beyond the last four digits.</p>
          </div>
        </div>

        <div class="cart-layout">

          <div class="form-panel">
            <h2 class="section-heading">Confirm Your Details</h2>
            <form method="POST" action="../php/InitiatePayment.php">

              <div class="form-row">
                <label for="email">Email for receipt</label>
                <input type="email" id="email" name="email"
                       value="<?= e($_SESSION['email'] ?? '') ?>"
                       maxlength="190" required>
                <span class="form-hint">
                  Pre-filled from your account. Payment providers require an email
                  to identify the payer and send the receipt — change it here if
                  you would rather the receipt went elsewhere.
                </span>
              </div>

              <div class="checkout-steps">
                <div class="checkout-step done"><span>1</span> Review order</div>
                <div class="checkout-step current"><span>2</span> Confirm details</div>
                <div class="checkout-step"><span>3</span> Pay securely</div>
              </div>

              <div class="form-actions">
                <button type="submit" class="btn-submit">Continue to payment &rarr;</button>
              </div>
            </form>
          </div>

          <aside class="cart-summary">
            <h3>Order Summary</h3>
            <?php foreach ($items as $it): ?>
              <div class="cart-summary-row">
                <span><?= e($it['title']) ?></span>
                <span>$<?= number_format((float) $it['price'], 2) ?></span>
              </div>
            <?php endforeach; ?>
            <div class="cart-summary-row total">
              <span>Total</span><span>$<?= number_format($total, 2) ?></span>
            </div>
            <p class="cart-note">You'll be enrolled immediately after payment.</p>
          </aside>

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