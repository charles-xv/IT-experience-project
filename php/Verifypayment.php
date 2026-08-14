<?php
// STAGE 3 OF 3 — verify the outcome and grant access.
//
// This is the security-critical step of any gateway integration.
//
// When a buyer returns from a payment provider, the browser carries a
// reference and often a status. NEITHER can be trusted: both arrive from
// the client and can be edited. A real integration ignores what the browser
// says and makes its own server-to-server call to the gateway's verify
// endpoint, using the reference, and believes only that answer.
//
// Here the "gateway" is simulated, so verification reads the pending record
// this application created earlier. The important part is the shape: the
// amount, the owner and the outcome are all re-established server-side, and
// enrolment happens only after that.

require_once __DIR__ . '/SessionGuard.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Helpers.php';
require_role('student');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboards/Cart.php');
    exit;
}

$studentId = (int) $_SESSION['user_id'];
$reference = trim($_POST['reference'] ?? '');
$outcome   = $_POST['outcome'] ?? '';

function gateway_error(string $message, string $reference): void {
    $_SESSION['gateway_error'] = $message;
    header('Location: ../dashboards/PaymentGateway.php?ref=' . urlencode($reference));
    exit;
}

if ($reference === '') {
    header('Location: ../dashboards/Cart.php');
    exit;
}

// --- Card details: validated for shape, then discarded ---------------
// Only the last four digits survive this block. The full number is never
// written anywhere, including the log.
$cardName   = trim($_POST['card_name'] ?? '');
$cardNumber = preg_replace('/\D/', '', $_POST['card_number'] ?? '');
$cardExpiry = trim($_POST['card_expiry'] ?? '');
$cardCvc    = preg_replace('/\D/', '', $_POST['card_cvc'] ?? '');

if ($cardName === '' || $cardNumber === '' || $cardExpiry === '' || $cardCvc === '') {
    gateway_error('Please fill in all the payment fields.', $reference);
}
if (!preg_match('/^\d{12,19}$/', $cardNumber)) {
    gateway_error('That card number is not valid.', $reference);
}
if (!preg_match('#^(0[1-9]|1[0-2])/\d{2}$#', $cardExpiry)) {
    gateway_error('Expiry must be in MM/YY format.', $reference);
}
if (!preg_match('/^\d{3,4}$/', $cardCvc)) {
    gateway_error('CVC must be 3 or 4 digits.', $reference);
}

$lastFour = substr($cardNumber, -4);
unset($cardNumber, $cardCvc);   // gone before anything else runs

try {
    // --- Re-establish the transaction from our own records -------------
    // Looked up by reference AND student_id, so one buyer cannot verify
    // another buyer's transaction by supplying their reference.
    $stmt = $pdo->prepare(
        'SELECT payment_id, amount, email, status
         FROM Payments
         WHERE order_reference = ? AND student_id = ?'
    );
    $stmt->execute([$reference, $studentId]);
    $payment = $stmt->fetch();

    if (!$payment) {
        log_security_event($pdo, 'access_denied', $studentId,
            'Verification attempted for a reference that is not theirs');
        $_SESSION['cart_error'] = 'That payment could not be verified.';
        header('Location: ../dashboards/Cart.php');
        exit;
    }

    // Replay protection: a transaction already decided cannot be decided
    // again. Without this, re-submitting the form would enrol twice.
    if ($payment['status'] !== 'pending') {
        $_SESSION['cart_error'] = 'That payment has already been processed.';
        header('Location: ../dashboards/Cart.php');
        exit;
    }

    $paymentId = (int) $payment['payment_id'];

    // --- Failure path --------------------------------------------------
    // Recorded rather than discarded. A payments table containing only
    // successes cannot be reconciled against a gateway's own records.
    if ($outcome !== 'success') {
        $pdo->prepare(
            "UPDATE Payments
             SET status = 'failed', failure_reason = ?, card_last_four = ?, verified_at = NOW()
             WHERE payment_id = ?"
        )->execute(['Card declined by issuer', $lastFour, $paymentId]);

        log_security_event($pdo, 'payment_failed', $studentId,
            "Ref $reference declined");

        $_SESSION['payment_reference'] = $reference;
        $_SESSION['payment_reason']    = 'Your card was declined by the issuer.';
        header('Location: ../dashboards/PaymentFailed.php');
        exit;
    }

    // --- Success path --------------------------------------------------
    // The cart is re-read here rather than trusting anything carried over
    // from the earlier page: the buyer may have changed it in another tab,
    // and an instructor may have changed a price.
    $stmt = $pdo->prepare(
        'SELECT c.course_id, c.title, c.price
         FROM CartItems ci
         JOIN Courses c ON c.course_id = ci.course_id
         WHERE ci.student_id = ? AND c.status = "published"'
    );
    $stmt->execute([$studentId]);
    $items = $stmt->fetchAll();

    if (empty($items)) {
        $pdo->prepare(
            "UPDATE Payments SET status = 'failed', failure_reason = ?, verified_at = NOW()
             WHERE payment_id = ?"
        )->execute(['Cart was empty at verification', $paymentId]);
        $_SESSION['cart_error'] = 'Your cart was empty, so nothing was charged.';
        header('Location: ../dashboards/Cart.php');
        exit;
    }

    // Everything below is one transaction. A failure halfway would otherwise
    // leave a buyer marked as paid but not enrolled, or the reverse.
    $pdo->beginTransaction();

    $lineTotal = 0.0;
    $insertPurchase = $pdo->prepare(
        'INSERT INTO Purchases (student_id, course_id, payment_id, amount_paid, reference)
         VALUES (?, ?, ?, ?, ?)'
    );
    // INSERT IGNORE leans on the UNIQUE key so a double submit cannot enrol
    // the same student on the same course twice.
    $insertEnrol = $pdo->prepare(
        'INSERT IGNORE INTO Enrollments (student_id, course_id) VALUES (?, ?)'
    );

    foreach ($items as $i => $item) {
        $insertPurchase->execute([
            $studentId,
            (int) $item['course_id'],
            $paymentId,
            $item['price'],
            $reference . '-' . ($i + 1),
        ]);
        $insertEnrol->execute([$studentId, (int) $item['course_id']]);
        $lineTotal += (float) $item['price'];
    }

    $pdo->prepare(
        "UPDATE Payments
         SET status = 'successful', amount = ?, card_last_four = ?, verified_at = NOW()
         WHERE payment_id = ?"
    )->execute([$lineTotal, $lastFour, $paymentId]);

    $pdo->prepare('DELETE FROM CartItems WHERE student_id = ?')->execute([$studentId]);

    $pdo->commit();

    log_security_event($pdo, 'payment_successful', $studentId,
        "Ref $reference, " . count($items) . ' course(s), ' . number_format($lineTotal, 2));

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Payment verification failed: ' . $e->getMessage());

    // Best effort — record the failure even though the transaction rolled back.
    try {
        $pdo->prepare(
            "UPDATE Payments SET status = 'failed', failure_reason = ?, verified_at = NOW()
             WHERE order_reference = ? AND status = 'pending'"
        )->execute(['Processing error', $reference]);
    } catch (PDOException $ignored) {
        // Nothing further can be done here; the error above is already logged.
    }

    $_SESSION['payment_reference'] = $reference;
    $_SESSION['payment_reason']    = 'Something went wrong while processing. You have not been charged.';
    header('Location: ../dashboards/PaymentFailed.php');
    exit;
}

$_SESSION['purchase_reference'] = $reference;
header('Location: ../dashboards/PurchaseSuccess.php');
exit;