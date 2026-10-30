-- Migration 02: Automatic, incrementing control numbers (4 distinct series)
-- Run once against `dbadmin`. Safe to re-run.
--
-- Series (columns on scilab_form_requests, unchanged):
--   control_equipment | control_reagent | control_permit | control_reservation
-- A number = prefix + zero-padded integer, e.g. "RG-001". The next number for a
-- series is MAX(existing integer suffix with that prefix) + 1, computed under a
-- lock at final approval. Editing a number shifts every LATER form in that
-- series (later = higher controlAssignedAt, then id) to value+1, +2, ...

CREATE TABLE IF NOT EXISTS `scilab_control_sequences` (
  `formType`    varchar(20)  NOT NULL,           -- equipment | reagent | permit | reservation
  `prefix`      varchar(30)  NOT NULL DEFAULT '',
  `padWidth`    tinyint      NOT NULL DEFAULT 3,
  `startNumber` int          NOT NULL DEFAULT 1, -- first number when the series is empty
  PRIMARY KEY (`formType`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Prefixes follow the placeholders already used in admin_approve.php. Edit freely.
INSERT IGNORE INTO `scilab_control_sequences` (`formType`, `prefix`, `padWidth`, `startNumber`) VALUES
  ('equipment',   'CIID-20-', 3, 1),
  ('reagent',     'RG-',      3, 1),
  ('permit',      'PT-',      3, 1),
  ('reservation', 'CID-05-',  3, 1);

-- Order in which numbers were handed out (needed to know which forms "succeed").
ALTER TABLE `scilab_form_requests`
  ADD COLUMN IF NOT EXISTS `controlAssignedAt` datetime(6) NULL DEFAULT NULL;

UPDATE `scilab_form_requests`
   SET `controlAssignedAt` = COALESCE(`cid_chief_approved_at`, `lab_personnel_approved_at`, `dateRequested`)
 WHERE `controlAssignedAt` IS NULL
   AND (COALESCE(`control_equipment`,'') <> '' OR COALESCE(`control_reagent`,'') <> ''
     OR COALESCE(`control_permit`,'') <> ''    OR COALESCE(`control_reservation`,'') <> '');

CREATE INDEX IF NOT EXISTS `idx_fr_control_equipment`   ON `scilab_form_requests` (`control_equipment`);
CREATE INDEX IF NOT EXISTS `idx_fr_control_reagent`     ON `scilab_form_requests` (`control_reagent`);
CREATE INDEX IF NOT EXISTS `idx_fr_control_permit`      ON `scilab_form_requests` (`control_permit`);
CREATE INDEX IF NOT EXISTS `idx_fr_control_reservation` ON `scilab_form_requests` (`control_reservation`);
CREATE INDEX IF NOT EXISTS `idx_fr_control_assigned`    ON `scilab_form_requests` (`controlAssignedAt`);

INSERT IGNORE INTO `schema_migrations` (`migration`) VALUES ('2026-10-28_02_control_numbers');
