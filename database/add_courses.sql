-- Adds THREE more courses alongside whatever you already have.
-- Nothing is deleted. Run once in phpMyAdmin -> SQL tab.
--
-- Mixed channels, no freeCodeCamp thumbnails:
--   SuperSimpleDev        · HTML & CSS Full Course
--   Programming with Mosh · JavaScript Tutorial for Beginners
--   Traversy Media        · JavaScript Crash Course
--
-- Courses attach to the Test Instructor by email lookup, so this works
-- regardless of what user_id that account happens to have.

USE itexperience_db;

INSERT INTO Courses (instructor_id, title, description, youtube_video_id, category, thumbnail_url, status)
SELECT u.user_id,
       'HTML & CSS Full Course',
       'Build real pages from scratch. The box model, flexbox, CSS grid, positioning and responsive layouts, ending with a full YouTube clone you build yourself.',
       'G3e-cpL7ofc',
       'Development',
       'https://img.youtube.com/vi/G3e-cpL7ofc/maxresdefault.jpg',
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
       'JavaScript Crash Course',
       'A faster route through the essentials for anyone who already codes. Syntax, DOM manipulation and events, straight to the point.',
       'hdI2bqOjy3c',
       'Development',
       'https://img.youtube.com/vi/hdI2bqOjy3c/maxresdefault.jpg',
       'published'
FROM Users u WHERE u.email = 'instructor@mechspec.local';

-- Check what you now have:
SELECT course_id, title, category, youtube_video_id, status FROM Courses ORDER BY course_id;