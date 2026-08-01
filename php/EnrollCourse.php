<?php
// Enrols the logged-in student in a course.

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
    // Only published courses can be enrolled in. Checking here rather than
    // trusting the button means a crafted request can't enrol someone in a
    // draft the instructor hasn't released.
    $stmt = $pdo->prepare('SELECT title FROM Courses WHERE course_id = ? AND status = "published"');
    $stmt->execute([$courseId]);
    $course = $stmt->fetch();

    if (!$course) {
        back('enrol_error', 'That course is not available.');
    }

    // The UNIQUE key on (student_id, course_id) makes a duplicate enrolment
    // impossible at the database level; this just gives a friendly message
    // instead of a raw constraint error.
    $check = $pdo->prepare('SELECT enrollment_id FROM Enrollments WHERE student_id = ? AND course_id = ?');
    $check->execute([$studentId, $courseId]);
    if ($check->fetch()) {
        back('enrol_error', 'You are already enrolled in that course.');
    }

    $pdo->prepare('INSERT INTO Enrollments (student_id, course_id) VALUES (?, ?)')
        ->execute([$studentId, $courseId]);

    log_security_event($pdo, 'course_enrolled', $studentId, "Enrolled in course #$courseId");

} catch (PDOException $e) {
    error_log('Enrolment failed: ' . $e->getMessage());
    back('enrol_error', 'Something went wrong. Please try again.');
}

back('enrol_success', 'You are now enrolled in "' . $course['title'] . '".');