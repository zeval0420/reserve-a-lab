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
    <meta charset='UTF-8'>
    <title>Laboratory Request Form</title>
    <style>
        @page {
            margin: 0.5in;
        }
        
        body { 
            font-family: Arial, sans-serif; 
            font-size: 10pt; 
            color: #000; 
            line-height: 1.3;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
        }

        .header h1 {
            margin: 2px 0;
            font-size: 12pt;
            font-weight: bold;
        }

        .header h2 {
            margin: 2px 0;
            font-size: 11pt;
            font-weight: bold;
        }

        .control-sy-row {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }

        .control-sy-cell {
            display: table-cell;
            width: 50%;
            border: 1px solid #000;
            padding: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .info-table td {
            border: 1px solid #000;
            padding: 4px 8px;
        }

        .info-table td:first-child {
            width: 35%;
            font-weight: normal;
        }

        .section-title {
            font-weight: bold;
            margin: 10px 0 5px 0;
        }

        .materials-table th {
            background: #d9d9d9;
            border: 1px solid #000;
            padding: 5px;
            font-weight: bold;
            text-align: center;
            font-size: 9pt;
        }

        .materials-table td {
            border: 1px solid #000;
            padding: 5px;
            font-size: 9pt;
        }

        .signature-row {
            display: table;
            width: 100%;
            margin: 15px 0;
        }

        .signature-cell {
            display: table-cell;
            width: 50%;
            padding: 0 10px;
        }

        .signature-line {
            margin-top: 30px;
            border-bottom: 1px solid #000;
            display: inline-block;
            width: 200px;
        }

        .instructions {
            font-size: 9pt;
            font-style: italic;
            margin: 10px 0;
        }

        .student-list {
            margin: 10px 0;
        }

        .student-list-item {
            margin: 3px 0;
        }

        .form-code {
            text-align: center;
            font-size: 8pt;
            font-style: italic;
            margin-top: 20px;
        }

    </style>
</head>
<body>

    <div class='header'>
        <h1>PHILIPPINE SCIENCE HIGH SCHOOL SYSTEM</h1>
        <h1>CAMPUS: ILOCOS REGION</h1>
        <h2>LABORATORY REQUEST AND EQUIPMENT ACCOUNTABILITY FORM</h2>
    </div>

    <div class='control-sy-row'>
        <div class='control-sy-cell'>
            <strong>Control No:</strong> " . htmlspecialchars($formData['controlNumber'] ?? '') . "
        </div>
        <div class='control-sy-cell'>
            <strong>SY:</strong> " . htmlspecialchars($formData['sy'] ?? '') . "
        </div>
    </div>

    <table class='info-table'>
        <tr>
            <td><strong>Grade Level and Section:</strong></td>
            <td>" . htmlspecialchars('Grade ' . $formData['gradeLevel'] . ' - ' . $formData['section/s']) . "</td>
        </tr>
        <tr>
            <td><strong>Subject:</strong></td>
            <td>" . htmlspecialchars($formData['subject'] ?? '') . "</td>
        </tr>
        <tr>
            <td><strong>Concurrent Topic:</strong></td>
            <td>" . htmlspecialchars($formData['subjectTopic'] ?? '') . "</td>
        </tr>
        <tr>
            <td><strong>Teacher In-Charge:</strong></td>
            <td>" . htmlspecialchars($formData['teacherInCharge'] ?? '') . "</td>
        </tr>
        <tr>
            <td><strong>Venue of the Experiment:</strong></td>
            <td>" . htmlspecialchars($formData['scilabName'] ?? '') . "</td>
        </tr>
        <tr>
            <td><strong>Date/Inclusive Date:</strong></td>
            <td>" . htmlspecialchars($formData['inclusiveDate'] ?? '') . "</td>
        </tr>
        <tr>
            <td><strong>Inclusive Time of Use:</strong></td>
            <td>" . htmlspecialchars($formData['inclusiveTime'] ?? '') . "</td>
        </tr>
    </table>

    <div class='section-title'>Materials/Equipment Needed:</div>

    <table class='materials-table'>
        <tr>
            <th style='width: 10%;'>Quantity</th>
            <th style='width: 35%;'>Item Description</th>
            <th style='width: 10%;'>Issued</th>
            <th style='width: 10%;'>Returned</th>
            <th style='width: 10%;'>Condition</th>
            <th style='width: 25%;'>Condition/Remarks</th>
        </tr>
";

// Add materials rows
if (!empty($materials)) {
    foreach ($materials as $material) {
        $itemDescription = htmlspecialchars($material['item']);
        if (!empty($material['description'])) {
            $itemDescription .= ' - ' . htmlspecialchars($material['description']);
        }
        
        $quantity = htmlspecialchars($material['quantity']);
        if (!empty($material['unit'])) {
            $quantity .= ' ' . htmlspecialchars($material['unit']);
        }
        
        $html .= "
        <tr>
            <td style='text-align: center;'>" . $quantity . "</td>
            <td>" . $itemDescription . "</td>
            <td style='text-align: center;'>" . htmlspecialchars($material['issuedCondition'] ?? '') . "</td>
            <td style='text-align: center;'>" . htmlspecialchars($material['returnedCondition'] ?? '') . "</td>
            <td style='text-align: center;'>" . htmlspecialchars($material['returnedCondition'] ?? '') . "</td>
            <td>" . htmlspecialchars($material['returnedItemInspector'] ?? '') . "</td>
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
        <td>&nbsp;</td>
    </tr>
    ";
}

$html .= "
    </table>

    <div class='signature-row'>
        <div class='signature-cell'>
            <strong>Received by:</strong> <span class='signature-line'></span>
            <div style='margin-top: 5px;'><strong>Date:</strong> _______________</div>
        </div>
        <div class='signature-cell'>
            <strong>Received and Inspected by:</strong> <span class='signature-line'></span>
            <div style='margin-top: 5px;'><strong>Date:</strong> _______________</div>
        </div>
    </div>

    <div class='instructions'>
        * Fill out this form completely and legibly; transact with the Unit SRA concerned during office hours.<br>
        * Requests not in accordance with existing Unit regulations and considerations may not be granted.
    </div>

    <table class='info-table'>
        <tr>
            <td><strong>Requested:</strong></td>
            <td>" . htmlspecialchars($requesterName) . "</td>
        </tr>
        <tr>
            <td><strong>Date Requested:</strong></td>
            <td>" . htmlspecialchars($dateRequested) . "</td>
        </tr>
        <tr>
            <td></td>
            <td style='text-align: center;'><strong>Teacher/Student</strong></td>
        </tr>
    </table>

    <div class='section-title'>If user of the lab is a group, list down the names of the students:</div>

    <div class='student-list'>
";

// Add student names
if (!empty($students)) {
    foreach ($students as $index => $studentName) {
        $html .= "
        <div class='student-list-item'>" . ($index + 1) . ". " . htmlspecialchars($studentName) . "</div>
        ";
    }
}

// Add empty lines for remaining students (minimum 10 lines)
$emptyLinesNeeded = max(0, 10 - count($students));
for ($i = count($students); $i < count($students) + $emptyLinesNeeded; $i++) {
    $html .= "
    <div class='student-list-item'>" . ($i + 1) . ". _________________________________</div>
    ";
}

$html .= "
    </div>

    <div class='signature-row' style='margin-top: 30px;'>
        <div class='signature-cell'>
            <strong>Endorsed by:</strong>
            <div class='signature-line' style='margin-top: 40px;'></div>
            <div style='text-align: center; margin-top: 5px;'><strong>Subject Teacher/Unit Head</strong></div>
        </div>
        <div class='signature-cell'>
            <strong>Approved by:</strong>
            <div class='signature-line' style='margin-top: 40px;'></div>
            <div style='text-align: center; margin-top: 5px;'><strong>SRS/SRA</strong></div>
        </div>
    </div>

    <div class='form-code'>
        PSHS-00-F-CIID-20-Ver02-Rev1-10/18/20
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