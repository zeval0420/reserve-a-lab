<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Request Form</title>

    <link rel="stylesheet" href="../bootstrap3.3.7/css/bootstrap.min.css">
    <link rel="stylesheet" href="../datatables/datatables.min.css">
    <script src="../jQuery-3.3.1/jquery-3.3.1.min.js"></script>
    <script src="../datatables/datatables.min.js"></script>
    <script src="../bootstrap3.3.7/js/bootstrap.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <style>
        * {
            font-size: 12px;
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
        }

        .a4 {
            width: 21cm;
            height: 29.7cm;
            margin: 0 auto;
            background-color: white;
            border: solid 1px black;
        }

        .formContent {
            padding: 0.5in 1in;
        }

        .name, .campus, .title {
            font-size: 12px;
            font-weight: bold;
            line-height: 0.1;
        }

        .formTitle {
            padding: 8px 0;
        }

        .row {
            width: 100%;
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            gap: 10px;
        }

        table, th, td {
            border: solid 1px black;
        }

        table {
            width: 100%;
        }

        th {
            text-align: center;
        }

        td {
            height: 16px;
        }

        .text {
            border-bottom: 1px solid #000;
            display: inline-block;
            width: 100%;
            min-height: 18px;
        }

        .text[contenteditable="true"] {
            padding: 2px 4px;
        }

        .studentList ol li {
            border-bottom: 1px solid #000;
            height: 20px;
            width: 50%;
            margin-bottom: 5px;
        }

        .footerText {
            position: absolute;
            bottom: 0;
        }

        .inline-field {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }

        label {
            white-space: nowrap;
            margin-right: 5px;
        }

        .inline-field span.text {
            border-bottom: 1px solid #000;
            flex-grow: 1;
            padding-left: 5px;
            min-height: 16px;
        }

        .studentList ol {
            padding-left: 15px;
            margin: 0;
            padding-bottom: 5px;
            list-style-position: inside;
        }

        .signature-label {
            display: block;
            text-align: center;
            font-size: 10px;
            font-style: italic;
        }


        .col-xs-5, .col-xs-6, .col-xs-7, .col-md-5, .col-md-6, .col-md-7, .col-md-12 {
            padding-right: 15px;
            padding-left: 15px;
            box-sizing: border-box;
        }

        .col-xs-5 { flex: 0 0 41.666666%; max-width: 41.666666%; }
        .col-xs-6 { flex: 0 0 50%; max-width: 50%; }
        .col-xs-7 { flex: 0 0 58.333333%; max-width: 58.333333%; }
        .col-md-5 { flex: 0 0 41.666666%; max-width: 41.666666%; }
        .col-md-6 { flex: 0 0 50%; max-width: 50%; }
        .col-md-7 { flex: 0 0 58.333333%; max-width: 58.333333%; }
        .col-md-12 { flex: 0 0 100%; max-width: 100%; }
    </style>
</head>
<body class="a4">
    <div class="a4 formContent">
        <div class="campus">
            <h3 class="name">PHILIPPINE SCIENCE HIGH SCHOOL SYSTEM</h3>
            <div class="inline-field" style="width: fit-content;">
                <label>CAMPUS:</label>
                <span class="text" style="width: 200px; padding-top: 20;">ILOCOS REGION</span>
            </div>
        </div>

        <div class="formTitle">
            <h3 class="title">LABORATORY REQUEST AND EQUIPMENT ACCOUNTABILITY FORM</h3>
        </div>

        <div class="info">
            <div class="row">
                <div class="col-xs-6"></div>
                <div class="col-xs-6">
                    <div class="inline-field col-xs-7">
                        <label>Control No:</label>
                        <span class="text"><?= htmlspecialchars($form['controlNumber']) ?></span>
                    </div>
                    <div class="inline-field col-xs-5">
                        <label>SY:</label>
                        <span class="text"><?= htmlspecialchars($form['sy']) ?></span>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="inline-field col-md-7">
                    <label>Grade Level and Section:</label>
                    <span class="text"><?= htmlspecialchars($form['gradeLevel'] . ' - ' . $form['section/s']) ?></span>
                </div>
                <div class="inline-field col-md-5">
                    <label>Number of Students:</label>
                    <span class="text"><?= count($students) ?></span>
                </div>
            </div>

            <div class="row">
                <div class="inline-field col-md-7">
                    <label>Subject:</label>
                    <span class="text"><?= htmlspecialchars($form['subject']) ?></span>
                </div>
                <div class="inline-field col-md-5">
                    <label>Concurrent Topic:</label>
                    <span class="text"><?= htmlspecialchars($form['subjectTopic']) ?></span>
                </div>
            </div>

            <div class="row">
                <div class="inline-field col-md-7">
                    <label>Unit:</label>
                    <span class="text"><?= htmlspecialchars($form['subjectAcademicUnit']) ?></span>
                </div>
                <div class="inline-field col-md-5">
                    <label>Teacher In-Charge:</label>
                    <span class="text"><?= htmlspecialchars($form['teacherInCharge']) ?></span>
                </div>
            </div>

            <div class="row">
                <div class="inline-field col-md-12">
                    <label>Venue of the Experiment:</label>
                    <span class="text"><?= htmlspecialchars($form['scilabName']) ?></span>
                </div>
            </div>

            <div class="row">
                <div class="inline-field col-md-6">
                    <label>Date/Inclusive Date:</label>
                    <span class="text"><?= date("F j, Y", strtotime($form['inclusiveDate'])) ?></span>
                </div>
                <div class="inline-field col-md-6">
                    <label>Inclusive Time of Use:</label>
                    <span class="text"><?= htmlspecialchars($form['inclusiveTime']) ?></span>
                </div>
            </div>
        </div>

        <div class="materialList">
            <p class="materialName">Materials/Equipment Needed:</p>
            <table class="materialTable">
                <thead>
                    <tr>
                        <th rowspan="2">Quantity</th>
                        <th rowspan="2">Item</th>
                        <th rowspan="2">Description</th>
                        <th>Issued</th>
                        <th>Returned</th>
                    </tr>
                    <tr>
                        <th>Condition</th>
                        <th>Condition/Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($materials as $m): ?>
                    <tr>
                        <td><?= $m['quantity'] ?></td>
                        <td><?= htmlspecialchars($m['item']) ?></td>
                        <td><?= htmlspecialchars($m['description']) ?></td>
                        <td><?= htmlspecialchars($m['issuedCondition']) ?></td>
                        <td><?= htmlspecialchars($m['returnedCondition']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr><td></td><td></td><td></td><td>Received by:</td><td>Received and<p>Inspected by:</p></td></tr>
                    <tr><td></td><td></td><td></td><td>Date:</td><td>Date:</td></tr>
                </tbody>
            </table>
        </div>

        <div class="additionalNotes">
            <ul>
                <li>Fill out this form completely and legibly; transact with the Unit SRA concerned during office hours.</li>
                <li>Requests not in accordance with existing Unit regulations and considerations may not be granted.</li>
            </ul>

            <div class="row">
                <div class="inline-field col-md-6">
                    <label>Requested:</label>
                    <span class="text"><?= htmlspecialchars($form['firstname'] . ' ' . $form['middlename'] . ' ' . $form['lastname']) ?></span>
                </div>
                <div class="inline-field col-md-6">
                    <label>Date Requested:</label>
                    <span class="text"><?= date("F j, Y", strtotime($form['dateRequested'])) ?></span>
                </div>
            </div>
            <div class="row" style="margin-top: -14px">
                <div class="inline-field col-md-6">
                    <label style="color: white;">Requested:</label>
                    <span class="signature-label">Teacher/Student</span>
                </div>
                <div class="inline-field col-md-6"></div>
            </div>

            <div class="studentList">
                <p>If user of the lab is a group, list down the names of the students</p>
                <ol>
                    <?php foreach ($students as $s): ?>
                        <li><?= htmlspecialchars($s) ?></li>
                    <?php endforeach; ?>
                </ol>
            </div>

            <div class="row">
                <div class="inline-field col-md-6">
                    <label>Endorsed by:</label>
                    <span class="text"></span>
                </div>
                <div class="inline-field col-md-6">
                    <label>Approved by:</label>
                    <span class="text"></span>
                </div>
            </div>
            <div class="row" style="margin-top: -14px">
                <div class="inline-field col-md-6">
                    <label style="color: white;">Endorsed by:</label>
                    <span class="signature-label">Subject Teacher/Unit Head</span>
                </div>
                <div class="inline-field col-md-6">
                    <label style="color: white;">Approved by:</label>
                    <span class="signature-label">SRS/SRA</span>
                </div>
            </div>
        </div>

        <div class="footerText">
            <p>PSHS-00-F-CIID-20-Ver02-Rev1-10/18/20</p>
        </div>
    </div>
</body>
</html>