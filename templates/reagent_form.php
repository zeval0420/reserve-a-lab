<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PSHS Reagent Request Form</title>
    <style>
        /* A4 Page Setup */
        @page {
            size: A4;
            margin: 20mm 15mm 15mm 15mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            /* Font approximation: The form uses a sans-serif font similar to Arial or Helvetica */
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10pt;
            line-height: 1.3;
            color: #000;
            background: #fff;
        }
        
        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: #fff;
            padding: 15mm 20mm;
        }
        
        /* Header Section */
        .header {
            margin-bottom: 8mm;
        }
        
        .header h1 {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 2mm;
            letter-spacing: 0.3px;
        }
        
        .header .campus-line {
            font-size: 10pt;
            font-weight: bold;
            margin-bottom: 8mm;
        }
        
        .header .campus-line span {
            display: inline-block;
            border-bottom: 1px solid #000;
            width: 120mm;
            margin-left: 3mm;
        }
        
        .header h2 {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 8mm;
            letter-spacing: 0.3px;
        }
        
        /* Form Fields Section */
        .form-fields {
            margin-bottom: 3mm;
        }
        
        .field-row {
            display: flex;
            margin-bottom: 2.5mm;
            align-items: baseline;
        }
        
        .field-row.double {
            justify-content: space-between;
        }
        
        .field-label {
            font-size: 10pt;
            font-weight: normal;
            white-space: nowrap;
        }
        
        .field-underline {
            border-bottom: 1px solid #000;
            flex-grow: 1;
            margin-left: 2mm;
            min-height: 14px;
        }
        
        .field-row.double .field-group {
            display: flex;
            align-items: baseline;
        }
        
        .field-row.double .field-group:first-child {
            width: 58%;
        }
        
        .field-row.double .field-group:last-child {
            width: 40%;
        }
        
        /* Table Section */
        .table-section {
            margin: 5mm 0;
        }
        
        .reagent-needed {
            font-size: 10pt;
            font-weight: bold;
            margin-bottom: 2mm;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #000;
        }
        
        th, td {
            border: 1px solid #000;
            padding: 3mm 2mm;
            font-size: 9.5pt;
            text-align: left;
            vertical-align: top;
        }
        
        th {
            font-weight: bold;
            text-align: center;
            background: #fff;
        }
        
        .col-quantity {
            width: 15%;
        }
        
        .col-reagent {
            width: 35%;
        }
        
        .col-sds {
            width: 18%;
        }
        
        .col-issued {
            width: 32%;
        }
        
        .sds-note {
            display: block;
            font-size: 8pt;
            margin-top: 1mm;
        }
        
        /* Large Cell Rows */
        tr.large-cell td {
            height: 35mm;
        }
        
        tr.signature-cell td {
            height: 25mm;
            position: relative;
        }
        
        .received-by {
            position: absolute;
            top: 3mm;
            right: 3mm;
            font-size: 9.5pt;
        }
        
        .date-field {
            position: absolute;
            bottom: 3mm;
            right: 3mm;
            font-size: 9.5pt;
        }
        
        /* Notes Section */
        .notes {
            margin-top: 5mm;
            font-size: 9pt;
            line-height: 1.5;
        }
        
        .notes ul {
            list-style: none;
            padding-left: 0;
        }
        
        .notes li {
            margin-bottom: 2mm;
            padding-left: 4mm;
            position: relative;
        }
        
        .notes li:before {
            content: "•";
            position: absolute;
            left: 0;
        }
        
        .notes li em {
            font-style: italic;
        }
        
        /* Request Section */
        .request-section {
            margin-top: 6mm;
            display: flex;
            justify-content: space-between;
            align-items: baseline;
        }
        
        .request-field {
            display: flex;
            align-items: baseline;
            gap: 3mm;
        }
        
        .request-field label {
            font-size: 10pt;
            font-weight: bold;
        }
        
        .request-field .underline {
            border-bottom: 1px solid #000;
            min-width: 80mm;
            height: 14px;
        }
        
        .request-field.date .underline {
            min-width: 60mm;
        }
        
        .teacher-label {
            text-align: center;
            font-size: 9pt;
            margin-top: 1mm;
        }
        
        /* Student List Section */
        .student-list {
            margin-top: 6mm;
            margin-bottom: 8mm;
        }
        
        .student-list p {
            font-size: 9.5pt;
            font-style: italic;
            margin-bottom: 2mm;
        }
        
        .student-list ol {
            list-style: decimal;
            padding-left: 8mm;
        }
        
        .student-list li {
            margin-bottom: 2mm;
            border-bottom: 1px solid #000;
            padding-bottom: 1mm;
            padding-right: 5mm;
        }
        
        /* Approval Section */
        .approval-section {
            display: flex;
            justify-content: space-between;
            margin-top: 8mm;
        }
        
        .approval-field {
            display: flex;
            align-items: baseline;
            gap: 3mm;
        }
        
        .approval-field label {
            font-size: 10pt;
            font-weight: bold;
        }
        
        .approval-field .underline {
            border-bottom: 1px solid #000;
            min-width: 70mm;
            height: 14px;
        }
        
        .approval-field.right .underline {
            min-width: 80mm;
        }
        
        .sublabel {
            text-align: center;
            font-size: 9pt;
            margin-top: 1mm;
        }
        
        /* Footer */
        .footer {
            margin-top: 20mm;
            font-size: 9pt;
            font-weight: bold;
        }
        
        /* Print Styles */
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            
            .page {
                margin: 0;
                width: 210mm;
                height: 297mm;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <!-- Header -->
        <div class="header">
            <h1>PHILIPPINE SCIENCE HIGH SCHOOL SYSTEM</h1>
            <div class="campus-line">
                CAMPUS: <span></span>
            </div>
            <h2>REAGENT REQUEST FORM</h2>
        </div>
        
        <!-- Form Fields -->
        <div class="form-fields">
            <div class="field-row double">
                <div class="field-group">
                    <span class="field-label">Grade Level and Section:</span>
                    <span class="field-underline"></span>
                </div>
                <div class="field-group">
                    <span class="field-label">Control No:</span>
                    <span class="field-underline"></span>
                </div>
            </div>
            
            <div class="field-row double">
                <div class="field-group">
                    <span class="field-label">Subject:</span>
                    <span class="field-underline"></span>
                </div>
                <div class="field-group">
                    <span class="field-label">SY:</span>
                    <span class="field-underline"></span>
                </div>
            </div>
            
            <div class="field-row double">
                <div class="field-group">
                    <span class="field-label">Unit:</span>
                    <span class="field-underline"></span>
                </div>
                <div class="field-group">
                    <span class="field-label">Number of Students:</span>
                    <span class="field-underline"></span>
                </div>
            </div>
            
            <div class="field-row double">
                <div class="field-group">
                    <span class="field-label">Venue of the Experiment:</span>
                    <span class="field-underline"></span>
                </div>
                <div class="field-group">
                    <span class="field-label">Concurrent Topic:</span>
                    <span class="field-underline"></span>
                </div>
            </div>
            
            <div class="field-row double">
                <div class="field-group">
                    <span class="field-label">Date/Inclusive Dates:</span>
                    <span class="field-underline"></span>
                </div>
                <div class="field-group">
                    <span class="field-label">Teacher In-Charge:</span>
                    <span class="field-underline"></span>
                </div>
            </div>
            
            <div class="field-row">
                <span class="field-label"></span>
                <span class="field-underline"></span>
            </div>
            
            <div class="field-row">
                <span class="field-label"></span>
                <span class="field-underline" style="margin-right: 50%;"></span>
                <span class="field-label">Inclusive Time of Use:</span>
                <span class="field-underline"></span>
            </div>
        </div>
        
        <!-- Table Section -->
        <div class="table-section">
            <div class="reagent-needed">Reagent Needed:</div>
            <table>
                <thead>
                    <tr>
                        <th class="col-quantity">Quantity</th>
                        <th class="col-reagent">Reagent</th>
                        <th class="col-sds">SDS<span class="sds-note">(√ if × )</span></th>
                        <th class="col-issued">Issued<br>Amount/ Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr class="large-cell">
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr class="signature-cell">
                        <td colspan="3"></td>
                        <td>
                            <div class="received-by">Received by:</div>
                            <div class="date-field">Date:</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Notes -->
        <div class="notes">
            <ul>
                <li><em>Students must certify that he/she/they have read the safety information as specified in the Safety Data Sheet (SDS) of the reagents being requested.</em></li>
                <li><em>This form must be filled out completely and legibly and submitted, together with a suitable container with cover and proper label, to the SRA of the unit which will release the reagents.</em></li>
                <li><em>Requests not in accordance with existing Unit regulations and considerations may not be granted.</em></li>
                <li><em>The reagents will be released to the SRA of the requesting unit.</em></li>
            </ul>
        </div>
        
        <!-- Request Section -->
        <div class="request-section">
            <div class="request-field">
                <label>Requested by:</label>
                <div>
                    <div class="underline"></div>
                    <div class="teacher-label">Teacher/Student</div>
                </div>
            </div>
            <div class="request-field date">
                <label>Date Requested:</label>
                <div class="underline"></div>
            </div>
        </div>
        
        <!-- Student List -->
        <div class="student-list">
            <p>If user of the lab is a group, list down the names of students.</p>
            <ol>
                <li></li>
                <li></li>
                <li></li>
                <li></li>
                <li></li>
            </ol>
        </div>
        
        <!-- Approval Section -->
        <div class="approval-section">
            <div class="approval-field">
                <label>Endorsed by:</label>
                <div>
                    <div class="underline"></div>
                    <div class="sublabel">Subject Teacher/Unit Head</div>
                </div>
            </div>
            <div class="approval-field right">
                <label>Approved by:</label>
                <div>
                    <div class="underline"></div>
                    <div class="sublabel">SRS / SRA<br>(Releasing Unit)</div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            PSHS-00-F-CID-19-Ver02-Rev1-10/18/20
        </div>
    </div>
</body>
</html>