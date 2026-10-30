<?php

    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    include('../scilab/helperFiles/db_connection.php');
    include('helperFiles/session_handler.php');
    require_once __DIR__ . '/helperFiles/input.php';

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

    $statusFilter = input_enum('status', array_merge(RAL_REQUEST_STATUSES, ['Cancelled']), 'Pending', $_GET);
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

    $fromDate = input_date('fromDate', $_GET);
    $toDate = input_date('toDate', $_GET);


    // ===== Build date filter (bound parameters) =====
    [$dateFilter, $dateTypes, $dateParams] = ral_request_date_filter($filterMode, $fromDate, $toDate, $days);
    // ===== END ADDED =====

    $sql = "
        SELECT *
        FROM scilab_form_requests
        WHERE statusScilabPersonnel = ?
        $dateFilter
        ORDER BY dateRequested DESC
    ";
    $result = ral_query($conn, $sql, 's' . $dateTypes, array_merge([$statusFilter], $dateParams));

    $requests = [];
    $formIDs = [];

    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
        $formIDs[] = $row['id'];
    }

    $counts = [
        'Pending' => 0,
        'Approved' => 0,
        'Rejected' => 0,
        'Cancelled' => 0
    ];

    $countQuery = ral_query($conn, "
        SELECT statusScilabPersonnel AS status, COUNT(*) AS total
        FROM scilab_form_requests
        WHERE 1 = 1
        $dateFilter
        GROUP BY statusScilabPersonnel
    ", $dateTypes, $dateParams);

    while ($row = $countQuery->fetch_assoc()) {
        $status = ucfirst(strtolower($row['status']));
        if (isset($counts[$status])) {
            $counts[$status] = $row['total'];
        }
    }

    $materials = [];
    $materialsDetailed = [];
    $releaseInfo = [];   // formID => latest release (who received the materials), for Approved rows

    if ($formIDs) {
        $ids = implode(',', array_map('intval', $formIDs));

        $relQ = $conn->query("
            SELECT r.formID, r.releasedAt, r.releasedBy, r.receiverType, r.receiverID, r.receiverName
            FROM scilab_material_releases r
            WHERE r.formID IN ($ids)
            ORDER BY r.id ASC
        ");
        while ($relQ && ($rel = $relQ->fetch_assoc())) {
            $releaseInfo[$rel['formID']] = $rel;   // later releases overwrite earlier ones
        }

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
        <?php $pageAssets = ['datatables', 'multiselect', 'barcode']; ?>
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
            table.dataTable { width: 100% !important; min-width: 1250px; table-layout: auto !important; white-space: nowrap; }
            .dataTables_scrollBody { -webkit-overflow-scrolling: touch; }
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
            /* Details column: only on tablet/phone widths, where DataTables collapses the other columns */
            .details-col { display: none; text-align: center; }
            @media (max-width: 430px) {
                .details-col { display: table-cell; }
                .details-col .view-details-btn { padding: 6px 14px; white-space: nowrap; }
            }

            /* Details modal: actions pinned to the bottom */
            #detailsModal .custom-modal { display: flex; flex-direction: column; }
            #detailsModal .custom-modal-body { overflow-y: auto; flex: 1 1 auto; }
            #detailsModal .custom-modal-footer { position: sticky; bottom: 0; background: var(--card-bg); flex: 0 0 auto; }
            #detailsModal .custom-modal-footer > a,
            #detailsModal .custom-modal-footer > button { text-align: center; margin: 0; }
            @media (max-width: 430px) {
                #detailsModal { padding: 10px; }
                #detailsModal .custom-modal-body { padding: 16px; }
                #detailsModal .detail-grid { grid-template-columns: 1fr; gap: 12px; }
                #detailsModal .custom-modal-footer { padding: 12px 16px; gap: 8px; }
                #detailsModal .custom-modal-footer > a,
                #detailsModal .custom-modal-footer > button { flex: 1 1 calc(50% - 8px); }
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

            /* Extend Force Approve input fields to fill their outlined containers */
            #approveModal .detail-value .liquid-input {
                width: 100%;
                box-sizing: border-box;
                border: none;
                outline: none;
                background: transparent;
            }

            /* Remarks textarea */
            #approveModal .detail-value textarea.liquid-input {
                width: 100%;
                min-height: 90px;
                resize: vertical;
                box-sizing: border-box;
            }

            .required-asterisk {
                color: #DC2626;
                font-weight: 800;
                font-size: 16px;
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
                    <a href="?status=Cancelled&filterMode=<?= urlencode($filterMode) ?>&timeframe=<?= urlencode($timeFrame) ?>&fromDate=<?= urlencode($fromDate) ?>&toDate=<?= urlencode($toDate) ?>" class="btn-liquid-secondary <?= $statusFilter === 'Cancelled' ? 'active' : '' ?>">
                        <span class="badge badge-pill badge-secondary"><?= $counts['Cancelled'] ?></span> Cancelled
                    </a>
                </div>
                <br/>

                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="admin-approval-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th class="details-col">Details</th>
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
                                <?php if ($statusFilter === 'Approved'): ?><th>Materials Released To</th><?php endif; ?>
                                <?php if ($statusFilter === 'Rejected'): ?><th>Feedback</th><?php endif; ?>
                                <?php if ($statusFilter === 'Cancelled'): ?><th>Cancelled</th><?php endif; ?>
                                <?php if ($statusFilter !== 'Rejected' && $statusFilter !== 'Cancelled'): ?><th>Action</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php
                                    $i = 1;
                                    foreach ($requests as $row):
                                        $employeeID = $row['requesterEmployeeID'];
                                        $fullName = ral_display_name($conn, (string)$employeeID);
                                        $row['requesterName'] = $fullName;
                                        $formID = $row['id'];
                                        $materialText = isset($materials[$formID]) ? implode("", $materials[$formID]) : '—';
                                        $row['materialsDetailed'] = isset($materialsDetailed[$formID]) ? implode("", $materialsDetailed[$formID]) : '—';
                                        $teacherInCharge = !empty($row['teacherInCharge']) ? htmlspecialchars($row['teacherInCharge']) : '—';
                                        $flagged = (!empty($row['dateRequested']) && !empty($row['inclusiveDate']) && (strtotime($row['inclusiveDate']) - strtotime($row['dateRequested'])) < 3 * 86400);
                                        $daysBeforeUse = $flagged ? max(0, (int)floor((strtotime($row['inclusiveDate']) - strtotime($row['dateRequested'])) / 86400)) : 0;
                                        $row['flagged'] = $flagged;
                                        $row['daysBeforeUse'] = $daysBeforeUse;
                                        $isReleased = (($row['releaseStatus'] ?? 'none') === 'released');
                                        $row['releaseInfo'] = $releaseInfo[$formID] ?? null;
                                ?>
                                    <tr id="row-<?= $row['id'] ?>" class="<?= $flagged ? 'request-flagged-row' : '' ?>">
                                        <td><span style="display:none;"><?= $row['id'] ?></span><?= $i++ ?></td>
                                        <td class="details-col">
                                            <button type="button" class="btn-liquid view-details-btn" data-request='<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>'>View</button>
                                        </td>
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
                                        <td><?= $flagged ? '<span class="request-flag-badge" title="Requested less than 3 days before lab use">FLAGGED</span><div class="request-flag-sub">' . $daysBeforeUse . ' day(s) before use</div>' : '<span class="request-flag-none">—</span>' ?></td>

                                        <?php if ($statusFilter === 'Approved'): ?>
                                            <td><?= htmlspecialchars($row['feedback'] ?? '—') ?></td>
                                            <td>
                                                <?php if ($isReleased && !empty($row['releaseInfo'])): $ri = $row['releaseInfo']; ?>
                                                    <span class="release-badge">RELEASED</span>
                                                    <div class="release-sub"><?= htmlspecialchars($ri['receiverName']) ?></div>
                                                    <div class="release-sub"><?= htmlspecialchars(ucfirst($ri['receiverType'])) ?> ID: <?= htmlspecialchars($ri['receiverID']) ?></div>
                                                    <div class="release-sub"><?= htmlspecialchars(date('M j, Y g:i A', strtotime($ri['releasedAt']))) ?></div>
                                                <?php elseif ($isReleased): ?>
                                                    <span class="release-badge">RELEASED</span>
                                                <?php else: ?>
                                                    <span class="request-flag-none">Not yet released</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>

                                        <?php if ($statusFilter === 'Rejected'): ?>
                                            <td><?= htmlspecialchars($row['feedback'] ?? 'No reason provided') ?></td>
                                        <?php endif; ?>

                                        <?php if ($statusFilter === 'Cancelled'): ?>
                                            <td>
                                                <span class="cancelled-badge">CANCELLED</span>
                                                <?php if (!empty($row['cancelReason'])): ?><div class="release-sub">Reason: <?= htmlspecialchars($row['cancelReason']) ?></div><?php endif; ?>
                                                <div class="release-sub">By <?= htmlspecialchars($row['cancelledBy'] ?? '—') ?><?= !empty($row['cancelledAt']) ? ', ' . htmlspecialchars(date('M j, Y g:i A', strtotime($row['cancelledAt']))) : '' ?></div>
                                            </td>
                                        <?php endif; ?>

                                        <?php if ($statusFilter !== 'Rejected' && $statusFilter !== 'Cancelled'): ?>
                                            <td>
                                                <?php if ($statusFilter === 'Pending'): ?>
                                                    <a href="supervisor_approve.php?id=<?= $row['id'] ?>" class="btn-liquid" style="width: 90%; margin-bottom: 10px; display:block; text-align:center; padding-left:0; padding-right:0;">Review</a>
                                                    <button class="btn-liquid-success approve-btn" style="width: 90%; margin-bottom: 10px; padding-left:0; padding-right:0;" data-request='<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>'>Force Approve</button>
                                                    <button class="btn-liquid-danger reject-btn" style="width: 90%; padding-left:0; padding-right:0;" data-request='<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>'>Reject</button>
                                                    <button type="button" class="btn-liquid-secondary cancel-req-btn" style="width: 90%; margin-top: 10px; padding-left:0; padding-right:0;" data-request='<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>'>Cancel Request</button>
                                                <?php elseif ($statusFilter === 'Approved'): ?>
                                                    <a href="templates/print_template.php?id=<?= $row['id'] ?>" target="_blank" class="btn-liquid" style="width: 90%; margin-bottom: 10px; display:block; text-align:center; padding-left:0; padding-right:0;">
                                                        <i class="glyphicon glyphicon-print"></i> Print
                                                    </a>
                                                    <button class="btn-liquid edit-control-btn" style="width: 90%; padding-left:0; padding-right:0;" data-request='<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>'>
                                                        <i class="glyphicon glyphicon-edit"></i> Edit Control #'s
                                                    </button>
                                                    <button type="button" class="btn-liquid-success release-btn" style="width: 90%; margin-top: 10px; padding-left:0; padding-right:0;" data-request='<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>'>
                                                        <?= $isReleased ? 'View Release' : 'Release Materials' ?>
                                                    </button>
                                                    <?php if ($isReleased): ?>
                                                        <button type="button" class="btn-liquid-secondary" style="width: 90%; margin-top: 10px; padding-left:0; padding-right:0;" disabled title="Materials were already released, so this request can no longer be cancelled.">Cancel Request</button>
                                                    <?php else: ?>
                                                        <button type="button" class="btn-liquid-secondary cancel-req-btn" style="width: 90%; margin-top: 10px; padding-left:0; padding-right:0;" data-request='<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>'>Cancel Request</button>
                                                    <?php endif; ?>
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
            <!-- Request details (phone / tablet) -->
            <div class="custom-modal-overlay" id="detailsModal" role="dialog" aria-modal="true" aria-labelledby="detailsModalTitle">
                <div class="custom-modal">
                    <div class="custom-modal-header">
                        <div class="custom-modal-header-left">
                            <h2 id="detailsModalTitle">REQUEST DETAILS</h2>
                            <p>Request ID: <strong id="detailsRequestId">REQ-0000</strong></p>
                        </div>
                        <button type="button" class="custom-modal-close" id="detailsModalCloseTop" aria-label="Close modal">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                                stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                <line x1="18" y1="6" x2="6" y2="18" />
                                <line x1="6" y1="6" x2="18" y2="18" />
                            </svg>
                        </button>
                    </div>
                    <div class="custom-modal-body" id="detailsBody"></div>
                    <div class="custom-modal-footer" id="detailsFooter"></div>
                </div>
            </div>

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
                        <p class="text-muted" style="font-size: 12px; margin: -4px 0 8px;">Filled in automatically with the next number of each series. Edit a field to use a different number; later forms of that series are shifted to follow it.</p>

                        <div class="detail-grid">

                            <div class="detail-field">
                                <div class="detail-label">Equipment Form <span class="required-asterisk">*</span></div>
                                <div class="detail-value" style="padding: 0;">
                                    <input type="text"
                                        class="liquid-input control-input"
                                        name="control_equipment"
                                        id="control_equipment"
                                        placeholder="e.g. CIID-20-001">
                                </div>
                            </div>

                            <div class="detail-field">
                                <div class="detail-label">Reagent Form <span class="required-asterisk">*</span></div>
                                <div class="detail-value" style="padding: 0;">
                                    <input type="text"
                                        class="liquid-input control-input"
                                        name="control_reagent"
                                        id="control_reagent"
                                        placeholder="e.g. RG-001">
                                </div>
                            </div>

                            <div class="detail-field">
                                <div class="detail-label">Work Permit <span class="required-asterisk">*</span></div>
                                <div class="detail-value" style="padding: 0;">
                                    <input type="text"
                                        class="liquid-input control-input"
                                        name="control_permit"
                                        id="control_permit"
                                        placeholder="e.g. PT-001">
                                </div>
                            </div>

                            <div class="detail-field">
                                <div class="detail-label">Lab Reservation Form <span class="required-asterisk">*</span></div>
                                <div class="detail-value" style="padding: 0;">
                                    <input type="text"
                                        class="liquid-input control-input"
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

            <!-- ============================================================
                RELEASE MATERIALS / CANCEL REQUEST (agent C). The JS is the last script block at the end of this file.
            ============================================================ -->
            <style>
                .btn-liquid-secondary.active { background: #6c757d; color: #fff; }
                .btn-liquid-secondary .badge { margin-right: 5px; background: rgba(0,0,0,0.1); color: inherit; }
                .btn-liquid-secondary.active .badge { background: rgba(255,255,255,0.2); color: #fff; }
                .release-badge, .cancelled-badge { display: inline-block; padding: 3px 8px; border-radius: 4px; color: #fff; font-size: 11px; font-weight: 700; letter-spacing: 0.5px; white-space: nowrap; }
                .release-badge { background: #16A34A; }
                .cancelled-badge { background: #6c757d; }
                .release-sub { font-size: 11px; color: #666; margin-top: 2px; }

                #releaseModal .custom-modal { max-width: 900px; }
                .rel-table-wrap { overflow-x: auto; margin-bottom: 16px; }
                .rel-table { width: 100%; border-collapse: collapse; font-size: 13px; }
                .rel-table th { text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--muted); padding: 8px 10px; border-bottom: 1.5px solid var(--border); white-space: nowrap; }
                .rel-table td { padding: 8px 10px; border-bottom: 1px solid var(--border); vertical-align: middle; white-space: normal; max-width: none; }
                .rel-table input[type="number"] { width: 90px; padding: 6px 8px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; }
                .rel-table tr.rel-changed td { background: #FFF8E1; }
                .rel-table tr.rel-over td { background: #FEE2E2; }
                .rel-table tr.rel-added td { background: #EEF2FB; }
                .rel-muted { color: var(--muted); font-size: 12px; }
                .rel-sub { display: block; font-size: 11px; color: var(--text-secondary); }
                .rel-tag { display: inline-block; font-size: 10px; font-weight: 700; padding: 1px 6px; border-radius: 4px; background: var(--secondary); color: #fff; margin-left: 6px; vertical-align: middle; }
                .rel-add-wrap { position: relative; margin-bottom: 16px; }
                .rel-add-wrap input[type="text"], .rel-scan-input, #relRemarks, #cancelReason { width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1.5px solid var(--border); border-radius: var(--radius-input); font-size: 14px; background: var(--input-bg); }
                .rel-add-results { position: absolute; left: 0; right: 0; top: 100%; z-index: 20; background: #fff; border: 1px solid var(--border); border-radius: 10px; box-shadow: var(--shadow-card); max-height: 240px; overflow-y: auto; display: none; }
                .rel-add-results.open { display: block; }
                .rel-add-results button { display: block; width: 100%; text-align: left; border: 0; background: #fff; padding: 8px 12px; font-size: 13px; cursor: pointer; border-bottom: 1px solid var(--muted-light); }
                .rel-add-results button:hover:not([disabled]) { background: var(--secondary-light); }
                .rel-add-results button[disabled] { color: var(--muted); cursor: not-allowed; }
                .rel-scan-box { text-align: center; padding: 18px; border: 2px dashed var(--secondary); border-radius: 14px; background: var(--secondary-light); margin-bottom: 16px; }
                .rel-scan-box h3 { margin: 0 0 4px; font-size: 16px; color: var(--primary); }
                .rel-scan-row { display: flex; gap: 8px; margin-top: 12px; flex-wrap: wrap; }
                .rel-scan-row .rel-scan-input { flex: 1 1 240px; font-size: 16px; }
                #relCameraWrap { display: none; margin-top: 12px; }
                #relScanner { position: relative; width: 100%; max-width: 420px; height: 260px; margin: 0 auto; background: #000; border-radius: 10px; overflow: hidden; }
                #relScanner video, #relScanner canvas { position: absolute !important; top: 0 !important; left: 0 !important; width: 100% !important; height: 100% !important; object-fit: cover !important; }
                .rel-receiver { border: 1.5px solid var(--border); border-radius: 12px; padding: 14px 16px; margin-bottom: 16px; }
                .rel-receiver.ok { border-color: #16A34A; background: #F0FDF4; }
                .rel-receiver.bad { border-color: #DC2626; background: #FEF2F2; color: #991B1B; }
                .rel-receiver .rel-name { font-size: 18px; font-weight: 800; color: var(--primary); }
                .rel-note { font-size: 12px; margin-top: 6px; color: #92400E; }
                .rel-summary-line { font-size: 13px; color: var(--text-secondary); margin: -6px 0 12px; }
                @media (max-width: 600px) { #releaseModal .custom-modal-body { padding: 16px; } }
            </style>

            <div class="custom-modal-overlay" id="releaseModal" role="dialog" aria-modal="true" aria-labelledby="releaseModalTitle">
                <div class="custom-modal">
                    <div class="custom-modal-header">
                        <div class="custom-modal-header-left">
                            <h2 id="releaseModalTitle">RELEASE MATERIALS</h2>
                            <p>Request ID: <strong id="releaseRequestId">REQ-0000</strong></p>
                        </div>
                        <button type="button" class="custom-modal-close" id="releaseModalCloseTop" aria-label="Close modal">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                <line x1="18" y1="6" x2="6" y2="18" />
                                <line x1="6" y1="6" x2="18" y2="18" />
                            </svg>
                        </button>
                    </div>
                    <div class="custom-modal-body" id="releaseBody"></div>
                    <div class="custom-modal-footer" id="releaseFooter"></div>
                </div>
            </div>

            <div class="custom-modal-overlay" id="cancelRequestModal" role="dialog" aria-modal="true" aria-labelledby="cancelRequestTitle">
                <div class="custom-modal">
                    <div class="custom-modal-header">
                        <div class="custom-modal-header-left">
                            <h2 id="cancelRequestTitle">CANCEL REQUEST</h2>
                            <p>Request ID: <strong id="cancelRequestId">REQ-0000</strong></p>
                        </div>
                        <button type="button" class="custom-modal-close" id="cancelRequestCloseTop" aria-label="Close modal">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                <line x1="18" y1="6" x2="6" y2="18" />
                                <line x1="6" y1="6" x2="18" y2="18" />
                            </svg>
                        </button>
                    </div>
                    <div class="custom-modal-body">
                        <div id="cancelSummary"></div>
                        <div class="alert alert-warning" id="cancelNotice" style="border-radius:8px;"></div>
                        <label for="cancelReason" class="detail-label">Reason (optional)</label>
                        <textarea id="cancelReason" rows="3" maxlength="255" placeholder="Why is this request being cancelled?"></textarea>
                    </div>
                    <div class="custom-modal-footer">
                        <button type="button" class="btn-liquid-danger" id="confirmCancelRequest">Yes, Cancel Request</button>
                        <button type="button" class="btn-liquid-secondary" id="keepRequestBtn">Keep Request</button>
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
                // No column collapsing: every column stays visible and the table scrolls sideways
                responsive: false,
                scrollX: true,
                columnDefs: [
                    { targets: 'details-col', orderable: false }
                ],
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
            // REQUEST DETAILS (phone / tablet) with actions in the footer
            // ============================================================
            const REQUEST_STATUS = <?= json_encode($statusFilter) ?>;

            function escHtml(v) {
                return $('<div>').text(v == null ? '' : String(v)).html();
            }

            function detailField(label, value, full) {
                const text = (value === '' || value == null) ? '—' : escHtml(value);
                return '<div class="detail-field' + (full ? ' full-width' : '') + '">' +
                    '<div class="detail-label">' + label + '</div>' +
                    '<div class="detail-value">' + text + '</div></div>';
            }

            function closeDetails() {
                $('#detailsModal').removeClass('open');
                $('body').removeClass('modal-open');
            }

            $(document).on('click', '.view-details-btn', function () {
                const d = $(this).data('request');
                const ctrl = d.control_equipment || d.control_reagent || d.control_permit ||
                             d.control_reservation || d.controlNumber || '';

                $('#detailsRequestId').text('REQ-' + String(d.id).padStart(4, '0'));

                const html = '<p class="custom-modal-section-title">Request</p><div class="detail-grid">' +
                    detailField('Requester', d.requesterName || d.requesterEmployeeID) +
                    detailField('Laboratory', d.scilabName) +
                    detailField('Grade - Section/s', 'Grade ' + d.gradeLevel + ' - ' + d.sections) +
                    detailField('Subject', d.subject) +
                    detailField('Topic', d.subjectTopic) +
                    detailField('Date of Use', d.inclusiveDate) +
                    detailField('Time', d.inclusiveTime) +
                    detailField('Teacher-in-Charge', d.teacherInCharge) +
                    detailField('Flag', d.flagged ? 'Flagged: ' + d.daysBeforeUse + ' day(s) before use' : 'None') +
                    (REQUEST_STATUS === 'Approved' ? detailField('Control #', ctrl) : '') +
                    (REQUEST_STATUS === 'Approved' ? detailField('Remarks', d.feedback, true) : '') +
                    (REQUEST_STATUS === 'Rejected' ? detailField('Feedback', d.feedback || 'No reason provided', true) : '') +
                    '</div>' +
                    '<p class="custom-modal-section-title">Requested Materials</p>' +
                    '<div class="detail-grid"><div class="detail-field full-width"><div class="detail-value">' +
                    (d.materialsDetailed || '—') + '</div></div></div>';
                $('#detailsBody').html(html);

                const $footer = $('#detailsFooter').empty();
                if (REQUEST_STATUS === 'Pending') {
                    $footer.append($('<a class="btn-liquid">Review</a>').attr('href', 'supervisor_approve.php?id=' + d.id));
                    $footer.append($('<button type="button" class="btn-liquid-success approve-btn">Force Approve</button>').data('request', d));
                    $footer.append($('<button type="button" class="btn-liquid-danger reject-btn">Reject</button>').data('request', d));
                    $footer.append($('<button type="button" class="btn-liquid-secondary cancel-req-btn">Cancel Request</button>').data('request', d));
                } else if (REQUEST_STATUS === 'Approved') {
                    $footer.append($('<a class="btn-liquid" target="_blank">Print</a>').attr('href', 'templates/print_template.php?id=' + d.id));
                    $footer.append($('<button type="button" class="btn-liquid edit-control-btn">Edit Control #s</button>').data('request', d));
                    $footer.append($('<button type="button" class="btn-liquid-success release-btn"></button>').text(d.releaseStatus === 'released' ? 'View Release' : 'Release Materials').data('request', d));
                    if (d.releaseStatus !== 'released') {
                        $footer.append($('<button type="button" class="btn-liquid-secondary cancel-req-btn">Cancel Request</button>').data('request', d));
                    }
                } else if (REQUEST_STATUS === 'Cancelled') {
                    $footer.append($('<div class="release-sub" style="flex:1 1 100%;"></div>').text('Cancelled' + (d.cancelReason ? ': ' + d.cancelReason : '') + (d.cancelledBy ? ' (by ' + d.cancelledBy + ')' : '')));
                }
                $footer.append('<button type="button" class="btn-liquid-secondary details-close-btn">Close</button>');

                $('#detailsModal').addClass('open');
                $('body').addClass('modal-open');
            });

            // Hand off to the existing action modals
            $(document).on('click', '#detailsFooter .approve-btn, #detailsFooter .reject-btn, #detailsFooter .edit-control-btn', closeDetails);
            $(document).on('click', '#detailsModalCloseTop, .details-close-btn', closeDetails);
            $('#detailsModal').on('click', function (e) { if (e.target === this) closeDetails(); });

            // ============================================================
            // FORCE APPROVE BUTTON
            // ============================================================
            $(document).on('click', '.approve-btn', function () {
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

                // Prefill the next automatic control numbers (still editable)
                $('#approveForm .control-input').removeData('preview');
                $.post('ajax/ajax_admin_action.php', { action: 'next_control_numbers' }, function (res) {
                    if (res && res.success && res.numbers) {
                        const map = { equipment: '#control_equipment', reagent: '#control_reagent', permit: '#control_permit', reservation: '#control_reservation' };
                        Object.keys(map).forEach(k => {
                            $(map[k]).val(res.numbers[k] || '').data('preview', res.numbers[k] || '');
                        });
                    }
                }, 'json');

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
                        csrf_token: $('meta[name="csrf-token"]').attr('content'),
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
                                        <strong><i class="glyphicon glyphicon-ban-circle"></i> Severe Conflict:</strong> An <b>approved</b> request already exists for this timeframe (${ralEsc(res.details.time)}). Force approving this will double-book the room.
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
                                        <strong><i class="glyphicon glyphicon-warning-sign"></i> Pending Conflict:</strong> There is another pending request for this timeframe (${ralEsc(res.details.time)} - ${ralEsc(res.details.subject)}).
                                    </div>
                                `);
                            }
                        }

                    }, 'json');
                }

                // ========================================================
                // SHOW CUSTOM MODAL
                // ========================================================
                $('#approveModal').addClass('open');
                $('body').addClass('modal-open');
            });


            // ============================================================
            // FORCE APPROVE MODAL - CLOSE BUTTONS
            // ============================================================
            $('#approveModalCloseTop, #approveModalCancelBtn').click(function () {
                $('#approveModal').removeClass('open');
                $('body').removeClass('modal-open');
            });


            // ============================================================
            // FORCE APPROVE MODAL - CLICK OUTSIDE
            // ============================================================
            $('#approveModal').click(function (e) {
                if (e.target === this) {
                    $('#approveModal').removeClass('open');
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
                    // 0 = left as prefilled (server keeps its own automatic number), 1 = admin typed a value
                    control_equipment_edited: controlEquipment === ($('#control_equipment').data('preview') || '') ? 0 : 1,
                    control_reagent_edited: controlReagent === ($('#control_reagent').data('preview') || '') ? 0 : 1,
                    control_permit_edited: controlPermit === ($('#control_permit').data('preview') || '') ? 0 : 1,
                    control_reservation_edited: controlReservation === ($('#control_reservation').data('preview') || '') ? 0 : 1,
                    remarks: remarks
                }, function (response) {
                    const isSuccess = response.toLowerCase().includes('approved');
                    showToast(response, isSuccess ? 'success' : 'error');
                    if (isSuccess) {
                        setTimeout(() => location.reload(), 2200);
                    }
                });
            });

            // EDIT CONTROL NUMBERS (approved requests)
            $(document).on('click', '.edit-control-btn', function () {
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
                    const noChange = response.toLowerCase().startsWith('no changes');
                    showToast(response, isSuccess ? 'success' : (noChange ? 'warning' : 'error'));
                    if (isSuccess) {
                        // the toast reports how many later forms were shifted; give it time to be read
                        setTimeout(() => location.reload(), 2200);
                    }
                });
            });

            // REJECT BUTTON
            $(document).on('click', '.reject-btn', function () {
                const data = $(this).data('request');
                $('#rejectSummaryContent').html(`
                    <p><strong>Requester:</strong> ${ralEsc(data.requesterName || data.requesterEmployeeID)}</p>
                    <p><strong>Subject:</strong> ${ralEsc(data.subject)}</p>
                    <p><strong>Topic:</strong> ${ralEsc(data.subjectTopic)}</p>
                    <p><strong>Date of Use:</strong> ${ralEsc(data.inclusiveDate)}</p>
                    <p><strong>Time:</strong> ${ralEsc(data.inclusiveTime)}</p>
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
                                content += `<h4>${ralEsc(classification)}</h4>`;
                                content += '<table class="table table-bordered table-striped mt-2 mb-4"><thead><tr><th style="white-space: nowrap;">Control #</th><th>Item</th><th>Description</th><th>Quantity Used</th><th>Requestor</th><th>Date of Use</th><th style="width: 100px; text-align: center;">Action</th></tr></thead><tbody>';
                                response.items[classification].forEach(i => {
                                    const description = ralEsc(i.description || 'N/A');
                                    const unit = ralEsc(i.unit || ''); // Fallback for items with no unit
                                    content += `<tr><td style="white-space: nowrap;">${ralEsc(i.controlNumber || '')}</td><td>${ralEsc(i.item)}</td><td>${description}</td><td>${ralEsc(i.quantity)} ${unit}</td><td>${ralEsc(i.requestor)}</td><td>${ralEsc(i.date)} (${ralEsc(i.time)})</td><td style="text-align: center;"><a href="supervisor_approve.php?id=${encodeURIComponent(i.formID)}" target="_blank" class="btn btn-liquid" style="padding: 2px 8px; font-size: 11px; margin: 0; display: inline-block;">View Details</a></td></tr>`;
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
                        $('#summaryContent').html(`<p class="text-danger mt-3">Error: ${ralEsc(response.message || 'Could not generate summary.')}</p>`);
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

    <script>
    // ============================================================
    // RELEASE MATERIALS + CANCEL REQUEST
    // Endpoint: ajax/ajax_release.php. Step 1 edit the items, step 2 scan the receiver's ID.
    // ============================================================
    (function () {
        'use strict';

        const RELEASE_URL = 'ajax/ajax_release.php';
        const esc = function (v) { return window.ralEsc(v == null ? '' : v); };
        const pad = function (id) { return 'REQ-' + String(id).padStart(4, '0'); };
        const $modal = $('#releaseModal');
        const $body = $('#releaseBody');
        const $footer = $('#releaseFooter');

        let st = null;            // state of the open release modal
        let cameraOn = false;
        let camLast = { code: null, time: 0, hits: 0 };

        function field(label, value) {
            return '<div class="detail-field"><div class="detail-label">' + esc(label) + '</div>' +
                '<div class="detail-value">' + esc(value === '' || value == null ? '—' : value) + '</div></div>';
        }

        function requestHeader(r) {
            return '<p class="custom-modal-section-title">Request</p><div class="detail-grid">' +
                field('Requester', r.requesterName + ' (' + r.requesterID + ')') +
                field('Laboratory', r.lab) +
                field('Date of Use', r.date) +
                field('Time', r.time) +
                '</div>';
        }

        function openOverlay($el) { $el.addClass('open'); $('body').addClass('modal-open'); }
        function closeOverlay($el) { $el.removeClass('open'); $('body').removeClass('modal-open'); }

        // ------------------------------------------------------------
        // Camera (Quagga, as in inventory.php)
        // ------------------------------------------------------------
        function onCameraDetected(data) {
            const code = data && data.codeResult && data.codeResult.code ? String(data.codeResult.code).trim() : '';
            if (!code) return;
            const now = Date.now();
            // Same code twice within 2.5 s, to ignore a single misread
            if (camLast.code === code && now - camLast.time < 2500) { camLast.hits++; } else { camLast = { code: code, time: now, hits: 1 }; }
            camLast.time = now;
            if (camLast.hits < 2) return;
            stopCamera();
            lookupReceiver(code, 'camera');
        }

        function startCamera() {
            if (typeof Quagga === 'undefined') {
                showToast('The camera scanner is still loading. Try again in a moment.', 'warning');
                return;
            }
            if (cameraOn) return;
            cameraOn = true;
            camLast = { code: null, time: 0, hits: 0 };
            $('#relCameraWrap').show();
            $('#relCameraBtn').text('Stop Camera');
            Quagga.init({
                inputStream: { type: 'LiveStream', target: document.querySelector('#relScanner'), constraints: { facingMode: 'environment' } },
                decoder: { readers: ['code_128_reader', 'code_39_reader', 'ean_reader', 'ean_8_reader', 'upc_reader', 'codabar_reader', 'i2of5_reader'] },
                locate: true
            }, function (err) {
                if (err) {
                    cameraOn = false;
                    $('#relCameraWrap').hide();
                    $('#relCameraBtn').text('Use Camera');
                    showToast('Could not start the camera: ' + esc(err.message || err), 'error');
                    return;
                }
                if (!cameraOn) { try { Quagga.stop(); } catch (e) {} return; }
                Quagga.onDetected(onCameraDetected);
                Quagga.start();
            });
        }

        function stopCamera() {
            if (!cameraOn) return;
            cameraOn = false;
            try { if (typeof Quagga !== 'undefined') { Quagga.offDetected(onCameraDetected); Quagga.stop(); } } catch (e) { /* ignore */ }
            try {
                document.querySelectorAll('#relScanner video').forEach(function (v) {
                    const s = v.srcObject;
                    if (s && s.getTracks) s.getTracks().forEach(function (t) { try { t.stop(); } catch (e) {} });
                    try { v.srcObject = null; } catch (e) {}
                });
            } catch (e) { /* ignore */ }
            $('#relScanner').empty();
            $('#relCameraWrap').hide();
            $('#relCameraBtn').text('Use Camera');
        }

        function closeRelease() {
            stopCamera();
            closeOverlay($modal);
            st = null;
        }

        // ------------------------------------------------------------
        // Open: load the request, then show the editable list (or the read-only record)
        // ------------------------------------------------------------
        function openRelease(row) {
            st = { id: row.id, data: null, added: [], items: [], receiver: null, method: 'scanner', remarks: '', busy: false };
            $('#releaseRequestId').text(pad(row.id));
            $body.html('<p class="text-center" style="padding:30px 0;">Loading request...</p>');
            $footer.empty().append('<button type="button" class="btn-liquid-secondary rel-close">Close</button>');
            openOverlay($modal);

            $.post(RELEASE_URL, { action: 'get_release_data', id: row.id }, null, 'json').done(function (res) {
                if (!st || st.id !== row.id) return;
                if (!res || !res.success) {
                    $body.html('<p class="text-danger">' + esc(res && res.message ? res.message : 'Could not load the request.') + '</p>');
                    return;
                }
                st.data = res;
                if (res.canRelease) renderItems(); else renderRecord();
            }).fail(function (xhr) {
                const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Could not reach the server.';
                $body.html('<p class="text-danger">' + esc(msg) + '</p>');
            });
        }

        // ------------------------------------------------------------
        // Read-only view: released (old + new values + receiver) or not releasable
        // ------------------------------------------------------------
        function renderRecord() {
            const d = st.data;
            let html = requestHeader(d.request);
            if (d.release) {
                const r = d.release;
                html += '<p class="custom-modal-section-title">Received By</p><div class="rel-receiver ok">' +
                    '<div class="rel-name">' + esc(r.receiverName) + '</div>' +
                    '<div>' + esc(r.receiverType === 'student' ? 'Student' : 'Employee') + ' ID: <strong>' + esc(r.receiverID) + '</strong></div>' +
                    '<div class="rel-muted">Released ' + esc(r.releasedAt) + ' by ' + esc(r.releasedBy) + ' (' + esc(r.scanMethod) + ')' +
                    (r.remarks ? ' - ' + esc(r.remarks) : '') + '</div></div>';
                html += '<p class="custom-modal-section-title">Materials Released</p><div class="rel-table-wrap"><table class="rel-table"><thead><tr><th>Item</th><th>Requested</th><th>Released</th></tr></thead><tbody>';
                (r.items || []).forEach(function (i) {
                    const added = String(i.isAdded) === '1';
                    const changed = !added && Number(i.originalQuantity) !== Number(i.releasedQuantity);
                    html += '<tr class="' + (added ? 'rel-added' : (changed ? 'rel-changed' : '')) + '"><td>' + esc(i.item) +
                        (i.description && i.description !== 'N/A' ? '<span class="rel-sub">' + esc(i.description) + '</span>' : '') +
                        (added ? '<span class="rel-tag">Added</span>' : '') + '</td><td>' +
                        (added ? '—' : esc(i.originalQuantity) + ' ' + esc(i.unit || '')) + '</td><td><strong>' + esc(i.releasedQuantity) + '</strong> ' + esc(i.unit || '') + '</td></tr>';
                });
                html += '</tbody></table></div>';
            } else {
                html += '<div class="alert alert-info" style="border-radius:8px;">Materials can only be released for an approved request that has not been cancelled. This request is <strong>' + esc(d.request.status) + '</strong>.</div>';
            }
            $body.html(html);
            $footer.empty().append('<button type="button" class="btn-liquid-secondary rel-close">Close</button>');
        }

        // ------------------------------------------------------------
        // Step 1: requested materials autofilled, editable, plus extra items
        // ------------------------------------------------------------
        function renderItems() {
            const d = st.data;
            let rows = '';
            d.materials.forEach(function (m) {
                const max = m.maxQuantity;
                rows += '<tr data-mid="' + esc(m.materialRequestID) + '" data-orig="' + esc(m.quantity) + '" data-max="' + (max == null ? '' : esc(max)) + '" data-name="' + esc(m.item) + '">' +
                    '<td>' + esc(m.item) + (m.description && m.description !== 'N/A' ? '<span class="rel-sub">' + esc(m.description) + '</span>' : '') + '</td>' +
                    '<td>' + esc(m.quantity) + ' ' + esc(m.unit || '') + '</td>' +
                    '<td><input type="number" class="rel-qty" min="0" step="1" value="' + esc(m.quantity) + '" aria-label="Quantity to release for ' + esc(m.item) + '"></td>' +
                    '<td>' + (max == null ? '<span class="rel-muted">not tracked in inventory</span>' : '<span class="rel-muted">up to ' + esc(max) + ' ' + esc(m.unit || '') + '</span>') + '</td></tr>';
            });
            if (!rows) rows = '<tr><td colspan="4" class="rel-muted">This request has no requested materials. Add items below.</td></tr>';

            const html = requestHeader(d.request) +
                '<p class="custom-modal-section-title">Requested Materials (change the quantity to release)</p>' +
                '<div class="rel-table-wrap"><table class="rel-table"><thead><tr><th>Item</th><th>Requested</th><th>Release</th><th>Stock</th></tr></thead>' +
                '<tbody id="relOrigBody">' + rows + '</tbody><tbody id="relAddedBody"></tbody></table></div>' +
                '<div class="rel-summary-line" id="relChangeSummary"></div>' +
                '<p class="custom-modal-section-title">Add Additional Item</p>' +
                '<div class="rel-add-wrap"><input type="text" id="relAddSearch" placeholder="Search the inventory by item, description or classification..." autocomplete="off">' +
                '<div class="rel-add-results" id="relAddResults"></div></div>' +
                '<label for="relRemarks" class="detail-label">Remarks (optional)</label>' +
                '<textarea id="relRemarks" rows="2" maxlength="500" placeholder="e.g. items handed over at the lab"></textarea>';
            $body.html(html);
            $('#relRemarks').val(st.remarks);
            renderAdded();
            updateChangeState();

            $footer.empty()
                .append('<button type="button" class="btn-liquid-success" id="relConfirmItems">Confirm</button>')
                .append('<button type="button" class="btn-liquid-secondary rel-close">Close</button>');
        }

        function renderAdded() {
            let html = '';
            st.added.forEach(function (a, idx) {
                html += '<tr class="rel-added" data-idx="' + idx + '"><td>' + esc(a.item) + '<span class="rel-tag">Added</span>' +
                    (a.description ? '<span class="rel-sub">' + esc(a.description) + '</span>' : '') + '</td>' +
                    '<td>—</td>' +
                    '<td><input type="number" class="rel-qty-added" min="1" step="1" value="' + esc(a.qty) + '" aria-label="Quantity to release for ' + esc(a.item) + '"></td>' +
                    '<td><span class="rel-muted">up to ' + esc(a.stock) + ' ' + esc(a.unit || '') + '</span> ' +
                    '<button type="button" class="btn-liquid-danger rel-remove-added" style="padding:2px 10px; margin-left:6px;" aria-label="Remove ' + esc(a.item) + '">Remove</button></td></tr>';
            });
            $('#relAddedBody').html(html);
        }

        function updateChangeState() {
            let changed = 0;
            $('#relOrigBody tr[data-mid]').each(function () {
                const $r = $(this);
                const raw = String($r.find('.rel-qty').val()).trim();
                const q = /^\d+$/.test(raw) ? parseInt(raw, 10) : NaN;
                const orig = Number($r.attr('data-orig'));
                const maxAttr = $r.attr('data-max');
                $r.toggleClass('rel-changed', q !== orig && !isNaN(q));
                $r.toggleClass('rel-over', !isNaN(q) && maxAttr !== '' && q > Number(maxAttr));
                if (q !== orig) changed++;
            });
            const parts = [];
            if (changed) parts.push(changed + ' quantity change' + (changed > 1 ? 's' : ''));
            if (st.added.length) parts.push(st.added.length + ' item' + (st.added.length > 1 ? 's' : '') + ' added');
            $('#relChangeSummary').text(parts.length ? 'Changes from the original request: ' + parts.join(', ') + '. The original and the released quantities are both saved.' : 'No changes from the original request.');
        }

        function showAddResults(term) {
            const t = String(term || '').toLowerCase().trim();
            const $box = $('#relAddResults');
            if (t.length < 2) { $box.removeClass('open').empty(); return; }
            const hits = st.data.catalog.filter(function (c) {
                return (c.item + ' ' + c.description + ' ' + c.classification).toLowerCase().indexOf(t) !== -1;
            }).slice(0, 12);
            if (!hits.length) { $box.html('<button type="button" disabled>No matching inventory item</button>').addClass('open'); return; }
            $box.html(hits.map(function (c) {
                return '<button type="button" data-id="' + esc(c.id) + '"' + (c.quantity <= 0 ? ' disabled' : '') + '>' + esc(c.item) +
                    (c.description ? ' <span class="rel-muted">(' + esc(c.description) + ')</span>' : '') +
                    ' <span class="rel-muted">- ' + esc(c.classification) + ', ' + (c.quantity <= 0 ? 'out of stock' : esc(c.quantity) + ' ' + esc(c.unit || '') + ' in stock') + '</span></button>';
            }).join('')).addClass('open');
        }

        function addCatalogItem(id) {
            const c = st.data.catalog.find(function (x) { return Number(x.id) === Number(id); });
            if (!c) return;
            const existing = st.added.find(function (a) { return a.inventoryID === c.id; });
            if (existing) {
                existing.qty = Math.min(existing.qty + 1, c.quantity);
            } else {
                st.added.push({ inventoryID: c.id, item: c.item, description: c.description, unit: c.unit, stock: c.quantity, qty: 1 });
            }
            renderAdded();
            updateChangeState();
            $('#relAddSearch').val('');
            $('#relAddResults').removeClass('open').empty();
        }

        // Read and validate the table. Returns {items, total} or null (after a toast).
        function collectItems() {
            const items = [];
            let total = 0;
            let ok = true;
            $('#relOrigBody tr[data-mid]').each(function () {
                const $r = $(this);
                const raw = String($r.find('.rel-qty').val()).trim();
                if (!/^\d{1,6}$/.test(raw)) { showToast('Quantities must be whole numbers of 0 or more.', 'warning'); ok = false; return false; }
                const q = parseInt(raw, 10);
                const maxAttr = $r.attr('data-max');
                if (maxAttr !== '' && q > Number(maxAttr)) {
                    showToast('Not enough stock for ' + esc($r.attr('data-name')) + ' (at most ' + esc(maxAttr) + ').', 'warning');
                    ok = false; return false;
                }
                total += q;
                items.push({ materialRequestID: Number($r.attr('data-mid')), quantity: q, item: $r.attr('data-name'), orig: Number($r.attr('data-orig')) });
            });
            if (!ok) return null;
            st.added.forEach(function (a) {
                if (!(a.qty >= 1) || a.qty > a.stock) { showToast('Check the quantity of ' + esc(a.item) + ' (1 to ' + esc(a.stock) + ').', 'warning'); ok = false; return; }
                total += a.qty;
                items.push({ inventoryID: a.inventoryID, quantity: a.qty, item: a.item, added: true });
            });
            if (!ok) return null;
            if (total <= 0) { showToast('Every quantity is 0. Use Cancel Request instead.', 'warning'); return null; }
            return { items: items, total: total };
        }

        // ------------------------------------------------------------
        // Step 2: scan the receiver's ID
        // ------------------------------------------------------------
        let keyTimes = [];

        function renderScan() {
            const d = st.data;
            let list = '<div class="rel-table-wrap"><table class="rel-table"><thead><tr><th>Item</th><th>Requested</th><th>Releasing</th></tr></thead><tbody>';
            st.items.forEach(function (i) {
                const changed = !i.added && i.quantity !== i.orig;
                list += '<tr class="' + (i.added ? 'rel-added' : (changed ? 'rel-changed' : '')) + '"><td>' + esc(i.item) + (i.added ? '<span class="rel-tag">Added</span>' : '') + '</td>' +
                    '<td>' + (i.added ? '—' : esc(i.orig)) + '</td><td><strong>' + esc(i.quantity) + '</strong></td></tr>';
            });
            list += '</tbody></table></div>';

            $body.html(requestHeader(d.request) +
                '<p class="custom-modal-section-title">Materials to Release</p>' + list +
                '<p class="custom-modal-section-title">Receiver</p>' +
                '<div class="rel-scan-box"><h3>Scan the receiver\'s ID</h3>' +
                '<div class="rel-muted">Employee ID or student LRN. Use the barcode scanner, the camera, or type the ID and press Enter.</div>' +
                '<div class="rel-scan-row"><input type="text" class="rel-scan-input" id="relScanInput" placeholder="Scan or type ID, then press Enter" autocomplete="off" autocapitalize="off" spellcheck="false">' +
                '<button type="button" class="btn-liquid" id="relLookupBtn">Look Up</button>' +
                '<button type="button" class="btn-liquid-secondary" id="relCameraBtn">Use Camera</button></div>' +
                '<div id="relCameraWrap"><div id="relScanner"></div></div></div>' +
                '<div id="relReceiver"></div>');

            $footer.empty()
                .append('<button type="button" class="btn-liquid-success" id="relConfirmRelease" disabled>Confirm Release</button>')
                .append('<button type="button" class="btn-liquid-secondary" id="relBackBtn">Back</button>')
                .append('<button type="button" class="btn-liquid-secondary rel-close">Close</button>');
            st.receiver = null;
            keyTimes = [];
            setTimeout(function () { $('#relScanInput').trigger('focus'); }, 80);
        }

        function receiverCard(res) {
            if (!res.success) {
                return '<div class="rel-receiver bad"><strong>No match.</strong> ' + esc(res.message || 'ID not found.') + ' Scan again.</div>';
            }
            return '<div class="rel-receiver ok"><div class="detail-label">Matched ' + (res.type === 'student' ? 'student' : 'employee') + '</div>' +
                '<div class="rel-name">' + esc(res.name) + '</div>' +
                '<div>' + (res.type === 'student' ? 'Student LRN' : 'Employee ID') + ': <strong>' + esc(res.id) + '</strong></div>' +
                (res.isRequester ? '<div class="rel-muted">This is the person who made the request.</div>'
                    : '<div class="rel-note">Not the requester (' + esc(st.data.request.requesterName) + '). Confirm that this person is receiving the materials.</div>') +
                '</div>';
        }

        function lookupReceiver(code, method) {
            code = String(code || '').trim();
            if (!st || !code) return;
            $('#relReceiver').html('<p class="rel-muted">Looking up ' + esc(code) + '...</p>');
            $('#relConfirmRelease').prop('disabled', true);
            $.post(RELEASE_URL, { action: 'lookup_receiver', id: st.id, receiver_id: code }, null, 'json').done(function (res) {
                if (!st) return;
                if (res && res.success) {
                    st.receiver = { id: res.id, type: res.type, name: res.name };
                    st.method = method;
                    $('#relConfirmRelease').prop('disabled', false);
                    $('#relScanInput').val(res.id);
                } else {
                    st.receiver = null;
                    $('#relScanInput').val('').trigger('focus');
                }
                $('#relReceiver').html(receiverCard(res || { success: false }));
            }).fail(function (xhr) {
                if (!st) return;
                st.receiver = null;
                $('#relReceiver').html(receiverCard({ success: false, message: xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Could not reach the server.' }));
            });
        }

        function confirmRelease() {
            if (!st || !st.receiver || st.busy) return;
            st.busy = true;
            $('#relConfirmRelease').prop('disabled', true).text('Releasing...');
            const payload = st.items.map(function (i) {
                return i.added ? { inventoryID: i.inventoryID, quantity: i.quantity } : { materialRequestID: i.materialRequestID, quantity: i.quantity };
            });
            $.post(RELEASE_URL, {
                action: 'confirm_release',
                id: st.id,
                items: JSON.stringify(payload),
                receiver_id: st.receiver.id,      // the server looks the ID up again; the name is never sent
                scan_method: st.method,
                remarks: st.remarks
            }, null, 'json').done(function (res) {
                if (res && res.success) {
                    showToast(esc(res.message), 'success');
                    closeRelease();
                    setTimeout(function () { location.reload(); }, 1200);
                } else {
                    st.busy = false;
                    $('#relConfirmRelease').prop('disabled', false).text('Confirm Release');
                    showToast(esc(res && res.message ? res.message : 'Release failed.'), 'error');
                }
            }).fail(function (xhr) {
                st.busy = false;
                $('#relConfirmRelease').prop('disabled', false).text('Confirm Release');
                showToast(esc(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Could not reach the server.'), 'error');
            });
        }

        // ------------------------------------------------------------
        // Events
        // ------------------------------------------------------------
        $(document).on('click', '.release-btn', function () {
            const row = $(this).data('request');
            if (row) openRelease(row);
        });
        $(document).on('click', '#detailsFooter .release-btn, #detailsFooter .cancel-req-btn', function () {
            $('#detailsModal').removeClass('open');
            $('body').removeClass('modal-open');
        });

        $('#releaseModalCloseTop').on('click', closeRelease);
        $modal.on('click', function (e) { if (e.target === this && !(st && st.busy)) closeRelease(); });
        $modal.on('click', '.rel-close', closeRelease);

        $modal.on('input', '.rel-qty', updateChangeState);
        $modal.on('input', '.rel-qty-added', function () {
            const idx = Number($(this).closest('tr').attr('data-idx'));
            const v = parseInt($(this).val(), 10);
            if (st.added[idx]) st.added[idx].qty = isNaN(v) ? 0 : v;
        });
        $modal.on('click', '.rel-remove-added', function () {
            st.added.splice(Number($(this).closest('tr').attr('data-idx')), 1);
            renderAdded();
            updateChangeState();
        });
        $modal.on('input focusin', '#relAddSearch', function () { showAddResults($(this).val()); });
        $modal.on('click', '#relAddResults button[data-id]', function () { addCatalogItem($(this).attr('data-id')); });
        $modal.on('click', function (e) { if (!$(e.target).closest('.rel-add-wrap').length) $('#relAddResults').removeClass('open'); });

        $modal.on('click', '#relConfirmItems', function () {
            const got = collectItems();
            if (!got) return;
            st.remarks = $('#relRemarks').val().trim();
            st.items = got.items;
            renderScan();
        });
        $modal.on('click', '#relBackBtn', function () {
            stopCamera();
            const kept = st.items;
            renderItems();
            // keep what the admin typed
            kept.forEach(function (i) {
                if (i.added) return;
                $('#relOrigBody tr[data-mid="' + i.materialRequestID + '"] .rel-qty').val(i.quantity);
            });
            updateChangeState();
        });

        // Keyboard-wedge scanner: types the digits fast, then Enter. A person types slower, so the
        // timing tells 'scanner' from 'manual'.
        $modal.on('keydown', '#relScanInput', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const val = $(this).val();
                const n = keyTimes.length;
                const avg = n > 1 ? (keyTimes[n - 1] - keyTimes[0]) / (n - 1) : 999;
                keyTimes = [];
                lookupReceiver(val, avg < 60 ? 'scanner' : 'manual');
            } else if (e.key.length === 1) {
                keyTimes.push(Date.now());
            }
        });
        $modal.on('click', '#relLookupBtn', function () { lookupReceiver($('#relScanInput').val(), 'manual'); });
        $modal.on('click', '#relCameraBtn', function () { if (cameraOn) stopCamera(); else startCamera(); });
        $modal.on('click', '#relConfirmRelease', confirmRelease);
        // Keep the scan field focused so a keyboard-wedge scanner always has somewhere to type
        $modal.on('click', '.custom-modal-body', function (e) {
            if ($('#relScanInput').length && !$(e.target).closest('input, textarea, button, select, a').length) {
                $('#relScanInput').trigger('focus');
            }
        });

        // ------------------------------------------------------------
        // Cancel request
        // ------------------------------------------------------------
        const $cancel = $('#cancelRequestModal');
        let cancelRow = null;
        let cancelBusy = false;

        $(document).on('click', '.cancel-req-btn', function () {
            const row = $(this).data('request');
            if (!row) return;
            cancelRow = row;
            cancelBusy = false;
            const approved = String(row.statusScilabPersonnel || '').toLowerCase() === 'approved';
            $('#cancelRequestId').text(pad(row.id));
            $('#cancelSummary').html('<div class="detail-grid">' +
                field('Requester', row.requesterName || row.requesterEmployeeID) +
                field('Laboratory', row.scilabName) +
                field('Date of Use', row.inclusiveDate + ' (' + row.inclusiveTime + ')') +
                field('Status', row.statusScilabPersonnel) + '</div>' +
                '<p class="custom-modal-section-title">Requested Materials</p><div style="margin-bottom:16px;">' + (row.materialsDetailed || '—') + '</div>');
            $('#cancelNotice').html(approved
                ? '<strong>This cannot be undone.</strong> The materials deducted from the inventory for this request will be returned to stock, its control numbers are cleared and the lab time slot is freed.'
                : '<strong>This cannot be undone.</strong> The pending request will be cancelled. Nothing has been deducted from the inventory yet.');
            $('#cancelReason').val('');
            $('#confirmCancelRequest').prop('disabled', false).text('Yes, Cancel Request');
            openOverlay($cancel);
        });

        function closeCancel() { closeOverlay($cancel); cancelRow = null; }
        $('#cancelRequestCloseTop, #keepRequestBtn').on('click', closeCancel);
        $cancel.on('click', function (e) { if (e.target === this && !cancelBusy) closeCancel(); });

        $('#confirmCancelRequest').on('click', function () {
            if (!cancelRow || cancelBusy) return;
            cancelBusy = true;
            const $btn = $(this).prop('disabled', true).text('Cancelling...');
            $.post(RELEASE_URL, { action: 'cancel_request', id: cancelRow.id, reason: $('#cancelReason').val().trim() }, null, 'json').done(function (res) {
                if (res && res.success) {
                    showToast(esc(res.message), 'success', 4500);
                    closeCancel();
                    setTimeout(function () { location.reload(); }, 1800);
                } else {
                    cancelBusy = false;
                    $btn.prop('disabled', false).text('Yes, Cancel Request');
                    showToast(esc(res && res.message ? res.message : 'Could not cancel the request.'), 'error');
                }
            }).fail(function (xhr) {
                cancelBusy = false;
                $btn.prop('disabled', false).text('Yes, Cancel Request');
                showToast(esc(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Could not reach the server.'), 'error');
            });
        });
    })();
    </script>
</html>
