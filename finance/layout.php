<?php
/**
 * Shared HTML layout: <head>, sidebar, topbar
 * Usage: include with $pageTitle and $activePage set
 */
require_once __DIR__ . '/auth.php';
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?> — SchoolFinance</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.2.0/remixicon.min.css">
<link rel="stylesheet" href="assets/css/main.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js" defer></script>
<script src="assets/js/app.js" defer></script>
</head>
<body>
<div id="sidebarOverlay" class="sidebar-overlay"></div>

<div class="app-shell">
  <!-- Sidebar -->
  <nav class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <div class="brand-logo">
        <div class="brand-icon"><i class="ri-bank-line"></i></div>
        <div>
          <div class="brand-name">SchoolFinance</div>
          <div class="brand-sub">Finance Manager</div>
        </div>
      </div>
    </div>

    <div class="sidebar-nav">
      <div class="nav-section-label">Main</div>
      <a href="index.php" class="nav-item <?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>">
        <i class="ri-dashboard-line"></i> Dashboard
      </a>
      <a href="events.php" class="nav-item <?= ($activePage ?? '') === 'events' ? 'active' : '' ?>">
        <i class="ri-calendar-event-line"></i> Events / Projects
      </a>

      <div class="nav-section-label">Finance</div>
      <a href="income.php" class="nav-item <?= ($activePage ?? '') === 'income' ? 'active' : '' ?>">
        <i class="ri-arrow-up-circle-line"></i> Income
      </a>
      <a href="expenses.php" class="nav-item <?= ($activePage ?? '') === 'expenses' ? 'active' : '' ?>">
        <i class="ri-arrow-down-circle-line"></i> Expenses
      </a>
      <a href="transactions.php" class="nav-item <?= ($activePage ?? '') === 'transactions' ? 'active' : '' ?>">
        <i class="ri-list-check"></i> Transactions
      </a>

      <div class="nav-section-label">Reports</div>
      <a href="reports.php" class="nav-item <?= ($activePage ?? '') === 'reports' ? 'active' : '' ?>">
        <i class="ri-bar-chart-line"></i> Reports
      </a>
      <a href="public_dashboard.php" class="nav-item" target="_blank">
        <i class="ri-eye-line"></i> Public View
        <span class="nav-badge"><i class="ri-external-link-line"></i></span>
      </a>
    </div>

    <div class="sidebar-footer">
      <?php if ($user): ?>
      <div class="user-card" onclick="window.location='logout.php'">
        <div class="user-avatar"><?= strtoupper(substr($user['display_name'] ?? $user['username'], 0, 2)) ?></div>
        <div class="user-info">
          <div class="user-name"><?= htmlspecialchars($user['display_name'] ?? $user['username']) ?></div>
          <div class="user-role"><?= htmlspecialchars($user['role']) ?></div>
        </div>
        <i class="ri-logout-box-r-line" style="color:rgba(255,255,255,.4);font-size:15px;flex-shrink:0"></i>
      </div>
      <?php endif; ?>
    </div>
  </nav>

  <!-- Main Content -->
  <div class="main-content">
    <!-- Topbar -->
    <header class="topbar">
      <div class="topbar-left">
        <button class="menu-toggle" id="menuToggle"><i class="ri-menu-line"></i></button>
        <h1 class="page-title"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h1>
      </div>
      <div class="topbar-right">
        <button class="topbar-btn" id="themeToggle" title="Toggle dark mode"><i class="ri-moon-line"></i></button>
        <a href="transactions.php?filter=search" class="topbar-btn" title="Search">
          <i class="ri-search-line"></i>
        </a>
      </div>
    </header>

    <main class="page-content">
