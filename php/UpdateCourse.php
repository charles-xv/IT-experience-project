<?php
// Updates a course the logged-in instructor owns.

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

$title       = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$category    = trim($_POST['category'] ?? '');
$youtubeUrl  = trim($_POST['youtube_url'] ?? '');
$price       = (float) ($_POST['price'] ?? 0);
$status      = $_POST['status'] ?? 'draft';

$_SESSION['course_old'] = [
    'title'       => $title,
    'description' => $description,
    'category'    => $category,
    'youtube_url' => $youtubeUrl,
    'price'       => $price,
    'status'      => $status,
];

function fail(string $message, int $id): void {
    $_SESSION['course_error'] = $message;
    header("Location: ../dashboards/EditCourse.php?id=$id");
    exit;
}

function extract_youtube_id(string $url): ?string
{
    $patterns = [
        '~youtube\.com/watch\?(?:.*&)?v=([A-Za-z0-9_-]{11})~',
        '~youtu\.be/([A-Za-z0-9_-]{11})~',
        '~youtube\.com/embed/([A-Za-z0-9_-]{11})~',
        '~youtube\.com/shorts/([A-Za-z0-9_-]{11})~',
    ];
    foreach ($patterns as $p) {
        if (preg_match($p, $url, $m)) return $m[1];
    }
    return null;
}

if ($title === '')      fail('Please enter a course title.', $courseId);
if ($youtubeUrl === '') fail('Please paste a YouTube link.', $courseId);

$videoId = extract_youtube_id($youtubeUrl);
if ($videoId === null) {
    fail('That does not look like a valid YouTube link.', $courseId);
}

if (!in_array($status, ['draft', 'published'], true)) {
    fail('Invalid publish status.', $courseId);
}

if ($price < 0) {
    fail('Price cannot be negative.', $courseId);
}
if ($price > 99999.99) {
    fail('That price is too high.', $courseId);
}

try {
    // Ownership is enforced in the WHERE clause, so a tampered course_id
    // updates nothing rather than someone else's course.
    $check = $pdo->prepare('SELECT course_id FROM Courses WHERE course_id = ? AND instructor_id = ?');
    $check->execute([$courseId, $instructorId]);
    if (!$check->fetch()) {
        log_security_event($pdo, 'access_denied', $instructorId, "Tried to update course #$courseId they do not own");
        $_SESSION['course_error'] = 'That course is not yours to edit.';
        header('Location: ../dashboards/InstructorDashboard.php');
        exit;
    }

    $pdo->prepare(
        'UPDATE Courses
         SET title = ?, description = ?, youtube_video_id = ?, category = ?,
             price = ?, thumbnail_url = ?, status = ?
         WHERE course_id = ? AND instructor_id = ?'
    )->execute([
        $title,
        $description,
        $videoId,
        $category !== '' ? $category : null,
        $price,
        "https://img.youtube.com/vi/$videoId/maxresdefault.jpg",
        $status,
        $courseId,
        $instructorId,
    ]);

    log_security_event($pdo, 'course_updated', $instructorId, "Course #$courseId: $title");

} catch (PDOException $e) {
    error_log('Course update failed: ' . $e->getMessage());
    fail('Something went wrong saving the course.', $courseId);
}

unset($_SESSION['course_old']);
$_SESSION['course_success'] = "\"$title\" has been updated.";
header('Location: ../dashboards/InstructorDashboard.php');
exit;