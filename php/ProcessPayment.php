<?php
// Simulated checkout. No live payment gateway — the card fields are never
// stored or transmitted anywhere, and no real charge occurs. What this does
// demonstrate is the transactional side: several rows have to be written
// together, and either all of them succeed or none of them do.

require_once __DIR__ . '/SessionGuard.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Helpers.php';
require_role('student');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboards/Cart.php');
    exit;
}

$studentId = (int) $_SESSION['user_id'];

function back(string $key, string $message, string $page = 'Checkout'): void {
    $_SESSION[$key] = $message;
    header("Location: ../dashboards/$page.php");
    exit;
}

// The card fields exist only to make the flow realistic. They are validated
// for shape and then discarded — never written to the database, never logged.
$cardName   = trim($_POST['card_name'] ?? '');
$cardNumber = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');

if ($cardName === '' || $cardNumber === '') {
    back('checkout_error', 'Please fill in all payment fields.');
}
if (!preg_match('/^\d{12,19}$/', $cardNumber)) {
    back('checkout_error', 'Please enter a valid card number (12 to 19 digits).');
}

try {
    // Prices come from the database, never from the form. If the total were
    // posted by the browser, a buyer could edit it to zero before submitting.
    $stmt = $pdo->prepare(
        'SELECT c.course_id, c.title, c.price
         FROM CartItems ci
         JOIN Courses c ON c.course_id = ci.course_id
         WHERE ci.student_id = ? AND c.status = "published"'
    );
    $stmt->execute([$studentId]);
    $items = $stmt->fetchAll();

    if (empty($items)) {
        back('cart_error', 'Your cart is empty.', 'Cart');
    }

    // Everything below is one transaction: purchases recorded, students
    // enrolled, cart cleared. A failure halfway would otherwise leave someone
    // charged but not enrolled, or enrolled without a purchase record.
    $pdo->beginTransaction();

    $total = 0.0;
    $insertPurchase = $pdo->prepare(
        'INSERT INTO Purchases (student_id, course_id, amount_paid, reference)
         VALUES (?, ?, ?, ?)'
    );
    // INSERT IGNORE leans on the UNIQUE key so a double submit cannot enrol
    // the same student twice.
    $insertEnrol = $pdo->prepare(
        'INSERT IGNORE INTO Enrollments (student_id, course_id) VALUES (?, ?)'
    );

    // One reference for the whole order, shown on the receipt.
    $reference = 'MS-PAY-' . strtoupper(bin2hex(random_bytes(4)));

    foreach ($items as $i => $item) {
        // Each row needs its own unique reference, so the order reference is
        // suffixed per line.
        $lineRef = $reference . '-' . ($i + 1);
        $insertPurchase->execute([
            $studentId,
            (int) $item['course_id'],
            $item['price'],
            $lineRef,
        ]);
        $insertEnrol->execute([$studentId, (int) $item['course_id']]);
        $total += (float) $item['price'];
    }

    $pdo->prepare('DELETE FROM CartItems WHERE student_id = ?')->execute([$studentId]);

    $pdo->commit();

    log_security_event($pdo, 'purchase_completed', $studentId,
        count($items) . ' course(s), total ' . number_format($total, 2) . ", ref $reference");

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Payment processing failed: ' . $e->getMessage());
    back('checkout_error', 'Something went wrong processing your payment. Nothing was charged.');
}

$_SESSION['purchase_reference'] = $reference;
$_SESSION['purchase_total']     = number_format($total, 2);
$_SESSION['purchase_count']     = count($items);
header('Location: ../dashboards/PurchaseSuccess.php');
exit;