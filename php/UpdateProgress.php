<?php
// Updates a student's progress on a course, and issues the certificate when
// the course is marked complete.

require_once __DIR__ . '/SessionGuard.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Helpers.php';
require_role('student');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboards/StudentDashboard.php');
    exit;
}

$studentId = (int) $_SESSION['user_id'];
$courseId  = (int) ($_POST['course_id'] ?? 0);
$action    = $_POST['action'] ?? '';

if ($courseId <= 0 || !in_array($action, ['halfway', 'complete'], true)) {
    header('Location: ../dashboards/StudentDashboard.php');
    exit;
}

try {
    // Confirm the enrolment exists before touching anything. Without this a
    // crafted POST could mark a course complete the student never enrolled in.
    $stmt = $pdo->prepare(
        'SELECT e.enrollment_id, c.title
         FROM Enrollments e
         JOIN Courses c ON c.course_id = e.course_id
         WHERE e.student_id = ? AND e.course_id = ?'
    );
    $stmt->execute([$studentId, $courseId]);
    $enrolment = $stmt->fetch();

    if (!$enrolment) {
        log_security_event($pdo, 'access_denied', $studentId, "Progress update on un-enrolled course #$courseId");
        header('Location: ../dashboards/BrowseCourses.php');
        exit;
    }

    if ($action === 'halfway') {
        // GREATEST keeps progress from going backwards if they click it after
        // already being further along.
        $pdo->prepare(
            'UPDATE Enrollments SET progress_percent = GREATEST(progress_percent, 50)
             WHERE student_id = ? AND course_id = ?'
        )->execute([$studentId, $courseId]);

        $_SESSION['watch_success'] = 'Progress saved at 50%.';
    } else {
        $pdo->prepare(
            'UPDATE Enrollments
             SET progress_percent = 100, completed_at = NOW()
             WHERE student_id = ? AND course_id = ?'
        )->execute([$studentId, $courseId]);

        // Issue the certificate. INSERT IGNORE leans on the UNIQUE key so
        // completing twice cannot produce two certificates.
        $pdo->prepare(
            'INSERT IGNORE INTO Certificates (student_id, course_id) VALUES (?, ?)'
        )->execute([$studentId, $courseId]);

        log_security_event($pdo, 'course_completed', $studentId, "Completed course #$courseId");
        $_SESSION['watch_success'] = 'Course complete. Your certificate has been issued.';
    }

} catch (PDOException $e) {
    error_log('Progress update failed: ' . $e->getMessage());
}

header('Location: ../dashboards/WatchCourse.php?id=' . $courseId);
exit;