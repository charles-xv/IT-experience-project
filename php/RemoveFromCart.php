<?php
// Removes one course from the student's cart.

require_once __DIR__ . '/SessionGuard.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Helpers.php';
require_role('student');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboards/Cart.php');
    exit;
}

$studentId = (int) $_SESSION['user_id'];
$courseId  = (int) ($_POST['course_id'] ?? 0);

try {
    // student_id in the WHERE clause is the ownership check — a tampered
    // course_id can only ever delete from the requester's own cart.
    $pdo->prepare('DELETE FROM CartItems WHERE student_id = ? AND course_id = ?')
        ->execute([$studentId, $courseId]);
    $_SESSION['cart_success'] = 'Removed from your cart.';
} catch (PDOException $e) {
    error_log('Remove from cart failed: ' . $e->getMessage());
    $_SESSION['cart_error'] = 'Something went wrong.';
}

header('Location: ../dashboards/Cart.php');
exit;