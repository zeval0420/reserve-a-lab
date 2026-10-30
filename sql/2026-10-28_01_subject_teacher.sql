-- Migration 01: Subject teacher (distinct from the teacher-in-charge)
-- Run once against `dbadmin`. Safe to re-run (IF NOT EXISTS).
--
-- Student approval flow becomes:
--   subject teacher -> teacher-in-charge -> lab admin -> CID chief
-- Teacher/faculty requests keep the old flow (see helperFiles/stage_flow.php).
--
-- The existing `subject_teacher_status|_approved_at|_approved_by` columns are
-- reused for this stage on NEW student requests (they previously held the AUH
-- stage). A request is on the new flow when `subjectTeacher` is not empty;
-- every older row has '' and keeps its legacy flow untouched.

ALTER TABLE `scilab_form_requests`
  ADD COLUMN IF NOT EXISTS `subjectTeacher` varchar(255) NOT NULL DEFAULT '' AFTER `teacherInCharge`;

-- teacherInCharge can hold several "Last, First M." names; 100 chars is tight.
ALTER TABLE `scilab_form_requests`
  MODIFY COLUMN `teacherInCharge` varchar(255) NOT NULL;

INSERT IGNORE INTO `schema_migrations` (`migration`) VALUES ('2026-10-28_01_subject_teacher');
