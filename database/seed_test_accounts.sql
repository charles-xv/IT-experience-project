-- Test accounts for development.
-- Run once in phpMyAdmin -> SQL tab. Adds nothing else and deletes nothing.
--
-- These exist purely so you can log in as each role without going through
-- the signup form every time you reset the database. Delete them before
-- any real deployment.
--
--   Student     student@mechspec.local     Student@2026
--   Instructor  instructor@mechspec.local  Instructor@2026
--   Admin       admin@mechspec.local       MechSpec@Admin2026   (already seeded)

USE itexperience_db;

INSERT INTO Users (full_name, email, password_hash, role, status, email_verified) VALUES
('Test Student',
 'student@mechspec.local',
 '$2y$10$U6GpcqoyRnqV4Hm22OK5keBHSojBhOSo8panmajOf9yd.nvugzuSC',
 'student',
 'active',
 1),
('Test Instructor',
 'instructor@mechspec.local',
 '$2y$10$uwpuot/IsoCDmAN3rKVWjOr5EA921VLuu0ICmv2XzqnXWckTFtEFC',
 'instructor',
 'active',
 1);