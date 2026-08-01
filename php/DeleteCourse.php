<?php
// Deletes a course the logged-in instructor owns.
//
// The foreign keys cascade, so the course's enrolments, lesson progress and
// certificates go with it. That is why the button asks for confirmation and
// names the course first.

require_once __DIR__ . '/SessionGuard.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Helpers.php';
require_role('instructor');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboards/InstructorDashboard.php');
    exit;
}

$instructorId = (int) $_SESSION['user_id'];
$courseId     = (int) ($_POST['course_id'] ?? 0);

function back(string $key, string $message): void {
    $_SESSION[$key] = $message;
    header('Location: ../dashboards/InstructorDashboard.php');
    exit;
}

if ($courseId <= 0) {
    back('course_error', 'Invalid course.');
}

try {
    // Ownership check before anything is removed.
    $stmt = $pdo->prepare(
        'SELECT title,
                (SELECT COUNT(*) FROM Enrollments e WHERE e.course_id = c.course_id) AS students
         FROM Courses c
         WHERE c.course_id = ? AND c.instructor_id = ?'
    );
    $stmt->execute([$courseId, $instructorId]);
    $course = $stmt->fetch();

    if (!$course) {
        log_security_event($pdo, 'access_denied', $instructorId, "Tried to delete course #$courseId they do not own");
        back('course_error', 'That course is not yours to delete.');
    }

    // Logged before the delete, while the title is still readable.
    log_security_event($pdo, 'course_deleted', $instructorId,
        "Deleted \"{$course['title']}\" ({$course['students']} enrolled)");

    $pdo->prepare('DELETE FROM Courses WHERE course_id = ? AND instructor_id = ?')
        ->execute([$courseId, $instructorId]);

} catch (PDOException $e) {
    error_log('Course delete failed: ' . $e->getMessage());
    back('course_error', 'Something went wrong deleting the course.');
}

back('course_success', "\"{$course['title']}\" has been deleted.");