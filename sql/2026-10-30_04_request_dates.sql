-- Migration 04: multiple dates per request (e.g. Nov 3, 5, 9 and Nov 10-14)
-- Run once against `dbadmin`. Safe to re-run.

-- One row per day a request uses the lab. All days share the request's inclusiveTime.
-- scilab_form_requests.inclusiveDate stays and holds the EARLIEST day, so sorting,
-- the "filed less than 3 days before use" flag and old reports keep working.
CREATE TABLE IF NOT EXISTS `scilab_request_dates` (
  `formID`  int(11) NOT NULL,
  `useDate` date    NOT NULL,
  PRIMARY KEY (`formID`, `useDate`),
  KEY `idx_request_dates_date` (`useDate`),
  CONSTRAINT `fk_request_dates_form` FOREIGN KEY (`formID`) REFERENCES `scilab_form_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Existing single-date requests get their one row.
INSERT IGNORE INTO `scilab_request_dates` (`formID`, `useDate`)
SELECT `id`, `inclusiveDate` FROM `scilab_form_requests`
WHERE `inclusiveDate` IS NOT NULL AND `inclusiveDate` <> '0000-00-00';
