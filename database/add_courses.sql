-- ============================================================
--  Course catalogue for Mech Spec LMS
--
--  Run this AFTER schema.sql and seed_test_accounts.sql.
--  The instructor account must exist first, because each course is
--  attached to it by email lookup rather than a hardcoded id — a
--  hardcoded id would point at the wrong account (or nothing) on a
--  different machine.
--
--  Import once in phpMyAdmin -> SQL tab.
-- ============================================================

USE itexperience_db;

-- Clears any earlier course rows so re-running this can't create duplicates.
-- On a fresh install there is nothing to remove. Delete this line if you have
-- courses of your own you want to keep.
DELETE FROM Courses;

INSERT INTO Courses (instructor_id, title, description, youtube_video_id, category, thumbnail_url, status)
SELECT u.user_id,
       'Ethical Hacking',
       'Think like an attacker to defend like a pro. Offensive security for defenders.',
       '3FNYvj2U0HM',
       'Web Security',
       'https://img.youtube.com/vi/3FNYvj2U0HM/maxresdefault.jpg',
       'published'
FROM Users u WHERE u.email = 'instructor@mechspec.local';

INSERT INTO Courses (instructor_id, title, description, youtube_video_id, category, thumbnail_url, status)
SELECT u.user_id,
       'JavaScript for Beginners',
       'The language that runs every website. Variables, functions, objects, arrays and the DOM, explained without the jargon.',
       'W6NZfCO5SIk',
       'Development',
       'https://img.youtube.com/vi/W6NZfCO5SIk/maxresdefault.jpg',
       'published'
FROM Users u WHERE u.email = 'instructor@mechspec.local';

INSERT INTO Courses (instructor_id, title, description, youtube_video_id, category, thumbnail_url, status)
SELECT u.user_id,
       'HTML Crash Course for Beginners',
       'A fast, practical introduction to HTML: how the web works, HTTP requests and responses, page structure, links, images and forms. Taught by Mosh Hamedani.',
       'Eb3lOiukwAQ',
       'Development',
       'https://img.youtube.com/vi/Eb3lOiukwAQ/maxresdefault.jpg',
       'published'
FROM Users u WHERE u.email = 'instructor@mechspec.local';

-- Should return 3 rows. If it returns 0, seed_test_accounts.sql was not run
-- first, so there was no instructor for the courses to attach to.
SELECT course_id, title, category, youtube_video_id, status FROM Courses ORDER BY course_id;