<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Lab Requests — Management Console</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet"/>
  <script src="server-table.js" defer></script>

<style>
/* ══════════════════════════════════════════════════════════════════
   CSS VARIABLES — Design Tokens
══════════════════════════════════════════════════════════════════ */
:root {
  --bg:           #f0f4f9;
  --surface:      #ffffff;
  --surface-2:    #f8fafc;
  --border:       #e1e8f0;
  --border-strong:#c8d5e3;

  --primary:      #1a56db;
  --primary-dark: #1447ba;
  --primary-light:#eff4ff;
  --primary-muted:#dde9ff;

  --success:      #0d9448;
  --success-bg:   #ecfdf5;
  --danger:       #dc2626;
  --danger-bg:    #fef2f2;
  --warning:      #b45309;
  --warning-bg:   #fffbeb;
  --info:         #0369a1;
  --info-bg:      #e0f2fe;
  --purple:       #6d28d9;
  --purple-bg:    #f5f3ff;

  --text:         #0f1d2e;
  --text-2:       #4a5e72;
  --text-3:       #8fa3b8;
  --text-inv:     #ffffff;

  --radius-sm:    6px;
  --radius:       10px;
  --radius-lg:    14px;
  --radius-xl:    20px;

  --shadow-sm:    0 1px 2px rgba(15,29,46,.06);
  --shadow:       0 2px 8px rgba(15,29,46,.07), 0 1px 2px rgba(15,29,46,.05);
  --shadow-md:    0 4px 20px rgba(15,29,46,.10), 0 1px 4px rgba(15,29,46,.06);
  --shadow-lg:    0 8px 40px rgba(15,29,46,.14), 0 2px 8px rgba(15,29,46,.08);

  --transition:   .2s cubic-bezier(.4,0,.2,1);
}

/* ══════════════════════════════════════════════════════════════════
   RESET & BASE
══════════════════════════════════════════════════════════════════ */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
  font-family: 'Plus Jakarta Sans', sans-serif;
  background: var(--bg);
  color: var(--text);
  font-size: 14px;
  line-height: 1.6;
  min-height: 100vh;
  /* Subtle grid texture */
  background-image:
    linear-gradient(rgba(26,86,219,.025) 1px, transparent 1px),
    linear-gradient(90deg, rgba(26,86,219,.025) 1px, transparent 1px);
  background-size: 32px 32px;
}

/* ══════════════════════════════════════════════════════════════════
   LAYOUT
══════════════════════════════════════════════════════════════════ */
.page-wrapper {
  max-width: 1400px;
  margin: 0 auto;
  padding: 32px 24px 64px;
}

/* ─── Page header row ─────────────────────────────────────────── */
.page-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}

.page-title-group {}
.page-title {
  font-size: 22px;
  font-weight: 700;
  color: var(--text);
  letter-spacing: -.3px;
  line-height: 1.2;
}
.page-sub {
  color: var(--text-2);
  font-size: 13px;
  margin-top: 4px;
}

/* ─── Card ────────────────────────────────────────────────────── */
.card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  overflow: hidden;
}

/* ════════════════════════════════════════════════════════════════
   GENERATE SUMMARY BUTTON
════════════════════════════════════════════════════════════════ */
.btn-generate-summary {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  background: var(--primary);
  color: #fff;
  border: none;
  border-radius: var(--radius);
  font-family: inherit;
  font-size: 13.5px;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 2px 8px rgba(26,86,219,.30);
  transition: background var(--transition), transform var(--transition), box-shadow var(--transition);
  white-space: nowrap;
}
.btn-generate-summary:hover {
  background: var(--primary-dark);
  box-shadow: 0 4px 16px rgba(26,86,219,.38);
  transform: translateY(-1px);
}
.btn-generate-summary:active { transform: translateY(0); }
.btn-generate-summary svg { flex-shrink: 0; }

/* ════════════════════════════════════════════════════════════════
   SERVER DATA TABLE — BASE STYLES
════════════════════════════════════════════════════════════════ */

/* ─── Toolbar ─────────────────────────────────────────────────── */
.sdt-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 16px 20px;
  border-bottom: 1px solid var(--border);
  flex-wrap: wrap;
  background: var(--surface);
}

.sdt-search-wrap {
  position: relative;
  flex: 1 1 260px;
  max-width: 340px;
}
.sdt-search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-3);
  pointer-events: none;
}
.sdt-search {
  width: 100%;
  padding: 9px 12px 9px 36px;
  border: 1.5px solid var(--border);
  border-radius: var(--radius);
  background: var(--surface-2);
  font-family: inherit;
  font-size: 13.5px;
  color: var(--text);
  outline: none;
  transition: border-color var(--transition), box-shadow var(--transition);
}
.sdt-search::placeholder { color: var(--text-3); }
.sdt-search:focus {
  border-color: var(--primary);
  background: #fff;
  box-shadow: 0 0 0 3px var(--primary-muted);
}

.sdt-toolbar-right {
  display: flex;
  align-items: center;
  gap: 12px;
}
.sdt-info {
  font-size: 12.5px;
  color: var(--text-2);
  white-space: nowrap;
}

.sdt-page-size {
  padding: 8px 10px;
  border: 1.5px solid var(--border);
  border-radius: var(--radius-sm);
  background: var(--surface-2);
  font-family: inherit;
  font-size: 13px;
  color: var(--text);
  cursor: pointer;
  outline: none;
  transition: border-color var(--transition);
}
.sdt-page-size:focus { border-color: var(--primary); }

/* ─── Table wrap & scroll ─────────────────────────────────────── */
.sdt-table-wrap {
  position: relative;
  overflow: hidden;
}
.sdt-table-scroll {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

/* ─── Loading overlay ─────────────────────────────────────────── */
.sdt-loading-overlay {
  position: absolute;
  inset: 0;
  background: rgba(255,255,255,.82);
  backdrop-filter: blur(3px);
  display: flex !important;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  z-index: 10;
  font-size: 13px;
  color: var(--text-2);
}
.sdt-spinner {
  width: 28px;
  height: 28px;
  border: 3px solid var(--primary-muted);
  border-top-color: var(--primary);
  border-radius: 50%;
  animation: sdt-spin .7s linear infinite;
}
@keyframes sdt-spin { to { transform: rotate(360deg); } }

/* ─── Table ───────────────────────────────────────────────────── */
.sdt-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13.5px;
}

.sdt-table thead {
  background: var(--surface-2);
  border-bottom: 1.5px solid var(--border);
}

.sdt-table th {
  padding: 12px 16px;
  text-align: left;
  font-size: 11.5px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .6px;
  color: var(--text-2);
  white-space: nowrap;
  user-select: none;
}

.sdt-table th.sdt-sortable {
  cursor: pointer;
  transition: color var(--transition), background var(--transition);
}
.sdt-table th.sdt-sortable:hover { color: var(--primary); background: var(--primary-light); }

.sdt-th-inner { display: inline-flex; align-items: center; gap: 6px; }

/* Sort indicator */
.sdt-sort-icon::after { content: '↕'; opacity: .3; font-size: 11px; }
.sdt-sort-asc  .sdt-sort-icon::after { content: '↑'; opacity: 1; color: var(--primary); }
.sdt-sort-desc .sdt-sort-icon::after { content: '↓'; opacity: 1; color: var(--primary); }

.sdt-table td {
  padding: 12px 16px;
  border-bottom: 1px solid var(--border);
  color: var(--text);
  vertical-align: top;
  max-width: 260px;
}

.sdt-row:last-child td { border-bottom: none; }

.sdt-row {
  transition: background var(--transition);
}
.sdt-row:hover { background: #f5f9ff; }

/* Mono for IDs */
.sdt-table td:first-child {
  font-family: 'JetBrains Mono', monospace;
  font-size: 12.5px;
  font-weight: 500;
  color: var(--text-2);
  white-space: nowrap;
}

/* ─── Badge ───────────────────────────────────────────────────── */
.sdt-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 10px;
  border-radius: 99px;
  font-size: 11.5px;
  font-weight: 600;
  white-space: nowrap;
  letter-spacing: .2px;
}
.sdt-badge::before {
  content: '';
  width: 6px; height: 6px;
  border-radius: 50%;
  background: currentColor;
  opacity: .7;
  flex-shrink: 0;
}
.badge-pending   { color: var(--warning); background: var(--warning-bg); }
.badge-approved  { color: var(--success); background: var(--success-bg); }
.badge-rejected  { color: var(--danger);  background: var(--danger-bg);  }
.badge-review    { color: var(--info);    background: var(--info-bg);    }
.badge-force     { color: var(--purple);  background: var(--purple-bg);  }
.badge-cancelled { color: var(--text-2);  background: var(--surface-2);  }
.badge-default   { color: var(--text-3);  background: var(--surface-2);  }

/* ─── Action buttons ──────────────────────────────────────────── */
.sdt-actions { display: flex; gap: 6px; flex-wrap: wrap; }

.sdt-btn {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 5px 10px;
  border: 1.5px solid transparent;
  border-radius: var(--radius-sm);
  font-family: inherit;
  font-size: 11.5px;
  font-weight: 600;
  cursor: pointer;
  transition: all var(--transition);
  white-space: nowrap;
}
.sdt-btn-review {
  color: var(--info);
  background: var(--info-bg);
  border-color: #bae6fd;
}
.sdt-btn-review:hover {
  background: #0ea5e9;
  color: #fff;
  border-color: transparent;
}
.sdt-btn-approve {
  color: var(--success);
  background: var(--success-bg);
  border-color: #a7f3d0;
}
.sdt-btn-approve:hover {
  background: var(--success);
  color: #fff;
  border-color: transparent;
}
.sdt-btn-reject {
  color: var(--danger);
  background: var(--danger-bg);
  border-color: #fecaca;
}
.sdt-btn-reject:hover {
  background: var(--danger);
  color: #fff;
  border-color: transparent;
}

/* ─── Expandable list ─────────────────────────────────────────── */
.sdt-expand-list {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 3px;
  min-width: 160px;
}
.sdt-expand-list li {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
}
.sdt-dot {
  width: 5px; height: 5px;
  border-radius: 50%;
  background: var(--primary);
  flex-shrink: 0;
  opacity: .6;
}
.sdt-expand-btn {
  margin-top: 2px;
  padding: 0;
  border: none;
  background: none;
  color: var(--primary);
  font-family: inherit;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: opacity var(--transition);
}
.sdt-expand-btn:hover { opacity: .75; }
.sdt-expand-extra { display: none; }
.sdt-expand-extra.sdt-open { display: block; }
.sdt-expand-extra ul { list-style: none; display: flex; flex-direction: column; gap: 3px; }
.sdt-toggle-wrap { list-style: none; }
.sdt-muted { color: var(--text-3); font-size: 13px; }

/* ─── Empty & error states ────────────────────────────────────── */
.sdt-empty-row td { padding: 0 !important; border: none !important; }
.sdt-empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 60px 20px;
  text-align: center;
  color: var(--text-3);
}
.sdt-empty-state svg { opacity: .4; }
.sdt-empty-state p {
  font-size: 15px;
  font-weight: 600;
  color: var(--text-2);
}
.sdt-empty-state small { font-size: 12.5px; }
.sdt-error-state { color: #f87171; }
.sdt-error-state p { color: var(--danger); }

/* ─── Footer & pagination ─────────────────────────────────────── */
.sdt-footer {
  padding: 14px 20px;
  border-top: 1px solid var(--border);
  background: var(--surface-2);
  display: flex;
  justify-content: flex-end;
}

.sdt-pagination {
  display: flex;
  align-items: center;
  gap: 4px;
}

.sdt-pg-btn {
  min-width: 34px;
  height: 34px;
  padding: 0 8px;
  border: 1.5px solid var(--border);
  border-radius: var(--radius-sm);
  background: var(--surface);
  color: var(--text-2);
  font-family: inherit;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all var(--transition);
}
.sdt-pg-btn:hover:not([disabled]):not(.active) {
  border-color: var(--primary);
  color: var(--primary);
  background: var(--primary-light);
}
.sdt-pg-btn.active {
  background: var(--primary);
  border-color: var(--primary);
  color: #fff;
  font-weight: 700;
  box-shadow: 0 2px 8px rgba(26,86,219,.28);
}
.sdt-pg-btn[disabled] { opacity: .35; cursor: not-allowed; }
.sdt-pg-btn.sdt-pg-nav { min-width: 34px; }
.sdt-pg-ellipsis {
  width: 28px;
  text-align: center;
  color: var(--text-3);
  font-size: 13px;
}

/* ════════════════════════════════════════════════════════════════
   MODAL — Generate Summary
════════════════════════════════════════════════════════════════ */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(10,18,28,.55);
  backdrop-filter: blur(6px);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  opacity: 0;
  pointer-events: none;
  transition: opacity .25s ease;
}
.modal-overlay.active {
  opacity: 1;
  pointer-events: auto;
}

.modal {
  background: var(--surface);
  border-radius: var(--radius-xl);
  box-shadow: var(--shadow-lg);
  width: 100%;
  max-width: 540px;
  border: 1px solid var(--border);
  transform: translateY(20px) scale(.97);
  transition: transform .3s cubic-bezier(.34,1.56,.64,1);
  overflow: hidden;
}
.modal-overlay.active .modal {
  transform: translateY(0) scale(1);
}

.modal-header {
  padding: 24px 28px 20px;
  border-bottom: 1px solid var(--border);
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}
.modal-title {
  font-size: 17px;
  font-weight: 700;
  color: var(--text);
  letter-spacing: -.2px;
}
.modal-sub {
  font-size: 12.5px;
  color: var(--text-2);
  margin-top: 3px;
}
.modal-close {
  width: 32px; height: 32px;
  border: none;
  background: var(--surface-2);
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-2);
  flex-shrink: 0;
  transition: background var(--transition), color var(--transition);
}
.modal-close:hover { background: var(--danger-bg); color: var(--danger); }

.modal-body {
  padding: 24px 28px;
  display: flex;
  flex-direction: column;
  gap: 24px;
}

/* ─── Timeframe quick-select ──────────────────────────────────── */
.field-label {
  display: block;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .7px;
  color: var(--text-2);
  margin-bottom: 10px;
}

.timeframe-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
}

.tf-btn {
  padding: 10px 12px;
  border: 1.5px solid var(--border);
  border-radius: var(--radius);
  background: var(--surface-2);
  font-family: inherit;
  font-size: 13px;
  font-weight: 600;
  color: var(--text-2);
  cursor: pointer;
  text-align: center;
  transition: all var(--transition);
}
.tf-btn:hover {
  border-color: var(--primary);
  color: var(--primary);
  background: var(--primary-light);
}
.tf-btn.selected {
  background: var(--primary);
  border-color: var(--primary);
  color: #fff;
  box-shadow: 0 2px 8px rgba(26,86,219,.25);
}

/* ─── Date pickers ────────────────────────────────────────────── */
.date-range-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.date-field {}
.date-field label {
  display: block;
  font-size: 12px;
  font-weight: 600;
  color: var(--text-2);
  margin-bottom: 6px;
}
.date-field input[type="date"] {
  width: 100%;
  padding: 9px 12px;
  border: 1.5px solid var(--border);
  border-radius: var(--radius);
  background: var(--surface-2);
  font-family: inherit;
  font-size: 13.5px;
  color: var(--text);
  outline: none;
  cursor: pointer;
  transition: border-color var(--transition), box-shadow var(--transition);
  appearance: none;
}
.date-field input[type="date"]:focus {
  border-color: var(--primary);
  background: #fff;
  box-shadow: 0 0 0 3px var(--primary-muted);
}

.modal-footer {
  padding: 16px 28px 24px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  border-top: 1px solid var(--border);
}

.btn-cancel {
  padding: 10px 20px;
  border: 1.5px solid var(--border);
  border-radius: var(--radius);
  background: var(--surface);
  font-family: inherit;
  font-size: 13.5px;
  font-weight: 600;
  color: var(--text-2);
  cursor: pointer;
  transition: all var(--transition);
}
.btn-cancel:hover { border-color: var(--border-strong); color: var(--text); }

.btn-confirm {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 22px;
  border: none;
  border-radius: var(--radius);
  background: var(--primary);
  font-family: inherit;
  font-size: 13.5px;
  font-weight: 600;
  color: #fff;
  cursor: pointer;
  box-shadow: 0 2px 8px rgba(26,86,219,.28);
  transition: background var(--transition), transform var(--transition);
}
.btn-confirm:hover { background: var(--primary-dark); transform: translateY(-1px); }
.btn-confirm:active { transform: translateY(0); }
.btn-confirm:disabled {
  opacity: .5;
  cursor: not-allowed;
  transform: none;
}

/* ════════════════════════════════════════════════════════════════
   REPORT SECTION
════════════════════════════════════════════════════════════════ */
.report-section {
  overflow: hidden;
  max-height: 0;
  opacity: 0;
  transform: translateY(-12px);
  transition:
    max-height .5s cubic-bezier(.4,0,.2,1),
    opacity .4s ease,
    transform .4s ease;
  margin-bottom: 0;
}
.report-section.visible {
  max-height: 2000px;
  opacity: 1;
  transform: translateY(0);
  margin-bottom: 24px;
}

/* ─── Report card ─────────────────────────────────────────────── */
.report-card {
  background: var(--surface);
  border: 1.5px solid var(--primary-muted);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-md);
  overflow: hidden;
}

.report-card-header {
  background: linear-gradient(135deg, #1a56db 0%, #1447ba 100%);
  padding: 22px 28px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
}

.report-title-group {}
.report-eyebrow {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: rgba(255,255,255,.65);
  margin-bottom: 4px;
}
.report-title {
  font-size: 18px;
  font-weight: 700;
  color: #fff;
  letter-spacing: -.2px;
}
.report-date-range {
  font-size: 12.5px;
  color: rgba(255,255,255,.75);
  margin-top: 4px;
}

.btn-print {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  border: 1.5px solid rgba(255,255,255,.35);
  border-radius: var(--radius);
  background: rgba(255,255,255,.15);
  font-family: inherit;
  font-size: 13.5px;
  font-weight: 600;
  color: #fff;
  cursor: pointer;
  backdrop-filter: blur(4px);
  transition: background var(--transition), border-color var(--transition), transform var(--transition);
  white-space: nowrap;
}
.btn-print:hover {
  background: rgba(255,255,255,.25);
  border-color: rgba(255,255,255,.6);
  transform: translateY(-1px);
}
.btn-print:active { transform: translateY(0); }

/* ─── Stat cards ──────────────────────────────────────────────── */
.report-stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0;
  border-bottom: 1.5px solid var(--border);
}

.stat-card {
  padding: 20px 24px;
  border-right: 1px solid var(--border);
  position: relative;
  overflow: hidden;
}
.stat-card:last-child { border-right: none; }
.stat-card::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
}
.stat-card.stat-total::before   { background: var(--primary); }
.stat-card.stat-approved::before{ background: var(--success); }
.stat-card.stat-pending::before { background: var(--warning); }
.stat-card.stat-rejected::before{ background: var(--danger); }

.stat-label {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .7px;
  color: var(--text-3);
  margin-bottom: 6px;
}
.stat-value {
  font-size: 28px;
  font-weight: 700;
  font-family: 'JetBrains Mono', monospace;
  color: var(--text);
  letter-spacing: -1px;
  line-height: 1;
}
.stat-card.stat-total   .stat-value { color: var(--primary); }
.stat-card.stat-approved .stat-value { color: var(--success); }
.stat-card.stat-pending  .stat-value { color: var(--warning); }
.stat-card.stat-rejected .stat-value { color: var(--danger);  }

.stat-sub {
  font-size: 11.5px;
  color: var(--text-3);
  margin-top: 4px;
}

/* ─── Report table container ──────────────────────────────────── */
.report-body {
  padding: 24px 28px;
}

.report-section-title {
  font-size: 13px;
  font-weight: 700;
  color: var(--text);
  letter-spacing: -.1px;
  margin-bottom: 14px;
  padding-bottom: 10px;
  border-bottom: 1.5px solid var(--border);
  display: flex;
  align-items: center;
  gap: 8px;
}
.report-section-title span {
  font-size: 11.5px;
  font-weight: 600;
  color: var(--text-3);
  font-weight: 400;
}

/* ─── Report table ────────────────────────────────────────────── */
.report-table-scroll { overflow-x: auto; }

.report-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 12.5px;
}

.report-table thead {
  background: var(--surface-2);
}
.report-table th {
  padding: 10px 14px;
  text-align: left;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .6px;
  color: var(--text-2);
  border-bottom: 1.5px solid var(--border);
  white-space: nowrap;
}
.report-table td {
  padding: 10px 14px;
  border-bottom: 1px solid var(--border);
  color: var(--text);
  vertical-align: middle;
}
.report-table tbody tr:last-child td { border-bottom: none; }
.report-table tbody tr:hover { background: #f5f9ff; }

/* Placeholder rows (no data yet) */
.report-placeholder {
  border: 2px dashed var(--border);
  border-radius: var(--radius);
  padding: 32px;
  text-align: center;
  color: var(--text-3);
  font-size: 13px;
  background: var(--surface-2);
  margin-top: 8px;
}
.report-placeholder strong { display: block; margin-bottom: 4px; color: var(--text-2); font-size: 14px; }

/* ─── Report table section spacing ───────────────────────────── */
.report-table-section + .report-table-section { margin-top: 32px; }

/* ════════════════════════════════════════════════════════════════
   PRINT STYLES
════════════════════════════════════════════════════════════════ */
@media print {
  body { background: #fff; }
  .page-top, .card, .modal-overlay { display: none !important; }
  .report-section {
    max-height: none !important;
    opacity: 1 !important;
    transform: none !important;
  }
  .report-card-header { background: #1a56db !important; -webkit-print-color-adjust: exact; }
  .btn-print { display: none !important; }
  .report-card { box-shadow: none; border: 1px solid #ddd; }
  .stat-card::before { -webkit-print-color-adjust: exact; }
}

/* ════════════════════════════════════════════════════════════════
   RESPONSIVE
════════════════════════════════════════════════════════════════ */
@media (max-width: 900px) {
  .report-stats { grid-template-columns: repeat(2, 1fr); }
  .stat-card:nth-child(2) { border-right: none; }
  .stat-card:nth-child(3) { border-top: 1px solid var(--border); }
  .stat-card:nth-child(4) { border-top: 1px solid var(--border); }
}

@media (max-width: 600px) {
  .page-wrapper { padding: 16px 12px 40px; }
  .page-top { flex-direction: column; align-items: stretch; }
  .btn-generate-summary { justify-content: center; }
  .sdt-toolbar { flex-direction: column; align-items: stretch; }
  .sdt-search-wrap { max-width: none; }
  .timeframe-grid { grid-template-columns: 1fr 1fr; }
  .date-range-row { grid-template-columns: 1fr; }
  .report-stats { grid-template-columns: 1fr 1fr; }
  .modal-footer { flex-direction: column; }
  .btn-cancel, .btn-confirm { width: 100%; justify-content: center; }
  .report-card-header { flex-direction: column; }
  .btn-print { width: 100%; justify-content: center; }
}

/* ════════════════════════════════════════════════════════════════
   ANIMATIONS
════════════════════════════════════════════════════════════════ */
@keyframes fadeSlideIn {
  from { opacity: 0; transform: translateY(6px); }
  to   { opacity: 1; transform: translateY(0);   }
}
.sdt-row {
  animation: fadeSlideIn .18s ease both;
}
</style>
</head>
<body>

<div class="page-wrapper">

  <!-- ── Page Top ──────────────────────────────────────────────── -->
  <div class="page-top">
    <div class="page-title-group">
      <h1 class="page-title">Lab Use Requests</h1>
      <p class="page-sub">Manage and review all laboratory usage requests.</p>
    </div>
    <button class="btn-generate-summary" id="btnOpenSummary">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
           stroke="currentColor" stroke-width="2.2">
        <rect x="3" y="3" width="18" height="18" rx="2"/>
        <path d="M3 9h18M9 21V9"/>
      </svg>
      Generate Summary
    </button>
  </div>

  <!-- ── Report Section (hidden until generated) ──────────────── -->
  <div class="report-section" id="reportSection">
    <div class="report-card">

      <!-- Header with Print button -->
      <div class="report-card-header">
        <div class="report-title-group">
          <div class="report-eyebrow">Lab Management System</div>
          <div class="report-title">Lab Requests Summary Report</div>
          <div class="report-date-range" id="reportDateRange">
            Period: — to —
          </div>
        </div>
        <button class="btn-print" id="btnPrint" onclick="window.print()">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
               stroke="currentColor" stroke-width="2">
            <path d="M6 9V2h12v7"/>
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
            <rect x="6" y="14" width="12" height="8"/>
          </svg>
          Print Report
        </button>
      </div>

      <!-- Summary stat cards -->
      <div class="report-stats">
        <div class="stat-card stat-total">
          <div class="stat-label">Total Requests</div>
          <div class="stat-value" id="statTotal">—</div>
          <div class="stat-sub">Within selected period</div>
        </div>
        <div class="stat-card stat-approved">
          <div class="stat-label">Approved</div>
          <div class="stat-value" id="statApproved">—</div>
          <div class="stat-sub">Incl. force approved</div>
        </div>
        <div class="stat-card stat-pending">
          <div class="stat-label">Pending / Review</div>
          <div class="stat-value" id="statPending">—</div>
          <div class="stat-sub">Awaiting action</div>
        </div>
        <div class="stat-card stat-rejected">
          <div class="stat-label">Rejected</div>
          <div class="stat-value" id="statRejected">—</div>
          <div class="stat-sub">Declined requests</div>
        </div>
      </div>

      <!-- Report table sections -->
      <div class="report-body">

        <div class="report-table-section">
          <div class="report-section-title">
            All Requests — Detail View
            <span>· Table generation coming soon</span>
          </div>
          <div class="report-table-scroll">
            <table class="report-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>ID No.</th>
                  <th>Requester</th>
                  <th>Lab</th>
                  <th>Grade / Section</th>
                  <th>Subject</th>
                  <th>Date &amp; Time</th>
                  <th>Materials</th>
                  <th>Supervisor</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody id="reportTableBody">
                <!-- Placeholder: replace with server data -->
              </tbody>
            </table>
          </div>
          <div class="report-placeholder" id="reportPlaceholder">
            <strong>Table data will appear here</strong>
            Report rows will be generated from the selected date range.
          </div>
        </div>

        <div class="report-table-section">
          <div class="report-section-title">
            Requests by Lab
            <span>· Table generation coming soon</span>
          </div>
          <div class="report-table-scroll">
            <table class="report-table">
              <thead>
                <tr>
                  <th>Lab Name</th>
                  <th>Total Uses</th>
                  <th>Approved</th>
                  <th>Pending</th>
                  <th>Rejected</th>
                  <th>Utilisation</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
          <div class="report-placeholder">
            <strong>Lab utilisation summary</strong>
            Breakdown by laboratory will appear here.
          </div>
        </div>

      </div><!-- /report-body -->
    </div><!-- /report-card -->
  </div><!-- /report-section -->

  <!-- ── Main Table Card ───────────────────────────────────────── -->
  <div class="card">
    <div id="requestsTable"></div>
  </div>

</div><!-- /page-wrapper -->

<!-- ══════════════════════════════════════════════════════════════
     MODAL — Generate Summary
══════════════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="summaryModal" role="dialog"
     aria-modal="true" aria-labelledby="modalTitle">
  <div class="modal">

    <div class="modal-header">
      <div>
        <div class="modal-title" id="modalTitle">Generate Summary Report</div>
        <div class="modal-sub">Select a date range for the report.</div>
      </div>
      <button class="modal-close" id="btnCloseModal" aria-label="Close">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2.5">
          <line x1="18" y1="6" x2="6" y2="18"/>
          <line x1="6"  y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    </div>

    <div class="modal-body">

      <!-- Quick timeframe selection -->
      <div>
        <span class="field-label">Quick Timeframe</span>
        <div class="timeframe-grid">
          <button class="tf-btn" data-tf="week">Past Week</button>
          <button class="tf-btn" data-tf="month">Past Month</button>
          <button class="tf-btn" data-tf="quarter">Past Quarter</button>
          <button class="tf-btn" data-tf="year">Past Year</button>
        </div>
      </div>

      <!-- Manual date range -->
      <div>
        <span class="field-label">Manual Date Range</span>
        <div class="date-range-row">
          <div class="date-field">
            <label for="dateStart">Start Date</label>
            <input type="date" id="dateStart"/>
          </div>
          <div class="date-field">
            <label for="dateEnd">End Date</label>
            <input type="date" id="dateEnd"/>
          </div>
        </div>
      </div>

    </div><!-- /modal-body -->

    <div class="modal-footer">
      <button class="btn-cancel" id="btnCancelModal">Cancel</button>
      <button class="btn-confirm" id="btnGenerateSummary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2.2">
          <path d="M9 11l3 3L22 4"/>
          <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
        </svg>
        Generate Summary
      </button>
    </div>

  </div><!-- /modal -->
</div><!-- /modal-overlay -->


<!-- ══════════════════════════════════════════════════════════════
     SCRIPTS
══════════════════════════════════════════════════════════════════ -->
<script>
/* ─── Initialise ServerDataTable ─────────────────────────────── */
const table = new ServerDataTable({
  tableId:         'requestsTable',
  endpoint:        '/api/get_requests.php',   /* ← point to your PHP file */
  defaultPageSize: 10,
  defaultSort:     'id',
  defaultSortDir:  'asc',

  columns: [
    { key: 'id',                 label: 'ID No.',             sortable: true },
    { key: 'requester_name',     label: 'Requester Name',     sortable: true },
    { key: 'lab_name',           label: 'Lab Name',           sortable: true },
    { key: 'grade_section',      label: 'Grade / Section',    sortable: true },
    { key: 'subject',            label: 'Subject',            sortable: true },
    { key: 'datetime_use',       label: 'Date & Time of Use', sortable: true },
    { key: 'materials',          label: 'Materials',          type: 'expandable', maxVisible: 3 },
    { key: 'teacher_supervisor', label: 'Teacher Supervisor', sortable: true },
    { key: 'status',             label: 'Status',             type: 'badge' },
    { key: 'actions',            label: 'Actions',            type: 'actions' },
  ],

  /** Handle action button clicks */
  onActionClick(action, id, row) {
    switch (action) {
      case 'review':
        console.log('Review request:', id, row);
        // e.g. openReviewModal(row);
        break;
      case 'force_approve':
        if (confirm(`Force approve request #${id}?`)) {
          console.log('Force approving:', id);
          // e.g. postAction('/api/approve.php', { id, force: true });
        }
        break;
      case 'reject':
        if (confirm(`Reject request #${id}?`)) {
          console.log('Rejecting:', id);
          // e.g. postAction('/api/reject.php', { id });
        }
        break;
    }
  },
});

document.addEventListener('DOMContentLoaded', () => {
  table.init();
  initModal();
  initSummaryReport();
});

/* ════════════════════════════════════════════════════════════════
   MODAL CONTROLLER
════════════════════════════════════════════════════════════════ */
function initModal() {
  const overlay = document.getElementById('summaryModal');
  const open    = document.getElementById('btnOpenSummary');
  const close   = document.getElementById('btnCloseModal');
  const cancel  = document.getElementById('btnCancelModal');

  const openModal = () => {
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
    document.getElementById('dateEnd').value = formatDate(new Date());
  };

  const closeModal = () => {
    overlay.classList.remove('active');
    document.body.style.overflow = '';
  };

  open.addEventListener('click', openModal);
  close.addEventListener('click', closeModal);
  cancel.addEventListener('click', closeModal);

  /* Close on backdrop click */
  overlay.addEventListener('click', e => {
    if (e.target === overlay) closeModal();
  });

  /* Close on Escape */
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && overlay.classList.contains('active')) closeModal();
  });
}

/* ════════════════════════════════════════════════════════════════
   QUICK TIMEFRAME BUTTONS
════════════════════════════════════════════════════════════════ */
document.querySelectorAll('.tf-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    /* Deselect all */
    document.querySelectorAll('.tf-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');

    const today = new Date();
    let startDate;

    switch (btn.dataset.tf) {
      case 'week':
        startDate = new Date(today);
        startDate.setDate(today.getDate() - 7);
        break;
      case 'month':
        startDate = new Date(today);
        startDate.setMonth(today.getMonth() - 1);
        break;
      case 'quarter':
        startDate = new Date(today);
        startDate.setMonth(today.getMonth() - 3);
        break;
      case 'year':
        startDate = new Date(today);
        startDate.setFullYear(today.getFullYear() - 1);
        break;
    }

    document.getElementById('dateStart').value = formatDate(startDate);
    document.getElementById('dateEnd').value   = formatDate(today);
  });
});

/* Deselect quick-select when user manually changes a date */
['dateStart', 'dateEnd'].forEach(id => {
  document.getElementById(id).addEventListener('input', () => {
    document.querySelectorAll('.tf-btn').forEach(b => b.classList.remove('selected'));
  });
});

/* ════════════════════════════════════════════════════════════════
   GENERATE SUMMARY ACTION
════════════════════════════════════════════════════════════════ */
function initSummaryReport() {
  document.getElementById('btnGenerateSummary').addEventListener('click', () => {
    const startVal = document.getElementById('dateStart').value;
    const endVal   = document.getElementById('dateEnd').value;

    if (!startVal || !endVal) {
      alert('Please select both a start and end date.');
      return;
    }
    if (startVal > endVal) {
      alert('Start date must be before end date.');
      return;
    }

    /* Close modal */
    document.getElementById('summaryModal').classList.remove('active');
    document.body.style.overflow = '';

    /* Update report header */
    const fmt = d => new Date(d + 'T00:00:00').toLocaleDateString('en-PH', {
      year: 'numeric', month: 'long', day: 'numeric',
    });
    document.getElementById('reportDateRange').textContent =
      `Period: ${fmt(startVal)} – ${fmt(endVal)}`;

    /* Reveal report section with animation */
    const section = document.getElementById('reportSection');
    section.classList.add('visible');
    section.scrollIntoView({ behavior: 'smooth', block: 'start' });

    /* ── Fetch summary stats from server ──────────────────────
       Replace this block with a real fetch() to a summary API.
       Using placeholder values here for demonstration.        */
    loadReportStats(startVal, endVal);
  });
}

/**
 * Loads report statistics.
 * Replace with: fetch(`/api/get_summary.php?start=${start}&end=${end}`)
 */
async function loadReportStats(start, end) {
  /* Placeholder — swap with real API call */
  const mockStats = { total: '—', approved: '—', pending: '—', rejected: '—' };

  document.getElementById('statTotal').textContent    = mockStats.total;
  document.getElementById('statApproved').textContent = mockStats.approved;
  document.getElementById('statPending').textContent  = mockStats.pending;
  document.getElementById('statRejected').textContent = mockStats.rejected;

  /*
  Real implementation:
  ─────────────────────────────────────────
  const res  = await fetch(`/api/get_summary.php?start=${start}&end=${end}`);
  const data = await res.json();
  document.getElementById('statTotal').textContent    = data.total;
  document.getElementById('statApproved').textContent = data.approved;
  document.getElementById('statPending').textContent  = data.pending;
  document.getElementById('statRejected').textContent = data.rejected;
  ─────────────────────────────────────────
  */
}

/* ════════════════════════════════════════════════════════════════
   UTILITY
════════════════════════════════════════════════════════════════ */
function formatDate(date) {
  const y = date.getFullYear();
  const m = String(date.getMonth() + 1).padStart(2, '0');
  const d = String(date.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
}
</script>
</body>
</html>