<?php
// Enable error reporting to debug 500 errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!file_exists('../vendor/autoload.php')) {
    die('Error: vendor/autoload.php not found. Please ensure the vendor directory is uploaded.');
}
require_once '../vendor/autoload.php';

if (!file_exists('../helperFiles/db_connection.php')) {
    die('Error: helperFiles/db_connection.php not found. Check directory case sensitivity (helperFiles vs helperfiles).');
}
include('../../scilab/helperFiles/db_connection.php');

use Dompdf\Dompdf;
use Dompdf\Options;

// Get form ID from request
$formID = $_GET['id'] ?? $_GET['formID'] ?? null;

if (!$formID) {
    die('Error: Form ID is required.');
}

// Fetch form data
$stmt = $conn->prepare("
    SELECT 
        fr.controlNumber,
        fr.control_equipment,
        fr.control_reagent,
        fr.control_permit,
        fr.control_reservation,
        fr.sy,
        fr.gradeLevel,
        fr.sections,
        fr.scilabName,
        fr.subject,
        fr.subjectTopic,
        fr.teacherInCharge,
        fr.inclusiveDate,
        fr.inclusiveTime,
        fr.requesterEmployeeID,
        fr.dateRequested,
        fr.statusScilabPersonnel,
        fr.subjectAcademicUnit,
        fr.supervisor_approved_at,
        fr.supervisor_approved_by,
        fr.subject_teacher_approved_at,
        fr.subject_teacher_approved_by,
        fr.lab_personnel_approved_at,
        fr.lab_personnel_approved_by,
        fr.cid_chief_approved_at,
        fr.cid_chief_approved_by
    FROM scilab_form_requests fr
    WHERE fr.id = ?
");

if ($stmt === false) {
    die('Database query preparation failed.');
}

$stmt->bind_param('i', $formID);
$stmt->execute();
$result = $stmt->get_result();
$formData = $result->fetch_assoc();

if (!$formData) {
    die('Error: Form not found.');
}

$stmt->close();

// Fetch materials/equipment
$stmt = $conn->prepare("
    SELECT 
        mr.quantity,
        mr.item,
        mr.unit,
        mr.description,
        mr.issuedCondition,
        mr.returnedCondition,
        mr.returnedItemInspector,
        COALESCE(si.classification, 'Uncategorized') AS classification
    FROM scilab_material_requests mr
    LEFT JOIN scilab_inventory si ON mr.item = si.item
    WHERE mr.formID = ?
    ORDER BY mr.id ASC
");

$stmt->bind_param('i', $formID);
$stmt->execute();
$materialsResult = $stmt->get_result();

$materials = [];
while ($row = $materialsResult->fetch_assoc()) {
    $materials[] = $row;
}

// Split materials between the equipment page and the reagent page.
$equipmentClasses = ['Equipment', 'Semi Expendable', 'Glassware', 'Specialized Equipment', 'Uncategorized'];
$reagentClasses   = ['Reagent', 'Consumable'];

$equipmentMaterials = [];
$reagentMaterials = [];

foreach ($materials as $material) {
    $classification = trim((string)($material['classification'] ?? ''));
    if (in_array($classification, $reagentClasses)) {
        $reagentMaterials[] = $material;
    } else {
        $equipmentMaterials[] = $material;
    }
}


$stmt->close();

// Fetch student list (if applicable)
$stmt = $conn->prepare("
    SELECT student_name
    FROM scilab_students_involved
    WHERE formID = ?
    ORDER BY id ASC
");

$stmt->bind_param('i', $formID);
$stmt->execute();
$studentsResult = $stmt->get_result();

$students = [];
while ($row = $studentsResult->fetch_assoc()) {
    $students[] = $row['student_name'];
}

// Get requester information from accounts table
$stmt = $conn->prepare("
    SELECT CONCAT(firstname, ' ', middlename, ' ', lastname) as fullName
    FROM accounts
    WHERE employeeID = ?
");

$stmt->bind_param('s', $formData['requesterEmployeeID']);
$stmt->execute();
$requesterResult = $stmt->get_result();
$requesterData = $requesterResult->fetch_assoc();
$stmt->close();

$requesterName = $requesterData['fullName'] ?? 'N/A';
$dateRequested = date('F d, Y', strtotime($formData['dateRequested']));

// Fetch SRS/SRA names for "Approved by"
$srsNames = [];
$srsStmt = $conn->prepare("SELECT firstname, middlename, lastname FROM accounts WHERE position IN ('Sci. Res. Assist.', 'Sci. Research Specialist I') AND status = 'active'");
$srsStmt->execute();
$srsResult = $srsStmt->get_result();
while ($row = $srsResult->fetch_assoc()) {
    $mi = !empty($row['middlename']) ? substr($row['middlename'], 0, 1) . '. ' : '';
    $srsNames[] = strtoupper($row['firstname'] . ' ' . $mi . $row['lastname']);
}
$srsString = implode(" / ", $srsNames);
$srsStmt->close();

$conn->close();

// Fix image path for Dompdf
$logoPath = $_SERVER['DOCUMENT_ROOT'] . "/img/logo.png";
$logoPath = dirname(__DIR__) . "/img/logo.png";

// Helper to generate signature HTML
function getSignatureHtml($relativePath) {
    if (empty($relativePath)) return '';
    $fullPath = dirname(__DIR__) . "/" . $relativePath;
    if (file_exists($fullPath)) {
        return "<img src='{$fullPath}' style='position: absolute; top: -30px; left: 50px; height: 60px; z-index: 10; opacity: 0.9;'>";
    }
    return '';
}

$endorsedBySig = getSignatureHtml($formData['subject_teacher_signature'] ?? '');
$approvedBySig = getSignatureHtml($formData['lab_personnel_signature'] ?? '');

/**
 * Build the "Approved on <date> via the system" line for a given approval stage.
 * Returns empty string when the stage has not been approved (no date recorded).
 */
function formatApprovedOn($formData, $stage) {
    $at = $formData[$stage . '_approved_at'] ?? null;
    $by = trim((string)($formData[$stage . '_approved_by'] ?? ''));
    if (empty($at)) {
        return '';
    }
    $dateStr = date('F j, Y', strtotime($at));
    $byLine = $by !== '' ? ' by ' . htmlspecialchars($by) : '';
    return "Approved on " . $dateStr . " via the system" . $byLine;
}

$endorsedOn = formatApprovedOn($formData, 'subject_teacher');
$approvedOn = formatApprovedOn($formData, 'lab_personnel');
$cidApprovedOn = formatApprovedOn($formData, 'cid_chief');
$supervisorOn = formatApprovedOn($formData, 'supervisor');

// Fallback for approved by if no digital signature but status is approved (legacy support)
if (empty($approvedBySig) && ($formData['statusScilabPersonnel'] ?? '') === 'Approved') {
    $approvedBySig = getSignatureHtml('img/signature.png');
}

// Build HTML
$html = "
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv='Content-Type' content='text/html'; charset='utf-8'/>
    <title>Laboratory Request and Equipment Accountability Form</title>
    <style>
        @page {
            margin: 15mm 20mm;
            size: A4 portrait;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            padding: 50px;
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 8pt;
            line-height: 1.3;
            color: #000;
        }
        
        .header {
            text-align: left;
            margin-bottom: 12pt;
        }
        
        .header h3 {
            font-size: 9pt;
            font-weight: bold;
            margin-bottom: 4pt;
        }
        
        .campus-line {
            display: block;
            margin-bottom: 8pt;
        }
        
        .campus-line span {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 180pt;
            padding-left: 4pt;
        }
        
        .form-title {
            text-align: left;
            font-size: 11pt;
            font-weight: bold;
            margin: 8pt 0 12pt 0;
        }
        
        .form-row {
            width: 100%;
            margin-bottom: 4pt;
            display: table;
        }
        
        .form-field {
            display: table-cell;
            vertical-align: top;
        }
        
        .form-field label {
            font-weight: normal;
            white-space: nowrap;
        }
        
        .form-field .value {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 80pt;
            padding: 0 4pt;
            min-height: 14pt;
        }
        
        .w-50 {
            width: 50%;
        }
        
        .w-60 {
            width: 60%;
        }
        
        .w-40 {
            width: 40%;
        }
        
        .w-100 {
            width: 100%;
        }
        
        .control-row {
            display: table;
            width: 100%;
            margin-bottom: 8pt;
        }
        
        .control-left {
            display: table-cell;
            width: 50%;
        }
        
        .control-right {
            display: table-cell;
            width: 50%;
            text-align: right;
        }
        
        .control-field {
            display: inline-block;
            margin-left: 8pt;
        }
        
        .material-section {
            margin: 8pt 0;
        }
        
        .material-section p {
            margin-bottom: 3pt;
            font-weight: bold;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 6pt 0;
        }
        
        table, th, td {
            border: 1px solid #000;
        }
        
        th {
            text-align: center;
            vertical-align: top;
            font-weight: normal;
            font-size: 8pt;
            padding: 0 10pt;
            background-color: #f0f0f0;
        }
        
        td {
            padding: 2.5pt;
            min-height: 16pt;
            font-size: 8pt;
        }
        
        .notes {
            margin: 8pt 0;
            font-size: 8pt;
        }
        
        .notes ul {
            list-style-type: disc;
            margin-left: 16pt;
        }
        
        .notes li {
            margin-bottom: 2pt;
        }
        
        .signature-section {
            margin-top: 5pt;    
        }
        
        .signature-label {
            display: block;
            text-align: left;
            font-size: 8pt;
            font-style: italic;
            margin-top: -8pt;
            margin-left: 85pt;
        }

        .approved-on {
            display: block;
            text-align: left;
            font-size: 7pt;
            font-style: italic;
            color: #333;
            margin-left: 85pt;
            margin-top: 2pt;
        }

        .labres-approved-on {
            display: block;
            text-align: left;
            font-size: 7pt;
            font-style: italic;
            color: #333;
            margin-left: 110pt;
            margin-top: 2pt;
        }

        .permit-approved-on {
            display: block;
            text-align: left;
            font-size: 7pt;
            font-style: italic;
            color: #333;
            margin-top: 2pt;
        }
        
        .student-list {
            margin: 8pt 0;
        }
        
        .student-list p {
            margin-bottom: 4pt;
        }
        
        .student-list ul {
            padding: 0 150 0 50;
            list-style-type: none;
        }
        
        .student-list li {
            border-bottom: 1px solid #000;
            min-height: 18pt;
            margin-bottom: 2pt;
            padding: 2pt 0;
        }
        
        .footer-text {
            font-size: 9pt;
            margin-top: 16pt;
        }

        .system-record-note {
            font-size: 7pt;
            font-style: italic;
            color: #444;
            text-align: center;
            margin-top: 20pt;
            padding-top: 6pt;
            border-top: 1px solid #999;
            line-height: 1.4;
        }
    </style>

    <style>
        .page-main {
            font-size: 8pt;
        }

        .page-reagent {
            font-size: 9pt;
            page-break-before: always;
        }

        .page-labres {
            font-size: 9pt;
            page-break-before: always;
        }

        .page-permit {
            font-size: 9pt;
            page-break-before: always;
        }

        /* ============ Page 4: Laboratory Reservation Form ============ */
        .labres-header { margin-bottom: 8pt; }
        .labres-header h3 { font-size: 10pt; font-weight: bold; margin-bottom: 6pt; }
        .labres-line { border-bottom: 1px solid #000; display: inline-block; min-width: 180pt; height: 12pt; }
        .labres-title { font-size: 10pt; font-weight: bold; margin-bottom: 10pt; }
        .labres-row { width: 100%; margin-bottom: 5pt; display: table; }
        .labres-field { display: table-cell; vertical-align: top; }
        .labres-label { white-space: nowrap; }
        .labres-value { border-bottom: 1px solid #000; display: inline-block; min-width: 180pt; min-height: 12pt; padding: 0 4pt; margin-left: 6pt; }
        .labres-w-25 { width: 25%; }
        .labres-w-50 { width: 50%; }
        .labres-w-100 { width: 100%; }
        .labres-student-list { margin-top: 6pt; }
        .labres-student-list p { margin-bottom: 4pt; }
        .labres-student-item { margin-bottom: 4pt; }
        .labres-student-line { border-bottom: 1px solid #000; min-width: 300pt; display: inline-block; min-height: 12pt; }
        .labres-signature-section { margin-top: 8pt; }
        .labres-signature-line { border-bottom: 1px solid #000; min-width: 200pt; min-height: 12pt; display: inline-block; padding: 0 4pt; margin-left: 6pt; }
        .labres-signature-label { display: block; font-size: 8pt; margin-left: 110pt; margin-top: 2pt; }
        .labres-footer-text { margin-top: 10pt; font-size: 9pt; }

        /* ============ Page 3: Science Laboratory Work Permit ============ */
        .permit-header { display: table; width: 100%; margin-bottom: 12pt; }
        .permit-logo { display: table-cell; width: 70px; vertical-align: top; }
        .permit-logo img { width: 60px; height: auto; }
        .permit-header-text { display: table-cell; vertical-align: top; padding-left: 10pt; }
        .permit-header-text p { margin-bottom: 2pt; font-weight: bold; }
        .permit-title { text-align: center; font-weight: bold; margin: 16pt 0 10pt 0; font-size: 11pt; }
        .permit-value { border-bottom: 1px solid #000; display: inline-block; min-width: 120pt; min-height: 12pt; padding: 0 4pt; }
        .permit-row { width: 100%; display: table; margin-bottom: 8pt; }
        .permit-field { display: table-cell; vertical-align: top; }
        .permit-w-50 { width: 50%; }
        .permit-w-100 { width: 100%; }
        .permit-line-block { margin-bottom: 6pt; }
        .permit-multi-lines span { display: block; border-bottom: 1px solid #000; min-height: 14pt; margin-bottom: 4pt; }
        .permit-note { font-size: 9pt; margin: 2pt 0; font-style: italic; }
        .permit-signature-section { margin-top: 10pt; }
        .permit-signature-line { border-bottom: 1px solid #000; min-width: 200pt; min-height: 14pt; display: inline-block; }
        .permit-signature-label { display: block; font-size: 8pt; margin-top: 2pt; }
        .permit-approval-block { margin-top: 14pt; }
        .permit-footer-text { font-size: 8pt; margin-top: 20pt; }
    </style>
</head>
<body>

    <div class='page page-main'>

    <div class='header'>
        <h3>PHILIPPINE SCIENCE HIGH SCHOOL SYSTEM</h3>
        <div class='campus-line'>
            <label>CAMPUS:</label>
            <span>ILOCOS REGION</span>
        </div>
    </div>

    <div class='form-title'>
        LABORATORY REQUEST AND EQUIPMENT ACCOUNTABILITY FORM
    </div>
    
    <div class='control-row'>
        <div class='control-left'></div>
        <div class='control-right'>
            <div class='control-field'>
                <label>Control No:</label>
                <span class='value' style='min-width: 60pt;'>" . htmlspecialchars($formData['control_equipment'] ?? '') . "</span>
            </div>
            <div class='control-field'>
                <label>SY:</label>
                <span class='value' style='min-width: 50pt;'>" . htmlspecialchars($formData['sy'] ?? '') . "</span>
            </div>
        </div>
    </div>

    <div class='form-row'>
        <div class='form-field w-60'>
            <label>Grade Level and Section:</label>
            <span class='value' style='min-width: 150pt;'>" . htmlspecialchars('Grade ' . $formData['gradeLevel'] . ' - ' . $formData['sections']) . "</span>
        </div>
        <div class='form-field w-40'>
            <label>Number of Students:</label>
            <span class='value' style='min-width: 60pt;'>" . count($students) . "</span>
        </div>
    </div>
    
    <div class='form-row'>
        <div class='form-field w-60'>
            <label>Subject:</label>
            <span class='value' style='min-width: 150pt;'>" . htmlspecialchars($formData['subject'] ?? '') . "</span>
        </div>
        <div class='form-field w-40'>
            <label>Concurrent Topic:</label>
            <span class='value' style='min-width: 90pt;'>" . htmlspecialchars($formData['subjectTopic'] ?? '') . "</span>
        </div>
    </div>

    <div class='form-row'>
        <div class='form-field w-60'>
            <label>Unit:</label>
            <span class='value' style='min-width: 150pt;'>" . htmlspecialchars($formData['subjectAcademicUnit'] ?? '') . "</span>
        </div>
        <div class='form-field w-40'>
            <label>Teacher In-Charge:</label>
            <span class='value' style='min-width: 90pt;'>" . htmlspecialchars($formData['teacherInCharge'] ?? '') . "</span>
        </div>
    </div>

    <div class='form-row'>
        <div class='form-field w-100'>
            <label>Venue of the Experiment:</label>
            <span class='value' style='min-width: 250pt;'>" . htmlspecialchars($formData['scilabName'] ?? '') . "</span>
        </div>
    </div>

    <div class='form-row'>
        <div class='form-field w-50'>
            <label>Date/Inclusive Date:</label>
            <span class='value' style='min-width: 120pt;'>". htmlspecialchars($formData['inclusiveDate'] ?? '') . "</span>
        </div>
        <div class='form-field w-50'>
            <label>Inclusive Time of Use:</label>
            <span class='value' style='min-width: 120pt;'>". htmlspecialchars($formData['inclusiveTime'] ?? '') . "</span>
        </div>
    </div>


    <div class='material-section'>
        <p>Materials/Equipment Needed:</p>
        <table>
            <thead>
                <tr>
                    <th rowspan='2' style='width: 8%;'>Quantity</th>
                    <th rowspan='2' style='width: 22%;'>Item</th>
                    <th rowspan='2' style='width: 25%;'>Description</th>
                    <th colspan='1' style='width: 20%;'>Issued</th>
                    <th colspan='1' style='width: 25%;'>Returned</th>
                </tr>
                <tr>
                    <th>Condition/Remarks</th>
                    <th>Condition/Remarks</th>
                </tr>
            </thead>
            <tbody>
";

// Add materials rows
if (!empty($equipmentMaterials)) {
    foreach ($equipmentMaterials as $material) {
        $itemName = htmlspecialchars($material['item']);
        if (!empty($material['description'])) {
            $itemDescription = htmlspecialchars($material['description']);
        }else{
            $itemDescription = 'N/A';
        }
        
        $quantity = htmlspecialchars($material['quantity']);
        if (!empty($material['unit'])) {
            $quantity .= ' ' . htmlspecialchars($material['unit']);
        }
        
        $html .= "
        <tr>
            <td style='text-align: center;'>" . $quantity . "</td>
            <td>" . $itemName . "</td>
            <td>" . $itemDescription . "</td>
            <td style='text-align: center;'>" . htmlspecialchars($material['issuedCondition'] ?? '') . "</td>
            <td style='text-align: center;'>" . htmlspecialchars($material['returnedCondition'] ?? '') . "</td>
        </tr>
        ";
    }
}

// Add empty rows if needed (minimum 5 rows)
$emptyRowsNeeded = max(0, 5 - count($equipmentMaterials));
for ($i = 0; $i < $emptyRowsNeeded; $i++) {
    $html .= "
    <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
    </tr>
    ";
}

$html .= "
    </table>
    </div>
    
    <div class='notes'>
        <ul>
            <li>Fill out this form completely and legibly; transact with the Unit SRA concerned during office hours.</li>
            <li>Requests not in accordance with existing Unit regulations and considerations may not be granted.</li>
        </ul>
    </div>

    <div class='signature-section'>
        <div class='form-row'>
            <div class='form-field w-50'>
                <label>Requested by:</label>
                <span class='value' style='min-width: 140pt;'>" . htmlspecialchars($requesterName) . "</span>
            </div>
            <div class='form-field w-50'>
                <label>Date Requested:</label>
                <span class='value' style='min-width: 120pt;'>" . htmlspecialchars($dateRequested) . "</span>
            </div>
        </div>
        <div class='form-row'>
            <div class='form-field w-50'>
                <span class='signature-label'>Teacher/Student</span>
            </div>
        </div>
    </div>

    <div class='student-list'>
        <p>If user of the lab is a group, list down the names of the students:</p>
        <ul>

";

// Add student names
if (!empty($students)) {
    foreach ($students as $index => $studentName) {
        $html .= "
        <li>" . htmlspecialchars($studentName) . "</li>
        ";
    }
}

// Add empty lines for remaining students (minimum 10 lines)
$emptyLinesNeeded = max(0, 5 - count($students));
for ($i = count($students); $i < count($students) + $emptyLinesNeeded; $i++) {
    $html .= "
    <li></li>
    ";
}

$html .= "
        </ul>
    </div>

    <div class='signature-section'>
        <div class='form-row'>
            <div class='form-field w-50'>
                <div style='position: relative;'>
                    <label>Endorsed by:</label>
                    <span class='value' style='min-width: 140pt;'>" . htmlspecialchars($formData['teacherInCharge'] ?? '') . "</span>
                    $endorsedBySig
                    " . ($endorsedOn !== '' ? "<span class='approved-on'>$endorsedOn</span>" : '') . "
                </div>
            </div>
            <div class='form-field w-50'>
                <div style='position: relative;'>
                    <label>Approved by:</label>
                    <span class='value' style='min-width: 140pt;'>" . htmlspecialchars($srsString) . "</span>
                    $approvedBySig
                    " . ($approvedOn !== '' ? "<span class='approved-on'>$approvedOn</span>" : '') . "
                </div>
            </div>
        </div>
        <div class='form-row'>
            <div class='form-field w-50'>
                <span class='signature-label'>Subject Teacher/Unit Head</span>
            </div>
            <div class='form-field w-50'>
                <span class='signature-label'>SRS/SRA</span>
            </div>
        </div>
    </div>

    <div class='footer-text'>
        <p>PSHS-00-F-CIID-20-Ver02-Rev1-10/18/20</p>
    </div>

    <div class='system-record-note'>
        This document is an electronically generated record of the Laboratory Reservation System. The corresponding reservation request has been duly reviewed and digitally approved through the system.
    </div>

    </div>


    <div class='page page-reagent'>

    <div class='header'>
        <h3>PHILIPPINE SCIENCE HIGH SCHOOL SYSTEM</h3>
        <div class='campus-line'>
            <label>CAMPUS:</label>
            <span>ILOCOS REGION</span>
        </div>
    </div>

    <div class='form-title'>
        REAGENT REQUEST FORM
    </div>
    
    <div class='control-row'>
        <div class='control-left'></div>
        <div class='control-right'>
            <div class='control-field'>
                <label>Control No:</label>
                <span class='value' style='min-width: 60pt;'>" . htmlspecialchars($formData['control_reagent'] ?? '') . "</span>
            </div>
            <div class='control-field'>
                <label>SY:</label>
                <span class='value' style='min-width: 50pt;'>" . htmlspecialchars($formData['sy'] ?? '') . "</span>
            </div>
        </div>
    </div>

    <div class='form-row'>
        <div class='form-field w-60'>
            <label>Grade Level and Section:</label>
            <span class='value' style='min-width: 150pt;'>" . htmlspecialchars('Grade ' . $formData['gradeLevel'] . ' - ' . $formData['sections']) . "</span>
        </div>
        <div class='form-field w-40'>
            <label>Number of Students:</label>
            <span class='value' style='min-width: 60pt;'>" . count($students) . "</span>
        </div>
    </div>
    
    <div class='form-row'>
        <div class='form-field w-60'>
            <label>Subject:</label>
            <span class='value' style='min-width: 150pt;'>" . htmlspecialchars($formData['subject'] ?? '') . "</span>
        </div>
        <div class='form-field w-40'>
            <label>Concurrent Topic:</label>
            <span class='value' style='min-width: 90pt;'>" . htmlspecialchars($formData['subjectTopic'] ?? '') . "</span>
        </div>
    </div>

    <div class='form-row'>
        <div class='form-field w-60'>
            <label>Unit:</label>
            <span class='value' style='min-width: 150pt;'>" . htmlspecialchars($formData['subjectAcademicUnit'] ?? '') . "</span>
        </div>
        <div class='form-field w-40'>
            <label>Teacher In-Charge:</label>
            <span class='value' style='min-width: 90pt;'>" . htmlspecialchars($formData['teacherInCharge'] ?? '') . "</span>
        </div>
    </div>

    <div class='form-row'>
        <div class='form-field w-100'>
            <label>Venue of the Experiment:</label>
            <span class='value' style='min-width: 250pt;'>" . htmlspecialchars($formData['scilabName'] ?? '') . "</span>
        </div>
    </div>

    <div class='form-row'>
        <div class='form-field w-50'>
            <label>Date/Inclusive Date:</label>
            <span class='value' style='min-width: 120pt;'>". htmlspecialchars($formData['inclusiveDate'] ?? '') . "</span>
        </div>
        <div class='form-field w-50'>
            <label>Inclusive Time of Use:</label>
            <span class='value' style='min-width: 120pt;'>". htmlspecialchars($formData['inclusiveTime'] ?? '') . "</span>
        </div>
    </div>


    <div class='material-section'>
        <p>Reagent Needed:</p>
        <table>
            <thead>
                <tr>
                    <th style='width: 8%;'>Quantity</th>
                    <th style='width: 33%;'>Reagent</th>
                    <th style='width: 16%;'>SDS [&check;][X]</th>
                    <th style='width: 33%;'>Issued&#10;&#13;Amount/Remarks</th>
                </tr>
            </thead>
            <tbody>
";

// Add materials rows
if (!empty($reagentMaterials)) {
    foreach ($reagentMaterials as $material) {
        $itemName = htmlspecialchars($material['item']);
        if (!empty($material['description'])) {
            $itemDescription = htmlspecialchars($material['description']);
        }else{
            $itemDescription = 'N/A';
        }
        
        $quantity = htmlspecialchars($material['quantity']);
        if (!empty($material['unit'])) {
            $quantity .= ' ' . htmlspecialchars($material['unit']);
        }
        
        $html .= "
        <tr>
            <td style='text-align: center;'>" . $quantity . "</td>
            <td>" . $itemName . "</td>
            <td>&nbsp;</td>
            <td style='text-align: center;'>&nbsp;</td>
        </tr>
        ";
    }
}

// Add empty rows if needed (minimum 5 rows)
$emptyRowsNeeded = max(0, 5 - count($reagentMaterials));
for ($i = 0; $i < $emptyRowsNeeded; $i++) {
    $html .= "
    <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
    </tr>
    ";
}

$html .= "
    </table>
    </div>
    
    <div class='notes'>
        <ul>
            <li>Students must certify that he/she/they have read the safety information as specified in the Safety Data Sheet (SDS) of the reagents being requested.</li>
            <li>This form must be filled out completely and legibly and submitted, together with a suitable container with cover and proper label, to the SRA of the unit which will release the reagents.</li>
            <li>Requests not in accordance with existing Unit regulations and considerations may not be granted.</li>
            <li>The reagents will be released to the SRA of the requesting unit.</li>
        </ul>
    </div>

    <div class='signature-section'>
        <div class='form-row'>
            <div class='form-field w-50'>
                <label>Requested by:</label>
                <span class='value' style='min-width: 140pt;'>" . htmlspecialchars($requesterName) . "</span>
            </div>
            <div class='form-field w-50'>
                <label>Date Requested:</label>
                <span class='value' style='min-width: 120pt;'>" . htmlspecialchars($dateRequested) . "</span>
            </div>
        </div>
        <div class='form-row'>
            <div class='form-field w-50'>
                <span class='signature-label'>Teacher/Student</span>
            </div>
        </div>
    </div>

    <div class='student-list'>
        <p>If user of the lab is a group, list down the names of the students:</p>
        <ul>

";

// Add student names
if (!empty($students)) {
    foreach ($students as $index => $studentName) {
        $html .= "
        <li>" . htmlspecialchars($studentName) . "</li>
        ";
    }
}

// Add empty lines for remaining students (minimum 10 lines)
$emptyLinesNeeded = max(0, 5 - count($students));
for ($i = count($students); $i < count($students) + $emptyLinesNeeded; $i++) {
    $html .= "
    <li></li>
    ";
}

$html .= "
        </ul>
    </div>

    <div class='signature-section'>
        <div class='form-row'>
            <div class='form-field w-50'>
                <div style='position: relative;'>
                    <label>Endorsed by:</label>
                    <span class='value' style='min-width: 140pt;'>" . htmlspecialchars($formData['teacherInCharge'] ?? '') . "</span>
                    $endorsedBySig
                    " . ($endorsedOn !== '' ? "<span class='approved-on'>$endorsedOn</span>" : '') . "
                </div>
            </div>
            <div class='form-field w-50'>
                <div style='position: relative;'>
                    <label>Approved by:</label>
                    <span class='value' style='min-width: 140pt;'>" . htmlspecialchars($srsString) . "</span>
                    $approvedBySig
                    " . ($approvedOn !== '' ? "<span class='approved-on'>$approvedOn</span>" : '') . "
                </div>
            </div>
        </div>
        <div class='form-row'>
            <div class='form-field w-50'>
                <span class='signature-label'>Subject Teacher/Unit Head</span>
            </div>
            <div class='form-field w-50'>
                <span class='signature-label'>SRS/SRA</span>
            </div>
        </div>
    </div>

    <div class='footer-text'>
        <p>PSHS-00-F-CIID-20-Ver02-Rev1-10/18/20</p>
    </div>

    <div class='system-record-note'>
        This document is an electronically generated record of the Laboratory Reservation System. The corresponding reservation request has been duly reviewed and digitally approved through the system.
    </div>

    </div>

    <div class='page page-permit'>

        <div class='permit-header'>
            <div class='permit-logo'>
                <img src='" . $logoPath . "' alt='Logo' style='width:60px; height:auto;'>
            </div>
            <div class='permit-header-text'>
                <p>Republic of the Philippines</p>
                <p>DEPARTMENT OF SCIENCE AND TECHNOLOGY</p>
                <p>PHILIPPINE SCIENCE HIGH SCHOOL</p>
                <p>ILOCOS REGION CAMPUS</p>
            </div>
        </div>

        <div class='permit-title'>
            SCIENCE LABORATORY WORK PERMIT No:
            <span class='permit-value' style='min-width: 200pt;'>" . htmlspecialchars($formData['control_permit'] ?? '') . "</span>
        </div>

        <div class='permit-row'>
            <div class='permit-field permit-w-50'>
                Date:
                <span class='permit-value' style='min-width: 140pt;'>" . htmlspecialchars($dateRequested) . "</span>
            </div>
            <div class='permit-field permit-w-50'></div>
        </div>

        <div class='permit-line-block'>
            Name/s:
            <span class='permit-value' style='min-width: 400pt;'>" . htmlspecialchars($requesterName) . "</span>
        </div>
        __PERMIT_NAMES__

        <div class='permit-row'>
            <div class='permit-field permit-w-50'>
                Year and Section:
                <span class='permit-value' style='min-width: 180pt;'>" . htmlspecialchars('Grade ' . $formData['gradeLevel'] . ' - ' . $formData['sections']) . "</span>
            </div>
            <div class='permit-field permit-w-50'></div>
        </div>

        <div class='permit-line-block'>
            Inclusive Date/s of Activity:
            <span class='permit-value' style='min-width: 300pt;'>" . htmlspecialchars(($formData['inclusiveDate'] ?? '') . (!empty($formData['inclusiveTime']) ? ' (' . $formData['inclusiveTime'] . ')' : '')) . "</span>
        </div>

        <br>
        <div class='permit-note'>
            (To be filled out by the Students)
        </div>

        <div class='permit-line-block'>
            <strong>Specific Laboratory Activities to be Undertaken Schedule (date and time)</strong>
        </div>

        <div class='permit-multi-lines'>
            <span>" . htmlspecialchars($formData['subjectTopic'] ?? '') . "</span>
            <span></span>
            <span></span>
            <span></span>
        </div>

        <div class='permit-note' style='font-weight: bold;'>
            (STRICTLY follow the scheduled activities. For changes inform the Science Research Specialists/Assistant)
        </div>

        <br>
        <div class='permit-signature-section'>
            Signature of Student/s:
            <div class='permit-multi-lines'>
                <span></span>
                <span></span>
            </div>
        </div>

        <br>
        <div class='permit-note'>
            (To be filled out by the Subject Teacher)
        </div>

        <div class='permit-row'>
            <div class='permit-field permit-w-50'>
                Designated Supervisor:
                <span class='permit-signature-line'></span>
                <span class='permit-signature-label' style='margin-left: 170pt;'>Name/Signature</span>
            </div>
            <div class='permit-field permit-w-50' style='text-align: right;'>
                <span class='permit-signature-line'></span>
                <span class='permit-signature-label'>Signature over Printed Name of Subject Teacher</span>
            </div>
        </div>

        <div class='permit-approval-block'>
            <div class='permit-line-block'>
                Noted:
            </div>
            <br>
            <div class='permit-line-block' style='font-weight: bold;'>
                PISANDAGAN / MRAGASA / EPURISIMA
            </div>
            <div class='permit-line-block'>
                S.R.S.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                S.R.S.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                S.R.A.
            </div>
            <br>
            <div class='permit-line-block' style='margin-top: 10pt;'>
                Approved:
            </div>
            <br><br>
            <div class='permit-line-block'>
                <strong>MARY ANN R. LAGUA</strong>
            </div>
            <div class='permit-line-block'>
                CID Chief
            </div>
            " . ($cidApprovedOn !== '' ? "<span class='permit-approved-on'>$cidApprovedOn</span>" : '') . "
        </div>

        <div class='permit-footer-text'>
            PSHS-08-F-CID-13-Rev0-11/07/19
        </div>

    </div>

    <div class='page page-labres'>

        <div class='labres-header'>
            <h3>PHILIPPINE SCIENCE HIGH SCHOOL SYSTEM</h3>
            <div style='font-weight: bold;'>
                CAMPUS: <span class='labres-line' style='min-width: 180pt;'>ILOCOS REGION</span>
            </div>
        </div>

        <div class='labres-title'>
            LABORATORY RESERVATION FORM
        </div>

        <div class='labres-row'>
            <div class='labres-field labres-w-50'></div>
            <div class='labres-field labres-w-25'>
                <span class='labres-label'>Control No:</span>
                <span class='labres-value' style='min-width: 80pt;'>" . htmlspecialchars($formData['control_reservation'] ?? '') . "</span>
            </div>
            <div class='labres-field labres-w-25'>
                <span class='labres-label'>SY:</span>
                <span class='labres-value' style='min-width: 120pt;'>" . htmlspecialchars($formData['sy'] ?? '') . "</span>
            </div>
        </div>

        <div class='labres-row'>
            <div class='labres-field labres-w-50'>
                <span class='labres-label'>Grade Level and Section:</span>
                <span class='labres-value'>" . htmlspecialchars('Grade ' . $formData['gradeLevel'] . ' - ' . $formData['sections']) . "</span>
            </div>
            <div class='labres-field labres-w-50'>
                <span class='labres-label'>Number of Students:</span>
                <span class='labres-value' style='min-width: 80pt;'>" . count($students) . "</span>
            </div>
        </div>

        <div class='labres-row'>
            <div class='labres-field labres-w-50'>
                <span class='labres-label'>Subject:</span>
                <span class='labres-value'>" . htmlspecialchars($formData['subject'] ?? '') . "</span>
            </div>
            <div class='labres-field labres-w-50'>
                <span class='labres-label'>Teacher In-Charge:</span>
                <span class='labres-value'>" . htmlspecialchars($formData['teacherInCharge'] ?? '') . "</span>
            </div>
        </div>

        <div class='labres-row'>
            <div class='labres-field labres-w-50'>
                <span class='labres-label'>Date/Inclusive Dates:</span>
                <span class='labres-value'>" . htmlspecialchars($formData['inclusiveDate'] ?? '') . "</span>
            </div>
            <div class='labres-field labres-w-50'>
                <span class='labres-label'>Inclusive Time of Use:</span>
                <span class='labres-value'>" . htmlspecialchars($formData['inclusiveTime'] ?? '') . "</span>
            </div>
        </div>

        <div class='labres-row'>
            <div class='labres-field labres-w-100'>
                <span class='labres-label'>Preferred Lab Room:</span>
                <span class='labres-value' style='min-width: 350pt;'>" . htmlspecialchars($formData['scilabName'] ?? '') . "</span>
            </div>
        </div>

        <div class='labres-row'>
            <div class='labres-field labres-w-50'>
                <span class='labres-label'>Requested by:</span>
                <span class='labres-value' style='min-width: 200pt;'>" . htmlspecialchars($requesterName) . "</span>
            </div>
            <div class='labres-field labres-w-50'>
                <span class='labres-label'>Date Requested:</span>
                <span class='labres-value' style='min-width: 160pt;'>" . htmlspecialchars($dateRequested) . "</span>
            </div>
        </div>

        <div class='labres-student-list'>
            <p>If user of the lab is a group, list down the names of students.</p>
            __LABRES_STUDENTS__
        </div>

        <div class='labres-signature-section'>
            <div class='labres-row'>
                <div class='labres-field labres-w-100'>
                    <span class='labres-label'>Endorsed by:</span>
                    <span class='labres-signature-line'>" . htmlspecialchars($formData['teacherInCharge'] ?? '') . "</span>
                    <span class='labres-signature-label'>Subject Teacher/Unit Head</span>
                    " . ($endorsedOn !== '' ? "<span class='labres-approved-on'>$endorsedOn</span>" : '') . "
                </div>
            </div>
            <div class='labres-row' style='margin-top: 8pt;'>
                <div class='labres-field labres-w-100'>
                    <span class='labres-label'>Approved by:</span>
                    <span class='labres-signature-line'>" . htmlspecialchars($srsString) . "</span>
                    <span class='labres-signature-label'>SRS / SRA</span>
                    " . ($approvedOn !== '' ? "<span class='labres-approved-on'>$approvedOn</span>" : '') . "
                </div>
            </div>
        </div>

        <div class='labres-footer-text'>
            PSHS-00-F-CID-05-Ver02-Rev1-10/18/20
        </div>

    </div>

</body>
</html>
";

// Fill reservation form student lines (up to 3)
$labresStudents = '';
for ($i = 1; $i <= 3; $i++) {
    $studentValue = htmlspecialchars($students[$i - 1] ?? '');
    $labresStudents .= "<div class='labres-student-item'>{$i}. <span class='labres-student-line'>{$studentValue}</span></div>";
}
$html = str_replace('__LABRES_STUDENTS__', $labresStudents, $html);

// Fill work permit remaining name lines with student names
$permitNames = '';
if (!empty($students)) {
    $permitNames = "<div class='permit-line-block'><span class='permit-value' style='min-width: 400pt;'>" . htmlspecialchars(implode(', ', $students)) . "</span></div>";
}
$html = str_replace('__PERMIT_NAMES__', $permitNames, $html);

// Generate PDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
// Set a temp directory for Dompdf, which can help on restricted hosting environments
$options->set('tempDir', sys_get_temp_dir());
// Set chroot to the project root for security and to help resolve local file paths
$options->set('chroot', dirname(__DIR__));

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = "Lab_Request_Form_" . (($formData['control_equipment'] ?? '') !== '' ? $formData['control_equipment'] : ($formData['controlNumber'] ?? $formID)) . ".pdf";
$dompdf->stream($filename, ["Attachment" => false]);

?>
