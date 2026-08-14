<?php
// STAGE 2 OF 3 — the payment page.
//
// In a live integration this screen belongs to the gateway, on their domain,
// and the application never sees the card details at all. It is reproduced
// here so the workflow is complete and testable end to end.
//
// The card fields below are validated for shape and then discarded. Nothing
// beyond the last four digits is ever written to the database, because
// storing a full card number would put this application inside PCI-DSS scope.

require_once __DIR__ . '/../php/SessionGuard.php';
require_once __DIR__ . '/../php/Database.php';
require_once __DIR__ . '/../php/Helpers.php';
require_role('student');

log_page_visit($pdo, 'PaymentGateway');

$studentId = (int) $_SESSION['user_id'];
$reference = trim($_GET['ref'] ?? '');

$error = $_SESSION['gateway_error'] ?? '';
unset($_SESSION['gateway_error']);

if ($reference === '') {
    header('Location: Cart.php');
    exit;
}

// student_id in the WHERE clause is the ownership check — a reference
// belonging to someone else simply returns no row.
$stmt = $pdo->prepare(
    'SELECT payment_id, order_reference, amount, email, status
     FROM Payments
     WHERE order_reference = ? AND student_id = ?'
);
$stmt->execute([$reference, $studentId]);
$payment = $stmt->fetch();

if (!$payment) {
    log_security_event($pdo, 'access_denied', $studentId, "Payment page opened with unknown reference");
    $_SESSION['cart_error'] = 'That payment session could not be found.';
    header('Location: Cart.php');
    exit;
}

// A transaction that has already been decided must not be payable again.
if ($payment['status'] !== 'pending') {
    $_SESSION['cart_error'] = 'That payment has already been processed.';
    header('Location: Cart.php');
    exit;
}

// The lines being paid for, shown so the buyer can confirm before committing.
$stmt = $pdo->prepare(
    'SELECT c.title, c.price
     FROM CartItems ci
     JOIN Courses c ON c.course_id = ci.course_id
     WHERE ci.student_id = ? AND c.status = "published"'
);
$stmt->execute([$studentId]);
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Secure Payment - Mech Spec LMS</title>
  <link rel="stylesheet" href="../Index.css">
  <link rel="stylesheet" href="Dashboard.css">
  <link rel="stylesheet" href="../LoadingBar.css">
</head>
<body class="gateway-body">

  <div class="gateway-shell">

    <div class="gateway-head">
      <span class="gateway-brand">
        <span class="brand-mark">M</span> Mech Spec Payments
      </span>
      <span class="gateway-secure">Secure checkout</span>
    </div>

    <div class="gateway-sim">
      <strong>Simulated gateway</strong>
      <span>
        This screen stands in for the payment provider's hosted page. No real
        payment is taken and no card details are stored. Use the test buttons
        below to produce a successful or a declined transaction.
      </span>
    </div>

    <?php if ($error): ?>
      <div class="form-notice error"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="gateway-amount">
      <span>Amount due</span>
      <strong>$<?= number_format((float) $payment['amount'], 2) ?></strong>
    </div>

    <div class="gateway-meta">
      <div><span>Reference</span><code><?= e($payment['order_reference']) ?></code></div>
      <div><span>Receipt to</span><span><?= e($payment['email']) ?></span></div>
    </div>

    <ul class="gateway-items">
      <?php foreach ($items as $it): ?>
        <li>
          <span><?= e($it['title']) ?></span>
          <span>$<?= number_format((float) $it['price'], 2) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>

    <form method="POST" action="../php/VerifyPayment.php" class="gateway-form">
      <input type="hidden" name="reference" value="<?= e($payment['order_reference']) ?>">

      <div class="form-row">
        <label for="card_name">Name on card</label>
        <input type="text" id="card_name" name="card_name" placeholder="John Doe" required>
      </div>

      <div class="form-row">
        <label for="card_number">Card number</label>
        <input type="text" id="card_number" name="card_number" inputmode="numeric"
               maxlength="19" placeholder="4242 4242 4242 4242" required>
        <span class="form-hint">Any 12 to 19 digits. Only the last four are ever stored.</span>
      </div>

      <div class="checkout-split">
        <div class="form-row">
          <label for="card_expiry">Expiry</label>
          <input type="text" id="card_expiry" name="card_expiry" placeholder="12/28" maxlength="5" required>
        </div>
        <div class="form-row">
          <label for="card_cvc">CVC</label>
          <input type="password" id="card_cvc" name="card_cvc" placeholder="123" maxlength="4" required>
        </div>
      </div>

      <!-- Two submit buttons posting different values, so both branches of the
           workflow can be demonstrated. A live gateway decides this itself. -->
      <div class="gateway-actions">
        <button type="submit" name="outcome" value="success" class="btn-submit">
          Pay $<?= number_format((float) $payment['amount'], 2) ?>
        </button>
        <button type="submit" name="outcome" value="decline" class="btn-decline">
          Simulate declined payment
        </button>
      </div>

      <a href="Cart.php" class="gateway-cancel">Cancel and return to cart</a>
    </form>

  </div>

  <script src="../LoadingBar.js"></script>
</body>
</html>