<?php
require '../dompdf/vendor/autoload.php';
require '../helperFiles/db_connection.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Get form ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch form data
$stmt = $conn->prepare("SELECT sfr.*, a.firstname, a.middlename, a.lastname 
    FROM scilab_form_requests sfr
    LEFT JOIN accounts a ON sfr.requesterEmployeeID = a.employeeID
    WHERE sfr.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$form = $result->fetch_assoc();

if (!$form) {
    die("Form not found.");
}

// Fetch materials
$materials = [];
$materialQuery = $conn->prepare("SELECT * FROM scilab_material_requests WHERE formID = ?");
$materialQuery->bind_param("i", $id);
$materialQuery->execute();
$materialResult = $materialQuery->get_result();
while ($row = $materialResult->fetch_assoc()) {
    $materials[] = $row;
}

// Fetch students
$students = [];
$studentQuery = $conn->prepare("SELECT * FROM scilab_students_involved WHERE formID = ?");
$studentQuery->bind_param("i", $id);
$studentQuery->execute();
$studentResult = $studentQuery->get_result();
while ($row = $studentResult->fetch_assoc()) {
    $students[] = $row['student_name'];
}

// HTML
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Science Lab Request System</title>
<link href="https://fonts.googleapis.com/css2?family=SF+Pro+Display:wght@400;600&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<style>
  body {
    margin: 0;
    padding: 40px;
    font-family: 'Poppins', 'SF Pro Display', sans-serif;
    background: linear-gradient(135deg, #eaeaea, #f5f5f5, #fdfdfd);
    color: #111;
  }

  .container {
    background: rgba(255, 255, 255, 0.15);
    border-radius: 28px;
    backdrop-filter: blur(25px) saturate(180%);
    -webkit-backdrop-filter: blur(25px) saturate(180%);
    border: 2px solid rgba(255, 255, 255, 0.55);
    box-shadow:
      0 25px 60px rgba(0, 0, 0, 0.25),
      inset 0 1px 1px rgba(255, 255, 255, 0.6),
      inset 0 0 30px rgba(255, 255, 255, 0.25);
    max-width: 720px;
    margin: auto;
    padding: 40px 50px;
  }

  h1 {
    text-align: center;
    font-size: 22px;
    font-weight: 600;
    letter-spacing: 0.3px;
    text-shadow: 0 1px 2px rgba(255, 255, 255, 0.6);
    margin-bottom: 25px;
  }

  .section {
    margin-bottom: 25px;
    background: rgba(255, 255, 255, 0.25);
    border-radius: 20px;
    padding: 20px 25px;
    border: 1px solid rgba(255, 255, 255, 0.55);
    box-shadow:
      0 6px 20px rgba(0, 0, 0, 0.05),
      inset 0 0 20px rgba(255, 255, 255, 0.3);
  }

  .section h3 {
    font-weight: 600;
    margin-bottom: 10px;
  }

  .section p {
    margin: 8px 0;
    font-size: 14px;
    color: #222;
  }

  ul {
    margin-left: 18px;
    padding: 0;
  }

  ul li {
    font-size: 14px;
    color: #333;
    margin: 5px 0;
  }

  /* ====== Apple glass button ====== */
  .button-wrapper {
    text-align: center;
    margin-top: 35px;
  }

  .button-wrapper a {
    display: inline-block;
    padding: 14px 38px;
    border-radius: 35px;
    background: linear-gradient(145deg, rgba(30,30,30,0.85), rgba(10,10,10,0.9));
    border: 2px solid rgba(255, 255, 255, 0.6);
    text-decoration: none;
    font-weight: 600;
    font-size: 15px;
    color: #f1f1f1;
    box-shadow:
      0 12px 28px rgba(0, 0, 0, 0.4),
      inset 0 0 10px rgba(255, 255, 255, 0.3);
    transition: all 0.35s ease;
    backdrop-filter: blur(20px);
    position: relative;
    overflow: hidden;
  }

  .button-wrapper a::before {
    content: "";
    position: absolute;
    top: 0;
    left: -80%;
    width: 50%;
    height: 100%;
    background: linear-gradient(120deg, rgba(255,255,255,0.6), rgba(255,255,255,0));
    transform: skewX(-25deg);
    transition: 0.7s;
    opacity: 0;
  }

  .button-wrapper a:hover::before {
    left: 130%;
    opacity: 1;
  }

  .button-wrapper a:hover {
    background: linear-gradient(145deg, rgba(50,50,50,1), rgba(15,15,15,1));
    transform: scale(1.07);
    border-color: rgba(255, 255, 255, 0.9);
    box-shadow:
      0 16px 40px rgba(0, 0, 0, 0.5),
      inset 0 0 15px rgba(255, 255, 255, 0.5);
    color: #fff;
  }

  .button-wrapper a:active {
    transform: scale(0.98);
    box-shadow:
      0 6px 15px rgba(0, 0, 0, 0.35),
      inset 0 0 8px rgba(255, 255, 255, 0.4);
  }

  .footer {
    text-align: center;
    font-size: 12px;
    color: #666;
    padding: 20px;
    margin-top: 30px;
    border-top: 1px solid rgba(255, 255, 255, 0.5);
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(12px);
    box-shadow: inset 0 1px 6px rgba(255, 255, 255, 0.15);
  }
</style>
</head>
<body>
  <div class="container">
    <h1>Science Lab Request System</h1>

    <div class="section">
      <h3>Requester Information</h3>
      <p><strong>Name:</strong> <?= htmlspecialchars($form['firstname'] . ' ' . $form['middlename'] . ' ' . $form['lastname']) ?></p>
      <p><strong>Grade & Section:</strong> <?= htmlspecialchars($form['gradeSection']) ?></p>
      <p><strong>Subject:</strong> <?= htmlspecialchars($form['subject']) ?></p>
      <p><strong>Concurrent Topic:</strong> <?= htmlspecialchars($form['concurrentTopic']) ?></p>
    </div>

    <div class="section">
      <h3>Request Details</h3>
      <p><strong>Control Number:</strong> <?= htmlspecialchars($form['controlNumber']) ?></p>
      <p><strong>Facility:</strong> <?= htmlspecialchars($form['facility']) ?></p>
      <p><strong>Schedule:</strong> <?= htmlspecialchars($form['schedule']) ?></p>
      <p><strong>Status:</strong> <?= htmlspecialchars($form['status']) ?></p>
    </div>

    <div class="section">
      <h3>Requested Materials</h3>
      <?php if (count($materials) > 0): ?>
        <ul>
          <?php foreach ($materials as $m): ?>
            <li><?= htmlspecialchars($m['materialName']) ?> — <?= htmlspecialchars($m['quantity']) ?> <?= htmlspecialchars($m['unit']) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p>No materials listed.</p>
      <?php endif; ?>
    </div>

    <div class="section">
      <h3>Students Involved</h3>
      <?php if (count($students) > 0): ?>
        <ul>
          <?php foreach ($students as $s): ?>
            <li><?= htmlspecialchars($s) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p>No students recorded.</p>
      <?php endif; ?>
    </div>

    <div class="button-wrapper">
      <a href="http://localhost/index.php">Go to Login Page</a>
    </div>

    <div class="footer">
      This is an auto-generated document. Please do not reply.
    </div>
  </div>
</body>
</html>
<?php
$html = ob_get_clean();

// PDF Setup
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$dompdf->stream("scilab_request_{$id}.pdf", ["Attachment" => false]);
exit;
?>
