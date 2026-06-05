<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>About Us - PSHS-IRC Lab System</title>
        <?php include('helperFiles/headData.php'); ?>

        <link rel="stylesheet" href="css/game-section.css">
        <style>
            /* ========================================
               CSS VARIABLES & RESET
            ======================================== */
            :root {
                --primary-blue: #2563eb;
                --primary-blue-dark: #1e40af;
                --secondary-blue: #3b82f6;
                --light-blue: #eff6ff;
                --accent-blue: #60a5fa;
                --white: #ffffff;
                --gray-50: #f9fafb;
                --gray-100: #f3f4f6;
                --gray-200: #e5e7eb;
                --gray-300: #d1d5db;
                --text-dark: #1e293b;
                --text-medium: #334155;
                --text-light: #475569;
                --text-lighter: #64748b;
                
                --shadow-sm: 0 1px 3px rgba(0,0,0,0.06);
                --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.08), 0 2px 4px -1px rgba(0,0,0,0.04);
                --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.08), 0 4px 6px -2px rgba(0,0,0,0.04);
                --shadow-xl: 0 20px 25px -5px rgba(0,0,0,0.08), 0 10px 10px -5px rgba(0,0,0,0.02);
                
                --transition-fast: 0.2s ease;
                --transition-normal: 0.3s ease;
                --transition-slow: 0.5s ease;
                
                --radius-sm: 8px;
                --radius-md: 12px;
                --radius-lg: 16px;
                --radius-xl: 24px;
            }

            * {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
            }

            html {
                scroll-behavior: smooth;
            }

            body {
                margin: 0;
                font-family: "Raleway", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                color: var(--text-dark);
                background: var(--white);
                line-height: 1.6;
                overflow-x: hidden;
            }

            /* ========================================
               HERO SECTION WITH PARALLAX
            ======================================== */
            .hero-section {
                position: relative;
                min-height: 85vh;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            }

            .hero-bg {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: linear-gradient(rgba(37, 99, 235, 0.85), rgba(30, 64, 175, 0.9)), 
                            url('img/about photos/bg.jpeg') no-repeat center center;
                background-size: cover;
                z-index: 0;
                transform: scale(1.1);
                transition: transform 0.5s ease-out;
            }

            .hero-content {
                position: relative;
                z-index: 2;
                text-align: center;
                color: white;
                max-width: 900px;
                padding: 2rem;
                opacity: 0;
                transform: translateY(30px);
                animation: fadeInUp 1s ease forwards 0.3s;
            }

            .hero-content h1 {
                font-size: clamp(2.5rem, 6vw, 4.5rem);
                font-weight: 800;
                margin-bottom: 1.5rem;
                letter-spacing: -0.02em;
                line-height: 1.1;
            }

            .hero-content .subtitle {
                font-size: clamp(1.1rem, 2.5vw, 1.5rem);
                font-weight: 300;
                margin-bottom: 2rem;
                opacity: 0.95;
                line-height: 1.6;
            }

            .hero-stats {
                display: flex;
                justify-content: center;
                gap: 3rem;
                margin-top: 3rem;
                flex-wrap: wrap;
            }

            .stat-item {
                text-align: center;
            }

            .stat-number {
                font-size: clamp(2rem, 4vw, 3rem);
                font-weight: 700;
                display: block;
                margin-bottom: 0.5rem;
            }

            .stat-label {
                font-size: 0.95rem;
                opacity: 0.9;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                font-weight: 500;
            }

            /* ========================================
               SECTION CONTAINER
            ======================================== */
            .container {
                max-width: 1280px;
                margin: 0 auto;
                padding: 0 2rem;
            }

            .section {
                padding: 6rem 0;
                position: relative;
            }

            .section-alt {
                background: var(--gray-50);
            }

            /* Section Headers */
            .section-header {
                text-align: center;
                max-width: 800px;
                margin: 0 auto 4rem;
                opacity: 0;
                transform: translateY(20px);
                transition: all 0.6s ease;
            }

            .section-header.reveal {
                opacity: 1;
                transform: translateY(0);
            }

            .section-overline {
                font-size: 0.875rem;
                font-weight: 600;
                color: var(--primary-blue);
                text-transform: uppercase;
                letter-spacing: 0.1em;
                margin-bottom: 0.75rem;
                display: block;
            }

            .section-title {
                font-size: clamp(2rem, 4vw, 3rem);
                font-weight: 700;
                color: var(--text-dark);
                margin-bottom: 1rem;
                letter-spacing: -0.02em;
            }

            .section-description {
                font-size: clamp(1rem, 2vw, 1.2rem);
                color: var(--text-light);
                line-height: 1.8;
            }

            /* ========================================
               SECTION DIVIDER / TRANSITION
            ======================================== */
            .section-divider {
                position: relative;
                height: 120px;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
            }

            .section-divider::before {
                content: '';
                position: absolute;
                top: 50%;
                left: 0;
                right: 0;
                height: 2px;
                background: linear-gradient(90deg, transparent, var(--gray-300), transparent);
                transform: translateY(-50%);
            }

            .divider-icon {
                position: relative;
                width: 60px;
                height: 60px;
                background: var(--white);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.5rem;
                color: var(--primary-blue);
                box-shadow: var(--shadow-md);
                z-index: 2;
            }

            /* ========================================
               TEAM SECTION - SHARED STYLES
            ======================================== */
            .team-section-wrapper {
                position: relative;
            }

            /* Current Team Accent */
            .current-team {
                background: linear-gradient(to bottom, var(--white) 0%, var(--gray-50) 100%);
            }

            /* Original Team Accent */
            .original-team {
                background: linear-gradient(to bottom, var(--gray-50) 0%, var(--light-blue) 100%);
            }

            /* Badge for Section Labels */
            .section-badge {
                display: inline-block;
                padding: 0.5rem 1.5rem;
                background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
                color: white;
                border-radius: 50px;
                font-size: 0.85rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.1em;
                margin-bottom: 1rem;
                box-shadow: var(--shadow-md);
            }

            .section-badge.original {
                background: linear-gradient(135deg, #764ba2, #667eea);
            }

            /* ========================================
               MISSION & VISION SECTION
            ======================================== */
            .mission-vision {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
                gap: 3rem;
                margin-top: 3rem;
            }

            .mission-card,
            .vision-card {
                background: var(--white);
                padding: 3rem;
                border-radius: var(--radius-lg);
                box-shadow: var(--shadow-lg);
                position: relative;
                overflow: hidden;
                transition: all var(--transition-normal);
                opacity: 0;
                transform: translateY(30px);
            }

            .mission-card.reveal,
            .vision-card.reveal {
                opacity: 1;
                transform: translateY(0);
            }

            .mission-card::before,
            .vision-card::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 4px;
                background: linear-gradient(90deg, var(--primary-blue), var(--secondary-blue));
            }

            .mission-card:hover,
            .vision-card:hover {
                transform: translateY(-8px);
                box-shadow: var(--shadow-xl);
            }

            .card-icon {
                width: 64px;
                height: 64px;
                background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
                border-radius: var(--radius-md);
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: 1.5rem;
                font-size: 2rem;
            }

            .mission-card h3,
            .vision-card h3 {
                font-size: 1.75rem;
                font-weight: 600;
                margin-bottom: 1rem;
                color: var(--text-dark);
            }

            .mission-card p,
            .vision-card p {
                color: var(--text-light);
                line-height: 1.8;
                font-size: 1.05rem;
            }

            /* ========================================
               TEAM LEADER CARD - SHARED STYLE
            ======================================== */
            .team-leader-section {
                text-align: center;
                max-width: 750px;
                margin: 0 auto 5rem;
            }

            .leader-card {
                background: var(--white);
                border-radius: var(--radius-xl);
                padding: 3rem;
                box-shadow: var(--shadow-xl);
                border: 2px solid var(--primary-blue);
                position: relative;
                overflow: hidden;
                opacity: 0;
                transform: scale(0.95);
                transition: all var(--transition-slow);
            }

            .leader-card.reveal {
                opacity: 1;
                transform: scale(1);
            }

            .leader-card::after {
                content: '';
                position: absolute;
                top: -50%;
                right: -50%;
                width: 200%;
                height: 200%;
                background: radial-gradient(circle, rgba(37, 99, 235, 0.08) 0%, transparent 70%);
                pointer-events: none;
            }

            .leader-image-wrapper {
                position: relative;
                display: inline-block;
                margin-bottom: 2rem;
            }

            .leader-image-wrapper::before {
                content: '';
                position: absolute;
                inset: -10px;
                background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
                border-radius: var(--radius-lg);
                z-index: 0;
                opacity: 0.25;
            }

            .leader-card img {
                width: 240px;
                height: 300px;
                object-fit: cover;
                border-radius: var(--radius-lg);
                box-shadow: var(--shadow-lg);
                position: relative;
                z-index: 1;
                transition: transform var(--transition-normal);
            }

            .leader-card:hover img {
                transform: scale(1.05) rotate(-1deg);
            }

            .leader-card h3 {
                font-size: 2.2rem;
                font-weight: 700;
                color: var(--primary-blue);
                margin-bottom: 0.5rem;
            }

            .leader-role {
                font-size: 1.1rem;
                color: var(--text-light);
                font-weight: 600;
                margin-bottom: 1.5rem;
                text-transform: uppercase;
                letter-spacing: 0.05em;
            }

            .leader-card p {
                color: var(--text-medium);
                line-height: 1.8;
                font-size: 1.05rem;
            }

            /* ========================================
               TEAM GRID - SHARED STYLE
            ======================================== */
            .team-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
                gap: 2.5rem;
                margin-top: 3rem;
            }

            .team-member {
                background: var(--white);
                border-radius: var(--radius-lg);
                padding: 2.5rem 2rem;
                text-align: center;
                box-shadow: var(--shadow-md);
                transition: all var(--transition-normal);
                position: relative;
                overflow: hidden;
                opacity: 0;
                transform: translateY(30px);
            }

            .team-member.reveal {
                opacity: 1;
                transform: translateY(0);
            }

            .team-member::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                width: 0;
                height: 4px;
                background: linear-gradient(90deg, var(--primary-blue), var(--secondary-blue));
                transition: width var(--transition-normal);
            }

            .team-member:hover {
                transform: translateY(-12px);
                box-shadow: var(--shadow-xl);
            }

            .team-member:hover::before {
                width: 100%;
            }

            .member-image-wrapper {
                position: relative;
                display: inline-block;
                margin-bottom: 1.5rem;
            }

            .member-image-wrapper::before {
                content: '';
                position: absolute;
                inset: -6px;
                background: linear-gradient(135deg, var(--accent-blue), var(--primary-blue));
                border-radius: var(--radius-md);
                opacity: 0;
                transition: opacity var(--transition-normal);
            }

            .team-member:hover .member-image-wrapper::before {
                opacity: 0.2;
            }

            .team-member img {
                width: 200px;
                height: 240px;
                object-fit: cover;
                border-radius: var(--radius-md);
                border: 3px solid var(--gray-100);
                position: relative;
                transition: all var(--transition-normal);
            }

            .team-member:hover img {
                transform: scale(1.05);
                border-color: var(--primary-blue);
            }

            .team-member h3 {
                font-size: 1.5rem;
                font-weight: 600;
                color: var(--text-dark);
                margin-bottom: 0.5rem;
            }

            .team-member .role {
                font-size: 0.95rem;
                color: var(--primary-blue);
                font-weight: 600;
                margin-bottom: 1rem;
                text-transform: uppercase;
                letter-spacing: 0.05em;
            }

            .team-member p {
                color: var(--text-light);
                font-size: 0.95rem;
                line-height: 1.7;
            }

            /* ========================================
               VISUAL BREAK
            ======================================== */
            .visual-break {
                height: 450px;
                background: linear-gradient(rgba(37, 99, 235, 0.9), rgba(30, 64, 175, 0.9)), 
                            url('img/about photos/bg.jpeg') no-repeat center center fixed;
                background-size: cover;
                display: flex;
                align-items: center;
                justify-content: center;
                position: relative;
                overflow: hidden;
            }

            .visual-break::before {
                content: '';
                position: absolute;
                inset: 0;
                background: radial-gradient(circle at center, transparent 0%, rgba(0,0,0,0.3) 100%);
            }

            .visual-break-content {
                text-align: center;
                color: white;
                z-index: 2;
                max-width: 900px;
                padding: 2rem;
                position: relative;
            }

            .visual-break h2 {
                font-size: clamp(2rem, 4vw, 3.5rem);
                font-weight: 700;
                margin-bottom: 1.5rem;
                line-height: 1.2;
            }

            .visual-break p {
                font-size: clamp(1.1rem, 2vw, 1.4rem);
                opacity: 0.95;
                line-height: 1.8;
            }

            /* Animated decorative elements */
            .visual-break::after {
                content: '';
                position: absolute;
                width: 300px;
                height: 300px;
                border: 2px solid rgba(255,255,255,0.1);
                border-radius: 50%;
                top: -150px;
                right: -150px;
                animation: float 6s ease-in-out infinite;
            }

            /* ========================================
               PROJECT FEATURES
            ======================================== */
            .features-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 2rem;
                margin-top: 3rem;
            }

            .feature-card {
                background: var(--white);
                padding: 2.5rem;
                border-radius: var(--radius-lg);
                box-shadow: var(--shadow-sm);
                border: 1px solid var(--gray-200);
                transition: all var(--transition-normal);
                opacity: 0;
                transform: translateY(20px);
            }

            .feature-card.reveal {
                opacity: 1;
                transform: translateY(0);
            }

            .feature-card:hover {
                box-shadow: var(--shadow-lg);
                border-color: var(--primary-blue);
                transform: translateY(-8px);
            }

            .feature-icon {
                width: 56px;
                height: 56px;
                background: var(--light-blue);
                color: var(--primary-blue);
                border-radius: var(--radius-md);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.75rem;
                margin-bottom: 1.5rem;
                transition: all var(--transition-normal);
            }

            .feature-card:hover .feature-icon {
                background: var(--primary-blue);
                color: white;
                transform: scale(1.1) rotate(5deg);
            }

            .feature-card h4 {
                font-size: 1.4rem;
                font-weight: 600;
                color: var(--text-dark);
                margin-bottom: 1rem;
            }

            .feature-card p {
                color: var(--text-light);
                line-height: 1.7;
            }

            /* ========================================
               TIMELINE / JOURNEY
            ======================================== */
            .timeline {
                position: relative;
                max-width: 900px;
                margin: 4rem auto;
                padding: 2rem 0;
            }

            .timeline::before {
                content: '';
                position: absolute;
                left: 50%;
                top: 0;
                bottom: 0;
                width: 2px;
                background: var(--gray-300);
                transform: translateX(-50%);
            }

            .timeline-item {
                display: flex;
                margin-bottom: 4rem;
                position: relative;
                opacity: 0;
                transform: translateX(-30px);
                transition: all var(--transition-slow);
            }

            .timeline-item.reveal {
                opacity: 1;
                transform: translateX(0);
            }

            .timeline-item:nth-child(even) {
                flex-direction: row-reverse;
                transform: translateX(30px);
            }

            .timeline-item:nth-child(even).reveal {
                transform: translateX(0);
            }

            .timeline-content {
                flex: 1;
                background: var(--white);
                padding: 2rem;
                border-radius: var(--radius-lg);
                box-shadow: var(--shadow-md);
                margin: 0 2rem;
                position: relative;
                transition: all var(--transition-normal);
            }

            .timeline-content:hover {
                box-shadow: var(--shadow-lg);
                transform: translateY(-4px);
            }

            .timeline-marker {
                position: absolute;
                left: 50%;
                transform: translateX(-50%);
                width: 20px;
                height: 20px;
                background: var(--primary-blue);
                border: 4px solid var(--white);
                border-radius: 50%;
                box-shadow: var(--shadow-md);
                z-index: 2;
            }

            .timeline-content h4 {
                font-size: 1.5rem;
                font-weight: 600;
                color: var(--text-dark);
                margin-bottom: 0.5rem;
            }

            .timeline-date {
                font-size: 0.875rem;
                color: var(--primary-blue);
                font-weight: 600;
                margin-bottom: 1rem;
            }

            .timeline-content p {
                color: var(--text-light);
                line-height: 1.7;
            }

            .timeline-image {
                flex: 1;
                margin: 0 2rem;
            }

            .timeline-image img {
                width: 100%;
                height: 250px;
                object-fit: cover;
                border-radius: var(--radius-lg);
                box-shadow: var(--shadow-md);
            }

            /* ========================================
               MASONRY GALLERY
            ======================================== */
            .gallery-masonry {
                column-count: 3;
                column-gap: 1.5rem;
                margin-top: 3rem;
            }

            .gallery-item {
                break-inside: avoid;
                margin-bottom: 1.5rem;
                position: relative;
                overflow: hidden;
                border-radius: var(--radius-md);
                cursor: pointer;
                opacity: 0;
                transform: scale(0.95);
                transition: all var(--transition-normal);
            }

            .gallery-item.reveal {
                opacity: 1;
                transform: scale(1);
            }

            .gallery-item img {
                width: 100%;
                height: auto;
                display: block;
                border-radius: var(--radius-md);
                transition: transform var(--transition-slow);
            }

            .gallery-item:hover img {
                transform: scale(1.08);
            }

            .gallery-overlay {
                position: absolute;
                inset: 0;
                background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, transparent 60%);
                opacity: 0;
                transition: opacity var(--transition-normal);
                display: flex;
                align-items: flex-end;
                padding: 1.5rem;
            }

            .gallery-item:hover .gallery-overlay {
                opacity: 1;
            }

            .gallery-caption {
                color: white;
                font-size: 1rem;
                font-weight: 500;
            }

            /* ========================================
               MODAL IMPROVEMENTS
            ======================================== */
            .modal-content {
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(16px);
                -webkit-backdrop-filter: blur(16px);
                border: 1px solid rgba(255, 255, 255, 0.5);
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
                border-radius: 20px;
                overflow: hidden;
            }

            .modal-header {
                border-bottom: 1px solid rgba(0, 0, 0, 0.08);
                padding: 1.5rem 2rem;
            }

            .modal-title {
                font-weight: 600;
                font-size: 1.3rem;
                color: var(--primary-blue);
            }

            .modal-header .close {
                color: var(--text-dark);
                opacity: 1;
                font-size: 1.5rem;
                transition: transform var(--transition-fast);
            }

            .modal-header .close:hover {
                transform: rotate(90deg);
            }

            .modal-body {
                padding: 0;
            }

            .modal-body img {
                width: 100%;
                border-radius: 0;
            }

            /* ========================================
               SCROLL TO TOP BUTTON
            ======================================== */
            .scroll-to-top {
                position: fixed;
                bottom: 2rem;
                right: 2rem;
                width: 50px;
                height: 50px;
                background: var(--primary-blue);
                color: white;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                opacity: 0;
                visibility: hidden;
                transition: all var(--transition-normal);
                box-shadow: var(--shadow-lg);
                z-index: 1000;
            }

            .scroll-to-top.visible {
                opacity: 1;
                visibility: visible;
            }

            .scroll-to-top:hover {
                background: var(--primary-blue-dark);
                transform: translateY(-4px);
            }

            /* ========================================
               ANIMATIONS
            ======================================== */
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            @keyframes float {
                0%, 100% {
                    transform: translateY(0);
                }
                50% {
                    transform: translateY(-20px);
                }
            }

            /* Staggered animation delays */
            .team-member:nth-child(1) { transition-delay: 0.1s; }
            .team-member:nth-child(2) { transition-delay: 0.2s; }
            .team-member:nth-child(3) { transition-delay: 0.3s; }
            .team-member:nth-child(4) { transition-delay: 0.4s; }
            .team-member:nth-child(5) { transition-delay: 0.5s; }

            .gallery-item:nth-child(1) { transition-delay: 0.05s; }
            .gallery-item:nth-child(2) { transition-delay: 0.1s; }
            .gallery-item:nth-child(3) { transition-delay: 0.15s; }
            .gallery-item:nth-child(4) { transition-delay: 0.2s; }
            .gallery-item:nth-child(5) { transition-delay: 0.25s; }
            .gallery-item:nth-child(6) { transition-delay: 0.3s; }
            .gallery-item:nth-child(7) { transition-delay: 0.35s; }
            .gallery-item:nth-child(8) { transition-delay: 0.4s; }
            .gallery-item:nth-child(9) { transition-delay: 0.45s; }
            .gallery-item:nth-child(10) { transition-delay: 0.5s; }

            .feature-card:nth-child(1) { transition-delay: 0.1s; }
            .feature-card:nth-child(2) { transition-delay: 0.2s; }
            .feature-card:nth-child(3) { transition-delay: 0.3s; }
            .feature-card:nth-child(4) { transition-delay: 0.4s; }
            .feature-card:nth-child(5) { transition-delay: 0.5s; }
            .feature-card:nth-child(6) { transition-delay: 0.6s; }

            /* ========================================
               RESPONSIVE DESIGN
            ======================================== */
            @media (max-width: 1024px) {
                .gallery-masonry {
                    column-count: 2;
                }

                .timeline::before {
                    left: 30px;
                }

                .timeline-item,
                .timeline-item:nth-child(even) {
                    flex-direction: column;
                }

                .timeline-marker {
                    left: 30px;
                }

                .timeline-content,
                .timeline-image {
                    margin: 0 0 1rem 60px;
                }

                .team-grid {
                    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                }
            }

            @media (max-width: 768px) {
                .section {
                    padding: 4rem 0;
                }

                .hero-section {
                    min-height: 70vh;
                }

                .hero-stats {
                    gap: 2rem;
                }

                .gallery-masonry {
                    column-count: 1;
                }

                .mission-vision {
                    gap: 2rem;
                }

                .team-grid {
                    grid-template-columns: 1fr;
                    gap: 2rem;
                }

                .features-grid {
                    grid-template-columns: 1fr;
                }

                .visual-break {
                    height: 350px;
                }
            }

            @media (max-width: 480px) {
                .container {
                    padding: 0 1.5rem;
                }

                .hero-content {
                    padding: 1.5rem;
                }

                .leader-card img {
                    width: 200px;
                    height: 260px;
                }

                .team-member img {
                    width: 170px;
                    height: 210px;
                }

                .section-divider {
                    height: 80px;
                }
            }
        </style>
    </head>
    <body>
        <!-- Header and Navbar -->
        <?php include('helperFiles/header.php'); ?>

        <!-- ========================================
             HERO SECTION
        ======================================== -->
        <section class="hero-section">
            <div class="hero-bg"></div>
            <div class="hero-content">
                <h1>Building the Future of Laboratory Management</h1>
                <p class="subtitle">
                    From concept to reality—witness the evolution of innovation through two generations 
                    of passionate developers united by a singular vision.
                </p>
                <div class="hero-stats">
                    <div class="stat-item">
                        <span class="stat-number">5</span>
                        <span class="stat-label">Brilliant Minds</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">3</span>
                        <span class="stat-label">Development Cycles</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">∞</span>
                        <span class="stat-label">Possibilities</span>
                    </div>
                </div>
            </div>
        </section>
        <!-- ========================================
             GAMES SECTION
        ======================================== -->

        <div class="games-section">
            <section>
                <div class="games-section__header">
                    <span class="games-section__title">Now Playing</span>
                </div>
                <div class="games-section__now-playing" id="games-now-playing">Select a game →</div>
        
                <div class="games-section__frame-wrap">
                    <!-- Placeholder shown before any game is selected -->
                    <div class="games-section__placeholder" id="games-placeholder">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.82m5.84-2.56a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.63 2a14.98 14.98 0 00-3.16 9.75m9.12 2.62A14.89 14.89 0 014.5 9.75"/>
                        </svg>
                        <span>Choose a game from the list</span>
                    </div>
                    <iframe id="games-iframe" src="" title="Game" allowfullscreen
                            style="display:none;"></iframe>
                </div>
            </section>
        
            <aside>
                <div class="games-carousel__label">Games</div>
                <button class="games-carousel__arrow" id="games-arrow-up" aria-label="Scroll up">▲</button>
                <div class="games-carousel__track-wrap">
                    <div class="games-carousel__track" id="games-track">
                        <!-- Skeleton placeholders while loading -->
                        <div class="game-card-skeleton"></div>
                        <div class="game-card-skeleton"></div>
                        <div class="game-card-skeleton"></div>
                    </div>
                </div>
                <button class="games-carousel__arrow" id="games-arrow-down" aria-label="Scroll down">▼</button>
            </aside>
        </div>


        <!-- ========================================
             MISSION & VISION SECTION
        ======================================== -->
        <section class="section">
            <div class="container">
                <div class="section-header">
                    <span class="section-overline">Our Purpose</span>
                    <h2 class="section-title">Mission & Vision</h2>
                    <p class="section-description">
                        Driven by innovation and a commitment to excellence
                    </p>
                </div>

                <div class="mission-vision">
                    <div class="mission-card">
                        <div class="card-icon">🎯</div>
                        <h3>Our Mission</h3>
                        <p>
                            To develop an efficient, user-friendly laboratory equipment management system 
                            that simplifies the borrowing process, reduces administrative overhead, and 
                            empowers research personnel with seamless access to essential tools and resources.
                        </p>
                    </div>
                    <div class="vision-card">
                        <div class="card-icon">🚀</div>
                        <h3>Our Vision</h3>
                        <p>
                            To become the leading student-developed solution for laboratory management 
                            in science high schools nationwide, setting a new standard for innovation, 
                            collaboration, and technological excellence in educational institutions.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================
             SECTION 1: CURRENT DEVELOPMENT TEAM
        ======================================== -->
        <section class="section section-alt current-team team-section-wrapper">
            <div class="container">
                <div class="section-header">
                    <span class="section-badge">Current Development Team</span>
                    <span class="section-overline">Leading the Evolution</span>
                    <h2 class="section-title">The Minds Shaping Today</h2>
                    <p class="section-description">
                        Our current development team is refining and advancing the system with fresh perspectives, 
                        cutting-edge techniques, and unwavering dedication to excellence.
                    </p>
                </div>

                <!-- Team Leader: Gabriel James Valdez -->
                <div class="team-leader-section">
                    <div class="leader-card">
                        <div class="leader-image-wrapper">
                            <img src="img/about photos/gab.jpeg" alt="Gabriel James Valdez">
                        </div>
                        <h3>Gabriel James Valdez</h3>
                        <p class="leader-role">Head of Development & UI/UX Lead</p>
                        <p>
                            A visionary designer obsessed with human-centered design and creating intuitive experiences. 
                            Gabriel leads the current team with strategic thinking, ensuring every interface element serves 
                            both form and function. His leadership transforms complex systems into elegant solutions.
                        </p>
                    </div>
                </div>

                <!-- Team Members Grid -->
                <div class="team-grid">
                    <div class="team-member">
                        <div class="member-image-wrapper">
                            <img src="img/about photos/zyx.jpeg" alt="Zyx Leiabe A. Barangan">
                        </div>
                        <h3>Zyx Leiabe A. Barangan</h3>
                        <p class="role">Lead Developer & Architect</p>
                        <p>
                            A master of clean code and elegant solutions. Zyx brings technical excellence and innovative 
                            problem-solving to every challenge, transforming complex requirements into seamless functionality.
                        </p>
                    </div>

                    <div class="team-member">
                        <div class="member-image-wrapper">
                            <img src="img/about photos/rojan.jpeg" alt="Rojan Joefel C. Dumlao">
                        </div>
                        <h3>Rojan Joefel C. Dumlao</h3>
                        <p class="role">Backend Engineer & Systems Specialist</p>
                        <p>
                            The powerhouse behind the scenes. Rojan builds robust, scalable backend systems that handle 
                            complex data operations with efficiency and reliability, ensuring the platform runs flawlessly.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================
             VISUAL DIVIDER / TRANSITION
        ======================================== -->
        <div class="section-divider">
            <div class="divider-icon">
                ⚡
            </div>
        </div>

        <!-- ========================================
             VISUAL BREAK - TRANSITION MESSAGE
        ======================================== -->
        <section class="visual-break">
            <div class="visual-break-content">
                <h2>From Vision to Evolution</h2>
                <p>
                    Every great innovation begins with a bold idea. Before the current team refined the system, 
                    a group of pioneering students laid the foundation. Here's where it all started—the original 
                    concept that sparked a revolution in laboratory management.
                </p>
            </div>
        </section>

        <!-- ========================================
             SECTION 2: ORIGINAL AI-GENERATED TEAM
        ======================================== -->
        <section class="section original-team team-section-wrapper">
            <div class="container">
                <div class="section-header">
                    <span class="section-badge original">Original Concept Team</span>
                    <span class="section-overline">Where It All Began</span>
                    <h2 class="section-title">The Pioneers Who Started It All</h2>
                    <p class="section-description">
                        The original team that conceived and developed the first iteration of our laboratory 
                        management system. Their innovative vision and collaborative spirit set the foundation 
                        for everything that followed.
                    </p>
                </div>

                <!-- Original Team Leader -->
                <div class="team-leader-section">
                    <div class="leader-card">
                        <div class="leader-image-wrapper">
                            <img src="img/about photos/gab.jpeg" alt="Gabriel James Valdez">
                        </div>
                        <h3>Gabriel James Valdez</h3>
                        <p class="leader-role">Original Team Lead & UI/UX Designer</p>
                        <p>
                            The original architect of user experience. Gabriel's initial designs established the 
                            foundational principles of intuitive interaction that continue to guide the system today. 
                            His vision for human-centered design became the cornerstone of the project.
                        </p>
                    </div>
                </div>

                <!-- Original Team Members Grid -->
                <div class="team-grid">
                    <div class="team-member">
                        <div class="member-image-wrapper">
                            <img src="img/about photos/zyx.jpeg" alt="Zyx Leiabe A. Barangan">
                        </div>
                        <h3>Zyx Leiabe A. Barangan</h3>
                        <p class="role">Founder & Lead Developer</p>
                        <p>
                            The visionary who sparked the initial concept. Zyx's passion for solving real-world problems 
                            through technology birthed the entire project, establishing coding standards that remain 
                            influential to this day.
                        </p>
                    </div>

                    <div class="team-member">
                        <div class="member-image-wrapper">
                            <img src="img/about photos/ben.jpeg" alt="Christian Benedict U. Soy">
                        </div>
                        <h3>Christian Benedict U. Soy</h3>
                        <p class="role">Content Strategist & Documentation Lead</p>
                        <p>
                            The storyteller who gave the project its voice. Christian crafted compelling narratives 
                            and comprehensive documentation that made complex technical concepts accessible to all users.
                        </p>
                    </div>

                    <div class="team-member">
                        <div class="member-image-wrapper">
                            <img src="img/about photos/rojan.jpeg" alt="Rojan Joefel C. Dumlao">
                        </div>
                        <h3>Rojan Joefel C. Dumlao</h3>
                        <p class="role">Backend Engineer & Database Architect</p>
                        <p>
                            The systems architect who built the foundational infrastructure. Rojan's robust backend 
                            design established the scalable architecture that powers the platform's core functionality.
                        </p>
                    </div>

                    <div class="team-member">
                        <div class="member-image-wrapper">
                            <img src="img/about photos/veca.jpg" alt="Riveca T. Pamerol">
                        </div>
                        <h3>Riveca T. Pamerol</h3>
                        <p class="role">Design Contributor & UX Researcher</p>
                        <p>
                            A Public Health student who brought unique interdisciplinary insights. Riveca's fresh 
                            perspectives on user needs and accessibility helped shape an inclusive, user-first design approach.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================
             VISUAL BREAK - INNOVATION MESSAGE
        ======================================== -->
        <section class="visual-break">
            <div class="visual-break-content">
                <h2>Innovation Through Collaboration</h2>
                <p>
                    Two teams, one vision. From the pioneering spirit of the original developers to the 
                    refinement and evolution led by today's team—every line of code tells a story of 
                    dedication, creativity, and the relentless pursuit of excellence.
                </p>
            </div>
        </section>

        <!-- ========================================
             PROJECT FEATURES
        ======================================== -->
        <section class="section section-alt">
            <div class="container">
                <div class="section-header">
                    <span class="section-overline">What We Built</span>
                    <h2 class="section-title">Core Features</h2>
                    <p class="section-description">
                        A comprehensive system designed with users in mind, refined across two development cycles
                    </p>
                </div>

                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">⚡</div>
                        <h4>Real-Time Tracking</h4>
                        <p>
                            Monitor equipment availability and borrowing status in real-time, 
                            ensuring smooth operations and preventing scheduling conflicts.
                        </p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">🔒</div>
                        <h4>Secure Access</h4>
                        <p>
                            Role-based authentication and authorization ensure only authorized 
                            personnel can access and manage laboratory equipment.
                        </p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">📊</div>
                        <h4>Analytics Dashboard</h4>
                        <p>
                            Gain insights into equipment usage patterns, generate reports, 
                            and make data-driven decisions for better resource management.
                        </p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">📱</div>
                        <h4>Mobile Responsive</h4>
                        <p>
                            Access the system from any device—desktop, tablet, or smartphone—
                            with a seamless, optimized experience across all platforms.
                        </p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">🔔</div>
                        <h4>Smart Notifications</h4>
                        <p>
                            Automated reminders and notifications keep users informed about 
                            due dates, pending requests, and equipment availability.
                        </p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">♻️</div>
                        <h4>Easy Returns</h4>
                        <p>
                            Streamlined return process with automated check-ins and condition 
                            reporting to maintain equipment integrity and accountability.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================
             JOURNEY TIMELINE
        ======================================== -->
        <section class="section">
            <div class="container">
                <div class="section-header">
                    <span class="section-overline">Our Story</span>
                    <h2 class="section-title">The Development Journey</h2>
                    <p class="section-description">
                        From initial concept to current iteration—a timeline of innovation
                    </p>
                </div>

                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <h4>The Genesis</h4>
                            <p class="timeline-date">August 2024</p>
                            <p>
                                It all started with a simple observation: laboratory equipment management needed 
                                a digital revolution. The original team assembled, brainstormed, and committed 
                                to making a difference.
                            </p>
                        </div>
                        <div class="timeline-image">
                            <img src="img/about photos/1.jpg" alt="Team Formation">
                        </div>
                    </div>

                    <div class="timeline-item">
                        <div class="timeline-marker"></div>
                        <div class="timeline-image">
                            <img src="img/about photos/2.jpg" alt="Coding Camp">
                        </div>
                        <div class="timeline-content">
                            <h4>Intensive Learning</h4>
                            <p class="timeline-date">September 2024</p>
                            <p>
                                The original team underwent rigorous training in full-stack development, 
                                mastering technologies from React to Node.js, building the foundational 
                                skills needed for the project.
                            </p>
                        </div>
                    </div>

                    <div class="timeline-item">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <h4>Design & Prototyping</h4>
                            <p class="timeline-date">October 2024</p>
                            <p>
                                Wireframes evolved into interactive prototypes. The UI/UX team crafted 
                                every screen, button, and workflow with meticulous attention to user 
                                experience and accessibility.
                            </p>
                        </div>
                        <div class="timeline-image">
                            <img src="img/about photos/4.jpg" alt="Development Phase">
                        </div>
                    </div>

                    <div class="timeline-item">
                        <div class="timeline-marker"></div>
                        <div class="timeline-image">
                            <img src="img/about photos/5.jpg" alt="Testing Phase">
                        </div>
                        <div class="timeline-content">
                            <h4>Testing & Iteration</h4>
                            <p class="timeline-date">November 2024</p>
                            <p>
                                Rigorous testing cycles began. User feedback was collected, bugs were 
                                squashed, and features were refined. The system grew stronger with each iteration.
                            </p>
                        </div>
                    </div>

                    <div class="timeline-item">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <h4>First Launch</h4>
                            <p class="timeline-date">December 2024</p>
                            <p>
                                The original version went live. Faculty and students experienced the system 
                                for the first time, providing invaluable insights that would shape future development.
                            </p>
                        </div>
                        <div class="timeline-image">
                            <img src="img/about photos/3.jpg" alt="Project Launch">
                        </div>
                    </div>

                    <div class="timeline-item">
                        <div class="timeline-marker"></div>
                        <div class="timeline-image">
                            <img src="img/about photos/10.jpg" alt="Evolution">
                        </div>
                        <div class="timeline-content">
                            <h4>Evolution Begins</h4>
                            <p class="timeline-date">January 2025</p>
                            <p>
                                The current development team took over, bringing fresh perspectives and 
                                advanced techniques. They refined the codebase, enhanced performance, 
                                and expanded functionality.
                            </p>
                        </div>
                    </div>

                    <div class="timeline-item">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <h4>Continuous Innovation</h4>
                            <p class="timeline-date">Ongoing</p>
                            <p>
                                Today, the system continues to evolve. New features are added, performance 
                                is optimized, and user experience is constantly refined—always pushing 
                                toward excellence.
                            </p>
                        </div>
                        <div class="timeline-image">
                            <img src="img/about photos/12.jpg" alt="Continuous Development">
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================
             GALLERY SECTION
        ======================================== -->
        <section class="section section-alt">
            <div class="container">
                <div class="section-header">
                    <span class="section-overline">Moments Captured</span>
                    <h2 class="section-title">Behind the Scenes</h2>
                    <p class="section-description">
                        A visual journey through collaboration, creativity, and dedication
                    </p>
                </div>

                <div class="gallery-masonry">
                    <div class="gallery-item" data-img-src="img/about photos/4.jpg">
                        <img src="img/about photos/4.jpg" alt="Team Working">
                        <div class="gallery-overlay">
                            <span class="gallery-caption">Designing the Future</span>
                        </div>
                    </div>

                    <div class="gallery-item" data-img-src="img/about photos/6.jpeg">
                        <img src="img/about photos/6.jpeg" alt="Brainstorming Session">
                        <div class="gallery-overlay">
                            <span class="gallery-caption">Vision into Reality</span>
                        </div>
                    </div>

                    <div class="gallery-item" data-img-src="img/about photos/7.jpeg">
                        <img src="img/about photos/7.jpeg" alt="Team Collaboration">
                        <div class="gallery-overlay">
                            <span class="gallery-caption">Building Connections</span>
                        </div>
                    </div>

                    <div class="gallery-item" data-img-src="img/about photos/8.jpeg">
                        <img src="img/about photos/8.jpeg" alt="Celebrating Success">
                        <div class="gallery-overlay">
                            <span class="gallery-caption">Success in Unity</span>
                        </div>
                    </div>

                    <div class="gallery-item" data-img-src="img/about photos/9.jpeg">
                        <img src="img/about photos/9.jpeg" alt="Innovation Moments">
                        <div class="gallery-overlay">
                            <span class="gallery-caption">Moments of Innovation</span>
                        </div>
                    </div>

                    <div class="gallery-item" data-img-src="img/about photos/10.jpg">
                        <img src="img/about photos/10.jpg" alt="Team Brainstorming">
                        <div class="gallery-overlay">
                            <span class="gallery-caption">Creative Collaboration</span>
                        </div>
                    </div>

                    <div class="gallery-item" data-img-src="img/about photos/11.jpg">
                        <img src="img/about photos/11.jpg" alt="Planning Session">
                        <div class="gallery-overlay">
                            <span class="gallery-caption">Strategizing Together</span>
                        </div>
                    </div>

                    <div class="gallery-item" data-img-src="img/about photos/12.jpg">
                        <img src="img/about photos/12.jpg" alt="Hands-On Work">
                        <div class="gallery-overlay">
                            <span class="gallery-caption">Turning Ideas to Action</span>
                        </div>
                    </div>

                    <div class="gallery-item" data-img-src="img/about photos/13.jpg">
                        <img src="img/about photos/13.jpg" alt="Presentation">
                        <div class="gallery-overlay">
                            <span class="gallery-caption">Presenting with Passion</span>
                        </div>
                    </div>

                    <div class="gallery-item" data-img-src="img/about photos/14.jpg">
                        <img src="img/about photos/14.jpg" alt="Feedback Session">
                        <div class="gallery-overlay">
                            <span class="gallery-caption">Learning Through Feedback</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================
             FINAL VISUAL BREAK - CLOSING MESSAGE
        ======================================== -->
        <section class="visual-break">
            <div class="visual-break-content">
                <h2>The Journey Continues</h2>
                <p>
                    From the original visionaries who dared to dream, to the current innovators who 
                    continue to push boundaries—this is more than a project. It's a testament to what 
                    passionate students can achieve when they unite around a common goal. The future 
                    of laboratory management is here, and it's just getting started.
                </p>
            </div>
        </section>

        <!-- ========================================
             MODAL FOR GALLERY
        ======================================== -->
        <div class="modal fade" id="galleryModal" tabindex="-1" role="dialog" aria-labelledby="galleryModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="galleryModalLabel">Gallery Image</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <img src="" id="modalImage" alt="Gallery Image">
                    </div>
                </div>
            </div>
        </div>

        <!-- Scroll to Top Button -->
        <div class="scroll-to-top" id="scrollToTop">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="18 15 12 9 6 15"></polyline>
            </svg>
        </div>

        <!-- Footer -->
        <?php include 'helperFiles/footer.php'; ?>

        <script src="helperFiles/game-section.js"></script>
        <script>
            $(document).ready(function () {
                // ========================================
                // SCROLL REVEAL ANIMATIONS
                // ========================================
                const revealElements = () => {
                    const reveals = document.querySelectorAll('.section-header, .mission-card, .vision-card, .leader-card, .team-member, .gallery-item, .feature-card, .timeline-item');
                    
                    reveals.forEach(element => {
                        const windowHeight = window.innerHeight;
                        const elementTop = element.getBoundingClientRect().top;
                        const revealPoint = 100;

                        if (elementTop < windowHeight - revealPoint) {
                            element.classList.add('reveal');
                        }
                    });
                };

                // Run on load and scroll
                revealElements();
                window.addEventListener('scroll', revealElements);

                // ========================================
                // PARALLAX EFFECT ON HERO
                // ========================================
                window.addEventListener('scroll', () => {
                    const scrolled = window.pageYOffset;
                    const heroBg = document.querySelector('.hero-bg');
                    if (heroBg) {
                        heroBg.style.transform = `translateY(${scrolled * 0.5}px) scale(1.1)`;
                    }
                });

                // ========================================
                // GALLERY MODAL
                // ========================================
                $('.gallery-item').on('click', function () {
                    const imgSrc = $(this).data('img-src');
                    $('#modalImage').attr('src', imgSrc);
                    $('#galleryModal').modal('show');
                });

                // ========================================
                // SCROLL TO TOP BUTTON
                // ========================================
                const scrollToTopBtn = $('#scrollToTop');

                $(window).scroll(function() {
                    if ($(this).scrollTop() > 300) {
                        scrollToTopBtn.addClass('visible');
                    } else {
                        scrollToTopBtn.removeClass('visible');
                    }
                });

                scrollToTopBtn.on('click', function() {
                    $('html, body').animate({ scrollTop: 0 }, 600);
                });

                // ========================================
                // SMOOTH INTERNAL LINK SCROLLING
                // ========================================
                $('a[href^="#"]').on('click', function(e) {
                    const target = $(this.getAttribute('href'));
                    if(target.length) {
                        e.preventDefault();
                        $('html, body').stop().animate({
                            scrollTop: target.offset().top - 80
                        }, 800);
                    }
                });
            });
        </script>
    </body>
</html>