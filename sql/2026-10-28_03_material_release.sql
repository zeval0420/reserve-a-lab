-- Migration 03: Release Materials / Cancel Request
-- Run once against `dbadmin`. Safe to re-run.

-- 'Cancelled' is 9 characters; the column was varchar(8).
ALTER TABLE `scilab_form_requests`
  MODIFY COLUMN `statusScilabPersonnel` varchar(12) NOT NULL DEFAULT 'pending';

ALTER TABLE `scilab_form_requests`
  ADD COLUMN IF NOT EXISTS `releaseStatus` varchar(12)  NOT NULL DEFAULT 'none',   -- none | released
  ADD COLUMN IF NOT EXISTS `cancelledAt`   datetime     NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `cancelledBy`   varchar(255) NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `cancelReason`  varchar(255) NULL DEFAULT NULL;

-- Ledger of what was actually taken out of stock for a request. Written when the
-- request is approved (scilab_deduct_inventory), adjusted on release, and read on
-- cancel so exactly what was deducted is put back.
CREATE TABLE IF NOT EXISTS `scilab_inventory_deductions` (
  `id`          int(11)  NOT NULL AUTO_INCREMENT,
  `formID`      int(11)  NOT NULL,
  `inventoryID` int(11)  NOT NULL,
  `quantity`    int(11)  NOT NULL,                -- net quantity currently deducted for this form + inventory row
  `createdAt`   datetime NOT NULL DEFAULT current_timestamp(),
  `updatedAt`   datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `restoredAt`  datetime NULL DEFAULT NULL,       -- set when returned to stock by a cancel
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_form_inventory` (`formID`, `inventoryID`),
  KEY `idx_ded_inventory` (`inventoryID`),
  CONSTRAINT `fk_ded_form` FOREIGN KEY (`formID`) REFERENCES `scilab_form_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- One row per confirmed release (header): who handed over, who received.
CREATE TABLE IF NOT EXISTS `scilab_material_releases` (
  `id`           int(11)      NOT NULL AUTO_INCREMENT,
  `formID`       int(11)      NOT NULL,
  `releasedAt`   datetime     NOT NULL DEFAULT current_timestamp(),
  `releasedBy`   varchar(255) NOT NULL,           -- admin who confirmed
  `receiverType` varchar(10)  NOT NULL,           -- employee | student
  `receiverID`   varchar(50)  NOT NULL,           -- accounts.employeeID or student.LRN (as matched)
  `receiverName` varchar(255) NOT NULL,           -- resolved at release time
  `scanMethod`   varchar(10)  NOT NULL DEFAULT 'scanner', -- scanner | camera | manual
  `remarks`      varchar(500) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_rel_form` (`formID`),
  KEY `idx_rel_receiver` (`receiverType`, `receiverID`),
  CONSTRAINT `fk_rel_form` FOREIGN KEY (`formID`) REFERENCES `scilab_form_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Line items of a release: the originally requested values AND the released values.
CREATE TABLE IF NOT EXISTS `scilab_material_release_items` (
  `id`                int(11)      NOT NULL AUTO_INCREMENT,
  `releaseID`         int(11)      NOT NULL,
  `formID`            int(11)      NOT NULL,
  `materialRequestID` int(11)      NULL DEFAULT NULL,  -- scilab_material_requests.id; NULL for items added at release
  `inventoryID`       int(11)      NULL DEFAULT NULL,  -- scilab_inventory row the stock moved from, if resolved
  `item`              varchar(255) NOT NULL,
  `description`       varchar(255) NOT NULL DEFAULT '',
  `unit`              varchar(50)  NULL DEFAULT NULL,
  `originalQuantity`  int(11)      NULL DEFAULT NULL,  -- OLD value (NULL when the item was added)
  `releasedQuantity`  int(11)      NOT NULL,           -- NEW value
  `isAdded`           tinyint(1)   NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_ri_release` (`releaseID`),
  KEY `idx_ri_form` (`formID`),
  CONSTRAINT `fk_ri_release` FOREIGN KEY (`releaseID`) REFERENCES `scilab_material_releases` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `schema_migrations` (`migration`) VALUES ('2026-10-28_03_material_release');
