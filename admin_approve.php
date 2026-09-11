<?php

    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    include('../scilab/helperFiles/db_connection.php');
    include('helperFiles/session_handler.php');

    $email = $_SESSION['email'];
    $username = $_SESSION['username'];

    if (isset($_SESSION['role']) && $_SESSION['role'] != 'admin') {
        header("Location: requester_home.php");
        exit();
    }

    if (!isset($_SESSION['role'])) {
        header("Location: index.php");
        exit();
    }

    $statusFilter = $_GET['status'] ?? 'Pending';
    $syResult = $conn->query("SELECT value FROM current WHERE description = 'School Year' ORDER BY id DESC LIMIT 1");
    $currentSY = ($syResult && $syResult->num_rows > 0) ? $syResult->fetch_assoc()['value'] : null;



    // ===== ADDED: Time frame / manual date filter =====
    $filterMode = $_GET['filterMode'] ?? 'timeframe';

    // Only allow these two modes
    if (!in_array($filterMode, ['timeframe', 'manual'])) {
        $filterMode = 'timeframe';
    }

    $timeFrame = $_GET['timeframe'] ?? 'month';

    $timeFrameDays = [
        'month' => 30,
        '3months' => 90,
        'year' => 365
    ];

    if (!isset($timeFrameDays[$timeFrame])) {
        $timeFrame = 'month';
    }

    $days = $timeFrameDays[$timeFrame];

    $fromDate = $_GET['fromDate'] ?? '';
    $toDate = $_GET['toDate'] ?? '';


    // ===== ADDED: Build date filter =====
    if (
        $filterMode === 'manual' &&
        !empty($fromDate) &&
        !empty($toDate)
    ) {

        // From Date starts at 12:00 AM
        // To Date includes the entire day
        $dateFilter = "
            AND dateRequested >= '$fromDate 00:00:00'
            AND dateRequested < DATE_ADD('$toDate', INTERVAL 1 DAY)
        ";

    } elseif ($filterMode === 'manual') {

        // Manual mode but dates have not been selected yet
        // Do not display any requests
        $dateFilter = "AND 1 = 0";

    } else {

        // Automatic timeframe mode
        $dateFilter = "
            AND dateRequested >= DATE_SUB(NOW(), INTERVAL $days DAY)
        ";
    }
    // ===== END ADDED =====

    $sql = "
        SELECT *
        FROM scilab_form_requests
        WHERE statusScilabPersonnel = '$statusFilter'
        $dateFilter
        ORDER BY dateRequested DESC
    ";
    $result = $conn->query($sql);

    $requests = [];
    $formIDs = [];

    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
        $formIDs[] = $row['id'];
    }

    $counts = [
        'Pending' => 0,
        'Approved' => 0,
        'Rejected' => 0
    ];

    $countQuery = $conn->query("
        SELECT statusScilabPersonnel AS status, COUNT(*) AS total
        FROM scilab_form_requests
        WHERE 1 = 1
        $dateFilter
        GROUP BY statusScilabPersonnel
    ");

    while ($row = $countQuery->fetch_assoc()) {
        $status = ucfirst(strtolower($row['status']));
        if (isset($counts[$status])) {
            $counts[$status] = $row['total'];
        }
    }

    $materials = [];
    $materialsDetailed = [];

    if ($formIDs) {
        $ids = implode(',', array_map('intval', $formIDs));

        $matQ = $conn->query("
            SELECT formID, quantity, item, description
            FROM scilab_material_requests
            WHERE formID IN ($ids)
        ");

        while ($m = $matQ->fetch_assoc()) {

            $item = htmlspecialchars($m['item']);
            $desc = !empty($m['description']) ? htmlspecialchars($m['description']) : '';
            $qty = $m['quantity'];

            // TABLE VERSION (ONLY ITEM)
            $materials[$m['formID']][] = "
                <div class='material-line'>
                    <span class='bullet'>•</span>
                    <span class='material-text'>{$item}</span>
                </div>
            ";

            // DETAILED VERSION (QTY + ITEM + DESCRIPTION)
            $materialsDetailed[$m['formID']][] = "
                <div class='material-line-detailed'>
                    <span class='material-qty'>[{$qty}x]</span>
                    <span class='material-text'>
                        {$item}" . ($desc ? " ({$desc})" : "") . "
                    </span>
                </div>
            ";
        }
    }
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Admin Approval</title>
        <?php include('helperFiles/headData.php'); ?>
        <style>
            body { background-color: #f5f5f5; }
            .form-container { background-color: #fff; padding: 25px; margin: 25px auto; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); width: 98%; }
            .table th { background-color: #2B55C4; color: white; text-align: center; white-space: nowrap; }
            .table td, .table th { font-size: 13px; vertical-align: middle; }
            .table-responsive { width: 100%; overflow-x: auto; }
            table tbody td { white-space: normal !important; word-wrap: break-word; vertical-align: top; }
            table td { max-width: 200px; }
            .action-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
            .dataTables_wrapper { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
            table.dataTable { width: 100% !important; table-layout: auto !important; white-space: nowrap; }
            .btn-scan { background-color: #0078D7; color: #fff; border: none; padding: 8px 12px; border-radius: 6px; margin-right: 8px; }
            .btn-scan:hover { background-color: #005fa3; }

            @media (max-width: 768px) {
                .form-container { padding: 10px; margin: 10px auto; }
                .action-header { flex-direction: column; align-items: flex-start; gap: 10px; }
            }

            .status-buttons { display: flex; gap: 10px; flex-wrap: wrap; }
            
            .btn-liquid.active {
                background: #2B55C4; color: white;
            }
            .btn-liquid-success.active {
                background: #28a745; color: white;
            }
            .btn-liquid-danger.active {
                background: #dc3545; color: white;
            }
            
            .btn-liquid .badge, .btn-liquid-success .badge, .btn-liquid-danger .badge {
                margin-right: 5px; background: rgba(0,0,0,0.1); color: inherit;
            }
            .btn-liquid.active .badge, .btn-liquid-success.active .badge, .btn-liquid-danger.active .badge {
                background: rgba(255,255,255,0.2); color: white;
            }

            @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
            .spin { animation: spin 1s linear infinite; display: inline-block; }

            .material-line {
                display: flex;
                align-items: flex-start;
                gap: 4px;
                margin-bottom: 4px;
            }

            .bullet {
                width: 14px; /* fixed width so all align */
                flex-shrink: 0;
                font-size: 16px;
                line-height: 1.4;
            }

            .material-text {
                flex: 1;
                line-height: 1.4;
            }
            .material-line-detailed {
                display: flex;
                align-items: flex-start;
                margin-bottom: 6px;
            }

            .material-qty {
                min-width: 30px;
                font-weight: 600;
            }

            .material-line-detailed .material-text {
                flex: 1;
                line-height: 1.4;
            }

            .request-flag-badge {
                display: inline-block;
                padding: 3px 8px;
                border-radius: 4px;
                background: #dc3545;
                color: #fff;
                font-size: 11px;
                font-weight: 700;
                letter-spacing: 0.5px;
                white-space: nowrap;
            }
            .request-flag-none { color: #999; font-size: 12px; }
            .request-flag-sub { font-size: 11px; color: #888; margin-top: 2px; white-space: nowrap; }
            tr.request-flagged-row { background: rgba(220, 53, 69, 0.07) !important; }

            /* Multiselect consistency */
            .form-group .multiselect-native-select .btn-group, 
            .form-group .btn-group { width: 100%; }
            button.multiselect {
                width: 100%; text-align: left; 
                background: linear-gradient(135deg, rgba(43, 85, 196, 0.05), rgba(43, 85, 196, 0.15)) !important;
                backdrop-filter: blur(8px);
                -webkit-backdrop-filter: blur(8px);
                border: 1px solid rgba(43, 85, 196, 0.2) !important;
                color: #2B55C4 !important; 
                display: flex; align-items: center; justify-content: space-between;
                height: 38px; border-radius: 20px !important; box-shadow: 0 4px 12px rgba(43, 85, 196, 0.1); background-image: none;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                font-weight: 600;
            }
            button.multiselect:focus, button.multiselect.active {
                box-shadow: 0 8px 20px rgba(43, 85, 196, 0.2); border-color: rgba(43, 85, 196, 0.4) !important; outline: 0;
            }
            button.multiselect .caret { margin-left: auto; }
            .multiselect-container {
                width: 100%;
                background: rgba(255, 255, 255, 0.95) !important;
                backdrop-filter: blur(16px);
                -webkit-backdrop-filter: blur(16px);
                border: 1px solid rgba(43, 85, 196, 0.2) !important;
                border-radius: 15px !important;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15) !important;
                margin-top: 5px !important;
                padding: 8px 0;
                max-height: 300px;
                overflow-y: auto;
            }
            .multiselect-container > li { margin-bottom: 2px; }
            .multiselect-container > li > a > label {
                padding: 2px 15px; width: 100%; cursor: pointer; font-weight: normal; margin: 0;
            }
            .multiselect-container > li > a { margin: 0 5px; border-radius: 8px; transition: all 0.2s; }
            .multiselect-container > li > a:hover { background-color: rgba(43, 85, 196, 0.1); color: #2B55C4; }
            .multiselect-container > li.active > a {
                background: linear-gradient(135deg, rgba(43, 85, 196, 0.8), rgba(43, 85, 196, 0.9)) !important;
                color: white !important;
                box-shadow: 0 4px 10px rgba(43, 85, 196, 0.3);
            }
            
            /* Custom Checkbox Style inside Dropdown */
            .multiselect-container input[type="checkbox"] {
                appearance: none;
                -webkit-appearance: none;
                width: 18px;
                height: 18px;
                border: 2px solid #2B55C4;
                border-radius: 5px;
                margin-right: 10px;
                position: relative;
                cursor: pointer;
                vertical-align: middle;
                margin-top: 0;
                background-color: rgba(255, 255, 255, 0.5);
                transition: all 0.2s ease;
            }

            @keyframes pulse-blue {
                0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(43, 85, 196, 0.7); }
                70% { transform: scale(1.15); box-shadow: 0 0 0 4px rgba(43, 85, 196, 0); }
                100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(43, 85, 196, 0); }
            }
            
            .multiselect-container input[type="checkbox"]:checked {
                background-color: #2B55C4;
                border-color: #2B55C4;
                animation: pulse-blue 0.4s ease-out;
            }
            
            .multiselect-container input[type="checkbox"]:checked::after {
                content: '';
                position: absolute;
                left: 5px;
                top: 1px;
                width: 5px;
                height: 10px;
                border: solid white;
                border-width: 0 2px 2px 0;
                transform: rotate(45deg);
            }
            
            /* Ensure label text aligns nicely with custom checkbox */
            .multiselect-container > li > a > label {
                display: flex !important;
                align-items: center;
                padding: 8px 15px !important;
            }
            
            /* Invert colors for active state row */
            .multiselect-container > li.active > a input[type="checkbox"] {
                border-color: rgba(255, 255, 255, 0.8);
            }
            .multiselect-container > li.active > a input[type="checkbox"]:checked {
                background-color: white;
                border-color: white;
            }
            .multiselect-container > li.active > a input[type="checkbox"]:checked::after {
                border-color: #2B55C4;
            }

            /* ===== ADDED: Request timeframe filter ===== */
            .requests-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                width: 100%;
                margin-bottom: 15px;
            }

            .requests-header h2 {
                margin: 0;
            }

            .timeframe-header {
                display: flex;
                flex-direction: column;
                align-items: flex-end;
                gap: 8px;
                margin: 0;
            }

            .timeframe-top {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .timeframe-title {
                font-size: 15px;
                font-weight: 600;
                color: #333;
                margin: 0;
                white-space: nowrap;
            }

            .timeframe-buttons {
                display: flex;
                justify-content: flex-end;
                gap: 8px;
                margin: 0 0 15px 0;
            }

            .timeframe-buttons .btn {
                font-size: 13px;
                padding: 6px 12px;
                border-color: #2B55C4;
            }

            .timeframe-buttons .btn.active {
                background: #2B55C4;
                color: white;
                border-color: #2B55C4;
            }


            /* Pill toggle */

            .timeframe-toggle {
                position: relative;
                display: inline-block;
                width: 48px;
                height: 24px;
            }

            .timeframe-toggle input {
                opacity: 0;
                width: 0;
                height: 0;
            }

            .timeframe-slider {
                position: absolute;
                cursor: pointer;
                inset: 0;
                background-color: #ccc;
                border-radius: 24px;
                transition: 0.2s;
            }

            .timeframe-slider:before {
                content: "";
                position: absolute;
                height: 18px;
                width: 18px;
                left: 3px;
                top: 3px;
                background-color: white;
                border-radius: 50%;
                transition: 0.2s;
                box-shadow: 0 1px 3px rgba(0,0,0,0.2);
            }

            .timeframe-toggle input:checked + .timeframe-slider {
                background-color: #2B55C4;
            }

            .timeframe-toggle input:checked + .timeframe-slider:before {
                transform: translateX(24px);
            }


            /* Manual date range */

            .manual-date-container {
                display: flex;
                justify-content: flex-end;
                align-items: flex-end;
                gap: 10px;
                margin-bottom: 15px;
            }

            .manual-date-group {
                display: flex;
                flex-direction: column;
                gap: 4px;
            }

            .manual-date-group label {
                font-size: 12px;
                font-weight: 600;
                color: #555;
            }

            .manual-date-group input {
                font-size: 13px;
                padding: 6px 8px;
                border: 1px solid #ccc;
                border-radius: 5px;
            }

            .manual-date-submit {
                height: 34px;
                padding: 5px 14px;
                border: none;
                border-radius: 5px;
                background-color: #2B55C4;
                color: white;
                cursor: pointer;
            }

            .manual-date-submit:hover {
                background-color: #21449f;
            }


            @media (max-width: 768px) {

                .requests-header {
                    flex-direction: column;
                    gap: 10px;
                }

                .timeframe-header {
                    width: 100%;
                    align-items: flex-start;
                }

                .timeframe-top {
                    flex-wrap: wrap;
                }

                .timeframe-title {
                    white-space: normal;
                }

                .timeframe-buttons {
                    justify-content: flex-start;
                    flex-wrap: wrap;
                }

                .manual-date-container {
                    justify-content: flex-start;
                    flex-wrap: wrap;
                }
            }
            /* ============================================================
                DESIGN TOKENS
                ============================================================ */
                    :root {
                        --primary: #0B1B62;
                        --secondary: #4F73D9;
                        --secondary-light: #EEF2FB;
                        --success: #16A34A;
                        --success-light: #DCFCE7;
                        --danger: #DC2626;
                        --danger-light: #FEE2E2;
                        --muted: #94A3B8;
                        --muted-light: #F1F5F9;
                        --text-primary: #0B1B62;
                        --text-secondary: #707070;
                        --border: #E2E8F0;
                        --card-bg: #FFFFFF;
                        --input-bg: #F8FAFC;
                        --bg-gradient: linear-gradient(135deg, #EBF0FA 0%, #dce6f8 100%);
                        --shadow-card: 0 4px 24px rgba(11, 27, 98, 0.08), 0 1px 4px rgba(11, 27, 98, 0.04);
                        --shadow-modal: 0 20px 60px rgba(11, 27, 98, 0.18), 0 4px 16px rgba(11, 27, 98, 0.1);
                        --radius-card: 16px;
                        --radius-btn: 10px;
                        --radius-input: 10px;
                        --font: 'Plus Jakarta Sans', system-ui, sans-serif;
                        --transition: 0.22s cubic-bezier(.4, 0, .2, 1);
                    }
            /* ============================================================
            MODAL OVERLAY
            ============================================================ */
                .custom-modal-overlay {
                    position: fixed;
                    inset: 0;
                    background: rgba(11, 27, 98, 0.45);
                    backdrop-filter: blur(4px);
                    z-index: 200000;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                    opacity: 0;
                    pointer-events: none;
                    transition: opacity 0.25s ease;
                }

                .custom-modal-overlay.open {
                    opacity: 1;
                    pointer-events: all;
                }

                .custom-modal {
                    background: var(--card-bg);
                    border-radius: var(--radius-card);
                    box-shadow: var(--shadow-modal);
                    width: 100%;
                    max-width: 620px;
                    max-height: 90vh;
                    overflow-y: auto;
                    transform: scale(0.94) translateY(16px);
                    transition: transform 0.28s cubic-bezier(.34, 1.56, .64, 1), opacity 0.25s ease;
                    opacity: 0;
                }

                .custom-modal-overlay.open .custom-modal {
                    transform: scale(1) translateY(0);
                    opacity: 1;
                }

                /* scrollbar */
                .custom-modal::-webkit-scrollbar {
                    width: 5px;
                }

                .custom-modal::-webkit-scrollbar-track {
                    background: transparent;
                }

                .custom-modal::-webkit-scrollbar-thumb {
                    background: var(--border);
                    border-radius: 10px;
                }

                .custom-modal-header {
                    padding: 24px 28px 19px;
                    border-bottom: 1px solid var(--border);
                    display: flex;
                    align-items: flex-start;
                    justify-content: space-between;
                    gap: 16px;
                    position: sticky;
                    top: 0;
                    background: var(--card-bg);
                    z-index: 10000;
                    border-radius: var(--radius-card) var(--radius-card) 0 0;
                }

                .custom-modal-header-left h2 {
                    font-size: 17px;
                    font-weight: 800;
                    color: var(--primary);
                }

                .custom-modal-header-left p {
                    font-size: 12px;
                    color: var(--text-secondary);
                    margin-top: 3px;
                }

                .custom-modal-close {
                    width: 32px;
                    height: 32px;
                    border: none;
                    background: var(--muted-light);
                    border-radius: 8px;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: var(--text-secondary);
                    transition: var(--transition);
                    flex-shrink: 0;
                }

                .custom-modal-close:hover {
                    background: var(--border);
                    color: var(--primary);
                }

                .custom-modal-body {
                    padding: 24px 28px;
                }

                .custom-modal-section-title {
                    font-size: 11px;
                    font-weight: 700;
                    text-transform: uppercase;
                    letter-spacing: 0.09em;
                    color: var(--secondary);
                    margin-bottom: 16px;
                    padding-bottom: 8px;
                    border-bottom: 1.5px solid var(--secondary-light);
                }

                .detail-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 16px 24px;
                    margin-bottom: 24px;
                }

                .detail-field {}

                .detail-label {
                    font-size: 11px;
                    font-weight: 700;
                    text-transform: uppercase;
                    letter-spacing: 0.08em;
                    color: var(--muted);
                    margin-bottom: 4px;
                }

                .detail-value {
                    font-size: 14px;
                    font-weight: 600;
                    color: var(--text-primary);
                    background: var(--input-bg);
                    border: 1px solid var(--border);
                    border-radius: 8px;
                    padding: 8px 12px;
                    min-height: 38px;
                    display: flex;
                    align-items: center;
                }

                .detail-value.full-width {
                    grid-column: 1 / -1;
                }

                .detail-field.full-width {
                    grid-column: 1 / -1;
                }

                .detail-field.full-width .detail-value {
                    align-items: flex-start;
                    line-height: 1.5;
                }

                /* ---- Rejection area ---- */
                .reject-area {
                    margin-top: 20px;
                    display: none;
                    animation: fade-in 0.22s ease;
                }

                .reject-area.visible {
                    display: block;
                }

                @keyframes fade-in {
                    from {
                        opacity: 0;
                        transform: translateY(6px);
                    }

                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }

                .reject-area label {
                    display: block;
                    font-size: 12px;
                    font-weight: 700;
                    color: var(--danger);
                    margin-bottom: 7px;
                }

                .reject-area textarea {
                    width: 100%;
                    background: var(--input-bg);
                    border: 1.5px solid rgba(220, 38, 38, 0.4);
                    border-radius: var(--radius-input);
                    padding: 10px 12px;
                    font-family: var(--font);
                    font-size: 14px;
                    color: var(--text-primary);
                    resize: vertical;
                    min-height: 90px;
                    outline: none;
                    transition: var(--transition);
                }

                .reject-area textarea:focus {
                    border-color: var(--danger);
                    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.12);
                }

                .reject-error {
                    font-size: 12px;
                    color: var(--danger);
                    margin-top: 5px;
                    display: none;
                }

                .reject-error.visible {
                    display: block;
                }

                .custom-modal-footer {
                    padding: 20px 28px;
                    border-top: 1px solid var(--border);
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    flex-wrap: wrap;
                }

                .custom-modal-footer-right {
                    margin-left: auto;
                    display: flex;
                    gap: 12px;
                    flex-wrap: wrap;
                }
        </style>
    </head>
    <body>
        <?php include('helperFiles/header.php'); ?>

        <div class="main-wrapper">
            
            <div class="form-container">
                <div class="action-header">
                    <div class="requests-header">
                        <h2>Request Forms Management</h2>
                        <div>
                            <button id="scanBarcodeBtn" class="btn-scan">Generate Summary</button>
                        </div>

                        <!-- ===== UPDATED: Request header with timeframe controls ===== -->
                        <div class="timeframe-header">
                            <!-- Heading + Toggle -->
                            <div class="timeframe-top">

                                <h5 class="timeframe-title">
                                    Display Past Requests within Timeframe:
                                </h5>

                                <form method="GET" id="filterModeForm">

                                    <input type="hidden"
                                        name="status"
                                        value="<?= htmlspecialchars($statusFilter) ?>">

                                    <input type="hidden"
                                        name="timeframe"
                                        value="<?= htmlspecialchars($timeFrame) ?>">

                                    <input type="hidden"
                                        name="fromDate"
                                        value="<?= htmlspecialchars($fromDate) ?>">

                                    <input type="hidden"
                                        name="toDate"
                                        value="<?= htmlspecialchars($toDate) ?>">

                                    <input
                                        type="hidden"
                                        name="filterMode"
                                        id="filterMode"
                                        value="<?= htmlspecialchars($filterMode) ?>"
                                    >

                                    <label class="timeframe-toggle">

                                        <input
                                            type="checkbox"
                                            id="timeframeToggle"
                                            <?= $filterMode === 'timeframe' ? 'checked' : '' ?>
                                        >

                                        <span class="timeframe-slider"></span>

                                    </label>

                                </form>

                            </div>


                            <?php if ($filterMode === 'timeframe'): ?>

                                <!-- ===== ON: Simple timeframe buttons ===== -->

                                <div class="timeframe-buttons">

                                    <a href="?status=<?= urlencode($statusFilter) ?>&filterMode=timeframe&timeframe=month"
                                    class="btn btn-outline-primary <?= $timeFrame === 'month' ? 'active' : '' ?>">
                                        Month
                                    </a>

                                    <a href="?status=<?= urlencode($statusFilter) ?>&filterMode=timeframe&timeframe=3months"
                                    class="btn btn-outline-primary <?= $timeFrame === '3months' ? 'active' : '' ?>">
                                        3 Months
                                    </a>

                                    <a href="?status=<?= urlencode($statusFilter) ?>&filterMode=timeframe&timeframe=year"
                                    class="btn btn-outline-primary <?= $timeFrame === 'year' ? 'active' : '' ?>">
                                        Year
                                    </a>

                                </div>

                            <?php else: ?>

                                <!-- ===== OFF: Manual date range ===== -->

                                <form method="GET" class="manual-date-container">

                                    <input type="hidden"
                                        name="status"
                                        value="<?= htmlspecialchars($statusFilter) ?>">

                                    <input type="hidden"
                                        name="filterMode"
                                        value="manual">

                                    <div class="manual-date-group">

                                        <label for="fromDate">
                                            From Date
                                        </label>

                                        <input
                                            type="date"
                                            id="fromDate"
                                            name="fromDate"
                                            value="<?= htmlspecialchars($fromDate) ?>"
                                            required
                                        >

                                    </div>

                                    <div class="manual-date-group">

                                        <label for="toDate">
                                            To Date
                                        </label>

                                        <input
                                            type="date"
                                            id="toDate"
                                            name="toDate"
                                            value="<?= htmlspecialchars($toDate) ?>"
                                            required
                                        >

                                    </div>

                                    <button type="submit" class="manual-date-submit">
                                        Apply
                                    </button>

                                </form>

                            <?php endif; ?>

                        </div>
                        <!-- ===== END UPDATED ===== -->
                    </div>
                </div>

                <div class="status-buttons mb-3">
                    <a href="?status=Pending&filterMode=<?= urlencode($filterMode) ?>&timeframe=<?= urlencode($timeFrame) ?>&fromDate=<?= urlencode($fromDate) ?>&toDate=<?= urlencode($toDate) ?>" class="btn-liquid <?= $statusFilter === 'Pending' ? 'active' : '' ?>">
                        <span class="badge badge-pill badge-secondary"><?= $counts['Pending'] ?></span> Pending
                    </a>
                    <a href="?status=Approved&filterMode=<?= urlencode($filterMode) ?>&timeframe=<?= urlencode($timeFrame) ?>&fromDate=<?= urlencode($fromDate) ?>&toDate=<?= urlencode($toDate) ?>" class="btn-liquid-success <?= $statusFilter === 'Approved' ? 'active' : '' ?>"><span class="badge badge-pill badge-secondary"><?= $counts['Approved'] ?></span> Approved
                    </a>
                    <a href="?status=Rejected&filterMode=<?= urlencode($filterMode) ?>&timeframe=<?= urlencode($timeFrame) ?>&fromDate=<?= urlencode($fromDate) ?>&toDate=<?= urlencode($toDate) ?>" class="btn-liquid-danger <?= $statusFilter === 'Rejected' ? 'active' : '' ?>">
                        <span class="badge badge-pill badge-secondary"><?= $counts['Rejected'] ?></span> Rejected
                    </a>
                </div>
                <br/>

                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="admin-approval-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Requester Name</th>
                                <?php if ($statusFilter === 'Approved'): ?><th>Control #</th><?php endif; ?>
                                <th>Lab Name</th>
                                <th>Grade - Section/s</th>
                                <th>Subject</th>
                                <th>Topic</th>
                                <th>Date of Use</th>
                                <th>Requested Materials</th>
                                <th>Teacher-in-Charge</th>
                                <th>Flag</th>
                                <?php if ($statusFilter === 'Approved'): ?><th>Remarks</th><?php endif; ?>
                                <?php if ($statusFilter === 'Rejected'): ?><th>Feedback</th><?php endif; ?>
                                <?php if ($statusFilter !== 'Rejected'): ?><th>Action</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php
                                    $i = 1;
                                    foreach ($requests as $row):
                                        $employeeID = $row['requesterEmployeeID'];
                                        $nameQuery = $conn->query("SELECT firstname, middlename, lastname FROM accounts WHERE employeeID = '$employeeID'");
                                        $fullName = $employeeID;
                                        if ($nameQuery && $nameRow = $nameQuery->fetch_assoc()) {
                                            $fullName = $nameRow['firstname'].' '.$nameRow['middlename'].' '.$nameRow['lastname'];
                                        } else {
                                            $studentQuery = $conn->query("SELECT firstname, middlename, lastname FROM student WHERE LRN = '$employeeID'");
                                            if ($studentQuery && $studentRow = $studentQuery->fetch_assoc()) {
                                                $fullName = $studentRow['firstname'].' '.$studentRow['middlename'].' '.$studentRow['lastname'];
                                            }
                                        }
                                        $row['requesterName'] = $fullName;
                                        $formID = $row['id'];
                                        $materialText = isset($materials[$formID]) ? implode("", $materials[$formID]) : '—';
                                        $row['materialsDetailed'] = isset($materialsDetailed[$formID]) ? implode("", $materialsDetailed[$formID]) : '—';
                                        $teacherInCharge = !empty($row['teacherInCharge']) ? htmlspecialchars($row['teacherInCharge']) : '—';
                                        $flagged = (!empty($row['dateRequested']) && strtotime($row['dateRequested']) >= strtotime('-3 days'));
                                ?>
                                    <tr id="row-<?= $row['id'] ?>" class="<?= $flagged ? 'request-flagged-row' : '' ?>">
                                        <td><span style="display:none;"><?= $row['id'] ?></span><?= $i++ ?></td>
                                        <td><?= htmlspecialchars($fullName) ?></td>
                                        <?php if ($statusFilter === 'Approved'):
                                            $ctrlPrimary = ($row['control_equipment'] ?? '') ?: (($row['control_reagent'] ?? '') ?: (($row['control_permit'] ?? '') ?: (($row['control_reservation'] ?? '') ?: ($row['controlNumber'] ?? ''))));
                                        ?><td><?= htmlspecialchars($ctrlPrimary) ?></td><?php endif; ?>
                                        <td><?= htmlspecialchars($row['scilabName']) ?></td>
                                        <td><?= htmlspecialchars("Grade ".$row['gradeLevel']." - ".$row['sections']) ?></td>
                                        <td><?= htmlspecialchars($row['subject']) ?></td>
                                        <td><?= htmlspecialchars($row['subjectTopic']) ?></td>
                                        <td><?= htmlspecialchars($row['inclusiveDate']).' ('.htmlspecialchars($row['inclusiveTime']).')' ?></td>
                                        <td><?= $materialText ?></td>
                                        <td><?= $teacherInCharge ?></td>
                                        <td><?= $flagged ? '<span class="request-flag-badge" title="Requested less than 3 days ago">FLAGGED</span><div class="request-flag-sub">' . htmlspecialchars(date('M d, Y', strtotime($row['dateRequested']))) . '</div>' : '<span class="request-flag-none">—</span>' ?></td>

                                        <?php if ($statusFilter === 'Approved'): ?>
                                            <td><?= htmlspecialchars($row['feedback'] ?? '—') ?></td>
                                        <?php endif; ?>

                                        <?php if ($statusFilter === 'Rejected'): ?>
                                            <td><?= htmlspecialchars($row['feedback'] ?? 'No reason provided') ?></td>
                                        <?php endif; ?>

                                        <?php if ($statusFilter !== 'Rejected'): ?>
                                            <td>
                                                <?php if ($statusFilter === 'Pending'): ?>
                                                    <a href="supervisor_approve.php?id=<?= $row['id'] ?>" class="btn-liquid" style="width: 90%; margin-bottom: 10px; display:block; text-align:center; padding-left:0; padding-right:0;">Review</a>
                                                    <button class="btn-liquid-success approve-btn" style="width: 90%; margin-bottom: 10px; padding-left:0; padding-right:0;" data-request='<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>'>Force Approve</button>
                                                    <button class="btn-liquid-danger reject-btn" style="width: 90%; padding-left:0; padding-right:0;" data-request='<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>'>Reject</button>
                                                <?php elseif ($statusFilter === 'Approved'): ?>
                                                    <a href="templates/print_template.php?id=<?= $row['id'] ?>" target="_blank" class="btn-liquid" style="width: 90%; margin-bottom: 10px; display:block; text-align:center; padding-left:0; padding-right:0;">
                                                        <i class="glyphicon glyphicon-print"></i> Print
                                                    </a>
                                                    <button class="btn-liquid edit-control-btn" style="width: 90%; padding-left:0; padding-right:0;" data-request='<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>'>
                                                        <i class="glyphicon glyphicon-edit"></i> Edit Control #'s
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ============================================================
                MODAL — FORCE APPROVE
            ============================================================ -->
            <div class="custom-modal-overlay" id="approveModal" role="dialog" aria-modal="true" aria-labelledby="approveModalTitle">
                <div class="custom-modal" id="custom-approve-modal">

                    <!-- Modal Header -->
                    <div class="custom-modal-header">
                        <div class="custom-modal-header-left">
                            <h2 id="approveModalTitle">FORCE APPROVE REQUEST</h2>
                            <p>Request ID:
                                <strong id="approveRequestId">REQ-0000</strong>
                            </p>
                        </div>

                        <button type="button" class="custom-modal-close" id="approveModalCloseTop" aria-label="Close modal">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                                stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                <line x1="18" y1="6" x2="6" y2="18" />
                                <line x1="6" y1="6" x2="18" y2="18" />
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="custom-modal-body">

                        <!-- Pending Conflict -->
                        <div id="admin-conflict-warning"></div>

                        <!-- Requester Information -->
                        <p class="custom-modal-section-title">Requester Information</p>

                        <div class="detail-grid">

                            <div class="detail-field">
                                <div class="detail-label">Full Name</div>
                                <div class="detail-value" id="approveRequesterName">—</div>
                            </div>

                            <div class="detail-field">
                                <div class="detail-label">Student / Faculty ID</div>
                                <div class="detail-value" id="approveRequesterID">—</div>
                            </div>

                            <div class="detail-field">
                                <div class="detail-label">Department</div>
                                <div class="detail-value" id="approveRequesterDept">—</div>
                            </div>

                            <div class="detail-field">
                                <div class="detail-label">Contact Email</div>
                                <div class="detail-value" id="approveRequesterEmail">—</div>
                            </div>

                        </div>

                        <!-- Reservation Details -->
                        <p class="custom-modal-section-title">Reservation Details</p>

                        <div class="detail-grid">

                            <div class="detail-field">
                                <div class="detail-label">Laboratory</div>
                                <div class="detail-value" id="approveLaboratory">—</div>
                            </div>

                            <div class="detail-field">
                                <div class="detail-label">Date of Use</div>
                                <div class="detail-value" id="approveDate">—</div>
                            </div>

                            <div class="detail-field">
                                <div class="detail-label">Time</div>
                                <div class="detail-value" id="approveTime">—</div>
                            </div>

                            <div class="detail-field">
                                <div class="detail-label">Teacher-in-Charge</div>
                                <div class="detail-value" id="approveTeacher">—</div>
                            </div>

                            <div class="detail-field">
                                <div class="detail-label">Subject</div>
                                <div class="detail-value" id="approveSubject">—</div>
                            </div>

                            <div class="detail-field">
                                <div class="detail-label">Topic</div>
                                <div class="detail-value" id="approveTopic">—</div>
                            </div>

                            <div class="detail-field full-width">
                                <div class="detail-label">Purpose / Activity</div>
                                <div class="detail-value" id="approvePurpose">—</div>
                            </div>

                        </div>

                        <!-- Requested Materials -->
                        <p class="custom-modal-section-title">Requested Materials</p>

                        <div class="detail-grid">
                            <div class="detail-field full-width">
                                <div class="detail-label">Materials</div>
                                <div class="detail-value" id="approveMaterials" style="display: block;">
                                    —
                                </div>
                            </div>
                        </div>

                        <!-- Control Numbers -->
                        <p class="custom-modal-section-title">Control Numbers</p>

                        <div class="detail-grid">

                            <div class="detail-field">
                                <div class="detail-label">Equipment Form</div>
                                <div class="detail-value" style="padding: 0;">
                                    <input type="text"
                                        class="liquid-input"
                                        name="control_equipment"
                                        id="control_equipment"
                                        placeholder="e.g. CIID-20-001">
                                </div>
                            </div>

                            <div class="detail-field">
                                <div class="detail-label">Reagent Form</div>
                                <div class="detail-value" style="padding: 0;">
                                    <input type="text"
                                        class="liquid-input"
                                        name="control_reagent"
                                        id="control_reagent"
                                        placeholder="e.g. RG-001">
                                </div>
                            </div>

                            <div class="detail-field">
                                <div class="detail-label">Work Permit</div>
                                <div class="detail-value" style="padding: 0;">
                                    <input type="text"
                                        class="liquid-input"
                                        name="control_permit"
                                        id="control_permit"
                                        placeholder="e.g. PT-001">
                                </div>
                            </div>

                            <div class="detail-field">
                                <div class="detail-label">Lab Reservation Form</div>
                                <div class="detail-value" style="padding: 0;">
                                    <input type="text"
                                        class="liquid-input"
                                        name="control_reservation"
                                        id="control_reservation"
                                        placeholder="e.g. CID-05-0042">
                                </div>
                            </div>

                            <div class="detail-field full-width">
                                <div class="detail-label">Remarks</div>
                                <div class="detail-value" style="padding: 0;">
                                    <textarea
                                        class="liquid-input"
                                        name="approveRemarks"
                                        id="approveRemarks"
                                        placeholder="Enter remarks (optional)"
                                        rows="3"></textarea>
                                </div>
                            </div>

                        </div>

                    </div><!-- /custom-modal-body -->

                    <!-- Modal Footer -->
                    <div class="custom-modal-footer" id="approveModalFooter">

                        <input type="hidden" name="approveId" id="approveId">

                        <button type="submit"
                            form="approveForm"
                            class="btn-liquid-success"
                            id="confirmForceApproveBtn">
                            Confirm Force Approval
                        </button>

                        <button type="button"
                            class="btn-liquid-secondary"
                            id="approveModalCancelBtn">
                            Cancel
                        </button>

                    </div>

                </div><!-- /custom-modal -->
            </div>

            <!-- Hidden form used for Force Approve submission -->
            <form id="approveForm" style="display:none;"></form>

            <!-- Edit Control Numbers Modal -->
            <div class="modal fade" id="editControlModal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <form id="editControlForm">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Control Numbers</h5>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                            </div>
                            <div class="modal-body">
                                <div class="form-group mt-3">
                                    <label for="ec_control_equipment">Control Number - Equipment Form:</label>
                                    <input type="text" class="form-control liquid-input" name="ec_control_equipment" id="ec_control_equipment" placeholder="e.g. CIID-20-001">
                                </div>
                                <div class="form-group">
                                    <label for="ec_control_reagent">Control Number - Reagent Form:</label>
                                    <input type="text" class="form-control liquid-input" name="ec_control_reagent" id="ec_control_reagent" placeholder="e.g. RG-001">
                                </div>
                                <div class="form-group">
                                    <label for="ec_control_permit">Control Number - Work Permit:</label>
                                    <input type="text" class="form-control liquid-input" name="ec_control_permit" id="ec_control_permit" placeholder="e.g. PT-001">
                                </div>
                                <div class="form-group">
                                    <label for="ec_control_reservation">Control Number - Lab Reservation Form:</label>
                                    <input type="text" class="form-control liquid-input" name="ec_control_reservation" id="ec_control_reservation" placeholder="e.g. CID-05-0042">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <input type="hidden" name="ec_request_id" id="ec_request_id">
                                <button type="submit" class="btn-liquid-success">Save</button>
                                <button type="button" class="btn-liquid-secondary" data-dismiss="modal">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Reject Modal -->
            <div class="modal fade" id="rejectModal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Reject Request</h5>
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>
                        <div class="modal-body">
                            <div id="rejectSummaryContent"></div>
                            <div class="form-group mt-3">
                                <label for="rejectionFeedback"><strong>Reason for Rejection:</strong></label>
                                <textarea id="rejectionFeedback" class="form-control liquid-input" rows="3" required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn-liquid-danger" id="confirmReject">Yes, Reject</button>
                            <button type="button" class="btn-liquid-secondary" data-dismiss="modal">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Modal --> 
            <div id="summaryModal" class="modal fade" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title">Request Summary</h4>
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group text-center">
                                <label><strong>Quick Select Timeframe</strong></label>
                                <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                                    <button type="button" class="btn-liquid date-range-btn" data-range="week">Past Week</button>
                                    <button type="button" class="btn-liquid date-range-btn" data-range="month">Past Month</button>
                                    <button type="button" class="btn-liquid date-range-btn" data-range="3-months">Past 3 Months</button>
                                    <button type="button" class="btn-liquid date-range-btn" data-range="year">Past Year</button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="summaryClassification"><strong>Material Classification:</strong></label>
                                <select class="form-control" id="summaryClassification" multiple="multiple">
                                    <option value="Equipment">Equipment</option>
                                    <option value="Semi Expendable">Semi Expendable</option>
                                    <option value="Consumable">Consumable</option>
                                    <option value="Reagent">Reagent</option>
                                    <option value="Glassware">Glassware</option>
                                    <option value="Food Lab">Food Lab</option>
                                    <option value="Uncategorized">Uncategorized</option>
                                </select>
                            </div>
                            <hr>
                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label for="startDate"><strong>Or Select Manually:</strong> Start Date</label>
                                    <input type="date" class="form-control liquid-input" id="startDate">
                                </div>
                                <div class="col-md-6">
                                    <label for="endDate">End Date</label>
                                    <input type="date" class="form-control liquid-input" id="endDate">
                                </div>
                            </div>
                            <div id="summaryContent"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn-liquid-secondary" data-dismiss="modal">Cancel</button>
                            <button type="button" class="btn-liquid" id="generateSummary">Generate Summary</button>
                        </div>
                </div>
            </div>
        </div>

        <?php include 'helperFiles/footer.php'; ?>
    </body>

    <script>
        $(document).ready(function () {
            var table = $('#admin-approval-table').DataTable({
                language: {
                    emptyTable: "No requests found."
                },
                responsive: true,
                pageLength: 10,
                lengthChange: true,
                searching: true,
                ordering: true,
                info: true
            });

            // Check for search parameter in URL
            const urlParams = new URLSearchParams(window.location.search);
            const searchParam = urlParams.get('search');
            if (searchParam) {
                table.search(searchParam).draw();
            }

            // ============================================================
            // FORCE APPROVE BUTTON
            // ============================================================
            $('.approve-btn').click(function () {
                const data = $(this).data('request');

                // Request ID
                $('#approveId').val(data.id);

                const formattedRequestId = String(data.id).padStart(4, '0');
                $('#approveRequestId').text('REQ-' + formattedRequestId);

                // Clear previous values
                $('#control_equipment').val('');
                $('#control_reagent').val('');
                $('#control_permit').val('');
                $('#control_reservation').val('');
                $('#approveRemarks').val('');

                // ========================================================
                // REQUESTER INFORMATION
                // ========================================================
                $('#approveRequesterName').text(
                    data.requesterName || data.requesterEmployeeID || '—'
                );

                $('#approveRequesterID').text(
                    data.requesterID ||
                    data.requesterEmployeeID ||
                    data.employeeID ||
                    '—'
                );

                $('#approveRequesterDept').text(
                    data.requesterDept ||
                    data.department ||
                    '—'
                );

                $('#approveRequesterEmail').text(
                    data.requesterEmail ||
                    data.email ||
                    '—'
                );

                // ========================================================
                // RESERVATION DETAILS
                // ========================================================
                $('#approveLaboratory').text(
                    data.scilabName || '—'
                );

                $('#approveDate').text(
                    data.inclusiveDate || '—'
                );

                $('#approveTime').text(
                    data.inclusiveTime || '—'
                );

                $('#approveTeacher').text(
                    data.teacherInCharge ||
                    data.teacher ||
                    data.teacherName ||
                    '—'
                );

                $('#approveSubject').text(
                    data.subject || '—'
                );

                $('#approveTopic').text(
                    data.subjectTopic || '—'
                );

                $('#approvePurpose').text(
                    data.purpose ||
                    data.activity ||
                    '—'
                );

                // Requested materials already contain the formatted
                // [quantity x] item (description) HTML from PHP.
                $('#approveMaterials').html(
                    data.materialsDetailed || '—'
                );

                // ========================================================
                // RESET CONFLICT WARNING
                // ========================================================
                $('#admin-conflict-warning').empty();

                $('#confirmForceApproveBtn').prop('disabled', false);

                // ========================================================
                // CHECK FOR CONFLICT
                // ========================================================
                // Parse inclusiveTime (e.g. "09:30 to 11:30")
                let timeParts = (data.inclusiveTime || '').split(' to ');

                if (
                    timeParts.length === 2 &&
                    data.scilabName &&
                    data.inclusiveDate
                ) {
                    $.post('ajax/ajax_forms.php', {
                        action: 'check_conflict',
                        scilabName: data.scilabName,
                        date: data.inclusiveDate,
                        startTime: timeParts[0].trim(),
                        endTime: timeParts[1].trim(),
                        exclude_id: data.id
                    }, function(res) {

                        if (res.status === 'success') {

                            // ====================================================
                            // APPROVED CONFLICT
                            // Existing behavior retained exactly
                            // ====================================================
                            if (res.conflict_type === 'approved') {

                                $('#admin-conflict-warning').html(`
                                    <div class="alert alert-danger" style="margin-bottom:15px; border-radius:8px;">
                                        <strong><i class="glyphicon glyphicon-ban-circle"></i> Severe Conflict:</strong> An <b>approved</b> request already exists for this timeframe (${res.details.time}). Force approving this will double-book the room.
                                    </div>
                                `);

                                $('#confirmForceApproveBtn').prop('disabled', true);

                            // ====================================================
                            // PENDING CONFLICT
                            // Existing behavior retained exactly
                            // ====================================================
                            } else if (res.conflict_type === 'pending') {

                                $('#admin-conflict-warning').html(`
                                    <div class="alert alert-warning" style="margin-bottom:15px; border-radius:8px;">
                                        <strong><i class="glyphicon glyphicon-warning-sign"></i> Pending Conflict:</strong> There is another pending request for this timeframe (${res.details.time} - ${res.details.subject}).
                                    </div>
                                `);
                            }
                        }

                    }, 'json');
                }

                // ========================================================
                // SHOW CUSTOM MODAL
                // ========================================================
                $('#approveModal').addClass('show');
                $('body').addClass('modal-open');
            });


            // ============================================================
            // FORCE APPROVE MODAL - CLOSE BUTTONS
            // ============================================================
            $('#approveModalCloseTop, #approveModalCancelBtn').click(function () {
                $('#approveModal').removeClass('show');
                $('body').removeClass('modal-open');
            });


            // ============================================================
            // FORCE APPROVE MODAL - CLICK OUTSIDE
            // ============================================================
            $('#approveModal').click(function (e) {
                if (e.target === this) {
                    $('#approveModal').removeClass('show');
                    $('body').removeClass('modal-open');
                }
            });

            $('#approveForm').submit(function (e) {
                e.preventDefault();
                const id = $('#approveId').val();
                const controlEquipment = $('#control_equipment').val().trim();
                const controlReagent = $('#control_reagent').val().trim();
                const controlPermit = $('#control_permit').val().trim();
                const controlReservation = $('#control_reservation').val().trim();
                const remarks = $('#approveRemarks').val().trim();

                $.post('ajax/ajax_admin_action.php', {
                    action: 'force_approve',
                    id: id,
                    control_equipment: controlEquipment,
                    control_reagent: controlReagent,
                    control_permit: controlPermit,
                    control_reservation: controlReservation,
                    remarks: remarks
                }, function (response) {
                    const isSuccess = response.toLowerCase().includes('approved');
                    showToast(response, isSuccess ? 'success' : 'error');
                    if (isSuccess) {
                        setTimeout(() => location.reload(), 1500);
                    }
                });
            });

            // EDIT CONTROL NUMBERS (approved requests)
            $('.edit-control-btn').click(function () {
                const data = $(this).data('request');
                $('#ec_request_id').val(data.id);
                $('#ec_control_equipment').val(data.control_equipment || '');
                $('#ec_control_reagent').val(data.control_reagent || '');
                $('#ec_control_permit').val(data.control_permit || '');
                $('#ec_control_reservation').val(data.control_reservation || '');
                $('#editControlModal').modal('show');
            });

            $('#editControlForm').submit(function (e) {
                e.preventDefault();
                const id = $('#ec_request_id').val();
                const controlEquipment = $('#ec_control_equipment').val().trim();
                const controlReagent = $('#ec_control_reagent').val().trim();
                const controlPermit = $('#ec_control_permit').val().trim();
                const controlReservation = $('#ec_control_reservation').val().trim();

                $.post('ajax/ajax_admin_action.php', {
                    action: 'update_control_numbers',
                    id: id,
                    control_equipment: controlEquipment,
                    control_reagent: controlReagent,
                    control_permit: controlPermit,
                    control_reservation: controlReservation
                }, function (response) {
                    const isSuccess = response.toLowerCase().includes('updated') || response.toLowerCase().includes('success');
                    showToast(response, isSuccess ? 'success' : 'error');
                    if (isSuccess) {
                        setTimeout(() => location.reload(), 1200);
                    }
                });
            });

            // REJECT BUTTON
            $('.reject-btn').click(function () {
                const data = $(this).data('request');
                $('#rejectSummaryContent').html(`
                    <p><strong>Requester:</strong> ${data.requesterName || data.requesterEmployeeID}</p>
                    <p><strong>Subject:</strong> ${data.subject}</p>
                    <p><strong>Topic:</strong> ${data.subjectTopic}</p>
                    <p><strong>Date of Use:</strong> ${data.inclusiveDate}</p>
                    <p><strong>Time:</strong> ${data.inclusiveTime}</p>
                    <p>
                        <strong>Requested Materials:</strong><br>
                        ${data.materialsDetailed}
                    </p>
                `);
                $('#confirmReject').data('id', data.id);
                $('#rejectModal').modal('show');
            });

            $('#confirmReject').click(function () {
                const id = $(this).data('id');
                const feedback = $('#rejectionFeedback').val().trim();
                if (feedback === '') {
                    showToast('Please provide feedback.', 'warning');
                    return;
                }

                $.post('ajax/ajax_admin_action.php', { action: 'reject', id: id, feedback: feedback }, function (response) {
                    showToast(response, response.toLowerCase().includes('rejected') ? 'success' : 'error');
                    location.reload();
                });
            });

            // SUMMARY MODAL
            // Set max date for endDate input to today
            const today = new Date().toISOString().split('T')[0];
            $('#endDate').attr('max', today);

            $('#scanBarcodeBtn').click(function () {
                // Clear previous summary content when modal is opened
                $('#summaryContent').html('');
                $('#startDate').val('');
                $('#endDate').val('');
                $('#summaryModal').modal('show');
            });

            // Quick select buttons for date range
            $('.date-range-btn').click(function() {
                const range = $(this).data('range');
                const endDate = new Date();
                let startDate = new Date();

                switch(range) {
                    case 'week':
                        startDate.setDate(endDate.getDate() - 7);
                        break;
                    case 'month':
                        startDate.setMonth(endDate.getMonth() - 1);
                        break;
                    case '3-months':
                        startDate.setMonth(endDate.getMonth() - 3);
                        break;
                    case 'year':
                        startDate.setFullYear(endDate.getFullYear() - 1);
                        break;
                }

                // Helper to format date as YYYY-MM-DD
                const formatDate = (date) => {
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    return `${year}-${month}-${day}`;
                }

                const formattedStartDate = formatDate(startDate);
                const formattedEndDate = formatDate(endDate);

                $('#startDate').val(formattedStartDate);
                $('#endDate').val(formattedEndDate);
            });

            $('#generateSummary').click(function () {
                const startDate = $('#startDate').val();
                const endDate = $('#endDate').val();
                const classification = $('#summaryClassification').val() || [];

                // Basic validation
                if (!startDate || !endDate) {
                    showToast('Please select both start and end dates.', 'warning');
                    return;
                }

                if (new Date(startDate) > new Date(endDate)) {
                    showToast('Start date cannot be after the end date.', 'warning');
                    return;
                }

                const $btn = $(this);
                const originalText = $btn.html();

                // Show a loading state and disable the button
                $('#summaryContent').html('<p class="text-center mt-3">Generating summary, please wait...</p>');
                $btn.prop('disabled', true).html('<i class="bi bi-arrow-repeat spin"></i> Generating...');

                $.post('ajax/ajax_admin_action.php', {
                    action: 'generate_summary',
                    startDate: startDate,
                    endDate: endDate,
                    classification: classification
                }, function (response) {
                    $btn.prop('disabled', false).html(originalText); // Re-enable button
                    // jQuery automatically parses the JSON response, so we can use it directly.
                    // The 'response' variable is already an object.
                    if (response && response.success) {
                        let content = '';
                        let hasItems = false;
                        
                        // Iterate over the classifications returned from the server
                        for (const classification in response.items) {
                            if (response.items.hasOwnProperty(classification) && response.items[classification].length > 0) {
                                hasItems = true;
                                content += `<h4>${classification}</h4>`;
                                content += '<table class="table table-bordered table-striped mt-2 mb-4"><thead><tr><th>Item</th><th>Description</th><th>Quantity Used</th><th>Requestor</th><th>Date of Use</th><th style="width: 100px; text-align: center;">Action</th></tr></thead><tbody>';
                                response.items[classification].forEach(i => {
                                    const description = i.description || 'N/A';
                                    const unit = i.unit || ''; // Fallback for items with no unit
                                    content += `<tr><td>${i.item}</td><td>${description}</td><td>${i.quantity} ${unit}</td><td>${i.requestor}</td><td>${i.date} (${i.time})</td><td style="text-align: center;"><a href="supervisor_approve.php?id=${i.formID}" target="_blank" class="btn btn-liquid" style="padding: 2px 8px; font-size: 11px; margin: 0; display: inline-block;">View Details</a></td></tr>`;
                                });
                                content += '</tbody></table>';
                            }
                        }

                        if (hasItems) {
                            const printButtonHtml = '<div class="text-right mb-3"><button type="button" class="btn-liquid-info" id="printSummary">Print Report</button></div>';
                            $('#summaryContent').html(printButtonHtml + content);
                        } else {
                            $('#summaryContent').html('<p class="text-center mt-3">No items were used in the selected date range.</p>');
                        }
                    } else {
                        // Handle cases where success is false or the response is malformed
                        $('#summaryContent').html(`<p class="text-danger mt-3">Error: ${response.message || 'Could not generate summary.'}</p>`);
                    }
                }).fail(function() {
                    $btn.prop('disabled', false).html(originalText);
                    $('#summaryContent').html('<p class="text-danger mt-3">Error: Could not connect to the server.</p>');
                });
            });

            // Initialize Classification Multiselect dropdown
            $('#summaryClassification').multiselect({
                includeSelectAllOption: true,
                nonSelectedText: 'Select Classification',
                selectAllText: 'All Classifications',
                allSelectedText: 'All Classifications'
            });
            $('#summaryClassification').multiselect('selectAll', false);
            $('#summaryClassification').multiselect('updateButtonText');

            // Use event delegation for the dynamically added print button
            $('#summaryModal').on('click', '#printSummary', function() {
                const startDate = $('#startDate').val();
                const endDate = $('#endDate').val();
                const classification = ($('#summaryClassification').val() || []).join(',');
                const summaryContent = $('#summaryContent').html();

                // Check if dates are selected and a summary has been generated
                if (!startDate || !endDate) {
                    showToast('Please select a date range before printing.', 'warning');
                    return;
                }
                if (!summaryContent || !summaryContent.includes('<table')) {
                    showToast('Please generate a summary before printing.', 'warning');
                    return;
                }

                const url = `helperFiles/generate_summary_pdf.php?startDate=${encodeURIComponent(startDate)}&endDate=${encodeURIComponent(endDate)}&classification=${encodeURIComponent(classification)}`;
                window.open(url, '_blank');
            });
        });

        // ===== ADDED: Timeframe toggle =====
        $('#timeframeToggle').on('change', function () {

            if ($(this).is(':checked')) {
                $('#filterMode').val('timeframe');
            } else {
                $('#filterMode').val('manual');
            }

            $('#filterModeForm').submit();
        });
        // ===== END ADDED =====
    </script>
</html>
