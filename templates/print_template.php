<?php
  include('../helperFiles/db_connection.php');

  $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

  $stmt = $conn->prepare("SELECT r.*, a.firstname, a.middlename, a.lastname 
                          FROM scilab_form_requests r
                          JOIN accounts a ON r.requesterEmployeeID = a.employeeID
                          WHERE r.id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $result = $stmt->get_result();

  if (!$result || $result->num_rows === 0) {
      die("Invalid request.");
  }

  $row = $result->fetch_assoc();

  // Fetch materials
  $materials = [];
  $matResult = $conn->query("SELECT item, quantity, description FROM scilab_material_requests WHERE formID = $id");
  while ($mat = $matResult->fetch_assoc()) {
      $desc = $mat['description'] ?: 'N/A';
      $materials[] = "{$mat['quantity']} x {$mat['item']} ({$desc})";
  }

  $requesterName = "{$row['firstname']} {$row['middlename']} {$row['lastname']}";
  $logo = 'data:image/png;base64,' . base64_encode(file_get_contents('../img/pshsLogo.png'));
  $venue = $row['scilabName'];
  $grade = $row['gradeLevel'];
  $section = $row['section/s'];
  $subject = $row['subject'];
  $topic = $row['subjectTopic'];
  $unit = $row['unit'] ?? 'N/A';
  $teacher = $row['teacher'] ?? 'N/A';
  $startDate = $row['inclusiveDate'];
  $time = $row['inclusiveTime'];
  $students = $row['students'] ?? 'None';

  $materialsList = !empty($materials) ? implode("; ", $materials) : "None";
  ?>
  <!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Laboratory Request and Equipment Accountability Form</title>
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;
      display: flex;
      justify-content: center;
    }

    .a4-page {
      background: white;
      width: 210mm;
      height: 297mm;
      padding: 20mm;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
      font-size: 12px;
    }

    .header, .section-title {
      text-align: center;
      font-weight: bold;
    }

    .form-group {
      margin: 5px 0;
    }

    .form-line {
      display: flex;
      justify-content: space-between;
    }

    .form-line div {
      flex: 1;
      margin-right: 10px;
    }

    .form-line div:last-child {
      margin-right: 0;
    }

    .table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }

    .table, .table th, .table td {
      border: 1px solid black;
    }

    .table th, .table td {
      height: 20px;
      text-align: left;
      padding: 2px 4px;
    }

    .signature-section {
      margin-top: 20px;
    }

    .signature-section div {
      margin-bottom: 5px;
    }

    .footer {
      font-size: 10px;
      margin-top: 10px;
    }

    .student-list {
      margin-top: 10px;
    }

    .student-list div {
      margin-bottom: 3px;
    }

    @media print {
      .a4-page {
        box-shadow: none;
        margin: 0;
      }
    }
  </style>
</head>
<body>
  <div class="a4-page">
    <div class="header">
      PHILIPPINE SCIENCE HIGH SCHOOL SYSTEM<br>
      CAMPUS: ______________________<br><br>
      <div class="section-title">LABORATORY REQUEST AND EQUIPMENT ACCOUNTABILITY FORM</div>
    </div>

    <br>
    <div class="form-line">
      <div>Grade Level and Section: <?= htmlspecialchars($grade) ?> - <?= htmlspecialchars($section) ?></div>
      <div>Control No: __________</div>
      <div>SY: __________</div>
    </div>

    <div class="form-line">
      <div>Number of Students: ____________</div>
      <div>Subject: <?= htmlspecialchars($subject) ?></div>
      <div>Concurrent Topic: <?= htmlspecialchars($topic) ?></div>
    </div>

    <div class="form-line">
      <div>Unit: <?= htmlspecialchars($unit) ?></div>
      <div>Teacher In-Charge: <?= htmlspecialchars($teacher) ?></div>
    </div>

    <div class="form-line">
      <div>Venue of the Experiment: <?= htmlspecialchars($venue) ?></div>
    </div>

    <div class="form-line">
      <div>Date/Inclusive Date: <?= htmlspecialchars($startDate) ?></div>
      <div>Inclusive Time of Use: <?= htmlspecialchars($time) ?></div>
    </div>

    <br><b>Materials/ Equipment Needed:</b>
    <table class="table">
      <thead>
        <tr>
          <th>Quantity</th>
          <th>Item</th>
          <th>Description</th>
          <th colspan="2">Issued</th>
          <th colspan="2">Returned</th>
        </tr>
        <tr>
          <th></th><th></th><th></th>
          <th>Condition</th><th></th>
          <th>Condition</th><th>Remarks</th>
        </tr>
      </thead>
      <tbody>
        <?php for ($i = 0; $i < 10; $i++): ?>
        <tr>
          <td></td><td></td><td></td><td></td><td></td><td></td><td></td>
        </tr>
        <?php endfor; ?>
      </tbody>
    </table>

    <div class="form-line" style="margin-top: 10px;">
      <div>Received by:<br><br>________________________</div>
      <div>Date: __________</div>
      <div>Received and Inspected by:<br><br>________________________</div>
      <div>Date: __________</div>
    </div>

    <div class="footer">
      <p>* Fill out this form completely and legibly; transact with the Unit SRA concerned during office hours.</p>
      <p>* Requests not in accordance with existing Unit regulations and considerations may not be granted.</p>
    </div>

    <br>
    <div class="form-line">
      <div>Requested by: <?= htmlspecialchars($requesterName) ?></div>
      <div>Date Requested: <?= date("Y-m-d") ?></div>
    </div>

    <div class="form-group student-list">
      <b>Teacher/Student</b><br>
      <p>If user of the lab is a group, list down the names of students.</p>
      <?php 
        $studentList = array_map('trim', explode(",", $students));
        for ($i = 0; $i < 5; $i++): 
      ?>
        <div><?= ($i + 1) ?>. <?= htmlspecialchars($studentList[$i] ?? '') ?></div>
      <?php endfor; ?>
    </div>

    <br>
    <div class="form-line">
      <div>Endorsed by: _________________________<br>Subject Teacher/Unit Head</div>
      <div>Approved by: _________________________<br>SRS / SRA</div>
    </div>

    <div class="footer" style="margin-top: 30px;">
      PSHS-00-F-CIID-20-Ver02-Rev1-10/18/20
    </div>
  </div>
</body>
</html>
