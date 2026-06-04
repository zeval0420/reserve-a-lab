<?php
/* 
 * Load foundational resources:
 * Establishes database connections and handles session persistence automatically.
 */
include('../scilab/helperFiles/db_connection.php');
include('helperFiles/session_handler.php');

if (isset($_POST['local_login']) && $_POST['local_login'] === 'true') {
    $u = $_POST['username'] ?? '';
    $p = $_POST['password'] ?? '';
    if ($u === 'admin.controller' && md5($p) === 'e63ff18bb1478deb7059c5bec3aeaa39') {
        $_SESSION['role'] = 'admin';
        $_SESSION['username'] = 'Admin Controller';
        $_SESSION['email'] = 'admin.controller@local';
        echo 'success';
    } else {
        echo 'fail';
    }
    exit();
}

/* 
 * Autologin logic: 
 * Detects existing valid sessions and routes the returning user to their respective dashboard instead of showing the login display.
 */
$sessionRedirectScript = "";
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin_home.php");
        exit();
    } elseif ($_SESSION['role'] === 'requester' || $_SESSION['role'] === 'teacher') {
        header("Location: requester_home.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reserve-a-Lab · PSHS-IRC</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700&display=swap"
        rel="stylesheet" />
    <style>
        /* ─────────────────────────────────────────
       DESIGN TOKENS & SYSTEM VARIABLES
    ───────────────────────────────────────── */
        :root {
            --primary: #0B1B62;
            --primary-light: #152985;
            --secondary: #4F73D9;
            --secondary-glow: rgba(79, 115, 217, 0.25);
            --bg-page: #EBF0FA;
            --bg-card: rgba(255, 255, 255, 0.9);
            --bg-modal: #FFFFFF;
            --text-primary: #0B1B62;
            --text-secondary: #5E6E88;
            --input-bg: #F8FAFC;
            --border: #E2E8F0;
            --error: #DC2626;
            --error-bg: #FEF2F2;
            --error-border: #FECACA;
            --success: #16A34A;
            --success-bg: #F0FDF4;
            --success-border: #BBF7D0;
            --radius-card: 16px;
            --radius-input: 10px;
            --shadow-card: 0 20px 40px rgba(11, 27, 98, 0.08), 0 1px 3px rgba(11, 27, 98, 0.04);
            --shadow-panel: 0 30px 60px rgba(11, 27, 98, 0.15);
            --shadow-modal: 0 30px 60px rgba(11, 27, 98, 0.2);
            --transition: cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* ─────────────────────────────────────────
       RESET & BASE RESILIENCE
    ───────────────────────────────────────── */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html,
        body {
            height: 100%;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--bg-page);
            color: var(--text-primary);
            -webkit-font-smoothing: antialiased;
            overflow: hidden;
        }

        /* ─────────────────────────────────────────
       LAYOUT SYSTEM (MOBILE DEFAULT)
    ───────────────────────────────────────── */
        .page-wrapper {
            height: 100vh;
            width: 100vw;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            background: linear-gradient(135deg, #F0F4FC 0%, #EBF0FA 100%);
            overflow: hidden;
        }

        /* Micro-pattern grid overlay */
        .page-wrapper::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: linear-gradient(rgba(11, 27, 98, 0.02) 1px, transparent 1px), linear-gradient(90deg, rgba(11, 27, 98, 0.02) 1px, transparent 1px);
            background-size: 24px 24px;
            pointer-events: none;
            z-index: 0;
        }

        /* Split-screen wrapper container */
        .split-container {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1;
        }

        /* Card container wrapper for isolated adaptive scaling */
        .card-pane {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem 1rem;
            overflow-y: auto;
        }

        /* Visual Illustration Sidebar Panel (Hidden on Mobile) */
        .visual-pane {
            display: none;
        }

        /* ─────────────────────────────────────────
       TABLET / DESKTOP SPLIT VIEW ENHANCEMENTS
    ───────────────────────────────────────── */
        @media (min-width: 992px) {
            .split-container {
                width: 92vw;
                max-width: 1200px;
                height: 85vh;
                max-height: 780px;
                gap: 2.5rem;
            }

            .card-pane {
                flex: 1;
                width: auto;
                height: 100%;
                padding: 0;
                justify-content: flex-end;
                overflow-y: visible;
            }

            .visual-pane {
                display: block;
                flex: 1.1;
                height: 100%;
                position: relative;
                border-radius: var(--radius-card);
                overflow: hidden;
                box-shadow: var(--shadow-panel);
                animation: panel-entrance 0.7s var(--transition) both;
            }

            @keyframes panel-entrance {
                from {
                    opacity: 0;
                    transform: translateX(30px) scale(0.98);
                }

                to {
                    opacity: 1;
                    transform: translateX(0) scale(1);
                }
            }

            /* Responsive landscape structural layout placeholder element using pure code styling architecture */
            .visual-panel-img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                background-color: var(--primary);
                background-image:
                    radial-gradient(circle at 80% 20%, rgba(79, 115, 217, 0.4) 0%, transparent 50%),
                    radial-gradient(circle at 20% 80%, rgba(11, 27, 98, 0.6) 0%, transparent 70%),
                    url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><g stroke="%23ffffff" stroke-width="0.5" stroke-opacity="0.1"><circle cx="50" cy="50" r="40" fill="none"/><circle cx="50" cy="50" r="25" fill="none"/><line x1="10" y1="50" x2="90" y2="50"/><line x1="50" y1="10" x2="50" y2="90"/></g></svg>');
                background-size: cover, cover, 180px 180px;
                border-radius: var(--radius-card);
                transition: transform 4s ease;
            }

            .visual-pane:hover .visual-panel-img {
                transform: scale(1.03);
            }

            /* Gradient and brand content decorative overlay */
            .visual-overlay {
                position: absolute;
                inset: 0;
                background: linear-gradient(to top, rgba(11, 27, 98, 0.85) 0%, rgba(11, 27, 98, 0.2) 60%, transparent 100%);
                padding: 3rem;
                display: flex;
                flex-direction: column;
                justify-content: flex-end;
                color: #FFFFFF;
                pointer-events: none;
            }

            .visual-overlay h2 {
                font-family: 'Playfair Display', Georgia, serif;
                font-size: 2.2rem;
                font-weight: 700;
                margin-bottom: 0.75rem;
                line-height: 1.2;
                text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            }

            .visual-overlay p {
                font-size: 0.95rem;
                color: rgba(255, 255, 255, 0.85);
                max-width: 440px;
                line-height: 1.5;
                text-shadow: 0 1px 5px rgba(0, 0, 0, 0.2);
            }
        }

        /* ─────────────────────────────────────────
       CARD ARCHITECTURE & INTERACTIVES
    ───────────────────────────────────────── */
        .card {
            background: var(--bg-card);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: var(--radius-card);
            box-shadow: var(--shadow-card);
            width: 100%;
            max-width: 440px;
            overflow: hidden;
            animation: card-entrance 0.6s var(--transition) both;
        }

        @keyframes card-entrance {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.98);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .card-header-band {
            background: var(--primary);
            padding: 1.75rem 2rem;
            text-align: center;
            position: relative;
        }

        .card-header-band::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--secondary), var(--primary));
        }

        .card-header-band .system-label {
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 0.3rem;
        }

        .card-header-band h1 {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 1.6rem;
            font-weight: 600;
            color: #FFFFFF;
            line-height: 1.25;
        }

        .card-body {
            padding: 2.25rem 2.25rem 1.75rem;
        }

        /* ─────────────────────────────────────────
       INSTITUTION PROFILE COMPONENT
    ───────────────────────────────────────── */
        .institution {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .logo-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #FFFFFF;
            border: 1.5px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(11, 27, 98, 0.04);
        }

        .logo-circle svg {
            width: 36px;
            height: 36px;
        }

        .institution-text {
            line-height: 1.4;
        }

        .inst-line1 {
            font-size: 0.7rem;
            font-weight: 500;
            color: var(--text-secondary);
        }

        .inst-line2 {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--secondary);
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .inst-line3 {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--primary);
        }

        .divider {
            border: none;
            border-top: 1px solid var(--border);
            margin: 0 0 1.75rem;
        }

        /* ─────────────────────────────────────────
       ALERTS & NOTIFICATIONS
    ───────────────────────────────────────── */
        #alert-area {
            margin-bottom: 1.25rem;
        }

        .alert {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.85rem 1rem;
            border-radius: var(--radius-input);
            font-size: 0.82rem;
            font-weight: 500;
            animation: alert-fade-in 0.25s ease both;
        }

        @keyframes alert-fade-in {
            from {
                opacity: 0;
                transform: translateY(-8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-error {
            background: var(--error-bg);
            border: 1px solid var(--error-border);
            color: var(--error);
        }

        .alert-success {
            background: var(--success-bg);
            border: 1px solid var(--success-border);
            color: var(--success);
        }

        .alert-icon {
            flex-shrink: 0;
            margin-top: 0.05rem;
        }

        .alert-close {
            margin-left: auto;
            background: none;
            border: none;
            cursor: pointer;
            color: inherit;
            opacity: 0.6;
            font-size: 1rem;
            padding-left: 0.5rem;
            line-height: 1;
        }

        .alert-close:hover {
            opacity: 1;
        }

        /* ─────────────────────────────────────────
       FORM STYLING & FIELD TOKEN SETS
    ───────────────────────────────────────── */
        .field {
            margin-bottom: 1.25rem;
            position: relative;
        }

        .field label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.4rem;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap .field-icon {
            position: absolute;
            left: 0.95rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            pointer-events: none;
            transition: color 0.2s ease;
        }

        .field input,
        .field select {
            width: 100%;
            height: 46px;
            padding: 0 1rem 0 2.6rem;
            background: var(--input-bg);
            border: 1.5px solid var(--border);
            border-radius: var(--radius-input);
            font-family: inherit;
            font-size: 0.9rem;
            color: var(--text-primary);
            outline: none;
            transition: border-color 0.2s var(--transition), box-shadow 0.2s var(--transition), background-color 0.2s ease;
        }

        .field input::placeholder {
            color: #A0AEC0;
        }

        .field input:focus,
        .field select:focus {
            background-color: #FFFFFF;
            border-color: var(--secondary);
            box-shadow: 0 0 0 4px var(--secondary-glow);
        }

        .field input:focus+.field-icon {
            color: var(--secondary);
        }

        .field input.is-invalid,
        .field select.is-invalid {
            border-color: var(--error) !important;
            box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.15) !important;
        }

        .field input.is-valid,
        .field select.is-valid {
            border-color: var(--success) !important;
            box-shadow: 0 0 0 4px rgba(22, 163, 74, 0.15) !important;
        }

        .inline-feedback {
            font-size: 0.75rem;
            font-weight: 500;
            margin-top: 0.35rem;
            display: none;
        }

        .field input.is-invalid~.inline-feedback {
            color: var(--error);
            display: block;
        }

        .field input.is-valid~.inline-feedback {
            color: var(--success);
            display: block;
        }

        .field .input-wrap-password input {
            padding-right: 2.8rem;
        }

        .pw-toggle {
            position: absolute;
            right: 0.95rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-secondary);
            padding: 0.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: color 0.2s ease;
        }

        .pw-toggle:hover {
            color: var(--primary);
        }

        .field input:disabled {
            background: #EDF2F7;
            color: #718096;
            cursor: not-allowed;
            border-color: var(--border);
        }

        .checkbox-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: -0.5rem;
            margin-bottom: 1.25rem;
        }

        .checkbox-row input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--secondary);
            cursor: pointer;
        }

        .checkbox-row label {
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--text-secondary);
            cursor: pointer;
            user-select: none;
        }

        .name-row {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1.25rem;
        }

        .name-row .field {
            margin-bottom: 0;
            flex: 1;
        }

        .name-row .field input {
            padding: 0 0.75rem;
        }

        .name-row .field .inline-feedback {
            font-size: 0.65rem;
        }

        /* ─────────────────────────────────────────
       BUTTON ARCHITECTURE
    ───────────────────────────────────────── */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            height: 46px;
            border: none;
            border-radius: var(--radius-input);
            font-family: inherit;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.1s var(--transition), background-color 0.2s var(--transition), box-shadow 0.2s var(--transition);
            letter-spacing: 0.01em;
        }

        .btn:active {
            transform: scale(0.98);
        }

        .btn-primary {
            background: var(--primary);
            color: #FFFFFF;
            box-shadow: 0 4px 12px rgba(11, 27, 98, 0.15);
        }

        .btn-primary:hover {
            background: var(--primary-light);
            box-shadow: 0 6px 20px rgba(11, 27, 98, 0.25);
        }

        .btn-secondary {
            background: transparent;
            color: var(--secondary);
            border: 1.5px solid var(--secondary);
        }

        .btn-secondary:hover {
            background: rgba(79, 115, 217, 0.06);
            box-shadow: 0 4px 12px rgba(79, 115, 217, 0.1);
        }

        .btn-ghost {
            background: none;
            border: none;
            color: var(--secondary);
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            padding: 0;
            font-family: inherit;
            transition: color 0.2s ease;
            text-decoration: none;
        }

        .btn-ghost:hover {
            color: var(--primary);
            text-decoration: underline;
        }

        .forgot-row {
            text-align: right;
            margin-top: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .guest-section {
            padding: 1.25rem 2.25rem 1.75rem;
            border-top: 1px solid var(--border);
            text-align: center;
            background: rgba(11, 27, 98, 0.01);
        }

        .guest-section p {
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-bottom: 0.85rem;
        }

        /* ─────────────────────────────────────────
       MODAL ARCHITECTURE
    ───────────────────────────────────────── */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(11, 27, 98, 0.4);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem 1rem;
            z-index: 1000;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.25s ease;
            overflow-y: auto;
        }

        .modal-overlay.open {
            opacity: 1;
            pointer-events: all;
        }

        .modal {
            background: var(--bg-modal);
            border-radius: var(--radius-card);
            box-shadow: var(--shadow-modal);
            width: 100%;
            max-width: 480px;
            padding: 2rem;
            transform: scale(0.94) translateY(10px);
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            margin: auto;
        }

        .modal-overlay.open .modal {
            transform: scale(1) translateY(0);
        }

        .modal-title {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.35rem;
        }

        .modal-desc {
            font-size: 0.82rem;
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }

        .modal-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.75rem;
        }

        .modal-actions .btn {
            height: 44px;
        }

        /* ─────────────────────────────────────────
       RESPONSIVE ADAPTATIONS (MOBILE BOUNDS)
    ───────────────────────────────────────── */
        @media (max-width: 480px) {
            .card-body {
                padding: 1.75rem 1.5rem 1.5rem;
            }

            .guest-section {
                padding: 1.25rem 1.5rem 1.5rem;
            }

            .modal {
                padding: 1.5rem;
            }
        }
    </style>
</head>

<body>

    <div class="page-wrapper">
        <div class="split-container">

            <div class="card-pane">
                <div class="card" role="main">

                    <div class="card-header-band">
                        <p class="system-label">Laboratory Management</p>
                        <h1>Welcome to Reserve-a-Lab</h1>
                    </div>

                    <div class="card-body">

                        <div class="institution" aria-label="Institution Profile">
                            <div class="logo-circle" aria-hidden="true">
                                <img src="img/logo.png" alt="Philippine Science High School Logo"
                                    style="width: 100%; height: 100%; object-fit: contain;">
                            </div>
                            <div class="institution-text">
                                <p class="inst-line1">Department of Science and Technology</p>
                                <p class="inst-line2">Ilocos Region Campus</p>
                                <p class="inst-line3">Philippine Science High School</p>
                            </div>
                        </div>

                        <hr class="divider" />

                        <div id="alert-area" role="alert" aria-live="polite"></div>

                        <form id="login-form" novalidate>

                            <div class="field">
                                <label for="username">Email or Username</label>
                                <div class="input-wrap">
                                    <svg class="field-icon" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" aria-hidden="true">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                        <circle cx="12" cy="7" r="4" />
                                    </svg>
                                    <input type="text" id="username" name="username" placeholder="you@pshs.edu.ph"
                                        autocomplete="username" />
                                    <div class="inline-feedback"></div>
                                </div>
                            </div>

                            <div class="field">
                                <label for="password">Password</label>
                                <div class="input-wrap input-wrap-password">
                                    <svg class="field-icon" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" aria-hidden="true">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                    </svg>
                                    <input type="password" id="password" name="password" placeholder="••••••••"
                                        autocomplete="current-password" />
                                    <button type="button" class="pw-toggle" id="pw-toggle"
                                        aria-label="Toggle password visibility">
                                        <svg id="eye-icon" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                            <circle cx="12" cy="12" r="3" />
                                        </svg>
                                    </button>
                                    <div class="inline-feedback"></div>
                                </div>
                            </div>

                            <div class="forgot-row">
                                <button type="button" class="btn-ghost" id="open-forgot">Forgot password?</button>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"
                                    aria-hidden="true">
                                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
                                    <polyline points="10 17 15 12 10 7" />
                                    <line x1="15" y1="12" x2="3" y2="12" />
                                </svg>
                                Login
                            </button>

                        </form>
                    </div>

                    <div class="guest-section">
                        <p>Don't have an account?</p>
                        <button type="button" class="btn btn-secondary" id="open-register">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                <circle cx="8.5" cy="7" r="4" />
                                <line x1="20" y1="8" x2="20" y2="14" />
                                <line x1="23" y1="11" x2="17" y2="11" />
                            </svg>
                            Create an Account
                        </button>
                    </div>

                </div>
            </div>

            <div class="visual-pane">
                <div class="visual-panel-img"></div>
                <div class="visual-overlay">
                    <h2>Empowering Scientific Discovery</h2>
                    <p>Access and reserve advanced campus research infrastructure, scientific laboratories, and
                        precision instruments across the Ilocos Region Campus network.</p>
                </div>
            </div>

        </div>
    </div>


    <div class="modal-overlay" id="modal-forgot" role="dialog" aria-modal="true" aria-labelledby="forgot-title">
        <div class="modal">
            <p class="modal-title" id="forgot-title">Reset Password</p>
            <p class="modal-desc">Enter your registered email address and we'll send you password reset instructions.
            </p>
            <div class="field">
                <label for="reset-email">Email Address</label>
                <div class="input-wrap">
                    <svg class="field-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                        <polyline points="22,6 12,13 2,6" />
                    </svg>
                    <input type="email" id="reset-email" placeholder="you@pshs.edu.ph" autocomplete="email" />
                    <div class="inline-feedback"></div>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" id="close-forgot">Cancel</button>
                <button type="button" class="btn btn-primary" id="submit-reset">Send Instructions</button>
            </div>
        </div>
    </div>


    <div class="modal-overlay" id="modal-register" role="dialog" aria-modal="true" aria-labelledby="register-title">
        <div class="modal">
            <p class="modal-title" id="register-title">Create an Account</p>
            <p class="modal-desc">Join the Reserve-a-Lab system to manage and coordinate your laboratory reservations
                efficiently.</p>

            <form id="register-form" novalidate>
                <div class="name-row">
                    <div class="field">
                        <label for="reg-firstname">First Name</label>
                        <div class="input-wrap">
                            <input type="text" id="reg-firstname" placeholder="First Name" />
                            <div class="inline-feedback"></div>
                        </div>
                    </div>
                    <div class="field">
                        <label for="reg-middlename">Middle Name</label>
                        <div class="input-wrap">
                            <input type="text" id="reg-middlename" placeholder="Middle Name" />
                            <div class="inline-feedback"></div>
                        </div>
                    </div>
                    <div class="field">
                        <label for="reg-lastname">Last Name</label>
                        <div class="input-wrap">
                            <input type="text" id="reg-lastname" placeholder="Last Name" />
                            <div class="inline-feedback"></div>
                        </div>
                    </div>
                </div>

                <div class="field" id="field-studentid">
                    <label for="reg-studentid">Student ID</label>
                    <div class="input-wrap">
                        <svg class="field-icon" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            aria-hidden="true">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                        </svg>
                        <input type="text" id="reg-studentid" placeholder="Your Student ID" />
                        <div class="inline-feedback"></div>
                    </div>
                </div>

                <div class="field">
                    <label for="reg-username">Username</label>
                    <div class="input-wrap">
                        <svg class="field-icon" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            aria-hidden="true">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                        <input type="text" id="reg-username" placeholder="Choose a secure username" />
                        <div class="inline-feedback"></div>
                    </div>
                </div>

                <div class="field">
                    <label for="reg-email">Email Address</label>
                    <div class="input-wrap">
                        <svg class="field-icon" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            aria-hidden="true">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                            <polyline points="22,6 12,13 2,6" />
                        </svg>
                        <input type="email" id="reg-email" placeholder="you@pshs.edu.ph" />
                        <div class="inline-feedback"></div>
                    </div>
                </div>

                <div class="field">
                    <label for="reg-password">Create Password</label>
                    <div class="input-wrap input-wrap-password">
                        <svg class="field-icon" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            aria-hidden="true">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                        </svg>
                        <input type="password" id="reg-password" placeholder="Min. 8 characters" />
                        <button type="button" class="pw-toggle" id="reg-pw-toggle"
                            aria-label="Toggle password visibility">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                        </button>
                        <div class="inline-feedback"></div>
                    </div>
                </div>

                <div class="field">
                    <label for="reg-confirm-password">Confirm Password</label>
                    <div class="input-wrap input-wrap-password">
                        <svg class="field-icon" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            aria-hidden="true">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                        </svg>
                        <input type="password" id="reg-confirm-password" placeholder="Re-enter password" />
                        <button type="button" class="pw-toggle" id="reg-confirm-pw-toggle"
                            aria-label="Toggle password visibility">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                        </button>
                        <div class="inline-feedback"></div>
                    </div>
                </div>

                <div class="field">
                    <label for="reg-institution">Institution</label>
                    <div class="input-wrap">
                        <svg class="field-icon" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            aria-hidden="true">
                            <path d="M22 10v6M2 10l10-5 10 5-10 5z" />
                            <path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5" />
                        </svg>
                        <input type="text" id="reg-institution"
                            value="Philippine Science High School - Ilocos Region Campus" disabled />
                        <div class="inline-feedback"></div>
                    </div>
                </div>

                <div class="checkbox-row">
                    <input type="checkbox" id="not-pshs" />
                    <label for="not-pshs">Not from PSHS-IRC?</label>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" id="close-register">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submit-register">Create Account</button>
                </div>
            </form>
        </div>
    </div>


    <script>
        const $ = id => document.getElementById(id);

        /* ── Modal Automation Logic ── */
        function openModal(id) {
            $(id).classList.add('open');
            const firstInput = $(id).querySelector('input');
            if (firstInput && !firstInput.disabled) setTimeout(() => firstInput.focus(), 260);
        }

        function closeModal(id) {
            $(id).classList.remove('open');
            const form = $(id).querySelector('form');
            if (form) {
                form.reset();
                const inputs = form.querySelectorAll('input');
                inputs.forEach(input => {
                    input.classList.remove('is-invalid', 'is-valid');
                });
                if ($('reg-institution')) $('reg-institution').disabled = true;
            }
        }

        /* Dismiss listeners */
        ['modal-forgot', 'modal-register'].forEach(id => {
            if ($(id)) {
                $(id).addEventListener('click', e => {
                    if (e.target === $(id)) closeModal(id);
                });
            }
        });

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                closeModal('modal-forgot');
                closeModal('modal-register');
            }
        });

        if ($('open-forgot')) $('open-forgot').addEventListener('click', () => openModal('modal-forgot'));
        if ($('close-forgot')) $('close-forgot').addEventListener('click', () => closeModal('modal-forgot'));
        if ($('open-register')) $('open-register').addEventListener('click', () => openModal('modal-register'));
        if ($('close-register')) $('close-register').addEventListener('click', () => closeModal('modal-register'));

        /* ── Visual State Controls ── */
        function setValidationState(element, state, message = '') {
            const feedback = element.parentElement.querySelector('.inline-feedback');
            if (state === 'invalid') {
                element.classList.remove('is-valid');
                element.classList.add('is-invalid');
                if (feedback) feedback.textContent = message;
            } else if (state === 'valid') {
                element.classList.remove('is-invalid');
                element.classList.add('is-valid');
                if (feedback) feedback.textContent = '';
            } else {
                element.classList.remove('is-invalid', 'is-valid');
                if (feedback) feedback.textContent = '';
            }
        }

        /* ── Password Visibility Toggles ── */
        function setupPasswordToggle(toggleId, inputId) {
            if (!$(toggleId) || !$(inputId)) return;
            $(toggleId).addEventListener('click', function () {
                const targetInput = $(inputId);
                const isHidden = targetInput.type === 'password';
                targetInput.type = isHidden ? 'text' : 'password';

                const valLength = targetInput.value.length;
                targetInput.setSelectionRange(valLength, valLength);
                targetInput.focus();

                this.innerHTML = isHidden
                    ? `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>`
                    : `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`;
            });
        }
        setupPasswordToggle('pw-toggle', 'password');
        setupPasswordToggle('reg-pw-toggle', 'reg-password');
        setupPasswordToggle('reg-confirm-pw-toggle', 'reg-confirm-password');

        /* ── Institution External Toggle Handler ── */
        if ($('not-pshs')) {
            $('not-pshs').addEventListener('change', function () {
                const instInput = $('reg-institution');
                const studentIdField = $('field-studentid');
                const studentIdInput = $('reg-studentid');

                if (this.checked) {
                    instInput.disabled = false;
                    instInput.value = '';
                    instInput.placeholder = 'Enter external school or affiliation corporate name';
                    instInput.focus();
                    studentIdField.style.display = 'none';
                    studentIdInput.value = '';
                } else {
                    instInput.disabled = true;
                    instInput.value = 'Philippine Science High School - Ilocos Region Campus';
                    instInput.placeholder = '';
                    setValidationState(instInput, 'neutral');
                    studentIdField.style.display = 'block';
                }
            });
        }

        /* ── Real Backend Interaction Handlers ── */

        // Centralized intercept for password reset flow initiating a token request via email.
        if ($('submit-reset')) {
            $('submit-reset').addEventListener('click', () => {
                const email = $('reset-email');
                const emailVal = email.value.trim();
                if (!emailVal || !/\S+@\S+\.\S+/.test(emailVal)) {
                    setValidationState(email, 'invalid', 'Please provide a valid standard email layout.');
                    email.focus();
                    return;
                }
                setValidationState(email, 'neutral');

                // Native AJAX implementation mapping to the real application layer endpoints
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'ajax/ajax_login.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function () {
                    if (xhr.status === 200) {
                        if (xhr.responseText.trim() === 'success') {
                            closeModal('modal-forgot');
                            showAlert('Reset link sent to your email.', 'success');
                        } else {
                            showAlert(xhr.responseText, 'error');
                        }
                    } else {
                        showAlert('Server error. Please try again.', 'error');
                    }
                };
                xhr.send('action=forgotPassword&email=' + encodeURIComponent(emailVal));
            });
        }

        // Initialize registration account creation pathway, processing fields through the dedicated register modal
        if ($('register-form')) {
            $('register-form').addEventListener('submit', function (e) {
                e.preventDefault();
                let isFormValid = true;

                const username = $('reg-username');
                const email = $('reg-email');
                const password = $('reg-password');
                const confirmPass = $('reg-confirm-password');
                const institution = $('reg-institution');
                const firstname = $('reg-firstname');
                const middlename = $('reg-middlename');
                const lastname = $('reg-lastname');
                const studentid = $('reg-studentid');
                const isNotPshs = $('not-pshs').checked;

                if (firstname.value.trim().length === 0) {
                    setValidationState(firstname, 'invalid', 'Required');
                    isFormValid = false;
                } else setValidationState(firstname, 'valid');

                if (lastname.value.trim().length === 0) {
                    setValidationState(lastname, 'invalid', 'Required');
                    isFormValid = false;
                } else setValidationState(lastname, 'valid');

                if (!isNotPshs && studentid.value.trim().length === 0) {
                    setValidationState(studentid, 'invalid', 'Student ID is required for PSHS.');
                    isFormValid = false;
                } else if (!isNotPshs) {
                    setValidationState(studentid, 'valid');
                }

                if (username.value.trim().length < 4) {
                    setValidationState(username, 'invalid', 'Username must span at least 4 text strings.');
                    isFormValid = false;
                } else {
                    setValidationState(username, 'valid');
                }

                if (!/\S+@\S+\.\S+/.test(email.value.trim())) {
                    setValidationState(email, 'invalid', 'Please enter a valid structure email account profile.');
                    isFormValid = false;
                } else {
                    setValidationState(email, 'valid');
                }

                if (password.value.length < 8) {
                    setValidationState(password, 'invalid', 'Weak password. Minimum threshold requires 8 characters.');
                    isFormValid = false;
                } else {
                    setValidationState(password, 'valid');
                }

                if (confirmPass.value.length === 0) {
                    setValidationState(confirmPass, 'invalid', 'Please retype your credential security phrase.');
                    isFormValid = false;
                } else if (password.value !== confirmPass.value) {
                    setValidationState(confirmPass, 'invalid', 'Mismatch detected. Passwords must align perfectly.');
                    isFormValid = false;
                } else {
                    setValidationState(confirmPass, 'valid');
                }

                if (!institution.disabled && institution.value.trim().length === 0) {
                    setValidationState(institution, 'invalid', 'Affiliated entity specification cannot remain blank.');
                    isFormValid = false;
                } else if (!institution.disabled) {
                    setValidationState(institution, 'valid');
                }

                if (!isFormValid) return;

                // Submit values to guest access framework route endpoint
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'ajax/ajax_login.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function () {
                    if (xhr.status === 200) {
                        if (xhr.responseText.trim() === 'success') {
                            closeModal('modal-register');
                            showAlert('Account created successfully. You can now log in.', 'success');
                        } else {
                            showAlert(xhr.responseText.trim(), 'error');
                        }
                    } else {
                        showAlert('Server response error.', 'error');
                    }
                };

                const payload = 'action=guestLogin' +
                    '&username=' + encodeURIComponent(username.value.trim()) +
                    '&email=' + encodeURIComponent(email.value.trim()) +
                    '&password=' + encodeURIComponent(password.value) +
                    '&firstname=' + encodeURIComponent(firstname.value.trim()) +
                    '&middlename=' + encodeURIComponent(middlename.value.trim()) +
                    '&lastname=' + encodeURIComponent(lastname.value.trim()) +
                    '&studentid=' + encodeURIComponent(studentid.value.trim()) +
                    '&institution=' + encodeURIComponent(institution.value.trim());
                xhr.send(payload);
            });
        }

        // Login system action request processing logic pipeline
        if ($('login-form')) {
            $('login-form').addEventListener('submit', function (e) {
                e.preventDefault();
                clearAlerts();

                const usernameField = $('username');
                const passwordField = $('password');
                let formValid = true;

                let email = usernameField.value.trim();
                const password = passwordField.value;

                if (!email) {
                    setValidationState(usernameField, 'invalid', 'Identity field missing context requirement.');
                    formValid = false;
                } else {
                    setValidationState(usernameField, 'neutral');
                }

                if (!password) {
                    setValidationState(passwordField, 'invalid', 'Security code entry required.');
                    formValid = false;
                } else {
                    setValidationState(passwordField, 'neutral');
                }

                if (!formValid) {
                    showAlert('Please fill out all active fields correctly before submission.', 'error');
                    return;
                }

                // Route processing configuration block handling admin.controller local login values
                if (email === 'admin.controller') {
                    const xhrLocal = new XMLHttpRequest();
                    xhrLocal.open('POST', 'index.php', true);
                    xhrLocal.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhrLocal.onload = function () {
                        if (xhrLocal.status === 200 && xhrLocal.responseText.trim() === 'success') {
                            window.location.href = "controller_dashboard.php";
                        } else {
                            setValidationState(passwordField, 'invalid');
                            showAlert('Incorrect password.', 'error');
                        }
                    };
                    xhrLocal.send('local_login=true&username=' + encodeURIComponent(email) + '&password=' + encodeURIComponent(password));
                    return;
                }

                // Routing check for login submission
                // Appending @irc.pshs.edu.ph logic removed as requested. Input is passed raw.

                // Real validation submission process mapping to main dynamic application modules
                const xhrAuth = new XMLHttpRequest();
                xhrAuth.open('POST', 'ajax/ajax_login.php', true);
                xhrAuth.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhrAuth.onload = function () {
                    if (xhrAuth.status === 200) {
                        const response = xhrAuth.responseText.trim();
                        if (response === "invalid_email") {
                        try {
                            data = JSON.parse(response);
                        } catch (e) {
                            data = null;
                        }

                        if (data && data.status === "prompt_create_account") {
                            setValidationState(usernameField, 'invalid');
                            showAlert('Account not found. Please create an account.', 'error');
                            
                            // Autofill the registration form
                            $('reg-firstname').value = data.firstname || '';
                            $('reg-middlename').value = data.middlename || '';
                            $('reg-lastname').value = data.lastname || '';
                            $('reg-studentid').value = '';
                            $('reg-username').value = data.username || '';
                            $('reg-email').value = data.email || '';
                            
                            // Disable the autofilled inputs so only passwords and student ID can be typed
                            $('reg-firstname').disabled = true;
                            $('reg-middlename').disabled = true;
                            $('reg-lastname').disabled = true;
                            $('reg-studentid').disabled = false;
                            $('reg-username').disabled = true;
                            $('reg-email').disabled = true;
                            if ($('not-pshs')) $('not-pshs').disabled = true;
                            
                            openModal('modal-register');
                        } else if (response === "invalid_email") {
                            setValidationState(usernameField, 'invalid');
                            showAlert('Email or Username not found.', 'error');
                        } else if (response === "prompt_create_account") {
                            setValidationState(usernameField, 'invalid');
                            showAlert('Account not found. Please create an account.', 'error');
                            // optionally open the register modal
                            openModal('modal-register');
                        } else if (response === "invalid_password") {
                            setValidationState(passwordField, 'invalid');
                            showAlert('Incorrect password.', 'error');
                        } else if (response === "admin") {
                            window.location.href = "sample_admin.php";
                        } else if (response === "requester" || response === "teacher" || response === "guest") {
                            window.location.href = "requester_home.php"; // Redirect guest/requester properly
                        } else {
                            showAlert('Unexpected response: ' + response, 'error');
                        }
                    } else {
                        showAlert('An error occurred. Please try again.', 'error');
                    }
                };
                xhrAuth.send('action=loginUser&email=' + encodeURIComponent(email) + '&password=' + encodeURIComponent(password));
            });
        }

        function clearAlerts() {
            if ($('alert-area')) $('alert-area').innerHTML = '';
        }

        function showAlert(message, type = 'error') {
            clearAlerts();
            if (!$('alert-area')) return;

            const vectors = {
                error: `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`,
                success: `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>`
            };

            const block = document.createElement('div');
            block.className = `alert alert-${type}`;
            block.innerHTML = `
            <span class="alert-icon">${vectors[type] || ''}</span>
            <span>${message}</span>
            <button class="alert-close" type="button" aria-label="Dismiss Alert" onclick="this.parentElement.remove()">✕</button>
        `;
            $('alert-area').appendChild(block);
        }
    </script>
</body>

</html>