<?php
// Receives the Create Course form, extracts the YouTube video ID from
// whatever link format was pasted, derives the thumbnail, and saves the
// course against the logged-in instructor.

require_once __DIR__ . '/SessionGuard.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Helpers.php';
require_role('instructor');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboards/CreateCourse.php');
    exit;
}

$instructorId = (int) $_SESSION['user_id'];

$title       = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$category    = trim($_POST['category'] ?? '');
$youtubeUrl  = trim($_POST['youtube_url'] ?? '');
$status      = $_POST['status'] ?? 'draft';

// Keep what was typed so a failed submit doesn't wipe the form.
$_SESSION['course_old'] = [
    'title'       => $title,
    'description' => $description,
    'category'    => $category,
    'youtube_url' => $youtubeUrl,
    'status'      => $status,
];

function fail(string $message): void {
    $_SESSION['course_error'] = $message;
    header('Location: ../dashboards/CreateCourse.php');
    exit;
}

/**
 * Pulls the 11-character video ID out of any YouTube link format:
 *   https://www.youtube.com/watch?v=ID&t=499s
 *   https://youtu.be/ID
 *   https://www.youtube.com/embed/ID
 *   https://www.youtube.com/shorts/ID
 * Returns null if no valid ID is present.
 */
function extract_youtube_id(string $url): ?string
{
    // YouTube IDs are exactly 11 characters of [A-Za-z0-9_-].
    $patterns = [
        '~youtube\.com/watch\?(?:.*&)?v=([A-Za-z0-9_-]{11})~',
        '~youtu\.be/([A-Za-z0-9_-]{11})~',
        '~youtube\.com/embed/([A-Za-z0-9_-]{11})~',
        '~youtube\.com/shorts/([A-Za-z0-9_-]{11})~',
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url, $m)) {
            return $m[1];
        }
    }
    return null;
}

// --- Validation ---

if ($title === '') {
    fail('Please enter a course title.');
}

if ($youtubeUrl === '') {
    fail('Please paste a YouTube link.');
}

$videoId = extract_youtube_id($youtubeUrl);
if ($videoId === null) {
    fail('That does not look like a valid YouTube link. Copy the full URL from the address bar.');
}

// The form only offers these two; anything else means the request was tampered with.
if (!in_array($status, ['draft', 'published'], true)) {
    fail('Invalid publish status.');
}

// maxresdefault is a true 16:9 image. hqdefault is 4:3 with black bars baked
// in, which is why it looks squeezed inside a widescreen card.
$thumbnailUrl = "https://img.youtube.com/vi/$videoId/maxresdefault.jpg";

// --- Save ---

try {
    $stmt = $pdo->prepare(
        'INSERT INTO Courses
            (instructor_id, title, description, youtube_video_id, category, thumbnail_url, status)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $instructorId,
        $title,
        $description,
        $videoId,
        $category !== '' ? $category : null,
        $thumbnailUrl,
        $status,
    ]);

    $courseId = (int) $pdo->lastInsertId();
    log_security_event($pdo, 'course_created', $instructorId, "Course #$courseId: $title");

} catch (PDOException $e) {
    error_log('Course creation failed: ' . $e->getMessage());
    fail('Something went wrong saving the course. Please try again.');
}

// Success — clear the remembered form values and report back.
unset($_SESSION['course_old']);
$_SESSION['course_success'] = $status === 'published'
    ? "\"$title\" has been published. Students can now enrol."
    : "\"$title\" has been saved as a draft.";

header('Location: ../dashboards/InstructorDashboard.php');
exit;