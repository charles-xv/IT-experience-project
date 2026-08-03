-- ============================================================
--  DATABASE UPDATE — run this after pulling the latest code.
--
--  Brings an existing database up to date with everything added since
--  the original schema: course pricing, the cart, purchases, and the
--  password reset tokens.
--
--  SAFE TO RUN MORE THAN ONCE. Every statement uses IF NOT EXISTS, so
--  anything already present is skipped rather than erroring. Nothing is
--  deleted and no existing data is touched.
--
--  Run in phpMyAdmin -> SQL tab.
-- ============================================================

USE itexperience_db;

-- ------------------------------------------------------------
--  1. Course pricing
--     DECIMAL, not FLOAT: money must never be stored as floating
--     point, because 0.1 + 0.2 does not equal 0.3 in binary and
--     totals drift by fractions of a unit.
-- ------------------------------------------------------------
ALTER TABLE Courses
  ADD COLUMN IF NOT EXISTS price DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER category;

-- Also needed if the database predates the video work.
ALTER TABLE Courses
  ADD COLUMN IF NOT EXISTS youtube_video_id VARCHAR(20) DEFAULT NULL AFTER description;


-- ------------------------------------------------------------
--  2. Cart
--     Held in the database rather than the session, so a cart
--     survives logging out and returning on another device.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS CartItems (
  cart_item_id INT AUTO_INCREMENT PRIMARY KEY,
  student_id   INT NOT NULL,
  course_id    INT NOT NULL,
  added_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES Users(user_id)     ON DELETE CASCADE,
  FOREIGN KEY (course_id)  REFERENCES Courses(course_id) ON DELETE CASCADE,
  UNIQUE KEY uniq_cart_student_course (student_id, course_id),
  INDEX idx_cart_student (student_id)
) ENGINE=InnoDB;


-- ------------------------------------------------------------
--  3. Purchases
--     amount_paid is recorded at the moment of sale rather than
--     read from Courses later, so a receipt never changes when an
--     instructor edits the price afterwards.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS Purchases (
  purchase_id  INT AUTO_INCREMENT PRIMARY KEY,
  student_id   INT NOT NULL,
  course_id    INT NOT NULL,
  amount_paid  DECIMAL(10,2) NOT NULL,
  reference    VARCHAR(40) NOT NULL UNIQUE,
  status       ENUM('completed','refunded') NOT NULL DEFAULT 'completed',
  purchased_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES Users(user_id)     ON DELETE CASCADE,
  FOREIGN KEY (course_id)  REFERENCES Courses(course_id) ON DELETE CASCADE,
  INDEX idx_purchase_student (student_id),
  INDEX idx_purchase_time (purchased_at)
) ENGINE=InnoDB;


-- ------------------------------------------------------------
--  4. Password reset
--     The token is stored hashed, never in plain text, so reading
--     the database does not hand anyone a working reset link.
-- ------------------------------------------------------------
ALTER TABLE Users
  ADD COLUMN IF NOT EXISTS reset_token   VARCHAR(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS reset_expires DATETIME     DEFAULT NULL;


-- ------------------------------------------------------------
--  5. Give the seeded courses a price so the checkout flow is
--     demonstrable. Skipped harmlessly if those courses are absent.
-- ------------------------------------------------------------
UPDATE Courses SET price = 19.99 WHERE title = 'Ethical Hacking'                 AND price = 0;
UPDATE Courses SET price = 14.99 WHERE title = 'JavaScript for Beginners'        AND price = 0;
UPDATE Courses SET price =  0.00 WHERE title = 'HTML Crash Course for Beginners';


-- ------------------------------------------------------------
--  Confirm. Expect price + youtube_video_id on Courses,
--  reset_token + reset_expires on Users, and both new tables.
-- ------------------------------------------------------------
SHOW COLUMNS FROM Courses LIKE 'price';
SHOW COLUMNS FROM Courses LIKE 'youtube_video_id';
SHOW COLUMNS FROM Users   LIKE 'reset_%';
SHOW TABLES LIKE 'CartItems';
SHOW TABLES LIKE 'Purchases';
SELECT course_id, title, price, status FROM Courses ORDER BY course_id;