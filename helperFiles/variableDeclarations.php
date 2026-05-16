<?php
// variableDeclarations.php
// Centralized configuration file
// Edit ONLY this file (and db_connection.php) to adapt the system to new setups.

// SESSION VARIABLE KEYS
$session_employeeID = "employeeID";
$session_email = "email";
$session_firstname = "firstname";
$session_middlename = "middlename";
$session_lastname = "lastname";
$session_username = "username";
$session_role = "role";

// FOLDER STRUCTURE
$lab_images_folder = "img/labimages";
$base_lab_image_dir = "../img/labimages/";
$relative_lab_image_dir = "img/labimages/";

// USER ROLES
$roles = [
    'admin' => 'Administrator',
    'personnel' => 'SciLab Personnel',
    'teacher' => 'Teacher',
    'student' => 'Student'
];

$accepted_admin_roles = [
    "Sci. Res. Assist.",
    "Sci. Research Specialist I"
];

// DATABASE TABLE NAMES
$db_table_requests = "scilab_form_requests";
$db_table_accounts = "accounts";
$db_table_current = "current";
$db_table_logs = "activity_logs";
$db_table_notifications = "notifications";
$db_table_settings = "system_settings";
$db_table_sections = "section";
$db_table_subjects = "subject";
$db_table_students = "student";
$db_table_materials = "scilab_material_requests";
$db_table_involved_students = "scilab_students_involved";
$db_table_availability = "scilab_availability";
$db_table_sy = "current";
$db_table_new_accounts = "scilab_new_accounts";

// DATABASE COLUMN NAMES
$col_id = "id";
$col_value = "value";
$col_status = "status";

$db_col_employeeID = "employeeID";
$db_col_email = "email";
$db_col_password = "password";
$db_col_firstname = "firstname";
$db_col_middlename = "middlename";
$db_col_lastname = "lastname";
$db_col_position = "position";
$db_col_status = "status";

$db_col_scilabName = "scilabName";
$db_col_location = "location";
$db_col_inclusiveDate = "inclusiveDate";
$db_col_inclusiveTime = "inclusiveTime";
$db_col_statusPersonnel = "statusScilabPersonnel";
$db_col_requester = "requester";
$db_col_sy = "sy";

$col_inclusiveDate = "inclusiveDate";
$col_inclusiveTime = "inclusiveTime";
$col_scilabName = "scilabName";
$col_statusScilabPersonnel = "statusScilabPersonnel";

// REQUESTS TABLE COLUMNS
$col_requests = [
    'id' => 'id',
    'requester_id' => 'requesterEmployeeID',
    'scilabName' => 'scilabName',
    'grade' => 'gradeLevel',
    'section' => 'section/s',
    'subject' => 'subject',
    'topic' => 'subjectTopic',
    'date' => 'inclusiveDate',
    'time' => 'inclusiveTime',
    'status' => 'statusScilabPersonnel',
    'controlNumber' => 'controlNumber',
    'feedback' => 'feedback'
];

// SECTIONS TABLE
$col_sectionName = "section";
$col_gradeLevel = "grade";

// SUBJECTS TABLE
$col_subjectDesc = "subjectDescription";
$col_subjectUnit = "subjectAcademicUnit";
$col_subjectGradeLevel = "subjectGradeLevel";

// STUDENT TABLE
$col_LRN = "LRN";
$col_batch = "batch";

// GENERAL INFORMATION
$school_name = "Philippine Science High School - Ilocos Region Campus";
$school_abbrev = "PSHS-IRC";
$system_name = "SciLab Reservation Management System";
$organization = "Department of Science and Technology (DOST)";

// EMAIL SETTINGS
$email_sender = "pshsircscilab@gmail.com";
$email_sender_name = "SciLab Admin";
$email_display_name = "SciLab Admin";
$email_smtp_host = "smtp.gmail.com";
$email_smtp_port = 587;
$email_smtp_user = "pshsircscilab@gmail.com";
$email_smtp_password = "wxzmkkrffptfchcc";
$email_smtp_secure = "tls";

// FRONTEND / SYSTEM PATHS
$template_dir = __DIR__ . "/../emailTemplates/";
$upload_dir = __DIR__ . "/../uploads/";
$log_dir = __DIR__ . "/../logs/";

// TIMEZONE & FORMATTING
date_default_timezone_set("Asia/Manila");
$time_format = "g:i A";
$date_format = "F j, Y";

// SYSTEM BEHAVIOR SETTINGS
$enable_email_notifications = true;
$enable_activity_logging = true;
$default_request_status = "Pending";
?>