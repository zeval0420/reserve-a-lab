<?php
$asset_base = '../';
require('../helperFiles/db_connection.php');
require_once('emailHelper.php');

/* PRG: confirmations arrive via ?saved=1 / ?saved=0 */
$saved = isset($_GET['saved']) ? (int)$_GET['saved'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_referral'])) {
    /* Sanitize basic fields */
    $campus        = trim($_POST['campus'] ?? '');
    $student       = trim($_POST['student'] ?? '');
    $gradeSection  = trim($_POST['grade'] ?? '');
    $dateReferred  = trim($_POST['date'] ?? '');
    if ($dateReferred === '') { $dateReferred = null; }
    $description   = trim($_POST['description'] ?? '');
    $intervention  = trim($_POST['intervention'] ?? '');
    $followup      = ($_POST['followup'] ?? '') === 'Yes' || ($_POST['followup'] ?? '') === 'No' ? $_POST['followup'] : null;
    $otherBehavior = trim($_POST['other'] ?? '');
    $referrerEmail = trim($_POST['referrer_email'] ?? '');
    if ($referrerEmail !== '' && !filter_var($referrerEmail, FILTER_VALIDATE_EMAIL)) {
        $referrerEmail = '';
    }
    if ($referrerEmail === '') { $referrerEmail = null; }

    /* Concern checkbox columns */
    $concernAcademic = isset($_POST['concern_academic']) ? 1 : 0;
    $concernBehavior = isset($_POST['concern_behavior']) ? 1 : 0;
    $concernPersonal = isset($_POST['concern_personal']) ? 1 : 0;

    /* Behavior checkbox columns */
    $behaviorColumns = [
        'behavior_depressed',
        'behavior_hopelessness',
        'behavior_crying',
        'behavior_suicide',
        'behavior_mood',
        'behavior_emotional',
        'behavior_withdrawal',
        'behavior_excessive_activity',
        'behavior_interaction',
        'behavior_disruptive',
        'behavior_appearance',
        'behavior_academic_decline',
    ];
    $behaviorValues = [];
    foreach ($behaviorColumns as $col) {
        $behaviorValues[] = isset($_POST[$col]) ? 1 : 0;
    }

    $stmt = $conn->prepare("INSERT INTO guidance_referral_form (
        campus, student, grade_section, date_referred,
        concern_academic, concern_behavior, concern_personal,
        description, intervention, requires_followup,
        behavior_depressed, behavior_hopelessness, behavior_crying,
        behavior_suicide, behavior_mood, behavior_emotional,
        behavior_withdrawal, behavior_excessive_activity, behavior_interaction,
        behavior_disruptive, behavior_appearance, behavior_academic_decline,
        other_behavior, referrer_email
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if ($stmt) {
        $types = 'ssssiiissiiiiiiiiiiiiiss';
        $params = [
            $campus, $student, $gradeSection, $dateReferred,
            $concernAcademic, $concernBehavior, $concernPersonal,
            $description, $intervention, $followup,
            $behaviorValues[0], $behaviorValues[1], $behaviorValues[2],
            $behaviorValues[3], $behaviorValues[4], $behaviorValues[5],
            $behaviorValues[6], $behaviorValues[7], $behaviorValues[8],
            $behaviorValues[9], $behaviorValues[10], $behaviorValues[11],
            $otherBehavior, $referrerEmail,
        ];

        $stmt->bind_param($types, ...$params);
        $ok = $stmt->execute();
        $newId = $stmt->insert_id;
        $stmt->close();
    } else {
        $ok = false;
        $newId = 0;
    }

    if ($ok) {
        guidance_send_submission_notification($conn, [
            'id'              => $newId,
            'student'         => $student,
            'grade_section'   => $gradeSection,
            'campus'          => $campus,
            'date_referred'   => $dateReferred,
            'requires_followup' => $followup,
            'description'     => $description,
        ]);
        header('Location: index.php?saved=1');
    } else {
        header('Location: index.php?saved=0');
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PSHS Referral Form</title>
    <?php include('../helperFiles/headData.php'); ?>
    <style>
        /* ─── Referral form styling (scoped so it never touches the shared header/footer) ─── */
        .referral-app {
            flex: 1 0 auto;
            width: 100%;
            max-width: 1180px;
            margin: 0 auto;
            padding: 32px 20px 48px;
            background: #eef1f6;
            color: #171a21;
        }

        .referral-app * { box-sizing: border-box; }

        .referral-app .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 22px;
            flex-wrap: wrap;
        }

        .referral-app .title h1 {
            margin: 0;
            font-size: 25px;
            letter-spacing: -.02em;
        }

        .referral-app .title p {
            margin: 5px 0 0;
            color: #667085;
            font-size: 14px;
        }

        .referral-app .actions {
            display: flex;
            gap: 9px;
            flex-wrap: wrap;
        }

        .referral-app .toolbar button {
            border: 0;
            border-radius: 9px;
            padding: 10px 15px;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }

        .referral-app .toolbar button:hover { filter: brightness(.97); }

        .referral-app .btn-primary { background: #0b1b62; color: white; }
        .referral-app .btn-secondary { background: white; color: #171a21; border: 1px solid #d9dee8; }

        .referral-app .sheet {
            background: #ffffff;
            box-shadow: 0 12px 35px rgba(20, 30, 60, .10);
            border-radius: 4px;
            padding: 28px;
        }

        .referral-app .sheet + .sheet { margin-top: 24px; }

        .referral-app .sheet-label {
            display: inline-block;
            margin-bottom: 12px;
            padding: 5px 9px;
            border-radius: 999px;
            background: #f5f7fb;
            color: #0b1b62;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .referral-app .form-card {
            min-width: 0;
            border: 1px solid #20242d;
            padding: 15px 15px 13px;
            min-height: 760px;
            display: flex;
            flex-direction: column;
        }

        .referral-app .form-card-head {
            text-align: center;
        }

        .referral-app .system {
            margin: 0;
            font-size: 15px;
            font-weight: 800;
            letter-spacing: .01em;
        }

        .referral-app .campus {
            margin: 2px 0 0;
            font-size: 13px;
            font-weight: 700;
        }

        .referral-app .campus input {
            width: 145px;
            border: 0;
            border-bottom: 1px solid #222;
            border-radius: 0;
            padding: 0 3px 1px;
            text-align: center;
            background: transparent;
            font-family: inherit;
            font-size: 13px;
        }

        .referral-app .form-title {
            margin: 34px 0 18px;
            font-size: 17px;
            letter-spacing: .14em;
            font-weight: 900;
        }

        .referral-app label { font-size: 13px; }

        .referral-app .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .referral-app .field {
            display: flex;
            align-items: baseline;
            gap: 4px;
            margin-bottom: 14px;
        }

        .referral-app .field.full { width: 100%; }

        .referral-app input[type="text"],
        .referral-app input[type="date"],
        .referral-app textarea {
            width: 100%;
            border: 0;
            border-bottom: 1px solid #444;
            border-radius: 0;
            outline: none;
            background: transparent;
            font: inherit;
            font-size: 13px;
            padding: 2px 3px 3px;
        }

        .referral-app input:focus,
        .referral-app textarea:focus {
            border-bottom-color: #0b1b62;
        }

        .referral-app .section {
            margin-top: 10px;
        }

        .referral-app .section-title {
            font-size: 13px;
            margin-bottom: 7px;
        }

        .referral-app .section-title u { text-underline-offset: 2px; }

        .referral-app .checks {
            display: grid;
            gap: 7px;
            margin-left: 54px;
        }

        .referral-app .check {
            display: flex;
            align-items: flex-start;
            gap: 7px;
            font-size: 13px;
            line-height: 1.35;
        }

        .referral-app .check input {
            appearance: none;
            -webkit-appearance: none;
            width: 16px;
            height: 16px;
            flex: 0 0 16px;
            border: 1.5px solid #111;
            border-radius: 2px;
            margin: 0;
            position: relative;
            cursor: pointer;
            background: white;
        }

        .referral-app .check input:checked::after {
            content: "×";
            position: absolute;
            inset: -4px 0 0;
            text-align: center;
            font-size: 20px;
            font-weight: 700;
        }

        .referral-app .lined-area {
            margin: 0 0 0 53px;
        }

        .referral-app textarea {
            resize: vertical;
            min-height: 112px;
            border-bottom: 0;
            padding: 0;
            line-height: 1.85;
            background:
                repeating-linear-gradient(
                    to bottom,
                    transparent 0,
                    transparent 22px,
                    #555 22px,
                    #555 23px
                );
        }

        .referral-app .followup {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-top: 11px;
            font-size: 13px;
        }

        .referral-app .radio {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .referral-app .radio input {
            accent-color: #0b1b62;
        }

        .referral-app .referred {
            text-align: center;
            margin-top: 24px;
        }

        .referral-app .signature {
            width: 58%;
            margin: 28px auto 0;
            border-bottom: 1px solid #222;
            height: 24px;
        }

        .referral-app .signature-caption {
            margin-top: 3px;
            font-size: 12px;
        }

        .referral-app .footer-code {
            margin-top: auto;
            padding-top: 22px;
            font-size: 11px;
            font-weight: 800;
        }

        /* Back side */
        .referral-app .behavior-card {
            min-height: 760px;
        }

        .referral-app .behavior-title {
            text-align: center;
            margin: 25px 0 28px;
            font-size: 17px;
            letter-spacing: .13em;
            font-weight: 900;
        }

        .referral-app .instruction {
            font-size: 13px;
            line-height: 1.4;
            margin-bottom: 25px;
        }

        .referral-app .behavior-list {
            display: grid;
            gap: 7px;
        }

        .referral-app .behavior-list .check {
            margin-left: 54px;
        }

        .referral-app .behavior-list .check input {
            margin-top: 1px;
        }

        .referral-app .other-input {
            display: inline-block;
            min-width: 170px;
            border-bottom: 1px solid #333;
            margin-left: 2px;
            background: transparent;
            font-family: inherit;
            font-size: 13px;
        }

        .referral-app .behavior-signature {
            text-align: center;
            margin-top: auto;
        }

        .referral-app .behavior-signature .signature {
            margin-top: 0;
        }

        .referral-app .privacy-note {
            margin-top: 18px;
            padding: 11px 13px;
            border: 1px solid #d9dee8;
            border-radius: 9px;
            background: white;
            color: #667085;
            font-size: 12px;
            line-height: 1.45;
        }

        @media (max-width: 850px) {
            .referral-app { padding: 18px 12px 30px; }
            .referral-app .toolbar { align-items: flex-start; flex-direction: column; }
            .referral-app .sheet { padding: 15px; overflow-x: auto; }
            .referral-app .form-card { min-width: 0; }
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 8mm;
            }

            .header,
            .site-footer,
            .referral-app .toolbar,
            .referral-app .privacy-note,
            .referral-app .sheet-label {
                display: none !important;
            }

            body {
                background: white;
            }

            .referral-app {
                background: white;
                max-width: none;
                padding: 0;
            }

            .referral-app .sheet {
                box-shadow: none;
                padding: 0;
                border-radius: 0;
            }

            .referral-app .sheet + .sheet {
                margin-top: 0;
                break-before: page;
            }

            .referral-app .form-card {
                min-height: 281mm;
                padding: 4mm;
                break-inside: avoid;
            }

            .referral-app .form-title {
                margin-top: 14mm;
                margin-bottom: 7mm;
            }

            .referral-app .field { margin-bottom: 5mm; }

            .referral-app textarea {
                min-height: 31mm;
            }

            .referral-app .referred {
                margin-top: 6mm;
            }

            .referral-app .footer-code {
                padding-top: 4mm;
            }

            .referral-app .behavior-title {
                margin: 9mm 0 9mm;
            }

            .referral-app .instruction {
                margin-bottom: 8mm;
            }

            .referral-app .behavior-list {
                gap: 3mm;
            }
        }
    </style>
</head>

<body>
    <?php include('../helperFiles/header.php'); ?>

    <div class="referral-app">
        <form id="referralForm" method="post" action="">
            <div class="toolbar">
                <div class="title">
                    <h1>PSHS Referral Form</h1>
                    <p>Digital, clean, and print-ready version of the prepared referral form.</p>
                </div>
                <div class="actions">
                    <button class="btn-secondary" type="button" onclick="clearForm()">Clear</button>
                    <button class="btn-primary" type="submit" name="submit_referral" value="1">Save to Records</button>
                    <button class="btn-secondary" type="button" onclick="window.print()">Print / Save PDF</button>
                </div>
            </div>

            <!-- FRONT -->
            <section class="sheet">
                <div class="sheet-label">Front • Referral information</div>
                <div class="form-card">
                    <div class="form-card-head">
                        <p class="system">PHILIPPINE SCIENCE HIGH SCHOOL SYSTEM</p>
                        <p class="campus">Campus: <input type="text" name="campus"></p>
                        <h2 class="form-title">REFERRAL FORM</h2>
                    </div>

                    <div class="field full">
                        <label>Name of Student:</label>
                        <input type="text" name="student">
                    </div>

                    <div class="form-row">
                        <div class="field">
                            <label>Grade &amp; Section:</label>
                            <input type="text" name="grade">
                        </div>
                        <div class="field">
                            <label>Date:</label>
                            <input type="date" name="date">
                        </div>
                    </div>

                    <div class="section">
                        <div class="section-title"><u>Concern</u>: (put an x inside the box)</div>
                        <div class="checks">
                            <label class="check"><input type="checkbox" name="concern_academic"> <span>Academic</span></label>
                            <label class="check"><input type="checkbox" name="concern_behavior"> <span>Behavior</span></label>
                            <label class="check"><input type="checkbox" name="concern_personal"> <span>Personal/Social</span></label>
                        </div>
                    </div>

                    <div class="section">
                        <div class="section-title">Brief Description:</div>
                        <div class="lined-area">
                            <textarea name="description" rows="4"></textarea>
                        </div>
                    </div>

                    <div class="section">
                        <div class="section-title">Intervention/s Done:</div>
                        <div class="lined-area">
                            <textarea name="intervention" rows="4"></textarea>
                        </div>
                    </div>

                    <div class="followup">
                        <span>Requires Follow-up?</span>
                        <label class="radio"><input type="radio" name="followup" value="Yes"> Yes</label>
                        <label class="radio"><input type="radio" name="followup" value="No"> No</label>
                    </div>

                    <div class="field full" style="margin-top: 10px;">
                        <label>Referrer Email (optional):</label>
                        <input type="email" name="referrer_email" placeholder="you@pshs.edu.ph">
                    </div>

                    <div class="referred">
                        <div>Referred by:</div>
                        <div class="signature"></div>
                        <div class="signature-caption">Signature over Full Name &amp; Designation</div>
                    </div>

                    <div class="footer-code">PSHS-00-F-GCU-03-Ver02-Rev0-02/01/20</div>
                </div>
            </section>

            <!-- BACK -->
            <section class="sheet">
                <div class="sheet-label">Back • Behaviors spotted</div>
                <div class="form-card behavior-card">
                    <h2 class="behavior-title">BEHAVIORS SPOTTED:</h2>
                    <p class="instruction">
                        Put an ‘x’ mark inside the box/es pertaining to the specific behaviors you have
                        observed from the student you are referring:
                    </p>

                    <div class="behavior-list">
                        <label class="check"><input type="checkbox" name="behavior_depressed"> <span>depressed or apathetic mood</span></label>
                        <label class="check"><input type="checkbox" name="behavior_hopelessness"> <span>expression of helplessness, hopelessness, worthlessness</span></label>
                        <label class="check"><input type="checkbox" name="behavior_crying"> <span>evidence of crying</span></label>
                        <label class="check"><input type="checkbox" name="behavior_suicide"> <span>verbal expressions or gestures of suicide</span></label>
                        <label class="check"><input type="checkbox" name="behavior_mood"> <span>noticeable changes in mood and/or sudden outburst</span></label>
                        <label class="check"><input type="checkbox" name="behavior_emotional"> <span>inappropriate or exaggerated emotional reactions to situations, including a lack of emotional response to stressful events</span></label>
                        <label class="check"><input type="checkbox" name="behavior_withdrawal"> <span>excessive dependency on others or extreme withdrawal and isolation from others</span></label>
                        <label class="check"><input type="checkbox" name="behavior_excessive_activity"> <span>excessive activity or talkativeness</span></label>
                        <label class="check"><input type="checkbox" name="behavior_interaction"> <span>unusual or noticeable changed interaction patterns with friends or classmates</span></label>
                        <label class="check"><input type="checkbox" name="behavior_disruptive"> <span>new or continuous behaviour which disrupts the class</span></label>
                        <label class="check"><input type="checkbox" name="behavior_appearance"> <span>noticeable changes in physical appearance (weight, dress, hygiene)</span></label>
                        <label class="check"><input type="checkbox" name="behavior_academic_decline"> <span>extremely poor academic performance, or a drastic decline in grades</span></label>
                        <label class="check"><input type="checkbox" name="other_behavior_flag"> <span>others, please specify <input class="other-input" type="text" name="other"></span></label>
                    </div>

                    <div class="behavior-signature">
                        <div class="signature"></div>
                        <div class="signature-caption">Name &amp; Signature</div>
                    </div>

                    <div class="footer-code">PSHS-00-F-GCU-03-Ver02-Rev0-02/01/20</div>
                </div>
            </section>
        </form>

        <div class="privacy-note">
            <strong>Printing:</strong> The layout is single-copy: page 1 contains the referral front
            and page 2 contains the corresponding behavior section. Use A4, portrait, 100% scale,
            and double-sided printing with the printer set to flip on the long edge if you want the
            two pages to align as a back-to-back sheet.
        </div>
    </div>

    <?php include('../helperFiles/footer.php'); ?>

    <script>
        function clearForm() {
            if (!confirm("Clear all entered information?")) return;
            document.getElementById("referralForm").reset();
        }
    </script>

    <?php if ($saved === 1): ?>
        <script>
            showToast('Referral record saved successfully.', 'success');
            document.getElementById("referralForm").reset();
        </script>
    <?php elseif ($saved === 0): ?>
        <script>
            showToast('Failed to save the referral record.', 'error');
        </script>
    <?php endif; ?>
</body>

</html>