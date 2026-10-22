-- Inventory deduction log (one row per item per deduction; rows of one batch share batch_id)
CREATE TABLE IF NOT EXISTS `scilab_inventory_deductions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_id` varchar(36) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `item` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL DEFAULT '',
  `classification` varchar(20) NOT NULL,
  `quantity_deducted` int(6) NOT NULL,
  `quantity_before` int(6) NOT NULL,
  `quantity_after` int(6) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `deducted_by` varchar(100) NOT NULL DEFAULT '',
  `deducted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_inventory_id` (`inventory_id`),
  KEY `idx_batch_id` (`batch_id`),
  KEY `idx_deducted_at` (`deducted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
