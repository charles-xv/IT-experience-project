-- ============================================================
--  CREATE AN ADMINISTRATOR ACCOUNT
--
--  Two options below. Pick ONE.
--  Run in phpMyAdmin -> SQL tab.
-- ============================================================

USE itexperience_db;


-- ============================================================
--  OPTION A — promote an account you already signed up with.
--  RECOMMENDED.
--
--  Sign up through the site as normal, then run this one line with
--  your own email. You keep a password you chose yourself, and no
--  password hash is ever written into a shared file.
-- ============================================================

UPDATE Users
SET role = 'admin'
WHERE email = 'REPLACE_WITH_YOUR_EMAIL';

-- Should show your account with role = admin:
SELECT user_id, full_name, email, role, status FROM Users WHERE role = 'admin';


-- ============================================================
--  OPTION B — create a fresh admin with a known password.
--
--  Use this only if you cannot sign up first. The password below is
--  shared with anyone who reads this file, so change it from Settings
--  immediately after logging in.
--
--    Email:    admin@mechspec.local
--    Password: MechSpec@Admin2026
--
--  Remove the -- from the start of each line to run it.
-- ============================================================

-- INSERT INTO Users (full_name, email, password_hash, role, status)
-- SELECT 'Platform Admin',
--        'admin@mechspec.local',
--        '$2y$10$MlG4OotazVQNCsKw18KHQ.gJj4509kJzjIDG3Gr0prMPFhqJle/cK',
--        'admin',
--        'active'
-- WHERE NOT EXISTS (SELECT 1 FROM Users WHERE email = 'admin@mechspec.local');


-- ============================================================
--  NOTE ON THE HASH IN OPTION B
--
--  That string is a bcrypt hash, not the password. It cannot be
--  reversed. But because the matching plaintext is written above it,
--  anyone with this file can log in — which is exactly why Option A
--  is preferred, and why a real deployment would never ship a file
--  like this.
-- ============================================================