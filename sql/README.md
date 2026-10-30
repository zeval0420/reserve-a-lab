# SQL migrations

Run in order against the `dbadmin` database (all are idempotent):

1. `2026-10-28_01_subject_teacher.sql`  – `subjectTeacher` column
2. `2026-10-28_02_control_numbers.sql`  – control-number series + ordering column
3. `2026-10-28_03_material_release.sql` – release / cancel tables and columns
4. `2026-10-30_04_request_dates.sql`    – `scilab_request_dates` (several dates per request) + backfill. The app also creates this table by itself on first use (helperFiles/request_dates.php), so running it by hand is optional.

```
mysql -u root dbadmin < sql/2026-10-28_01_subject_teacher.sql
mysql -u root dbadmin < sql/2026-10-28_02_control_numbers.sql
mysql -u root dbadmin < sql/2026-10-28_03_material_release.sql
mysql -u root dbadmin < sql/2026-10-30_04_request_dates.sql
```
