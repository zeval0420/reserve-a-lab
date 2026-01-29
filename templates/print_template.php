<?php
require_once '../vendor/autoload.php';
include('../helperFiles/db_connection.php');

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
        fr.sy,
        fr.gradeLevel,
        fr.`section/s`,
        fr.scilabName,
        fr.subject,
        fr.subjectTopic,
        fr.teacherInCharge,
        fr.inclusiveDate,
        fr.inclusiveTime,
        fr.requesterEmployeeID,
        fr.dateRequested
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
        mr.returnedItemInspector
    FROM scilab_material_requests mr
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

$conn->close();

// Fix image path for Dompdf
$logoPath = $_SERVER['DOCUMENT_ROOT'] . "/img/logo.png";

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
            margin-bottom: 6pt;
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
            margin: 12pt 0;
        }
        
        .material-section p {
            margin-bottom: 4pt;
            font-weight: bold;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 8pt 0;
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
            padding: 3pt;
            min-height: 16pt;
            font-size: 8pt;
        }
        
        .notes {
            margin: 12pt 0;
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
            margin-top: 8pt;    
        }
        
        .signature-label {
            display: block;
            text-align: left;
            font-size: 8pt;
            font-style: italic;
            margin-top: -10pt;
            margin-left: 85pt;
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
    </style>
</head>
<body>

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
                <span class='value' style='min-width: 60pt;'>" . htmlspecialchars($formData['controlNumber'] ?? '') . "</span>
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
            <span class='value' style='min-width: 150pt;'>" . htmlspecialchars('Grade ' . $formData['gradeLevel'] . ' - ' . $formData['section/s']) . "</span>
        </div>
        <div class='form-field w-40'>
            <label>Number of Students:</label>
            <span class='value' style='min-width: 60pt;'>25</span>
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
            <span class='value' style='min-width: 150pt;'>Chemical Reactions</span>
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
if (!empty($materials)) {
    foreach ($materials as $material) {
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
$emptyRowsNeeded = max(0, 5 - count($materials));
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
                <span class='value' style='min-width: 140pt;'><?php echo htmlspecialchars($requesterName); ?></span>
            </div>
            <div class='form-field w-50'>
                <label>Date Requested:</label>
                <span class='value' style='min-width: 120pt;'><?php echo htmlspecialchars($dateRequested); ?></span>
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
                <label>Endorsed by:</label>
                <span class='value' style='min-width: 140pt;'></span>
            </div>
            <div class='form-field w-50'>
                <label>Approved by:</label>
                <span class='value' style='min-width: 140pt;'></span>
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


</body>
</html>
";

// Generate PDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = "Lab_Request_Form_" . ($formData['controlNumber'] ?? $formID) . ".pdf";
$dompdf->stream($filename, ["Attachment" => false]);
?>