-- ============================================================
--  Mech Spec LMS — ITExperience Project
--  Complete database schema
--
--  Import ONCE in phpMyAdmin: Import tab -> choose this file -> Go
--
--  WARNING: the DROP statements below delete existing tables and all
--  their data. That is intentional so this file can be re-imported to
--  get a clean, consistent database. If you have real data you want to
--  keep, export it first.
-- ============================================================

CREATE DATABASE IF NOT EXISTS itexperience_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE itexperience_db;

-- Dropped child-first so foreign keys don't block the drop.
DROP TABLE IF EXISTS PageVisits;
DROP TABLE IF EXISTS SecurityLogs;
DROP TABLE IF EXISTS Certificates;
DROP TABLE IF EXISTS LessonProgress;
DROP TABLE IF EXISTS Enrollments;
DROP TABLE IF EXISTS Lessons;
DROP TABLE IF EXISTS Courses;
DROP TABLE IF EXISTS LoginAttempts;
DROP TABLE IF EXISTS Users;


-- ============================================================
--  Users
-- ============================================================
CREATE TABLE Users (
  user_id       INT AUTO_INCREMENT PRIMARY KEY,
  full_name     VARCHAR(120) NOT NULL,
  email         VARCHAR(190) NOT NULL UNIQUE,   -- UNIQUE blocks duplicate accounts at the database level, even if an application check is bypassed
  password_hash VARCHAR(255) NOT NULL,          -- bcrypt hash only, never the raw password
  role          ENUM('student','instructor','admin') NOT NULL DEFAULT 'student',
  status        ENUM('active','suspended')      NOT NULL DEFAULT 'active',  -- what the admin Suspend button flips; Login.php must refuse a suspended account
  last_login    TIMESTAMP NULL DEFAULT NULL,    -- lets the admin page identify inactive accounts
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_role (role),
  INDEX idx_status (status)
) ENGINE=InnoDB;


-- ============================================================
--  LoginAttempts — feeds the rate limiter and the security audit trail
-- ============================================================
CREATE TABLE LoginAttempts (
  attempt_id   INT AUTO_INCREMENT PRIMARY KEY,
  email        VARCHAR(190) NOT NULL,   -- the email TYPED, logged even when no such account exists, so attacks on unknown emails are still visible
  ip_address   VARCHAR(45)  NOT NULL,   -- 45 chars fits IPv6
  successful   TINYINT(1)   NOT NULL DEFAULT 0,
  attempted_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email_time (email, attempted_at),
  INDEX idx_ip_time (ip_address, attempted_at)
) ENGINE=InnoDB;


-- ============================================================
--  Courses — owned by an instructor
-- ============================================================
CREATE TABLE Courses (
  course_id     INT AUTO_INCREMENT PRIMARY KEY,
  instructor_id INT NOT NULL,
  title         VARCHAR(180) NOT NULL,
  description   TEXT,
  youtube_video_id VARCHAR(20) DEFAULT NULL, -- the 11-character ID from the YouTube URL; one video per course
  category      VARCHAR(80)  DEFAULT NULL,   -- 'Web Security', 'Development', 'Artificial Intelligence'
  thumbnail_url VARCHAR(500) DEFAULT NULL,   -- https://img.youtube.com/vi/<id>/maxresdefault.jpg
  status        ENUM('draft','published') NOT NULL DEFAULT 'draft',  -- only published courses show to students
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  -- If an instructor account is deleted, their courses go too.
  FOREIGN KEY (instructor_id) REFERENCES Users(user_id) ON DELETE CASCADE,
  INDEX idx_instructor (instructor_id),
  INDEX idx_status (status)
) ENGINE=InnoDB;


-- ============================================================
--  Lessons — the units inside a course; position sets the order
-- ============================================================
CREATE TABLE Lessons (
  lesson_id        INT AUTO_INCREMENT PRIMARY KEY,
  course_id        INT NOT NULL,
  title            VARCHAR(180) NOT NULL,
  duration_minutes INT DEFAULT 0,
  position         INT NOT NULL DEFAULT 1,
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (course_id) REFERENCES Courses(course_id) ON DELETE CASCADE,
  INDEX idx_course_position (course_id, position)
) ENGINE=InnoDB;


-- ============================================================
--  Enrollments — one row per student per course
-- ============================================================
CREATE TABLE Enrollments (
  enrollment_id    INT AUTO_INCREMENT PRIMARY KEY,
  student_id       INT NOT NULL,
  course_id        INT NOT NULL,
  progress_percent TINYINT UNSIGNED NOT NULL DEFAULT 0,
  enrolled_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  completed_at     TIMESTAMP NULL DEFAULT NULL,   -- non-null means finished; counting these gives "Completed Courses"
  FOREIGN KEY (student_id) REFERENCES Users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (course_id)  REFERENCES Courses(course_id) ON DELETE CASCADE,
  -- Stops the same student enrolling on the same course twice.
  UNIQUE KEY uniq_student_course (student_id, course_id),
  INDEX idx_student (student_id)
) ENGINE=InnoDB;


-- ============================================================
--  LessonProgress — which lessons a student has finished.
--  Counting these against a course's lesson count gives real progress.
-- ============================================================
CREATE TABLE LessonProgress (
  progress_id  INT AUTO_INCREMENT PRIMARY KEY,
  student_id   INT NOT NULL,
  lesson_id    INT NOT NULL,
  completed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES Users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (lesson_id)  REFERENCES Lessons(lesson_id) ON DELETE CASCADE,
  -- A lesson can only be completed once per student.
  UNIQUE KEY uniq_student_lesson (student_id, lesson_id)
) ENGINE=InnoDB;


-- ============================================================
--  Certificates — issued when a course is completed
-- ============================================================
CREATE TABLE Certificates (
  certificate_id INT AUTO_INCREMENT PRIMARY KEY,
  student_id     INT NOT NULL,
  course_id      INT NOT NULL,
  issued_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES Users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (course_id)  REFERENCES Courses(course_id) ON DELETE CASCADE,
  UNIQUE KEY uniq_student_course_cert (student_id, course_id)
) ENGINE=InnoDB;


-- ============================================================
--  SecurityLogs — broader audit trail than LoginAttempts alone.
--  Feeds the admin dashboard's security feed.
--  event_type values used: login_success, failed_login, account_locked,
--  account_suspended, account_reinstated, account_deleted,
--  role_changed, signup, logout, access_denied
-- ============================================================
CREATE TABLE SecurityLogs (
  log_id     INT AUTO_INCREMENT PRIMARY KEY,
  event_type VARCHAR(50)  NOT NULL,
  user_id    INT DEFAULT NULL,   -- nullable: some events happen with no known account
  ip_address VARCHAR(45)  DEFAULT NULL,
  details    VARCHAR(500) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  -- SET NULL, not CASCADE: deleting a user must not erase the security history.
  FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE SET NULL,
  INDEX idx_event_time (event_type, created_at)
) ENGINE=InnoDB;


-- ============================================================
--  PageVisits — every page load, for the admin's visitor IP view.
--  user_id is null for visitors who are not logged in.
-- ============================================================
CREATE TABLE PageVisits (
  visit_id   INT AUTO_INCREMENT PRIMARY KEY,
  page       VARCHAR(190) NOT NULL,
  ip_address VARCHAR(45)  NOT NULL,
  user_id    INT DEFAULT NULL,
  user_agent VARCHAR(255) DEFAULT NULL,
  visited_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE SET NULL,
  INDEX idx_ip_time (ip_address, visited_at),
  INDEX idx_time (visited_at)
) ENGINE=InnoDB;

-- ============================================================
--  NOTE: no administrator account is created here, deliberately.
--
--  Anyone who clones this repository can import this file and get a working
--  database, but not administrator access. The admin account is created
--  separately from a file that is not committed.
--
--  Next step: run seed_test_accounts.sql for the student and instructor
--  logins, then add_courses.sql for the course catalogue.
-- ============================================================