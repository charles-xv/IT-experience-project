-- ITExperience LMS — database schema
-- Run this once in phpMyAdmin (Import tab, or paste into the SQL tab).
-- It creates the database, the two tables the auth system needs, and the first admin.

CREATE DATABASE IF NOT EXISTS itexperience_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE itexperience_db;

CREATE TABLE IF NOT EXISTS Users (
  user_id       INT AUTO_INCREMENT PRIMARY KEY,
  full_name     VARCHAR(120)        NOT NULL,
  email         VARCHAR(190)        NOT NULL UNIQUE,   -- UNIQUE stops duplicate accounts at the database level, even if application checks are bypassed
  password_hash VARCHAR(255)        NOT NULL,          -- stores a bcrypt hash, never the raw password; 255 leaves room if the hashing algorithm changes
  role          ENUM('student','instructor','admin') NOT NULL DEFAULT 'student',  -- ENUM means the column physically cannot hold any role outside these three
  created_at    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- One row per login attempt. Read to count recent failures for rate limiting,
-- and doubles as an audit trail of who tried to sign in and when.
CREATE TABLE IF NOT EXISTS LoginAttempts (
  attempt_id   INT AUTO_INCREMENT PRIMARY KEY,
  email        VARCHAR(190) NOT NULL,   -- the email that was TYPED, logged even when no such account exists, so attacks on non-existent emails are still visible
  ip_address   VARCHAR(45)  NOT NULL,   -- 45 chars fits an IPv6 address; rate limiting keys on IP so one attacker can't cycle through many emails freely
  successful   TINYINT(1)   NOT NULL DEFAULT 0,
  attempted_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email_time (email, attempted_at),   -- these two indexes make the "recent failures" lookup fast once the table grows large
  INDEX idx_ip_time (ip_address, attempted_at)
);

-- First admin account. There is no one to promote the very first admin through
-- the app, so it is inserted here by hand, once.
-- The hash below is bcrypt for the password:  ChangeMe#2026
-- Log in with it, then change the password immediately. Do not ship this default.
INSERT INTO Users (full_name, email, password_hash, role) VALUES (
  'Platform Admin',
  'admin@itexperience.local',
  '$2y$10$tjpZx/dOXos.1ajwgocV8O3DqCCwlHHjUJ4c4T4hqKMY8lCwDQuZu',
  'admin'
);
