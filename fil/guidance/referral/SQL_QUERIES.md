# SQL Queries for Referral Form

## 1. Add missing `referrer_email` column (if needed)
If you still want to store referrer email in the future, run:
```sql
ALTER TABLE `guidance_referral_form`
  ADD COLUMN `referrer_email` VARCHAR(255) NULL DEFAULT NULL;
```

## 2. Ensure required columns exist and are NOT NULL
Run these to enforce constraints:
```sql
ALTER TABLE `guidance_referral_form`
  MODIFY `campus` VARCHAR(255) NOT NULL,
  MODIFY `student` VARCHAR(255) NOT NULL,
  MODIFY `grade_section` VARCHAR(100) NOT NULL,
  MODIFY `date_referred` DATE NOT NULL,
  MODIFY `description` TEXT NOT NULL,
  MODIFY `intervention` TEXT NOT NULL,
  MODIFY `requires_followup` ENUM('Yes', 'No') NOT NULL;
```

## 3. Full table creation (if not exists)
Use this as a reference for the expected schema:
```sql
CREATE TABLE IF NOT EXISTS `guidance_referral_form` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `campus` VARCHAR(255) NOT NULL,
  `student` VARCHAR(255) NOT NULL,
  `grade_section` VARCHAR(100) NOT NULL,
  `date_referred` DATE NOT NULL,
  `concern_academic` TINYINT(1) NOT NULL DEFAULT 0,
  `concern_behavior` TINYINT(1) NOT NULL DEFAULT 0,
  `concern_personal` TINYINT(1) NOT NULL DEFAULT 0,
  `description` TEXT NOT NULL,
  `intervention` TEXT NOT NULL,
  `requires_followup` ENUM('Yes', 'No') NOT NULL,
  `behavior_depressed` TINYINT(1) NOT NULL DEFAULT 0,
  `behavior_hopelessness` TINYINT(1) NOT NULL DEFAULT 0,
  `behavior_crying` TINYINT(1) NOT NULL DEFAULT 0,
  `behavior_suicide` TINYINT(1) NOT NULL DEFAULT 0,
  `behavior_mood` TINYINT(1) NOT NULL DEFAULT 0,
  `behavior_emotional` TINYINT(1) NOT NULL DEFAULT 0,
  `behavior_withdrawal` TINYINT(1) NOT NULL DEFAULT 0,
  `behavior_excessive_activity` TINYINT(1) NOT NULL DEFAULT 0,
  `behavior_interaction` TINYINT(1) NOT NULL DEFAULT 0,
  `behavior_disruptive` TINYINT(1) NOT NULL DEFAULT 0,
  `behavior_appearance` TINYINT(1) NOT NULL DEFAULT 0,
  `behavior_academic_decline` TINYINT(1) NOT NULL DEFAULT 0,
  `other_behavior` VARCHAR(255) DEFAULT NULL,
  `referrer_email` VARCHAR(255) NULL DEFAULT NULL,
  `status` ENUM('Pending', 'Reviewed', 'In Progress', 'Closed') NOT NULL DEFAULT 'Pending',
  `reviewed_by` VARCHAR(255) DEFAULT NULL,
  `reviewed_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Notes
- The form no longer includes the `referrer_email` field (removed from HTML and INSERT).
- All other fields are now required via HTML `required` attributes and PHP validation.
- The layout is responsive: side‑by‑side on screens ≥992px, stacked on smaller devices.
- A "Submit Referral Form" button is placed at the bottom of the form.