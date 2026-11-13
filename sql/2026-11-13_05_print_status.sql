-- Migration 05: mark approved requests as printed by the lab
-- Run once against `dbadmin`. Safe to re-run.

ALTER TABLE `scilab_form_requests`
  ADD COLUMN IF NOT EXISTS `printedAt` datetime     NULL DEFAULT NULL,   -- NULL = not printed yet
  ADD COLUMN IF NOT EXISTS `printedBy` varchar(255) NULL DEFAULT NULL;   -- admin who marked it

INSERT IGNORE INTO `schema_migrations` (`migration`) VALUES ('2026-11-13_05_print_status');
