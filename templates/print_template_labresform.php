<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Laboratory Reservation Form</title>

<style>
@page { margin: 15mm 20mm; size: A4 portrait; }
* { margin: 0; padding: 0; box-sizing: border-box; }

body { padding: 50px; font-family: Arial, sans-serif; font-size: 9pt; line-height: 1.4; color: #000; }

.header { margin-bottom: 18pt; }
.header h3 { font-size: 10pt; font-weight: bold; margin-bottom: 6pt; }

.line { border-bottom: 1px solid #000; display: inline-block; min-width: 180pt; height: 12pt; }

.form-title { font-size: 11pt; font-weight: bold; margin-bottom: 20pt; }

.form-row { width: 100%; margin-bottom: 10pt; display: table; }
.form-field { display: table-cell; vertical-align: top; }

.label { white-space: nowrap; }

.value { border-bottom: 1px solid #000; display: inline-block; min-width: 180pt; height: 12pt; margin-left: 6pt; }

.w-50 { width: 50%; }
.w-60 { width: 60%; }
.w-40 { width: 40%; }
.w-100 { width: 100%; }

.student-list { margin-top: 14pt; }
.student-line { border-bottom: 1px solid #000; min-width: 300pt; display: inline-block; height: 12pt; }

.signature-section { margin-top: 18pt; }

.signature-line { border-bottom: 1px solid #000; min-width: 200pt; display: inline-block; height: 12pt; }

.signature-label { display: block; font-size: 8pt; margin-left: 110pt; margin-top: 2pt; }

.footer-text { margin-top: 20pt; font-size: 9pt; }
</style>
</head>

<body>

<div class="header">
    <h3>PHILIPPINE SCIENCE HIGH SCHOOL SYSTEM</h3>
    <div style="font-weight: bold">
        CAMPUS: <span class="line"></span>
    </div>
</div>

<div class="form-title">
    LABORATORY RESERVATION FORM
</div>

<div class="form-row">
    <div class="form-field w-50"></div>
    <div class="form-field w-25">
        <span class="label">Control No:</span>
        <span class="value" style="min-width: 80pt;"></span>
    </div>
    <div class="form-field w-25">
        <span class="label">SY:</span>
        <span class="value" style="min-width: 120pt;"></span>
    </div>
</div>

<div class="form-row">
    <div class="form-field w-50">
        <span class="label">Grade Level and Section:</span>
        <span class="value"></span>
    </div>
    <div class="form-field w-50">
        <span class="label">Number of Students:</span>
        <span class="value" style="min-width: 80pt;"></span>
    </div>
</div>

<div class="form-row">
    <div class="form-field w-50">
        <span class="label">Subject:</span>
        <span class="value"></span>
    </div>
    <div class="form-field w-50">
        <span class="label">Teacher In-Charge:</span>
        <span class="value"></span>
    </div>
</div>

<div class="form-row">
    <div class="form-field w-50">
        <span class="label">Date/Inclusive Dates:</span>
        <span class="value"></span>
    </div>
    <div class="form-field w-50">
        <span class="label">Inclusive Time of Use:</span>
        <span class="value"></span>
    </div>
</div>

<div class="form-row">
    <div class="form-field w-100">
        <span class="label">Preferred Lab Room:</span>
        <span class="value" style="min-width: 350pt;"></span>
    </div>
</div>

<div class="form-row">
    <div class="form-field w-50">
        <span class="label">Requested by:</span>
        <span class="value" style="min-width: 200pt;"></span>
        <span class="signature-label">Teacher/Student</span>
    </div>
    <div class="form-field w-50">
        <span class="label">Date Requested:</span>
        <span class="value" style="min-width: 160pt;"></span>
    </div>
</div>

<div class="student-list">
    <p>If user of the lab is a group, list down the names of students.</p>

    <div>1. <span class="student-line"></span></div>
    <div>2. <span class="student-line"></span></div>
    <div>3. <span class="student-line"></span></div>
    <div>4. <span class="student-line"></span></div>
    <div>5. <span class="student-line"></span></div>
</div>

<div class="signature-section">
    <div class="form-row">
        <div class="form-field w-100">
            <span class="label">Endorsed by:</span>
            <span class="signature-line"></span>
            <span class="signature-label">Subject Teacher/Unit Head</span>
        </div>
    </div>

    <div class="form-row" style="margin-top: 20px;">
        <div class="form-field w-100">
            <span class="label">Approved by:</span>
            <span class="signature-line"></span>
            <span class="signature-label">SRS / SRA</span>
        </div>
    </div>
</div>

<div class="footer-text">
    PSHS-00-F-CID-05-Ver02-Rev1-10/18/20
</div>

</body>
</html>
