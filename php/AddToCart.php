<?php
// Adds a paid course to the student's cart.
// Free courses skip the cart entirely and enrol immediately.

require_once __DIR__ . '/SessionGuard.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Helpers.php';
require_role('student');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboards/BrowseCourses.php');
    exit;
}

$studentId = (int) $_SESSION['user_id'];
$courseId  = (int) ($_POST['course_id'] ?? 0);

function back(string $key, string $message): void {
    $_SESSION[$key] = $message;
    header('Location: ../dashboards/BrowseCourses.php');
    exit;
}

if ($courseId <= 0) {
    back('enrol_error', 'Invalid course.');
}

try {
    // Only published courses. Checking here rather than trusting the button
    // means a crafted request cannot add an unreleased draft to a cart.
    $stmt = $pdo->prepare('SELECT title, price FROM Courses WHERE course_id = ? AND status = "published"');
    $stmt->execute([$courseId]);
    $course = $stmt->fetch();

    if (!$course) {
        back('enrol_error', 'That course is not available.');
    }

    // Already enrolled? Nothing to buy.
    $check = $pdo->prepare('SELECT enrollment_id FROM Enrollments WHERE student_id = ? AND course_id = ?');
    $check->execute([$studentId, $courseId]);
    if ($check->fetch()) {
        back('enrol_error', 'You are already enrolled in that course.');
    }

    // A free course has nothing to check out, so enrol straight away.
    if ((float) $course['price'] <= 0) {
        $pdo->prepare('INSERT INTO Enrollments (student_id, course_id) VALUES (?, ?)')
            ->execute([$studentId, $courseId]);
        log_security_event($pdo, 'course_enrolled', $studentId, "Free enrolment in course #$courseId");
        back('enrol_success', 'You are now enrolled in "' . $course['title'] . '".');
    }

    // The UNIQUE key makes a duplicate cart row impossible at the database
    // level; this just produces a readable message instead of a raw error.
    $inCart = $pdo->prepare('SELECT cart_item_id FROM CartItems WHERE student_id = ? AND course_id = ?');
    $inCart->execute([$studentId, $courseId]);
    if ($inCart->fetch()) {
        back('enrol_error', 'That course is already in your cart.');
    }

    $pdo->prepare('INSERT INTO CartItems (student_id, course_id) VALUES (?, ?)')
        ->execute([$studentId, $courseId]);

} catch (PDOException $e) {
    error_log('Add to cart failed: ' . $e->getMessage());
    back('enrol_error', 'Something went wrong. Please try again.');
}

$_SESSION['enrol_success'] = '"' . $course['title'] . '" added to your cart.';
header('Location: ../dashboards/Cart.php');
exit;