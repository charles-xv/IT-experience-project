<?php
// STAGE 1 OF 3 — start a transaction.
//
// Mirrors how a real gateway (Paystack, Flutterwave, Stripe) is used:
// the application creates a PENDING transaction and generates its own
// reference BEFORE sending the buyer to the payment page. That reference
// is what lets us look the attempt up again on the way back, instead of
// trusting whatever the browser hands us.

require_once __DIR__ . '/SessionGuard.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Helpers.php';
require_role('student');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboards/Cart.php');
    exit;
}

$studentId = (int) $_SESSION['user_id'];

function back(string $message, string $page = 'Checkout'): void {
    $_SESSION['checkout_error'] = $message;
    header("Location: ../dashboards/$page.php");
    exit;
}

// The email is pre-filled from the session but editable, because a gateway
// sends the receipt here and the buyer may want a different address.
$email = trim($_POST['email'] ?? '');
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    back('Please enter a valid email address for your receipt.');
}

try {
    // The amount is calculated here, from the database. It is never taken
    // from the form — a total posted by the browser can be edited before
    // it is sent, and a gateway would happily charge whatever it was told.
    $stmt = $pdo->prepare(
        'SELECT c.course_id, c.title, c.price
         FROM CartItems ci
         JOIN Courses c ON c.course_id = ci.course_id
         WHERE ci.student_id = ? AND c.status = "published"'
    );
    $stmt->execute([$studentId]);
    $items = $stmt->fetchAll();

    if (empty($items)) {
        back('Your cart is empty.', 'Cart');
    }

    $total = 0.0;
    foreach ($items as $it) {
        $total += (float) $it['price'];
    }

    if ($total <= 0) {
        back('There is nothing to pay for. Free courses enrol directly.', 'Cart');
    }

    // Any earlier pending attempt by this student is marked abandoned rather
    // than deleted. Abandoned attempts are a real metric — they show where
    // buyers drop out — and deleting them would hide that.
    $pdo->prepare(
        "UPDATE Payments SET status = 'abandoned'
         WHERE student_id = ? AND status = 'pending'"
    )->execute([$studentId]);

    // Reference format mirrors what a gateway expects: a unique, opaque,
    // non-sequential string. Sequential ids would let anyone guess another
    // buyer's reference.
    $reference = 'MS-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(5)));

    $pdo->prepare(
        'INSERT INTO Payments (student_id, order_reference, amount, email, gateway, status)
         VALUES (?, ?, ?, ?, ?, "pending")'
    )->execute([$studentId, $reference, $total, $email, 'simulated']);

    log_security_event($pdo, 'payment_initiated', $studentId,
        'Ref ' . $reference . ', ' . count($items) . ' item(s), ' . number_format($total, 2));

} catch (PDOException $e) {
    error_log('Payment initiation failed: ' . $e->getMessage());
    back('Could not start the payment. Please try again.');
}

// A real integration would redirect to the gateway's hosted page here.
// This redirects to the simulated one, which plays the same role.
header('Location: ../dashboards/PaymentGateway.php?ref=' . urlencode($reference));
exit;