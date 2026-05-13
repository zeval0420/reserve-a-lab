<?php
/**
 * Reserve-a-Lab — Supervisor Approval Page
 * Frontend only. No backend logic or DB calls.
 */
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Supervisor Approval — Reserve-a-Lab</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet" />
    <style>
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
       RESET & BASE
    ============================================================ */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--font);
            background: var(--bg-gradient);
            min-height: 100vh;
            color: var(--text-primary);
            line-height: 1.6;
        }

        /* ============================================================
       TOP NAV / HEADER
    ============================================================ */
        .nav {
            background: var(--primary);
            padding: 0 2rem;
            height: 62px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 12px rgba(11, 27, 98, 0.25);
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .nav-logo {
            width: 32px;
            height: 32px;
            background: var(--secondary);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .nav-logo svg {
            width: 18px;
            height: 18px;
            fill: #fff;
        }

        .nav-title {
            font-size: 1rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.02em;
        }

        .nav-title span {
            color: #93B4FF;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-badge {
            background: rgba(255, 255, 255, 0.12);
            color: #C9D8FF;
            font-size: 0.72rem;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .nav-avatar {
            width: 34px;
            height: 34px;
            background: var(--secondary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 700;
            color: #fff;
            border: 2px solid rgba(255, 255, 255, 0.25);
        }

        /* ============================================================
       PAGE WRAPPER
    ============================================================ */
        .page {
            max-width: 860px;
            margin: 0 auto;
            padding: 2.5rem 1.25rem 4rem;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--secondary);
            margin-bottom: 6px;
        }

        .page-title {
            font-size: 1.65rem;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: -0.03em;
            line-height: 1.2;
        }

        .page-subtitle {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-top: 5px;
        }

        /* ============================================================
       SECTION: APPROVAL TRACKER
    ============================================================ */
        .card {
            background: var(--card-bg);
            border-radius: var(--radius-card);
            box-shadow: var(--shadow-card);
            border: 1px solid rgba(226, 232, 240, 0.7);
        }

        .card-inner {
            padding: 1.75rem 2rem;
        }

        .section-label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.09em;
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
        }

        /* ---- Step Tracker ---- */
        .tracker {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            position: relative;
            gap: 0;
        }

        /* Connecting line behind steps */
        .tracker::before {
            content: '';
            position: absolute;
            top: 22px;
            left: calc(12.5% - 0px);
            right: calc(12.5% - 0px);
            height: 3px;
            background: var(--border);
            border-radius: 3px;
            z-index: 0;
        }

        /* Progress fill line */
        .tracker-progress {
            position: absolute;
            top: 22px;
            left: calc(12.5% - 0px);
            height: 3px;
            background: linear-gradient(90deg, var(--success) 0%, var(--secondary) 100%);
            border-radius: 3px;
            z-index: 1;
            transition: width 0.8s cubic-bezier(.4, 0, .2, 1);
        }

        .step {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            position: relative;
            z-index: 2;
        }

        .step-dot {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            font-weight: 700;
            border: 3px solid var(--border);
            background: #fff;
            transition: var(--transition);
            position: relative;
        }

        .step.approved .step-dot {
            background: var(--success-light);
            border-color: var(--success);
            color: var(--success);
        }

        .step.pending .step-dot {
            background: var(--secondary-light);
            border-color: var(--secondary);
            color: var(--secondary);
            animation: pulse-ring 2s ease-in-out infinite;
        }

        .step.upcoming .step-dot {
            background: var(--muted-light);
            border-color: var(--border);
            color: var(--muted);
        }

        @keyframes pulse-ring {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(79, 115, 217, 0.35);
            }

            50% {
                box-shadow: 0 0 0 8px rgba(79, 115, 217, 0);
            }
        }

        .step-info {
            text-align: center;
        }

        .step-role {
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--text-primary);
            white-space: nowrap;
        }

        .step-status {
            font-size: 0.68rem;
            font-weight: 600;
            padding: 2px 9px;
            border-radius: 20px;
            display: inline-block;
            margin-top: 4px;
        }

        .step.approved .step-status {
            background: var(--success-light);
            color: var(--success);
        }

        .step.pending .step-status {
            background: var(--secondary-light);
            color: var(--secondary);
        }

        .step.upcoming .step-status {
            background: var(--muted-light);
            color: var(--muted);
        }

        /* ============================================================
       SECTION: RESERVATION SUMMARY
    ============================================================ */
        .summary-card {
            margin-top: 1.5rem;
        }

        .summary-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.5rem 2rem 1rem;
            border-bottom: 1px solid var(--border);
        }

        .summary-head-left h3 {
            font-size: 1rem;
            font-weight: 800;
            color: var(--primary);
        }

        .summary-head-left p {
            font-size: 0.78rem;
            color: var(--text-secondary);
            margin-top: 2px;
        }

        .request-id {
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--secondary);
            background: var(--secondary-light);
            padding: 4px 12px;
            border-radius: 20px;
            border: 1px solid rgba(79, 115, 217, 0.2);
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0;
        }

        .summary-field {
            padding: 1.2rem 2rem;
            border-right: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
        }

        .summary-field:nth-child(3n) {
            border-right: none;
        }

        .summary-field:nth-last-child(-n+3) {
            border-bottom: none;
        }

        .field-label {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--muted);
            margin-bottom: 5px;
        }

        .field-value {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .field-value.truncate {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 180px;
        }

        .summary-footer {
            padding: 1.25rem 2rem;
            display: flex;
            justify-content: flex-end;
            border-top: 1px solid var(--border);
        }

        /* ============================================================
       BUTTONS
    ============================================================ */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 10px 22px;
            border-radius: var(--radius-btn);
            font-family: var(--font);
            font-size: 0.88rem;
            font-weight: 700;
            cursor: pointer;
            border: none;
            transition: var(--transition);
            text-decoration: none;
            white-space: nowrap;
        }

        .btn-primary {
            background: var(--secondary);
            color: #fff;
            box-shadow: 0 2px 8px rgba(79, 115, 217, 0.35);
        }

        .btn-primary:hover {
            background: #3d5ec7;
            box-shadow: 0 4px 14px rgba(79, 115, 217, 0.45);
            transform: translateY(-1px);
        }

        .btn-success {
            background: var(--success);
            color: #fff;
            box-shadow: 0 2px 8px rgba(22, 163, 74, 0.3);
        }

        .btn-success:hover {
            background: #15803d;
            transform: translateY(-1px);
        }

        .btn-danger {
            background: var(--danger-light);
            color: var(--danger);
            border: 1.5px solid rgba(220, 38, 38, 0.25);
        }

        .btn-danger:hover {
            background: #fecaca;
            transform: translateY(-1px);
        }

        .btn-ghost {
            background: var(--muted-light);
            color: var(--text-secondary);
            border: 1.5px solid var(--border);
        }

        .btn-ghost:hover {
            background: #e9edf4;
            color: var(--text-primary);
        }

        .btn svg {
            width: 15px;
            height: 15px;
            flex-shrink: 0;
        }

        /* ============================================================
       MODAL OVERLAY
    ============================================================ */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(11, 27, 98, 0.45);
            backdrop-filter: blur(4px);
            z-index: 200;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.25rem;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.25s ease;
        }

        .modal-overlay.open {
            opacity: 1;
            pointer-events: all;
        }

        .modal {
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

        .modal-overlay.open .modal {
            transform: scale(1) translateY(0);
            opacity: 1;
        }

        /* scrollbar */
        .modal::-webkit-scrollbar {
            width: 5px;
        }

        .modal::-webkit-scrollbar-track {
            background: transparent;
        }

        .modal::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 10px;
        }

        .modal-header {
            padding: 1.5rem 1.75rem 1.2rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            position: sticky;
            top: 0;
            background: var(--card-bg);
            z-index: 1;
            border-radius: var(--radius-card) var(--radius-card) 0 0;
        }

        .modal-header-left h2 {
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--primary);
        }

        .modal-header-left p {
            font-size: 0.78rem;
            color: var(--text-secondary);
            margin-top: 3px;
        }

        .modal-close {
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

        .modal-close:hover {
            background: var(--border);
            color: var(--primary);
        }

        .modal-body {
            padding: 1.5rem 1.75rem;
        }

        .modal-section-title {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.09em;
            color: var(--secondary);
            margin-bottom: 1rem;
            padding-bottom: 8px;
            border-bottom: 1.5px solid var(--secondary-light);
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem 1.5rem;
            margin-bottom: 1.5rem;
        }

        .detail-field {}

        .detail-label {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--muted);
            margin-bottom: 4px;
        }

        .detail-value {
            font-size: 0.9rem;
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
            margin-top: 1.25rem;
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
            font-size: 0.78rem;
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
            font-size: 0.88rem;
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
            font-size: 0.75rem;
            color: var(--danger);
            margin-top: 5px;
            display: none;
        }

        .reject-error.visible {
            display: block;
        }

        .modal-footer {
            padding: 1.25rem 1.75rem;
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .modal-footer-right {
            margin-left: auto;
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        /* ---- Status toast ---- */
        .toast {
            position: fixed;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%) translateY(20px);
            background: var(--primary);
            color: #fff;
            padding: 13px 22px;
            border-radius: 12px;
            font-size: 0.88rem;
            font-weight: 600;
            box-shadow: 0 8px 28px rgba(11, 27, 98, 0.3);
            z-index: 500;
            opacity: 0;
            transition: opacity 0.3s ease, transform 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .toast.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        .toast.success {
            background: var(--success);
        }

        .toast.error {
            background: var(--danger);
        }

        /* ============================================================
       RESPONSIVE — MOBILE
    ============================================================ */
        @media (max-width: 640px) {
            .nav {
                padding: 0 1rem;
            }

            .page {
                padding: 1.5rem 1rem 3rem;
            }

            .page-title {
                font-size: 1.3rem;
            }

            .card-inner {
                padding: 1.25rem 1rem;
            }

            /* Vertical tracker on mobile */
            .tracker {
                flex-direction: column;
                align-items: flex-start;
                gap: 0;
            }

            .tracker::before {
                top: calc(12.5% - 0px);
                bottom: calc(12.5% - 0px);
                left: 21px;
                right: auto;
                width: 3px;
                height: auto;
            }

            .tracker-progress {
                top: calc(12.5% - 0px);
                left: 21px;
                width: 3px !important;
                height: 50% !important;
                /* Approx 1 step done */
            }

            .step {
                flex-direction: row;
                align-items: center;
                gap: 14px;
                width: 100%;
                padding: 10px 0;
            }

            .step-dot {
                flex-shrink: 0;
            }

            .step-info {
                text-align: left;
            }

            .summary-grid {
                grid-template-columns: 1fr 1fr;
            }

            .summary-field {
                padding: 1rem 1.25rem;
            }

            .summary-field:nth-child(3n) {
                border-right: 1px solid var(--border);
            }

            .summary-field:nth-child(2n) {
                border-right: none;
            }

            .summary-head {
                flex-direction: column;
                gap: 10px;
                padding: 1.25rem 1.25rem 1rem;
            }

            .summary-footer {
                padding: 1rem 1.25rem;
            }

            .modal {
                max-height: 95vh;
            }

            .modal-body {
                padding: 1.25rem 1.25rem;
            }

            .modal-header {
                padding: 1.25rem 1.25rem 1rem;
            }

            .modal-footer {
                padding: 1rem 1.25rem;
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }

            .modal-footer-right {
                margin-left: 0;
                width: 100%;
                justify-content: flex-end;
            }

            .nav-badge {
                display: none;
            }
        }
    </style>
</head>

<body>

    <!-- ============================================================
       NAV
  ============================================================ -->
    <nav class="nav">
        <a href="#" class="nav-brand">
            <!-- Simple flask/beaker icon -->
            <div class="nav-logo">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 3v8L4.5 18A2 2 0 006.3 21h11.4a2 2 0 001.8-3L15 11V3H9zm0 0h6" />
                </svg>
            </div>
            <span class="nav-title">Reserve<span>-a-Lab</span></span>
        </a>
        <div class="nav-right">
            <span class="nav-badge" id="roleDisplay">Supervisor</span>
            <div class="nav-avatar" id="avatarInitials">JS</div>
        </div>
    </nav>

    <!-- ============================================================
       PAGE
  ============================================================ -->
    <main class="page">

        <div class="page-header">
            <p class="page-label">Reservation Request</p>
            <h1 class="page-title">Approval Status</h1>
            <p class="page-subtitle">Track the progress of your laboratory reservation request.</p>
        </div>

        <!-- ==========================================
         SECTION 1 — APPROVAL STAGE TRACKER
    ========================================== -->
        <div class="card">
            <div class="card-inner">
                <p class="section-label">Approval Process</p>

                <div class="tracker" id="stepTracker">

                    <!-- Progress fill bar (width set by JS) -->
                    <div class="tracker-progress" id="trackerProgress"></div>

                    <!-- Step 1: Supervisor -->
                    <div class="step approved" data-step="1">
                        <div class="step-dot">
                            <!-- Checkmark -->
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                                stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                                <polyline points="20 6 9 17 4 12" />
                            </svg>
                        </div>
                        <div class="step-info">
                            <div class="step-role">Supervisor</div>
                            <span class="step-status">Approved</span>
                        </div>
                    </div>

                    <!-- Step 2: Subject Teacher — PENDING (current) -->
                    <div class="step pending" data-step="2">
                        <div class="step-dot">
                            <!-- Hourglass / clock icon -->
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                                stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                                <circle cx="12" cy="12" r="10" />
                                <polyline points="12 6 12 12 16 14" />
                            </svg>
                        </div>
                        <div class="step-info">
                            <div class="step-role">Subject Teacher</div>
                            <span class="step-status">Pending</span>
                        </div>
                    </div>

                    <!-- Step 3: Lab Personnel — Upcoming -->
                    <div class="step upcoming" data-step="3">
                        <div class="step-dot">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                                stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                                <circle cx="12" cy="12" r="10" />
                            </svg>
                        </div>
                        <div class="step-info">
                            <div class="step-role">Lab Personnel</div>
                            <span class="step-status">Upcoming</span>
                        </div>
                    </div>

                    <!-- Step 4: CID Chief — Upcoming -->
                    <div class="step upcoming" data-step="4">
                        <div class="step-dot">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                                stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                                <circle cx="12" cy="12" r="10" />
                            </svg>
                        </div>
                        <div class="step-info">
                            <div class="step-role">CID Chief</div>
                            <span class="step-status">Upcoming</span>
                        </div>
                    </div>

                </div><!-- /tracker -->
            </div>
        </div><!-- /tracker card -->

        <!-- ==========================================
         SECTION 2 — RESERVATION SUMMARY CARD
    ========================================== -->
        <div class="card summary-card">

            <div class="summary-head">
                <div class="summary-head-left">
                    <h3>Reservation Summary</h3>
                    <p>Submitted on May 12, 2025 at 09:14 AM</p>
                </div>
                <span class="request-id">REQ-2025-0047</span>
            </div>

            <div class="summary-grid">
                <div class="summary-field">
                    <div class="field-label">Requester</div>
                    <div class="field-value">Juan S. Santos</div>
                </div>
                <div class="summary-field">
                    <div class="field-label">Laboratory</div>
                    <div class="field-value">Computer Lab 3</div>
                </div>
                <div class="summary-field">
                    <div class="field-label">Date</div>
                    <div class="field-value">May 19, 2025</div>
                </div>
                <div class="summary-field">
                    <div class="field-label">Time</div>
                    <div class="field-value">08:00 AM – 10:00 AM</div>
                </div>
                <div class="summary-field">
                    <div class="field-label">Subject</div>
                    <div class="field-value">CS 311 – OS</div>
                </div>
                <div class="summary-field">
                    <div class="field-label">Purpose</div>
                    <div class="field-value truncate"
                        title="Hands-on laboratory session on file systems and memory management">
                        Hands-on lab session on file systems…
                    </div>
                </div>
            </div>

            <div class="summary-footer">
                <button class="btn btn-primary" id="openModalBtn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                    View Full Details
                </button>
            </div>

        </div><!-- /summary card -->

    </main>

    <!-- ============================================================
       MODAL — FULL DETAILS
  ============================================================ -->
    <div class="modal-overlay" id="modalOverlay" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal" id="modal">

            <!-- Modal Header -->
            <div class="modal-header">
                <div class="modal-header-left">
                    <h2 id="modalTitle">Full Reservation Details</h2>
                    <p>Request ID: <strong>REQ-2025-0047</strong></p>
                </div>
                <button class="modal-close" id="modalCloseTop" aria-label="Close modal">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"
                        stroke-linejoin="round" width="16" height="16">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body">

                <!-- Requester Information -->
                <p class="modal-section-title">Requester Information</p>
                <div class="detail-grid">
                    <div class="detail-field">
                        <div class="detail-label">Full Name</div>
                        <div class="detail-value">Juan S. Santos</div>
                    </div>
                    <div class="detail-field">
                        <div class="detail-label">Student / Faculty ID</div>
                        <div class="detail-value">2021-00123</div>
                    </div>
                    <div class="detail-field">
                        <div class="detail-label">Department</div>
                        <div class="detail-value">College of Information Technology</div>
                    </div>
                    <div class="detail-field">
                        <div class="detail-label">Contact Email</div>
                        <div class="detail-value">j.santos@school.edu.ph</div>
                    </div>
                </div>

                <!-- Reservation Details -->
                <p class="modal-section-title">Reservation Details</p>
                <div class="detail-grid">
                    <div class="detail-field">
                        <div class="detail-label">Laboratory</div>
                        <div class="detail-value">Computer Lab 3 — Room 214</div>
                    </div>
                    <div class="detail-field">
                        <div class="detail-label">Date of Use</div>
                        <div class="detail-value">May 19, 2025 (Monday)</div>
                    </div>
                    <div class="detail-field">
                        <div class="detail-label">Start Time</div>
                        <div class="detail-value">08:00 AM</div>
                    </div>
                    <div class="detail-field">
                        <div class="detail-label">End Time</div>
                        <div class="detail-value">10:00 AM</div>
                    </div>
                    <div class="detail-field">
                        <div class="detail-label">Subject Code</div>
                        <div class="detail-value">CS 311</div>
                    </div>
                    <div class="detail-field">
                        <div class="detail-label">Subject Description</div>
                        <div class="detail-value">Operating Systems</div>
                    </div>
                    <div class="detail-field full-width">
                        <div class="detail-label">Purpose / Activity</div>
                        <div class="detail-value">
                            Hands-on laboratory session on file systems and memory management as part of the CS 311
                            curriculum. Students will perform exercises on process scheduling and I/O handling.
                        </div>
                    </div>
                    <div class="detail-field">
                        <div class="detail-label">No. of Students</div>
                        <div class="detail-value">38</div>
                    </div>
                    <div class="detail-field">
                        <div class="detail-label">Subject Teacher</div>
                        <div class="detail-value">Prof. Maria R. Dela Cruz</div>
                    </div>
                </div>

                <!-- Additional Notes -->
                <p class="modal-section-title">Additional Notes</p>
                <div class="detail-grid">
                    <div class="detail-field full-width">
                        <div class="detail-label">Special Requirements</div>
                        <div class="detail-value">
                            Projector setup required. Internet access needed for software downloads. Please ensure all
                            PCs have the latest Ubuntu 22.04 LTS installed.
                        </div>
                    </div>
                </div>

                <!-- Rejection Reason Area (conditionally shown) -->
                <div class="reject-area" id="rejectArea">
                    <label for="rejectReason">
                        ⚠ Reason for Rejection <span style="color:var(--danger)">*</span>
                    </label>
                    <textarea id="rejectReason"
                        placeholder="Please provide a clear reason why this request is being rejected…"
                        maxlength="500"></textarea>
                    <div class="reject-error" id="rejectError">
                        A rejection reason is required before confirming.
                    </div>
                    <div style="margin-top:0.85rem; display:flex; gap:0.65rem; justify-content:flex-end;">
                        <button class="btn btn-ghost" id="cancelRejectBtn">Cancel</button>
                        <button class="btn btn-danger" id="confirmRejectBtn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10" />
                                <line x1="15" y1="9" x2="9" y2="15" />
                                <line x1="9" y1="9" x2="15" y2="15" />
                            </svg>
                            Confirm Rejection
                        </button>
                    </div>
                </div>

            </div><!-- /modal-body -->

            <!-- Modal Footer — role-based buttons -->
            <div class="modal-footer" id="modalFooter">
                <!-- Buttons injected by JS based on currentUserRole -->
            </div>

        </div><!-- /modal -->
    </div>

    <!-- ============================================================
       TOAST NOTIFICATION
  ============================================================ -->
    <div class="toast" id="toast" role="status" aria-live="polite"></div>

    <!-- ============================================================
       JAVASCRIPT
  ============================================================ -->
    <script>
        /* ============================================================
           ROLE SIMULATION
           Change `currentUserRole` to test different states:
             "Supervisor"        — can approve/reject (current approver)
             "Subject Teacher"   — not yet the approver
             "Laboratory Personnel"
             "CID Chief"
             "Student"           — requester / viewer only
        ============================================================ */
        const currentUserRole = "Supervisor";   // <-- CHANGE THIS TO TEST
        const currentApprover = "Supervisor";   // Step currently awaiting approval

        // Set nav display
        document.getElementById('roleDisplay').textContent = currentUserRole;
        document.getElementById('avatarInitials').textContent = getInitials(currentUserRole);

        function getInitials(role) {
            const map = {
                'Supervisor': 'SV',
                'Subject Teacher': 'ST',
                'Laboratory Personnel': 'LP',
                'CID Chief': 'CC',
                'Student': 'JS',
            };
            return map[role] || role.slice(0, 2).toUpperCase();
        }

        /* ============================================================
           TRACKER PROGRESS BAR WIDTH
           Based on how many steps are "approved"
        ============================================================ */
        (function initTracker() {
            const steps = document.querySelectorAll('.step');
            const approvedCount = [...steps].filter(s => s.classList.contains('approved')).length;
            const totalSteps = steps.length;
            // Width spans from first step center to last step center
            // Each step center is at (index / (total-1)) * 100% of inner width
            const progress = approvedCount / (totalSteps - 1); // 0 → 1
            const clampedPct = Math.min(Math.max(progress, 0), 1) * 100;
            document.getElementById('trackerProgress').style.width = clampedPct + '%';
        })();

        /* ============================================================
           MODAL OPEN / CLOSE
        ============================================================ */
        const overlay = document.getElementById('modalOverlay');
        const modal = document.getElementById('modal');
        const openBtn = document.getElementById('openModalBtn');
        const closeTopBtn = document.getElementById('modalCloseTop');
        const modalFooter = document.getElementById('modalFooter');

        function openModal() {
            renderFooterButtons();
            overlay.classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            overlay.classList.remove('open');
            document.body.style.overflow = '';
            hideRejectArea();
        }

        openBtn.addEventListener('click', openModal);
        closeTopBtn.addEventListener('click', closeModal);

        // Close on overlay click (outside modal)
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) closeModal();
        });

        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && overlay.classList.contains('open')) closeModal();
        });

        /* ============================================================
           ROLE-BASED FOOTER BUTTONS
        ============================================================ */
        function renderFooterButtons() {
            modalFooter.innerHTML = '';

            if (currentUserRole === currentApprover) {
                /* ---- Current approver: Approve + Reject + Close ---- */
                const closeBtn = document.createElement('button');
                closeBtn.className = 'btn btn-ghost';
                closeBtn.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Close`;
                closeBtn.addEventListener('click', closeModal);

                const rejectBtn = document.createElement('button');
                rejectBtn.className = 'btn btn-danger';
                rejectBtn.id = 'rejectBtn';
                rejectBtn.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg> Reject`;
                rejectBtn.addEventListener('click', showRejectArea);

                const approveBtn = document.createElement('button');
                approveBtn.className = 'btn btn-success';
                approveBtn.id = 'approveBtn';
                approveBtn.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Approve`;
                approveBtn.addEventListener('click', handleApprove);

                const rightGroup = document.createElement('div');
                rightGroup.className = 'modal-footer-right';
                rightGroup.appendChild(rejectBtn);
                rightGroup.appendChild(approveBtn);

                modalFooter.appendChild(closeBtn);
                modalFooter.appendChild(rightGroup);

            } else {
                /* ---- Not current approver: Close only ---- */
                const closeBtn = document.createElement('button');
                closeBtn.className = 'btn btn-ghost';
                closeBtn.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Close`;
                closeBtn.addEventListener('click', closeModal);

                const infoSpan = document.createElement('span');
                infoSpan.style.cssText = 'font-size:0.78rem;color:var(--muted);display:flex;align-items:center;gap:5px;';
                infoSpan.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg> Awaiting <strong style="color:var(--secondary)">${currentApprover}</strong>&nbsp;action`;

                const rightGroup = document.createElement('div');
                rightGroup.className = 'modal-footer-right';
                rightGroup.appendChild(closeBtn);

                modalFooter.appendChild(infoSpan);
                modalFooter.appendChild(rightGroup);
            }
        }

        /* ============================================================
           APPROVE HANDLER (SIMULATED)
        ============================================================ */
        function handleApprove() {
            closeModal();
            showToast('✓ Reservation approved successfully.', 'success');
            // Simulate updating tracker: mark Supervisor as approved visually already done
            // In a real app, would POST to server and reload state
        }

        /* ============================================================
           REJECT UX
        ============================================================ */
        const rejectArea = document.getElementById('rejectArea');
        const rejectReason = document.getElementById('rejectReason');
        const rejectError = document.getElementById('rejectError');

        function showRejectArea() {
            rejectArea.classList.add('visible');
            modal.scrollTo({ top: modal.scrollHeight, behavior: 'smooth' });
            rejectReason.focus();

            // Hide footer action buttons while rejection is active
            const approveBtn = document.getElementById('approveBtn');
            const rejectBtn = document.getElementById('rejectBtn');
            if (approveBtn) approveBtn.style.display = 'none';
            if (rejectBtn) rejectBtn.style.display = 'none';
        }

        function hideRejectArea() {
            rejectArea.classList.remove('visible');
            rejectReason.value = '';
            rejectError.classList.remove('visible');
        }

        document.getElementById('cancelRejectBtn').addEventListener('click', () => {
            hideRejectArea();
            // Restore buttons
            const approveBtn = document.getElementById('approveBtn');
            const rejectBtn = document.getElementById('rejectBtn');
            if (approveBtn) approveBtn.style.display = '';
            if (rejectBtn) rejectBtn.style.display = '';
        });

        document.getElementById('confirmRejectBtn').addEventListener('click', () => {
            const reason = rejectReason.value.trim();

            if (!reason) {
                rejectError.classList.add('visible');
                rejectReason.focus();
                rejectReason.style.borderColor = 'var(--danger)';
                return;
            }

            rejectError.classList.remove('visible');
            closeModal();
            showToast('✗ Reservation has been rejected.', 'error');
            // In real app: POST rejection with reason to server
            console.info('Rejection reason submitted:', reason);
        });

        rejectReason.addEventListener('input', () => {
            if (rejectReason.value.trim()) {
                rejectError.classList.remove('visible');
                rejectReason.style.borderColor = '';
            }
        });

        /* ============================================================
           TOAST HELPER
        ============================================================ */
        const toast = document.getElementById('toast');
        let toastTimer;

        function showToast(message, type = 'default') {
            clearTimeout(toastTimer);
            toast.textContent = message;
            toast.className = 'toast ' + (type === 'success' ? 'success' : type === 'error' ? 'error' : '');
            toast.classList.add('show');
            toastTimer = setTimeout(() => toast.classList.remove('show'), 3500);
        }
    </script>

</body>

</html>