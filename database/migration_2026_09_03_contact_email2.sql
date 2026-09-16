-- ============================================================
--  ITX — Migration: add a second email to contact_info
--  Date: 2026-09-03
--
--  Safe to run on a LIVE database:
--    • Idempotent — re-running it changes nothing.
--    • Works on both MySQL 5.7 / 8.x and MariaDB
--      (it does NOT use "ADD COLUMN IF NOT EXISTS",
--       which is MariaDB-only and errors on MySQL).
--    • Never overwrites an email that is already set.
--
--  Run via phpMyAdmin (SQL tab) or:
--    mysql -u USER -p DBNAME < migration_2026_09_03_contact_email2.sql
-- ============================================================

-- ── 1) Add the column only if it is missing ─────────────────
SET @stmt := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME   = 'contact_info'
       AND COLUMN_NAME  = 'email2') = 0,
  'ALTER TABLE `contact_info` ADD COLUMN `email2` VARCHAR(160) NULL AFTER `email`',
  'SELECT ''column contact_info.email2 already exists — skipped'' AS note'
);
PREPARE s FROM @stmt;
EXECUTE s;
DEALLOCATE PREPARE s;

-- ── 2) Make sure the single settings row exists ─────────────
INSERT INTO `contact_info` (`id`) VALUES (1)
  ON DUPLICATE KEY UPDATE `id` = `id`;

-- ── 3) Seed the value only when still empty ─────────────────
UPDATE `contact_info`
   SET `email2` = 'info@itxsmart.com'
 WHERE `id` = 1
   AND (`email2` IS NULL OR `email2` = '');

-- ── 4) Verify ───────────────────────────────────────────────
SELECT `id`, `email`, `email2` FROM `contact_info` WHERE `id` = 1;
