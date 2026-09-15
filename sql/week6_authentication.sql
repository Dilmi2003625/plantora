-- ==============================================================================
-- PLANTORA INDOOR PLANT E-COMMERCE
-- WEEK 06: USER AUTHENTICATION & REGISTRATION SQL MIGRATION
-- Database: plantora_db
-- Target Server: MySQL / MariaDB (Port: 3307)
-- File: sql/week6_authentication.sql
-- ==============================================================================

-- ------------------------------------------------------------------------------
-- DATABASE INSPECTION SUMMARY:
-- Current `users` table contains:
--   - user_id (int(11), PK, AUTO_INCREMENT)
--   - name (varchar(100), NOT NULL)
--   - email (varchar(100), NOT NULL, UNIQUE)
--   - password (varchar(255), NOT NULL) -- stores password_hash() bcrypt hashes
--   - phone (varchar(20), NULL)
--   - address (text, NULL)
--   - role (enum('customer','admin'), DEFAULT 'customer')
--   - created_at (timestamp, DEFAULT CURRENT_TIMESTAMP)
--
-- REQUIRED CHANGES FOR AUTHENTICATION & PROFILE:
--   - Add `shipping_address` (text, NULL) if missing.
--   - Add `billing_address` (text, NULL) if missing.
--   - Populate default shipping and billing addresses from `address` if present.
--   - Ensure role defaults to 'customer'.
--   - Ensure email uniqueness index.
--   - Ensure cart_items has unique index on (cart_id, variation_id).
-- ------------------------------------------------------------------------------

USE `plantora_db`;

-- 1. Safely add `shipping_address` column if it does not already exist
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `shipping_address` TEXT DEFAULT NULL AFTER `address`;

-- 2. Safely add `billing_address` column if it does not already exist
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `billing_address` TEXT DEFAULT NULL AFTER `shipping_address`;

-- 3. Copy existing `address` data to shipping and billing address columns if empty
UPDATE `users`
SET
  `shipping_address` = COALESCE(`shipping_address`, `address`),
  `billing_address` = COALESCE(`billing_address`, `address`)
WHERE `address` IS NOT NULL
  AND (`shipping_address` IS NULL OR `shipping_address` = '');

-- 4. Verify safe customer role default
ALTER TABLE `users`
  MODIFY COLUMN `role` ENUM('customer', 'admin') DEFAULT 'customer';

-- 5. Ensure created_at has standard default timestamp
ALTER TABLE `users`
  MODIFY COLUMN `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- 6. Ensure cart_items has composite unique constraint on (cart_id, variation_id)
SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'cart_items'
      AND index_name = 'unique_cart_variation'
);

SET @sql_statement = IF(
    @index_exists = 0,
    'ALTER TABLE `cart_items` ADD UNIQUE KEY `unique_cart_variation` (`cart_id`, `variation_id`)',
    'SELECT "Index unique_cart_variation already exists" AS info'
);

PREPARE stmt FROM @sql_statement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------------------------
-- MANUAL EXECUTION IN phpMyAdmin:
-- If executing through phpMyAdmin:
-- 1. Open http://localhost/phpmyadmin
-- 2. Select the `plantora_db` database on the left sidebar.
-- 3. Click the "SQL" tab.
-- 4. Copy and paste the contents of this file into the SQL query box.
-- 5. Click the "Go" button to execute.
-- ------------------------------------------------------------------------------
