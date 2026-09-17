-- ITX — hero background video support (2026-09-17)
ALTER TABLE `hero_section` ADD COLUMN `bg_video` VARCHAR(255) DEFAULT NULL AFTER `note`;
UPDATE `hero_section` SET `bg_video` = 'assets/hero-promo.mp4' WHERE id = 1;
