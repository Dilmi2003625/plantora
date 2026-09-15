-- ==============================================================================
-- PLANTORA INDOOR PLANT E-COMMERCE
-- WEEK 06 PRACTICAL: USER AUTHENTICATION & PROFILE MANAGEMENT MIGRATION
-- Database: plantora_db
-- Target MySQL / MariaDB Version: MariaDB 10.4+ / MySQL 8.0+
-- File: sql/week6_auth_profile.sql
-- ==============================================================================

-- ------------------------------------------------------------------------------
-- BACKUP RECOMMENDATION BEFORE APPLYING MIGRATION:
-- Before applying changes to production or development databases, create a backup:
-- Command Line:
--   mysqldump -u root -P 3307 -h 127.0.0.1 plantora_db > backups/plantora_db_backup_week06.sql
-- In phpMyAdmin:
--   1. Select `plantora_db`.
--   2. Click "Export" tab.
--   3. Choose "Quick" export method and click "Export".
-- ------------------------------------------------------------------------------

USE `plantora_db`;

-- ------------------------------------------------------------------------------
-- 1. UPDATE `users` TABLE
-- Add `shipping_address` and `billing_address` columns if they do not exist.
-- Preserve existing `name`, `email`, `password`, `phone`, `address`, `role`, `created_at`.
-- The existing `password` column (VARCHAR(255)) will safely store password hashes
-- generated using PHP's password_hash(..., PASSWORD_DEFAULT).
-- ------------------------------------------------------------------------------

-- Add shipping_address column if not exists (MariaDB 10.4+ / MySQL 8.0.29+)
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `shipping_address` TEXT DEFAULT NULL AFTER `address`;

-- Add billing_address column if not exists
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `billing_address` TEXT DEFAULT NULL AFTER `shipping_address`;

-- If existing users had data in `address`, propagate it as default to shipping & billing
UPDATE `users`
SET
  `shipping_address` = COALESCE(`shipping_address`, `address`),
  `billing_address` = COALESCE(`billing_address`, `address`)
WHERE `address` IS NOT NULL
  AND (`shipping_address` IS NULL OR `shipping_address` = '');

-- Ensure role defaults to 'customer'
ALTER TABLE `users`
  MODIFY COLUMN `role` ENUM('customer', 'admin') DEFAULT 'customer';

-- Ensure created_at has standard default timestamp
ALTER TABLE `users`
  MODIFY COLUMN `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- ------------------------------------------------------------------------------
-- 2. CART & CART ITEMS INTEGRATION
-- Verify `cart` has user_id foreign key referencing `users(user_id)`.
-- Add composite unique index on `cart_items(cart_id, variation_id)` to avoid
-- duplicate variation rows within the same cart.
-- ------------------------------------------------------------------------------

-- In case unique_cart_variation index does not exist, add it
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
