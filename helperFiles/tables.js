/**
 * ServerDataTable
 * ─────────────────────────────────────────────────────────────────
 * Lightweight server-side data table engine. No dependencies.
 * Pure vanilla JS. Handles fetch, sort, search, pagination.
 * ─────────────────────────────────────────────────────────────────
 *
 * USAGE
 * ─────────────────────────────────────────────────────────────────
 * new ServerDataTable({
 *   tableId:         "myTable",
 *   endpoint:        "/api/get_requests.php",
 *   columns:         [ { key, label, sortable, type, maxVisible } ],
 *   defaultPageSize: 10,
 *   defaultSort:     "id",
 *   defaultSortDir:  "asc",
 *   onActionClick:   (action, id, rowData) => {},
 *   onRenderCell:    (col, row) => htmlString | undefined,
 * });
 *
 * COLUMN TYPES   → (none) text | badge | actions | expandable
 * AJAX PARAMS    → page, pageSize, search, sortColumn, sortDirection
 * RESPONSE JSON  → { data:[...], total:125, page:1, pageSize:10 }
 */

class ServerDataTable {
  /* ═══════════════════════════════════════════════════════════════
     CONSTRUCTOR
  ═══════════════════════════════════════════════════════════════ */
  constructor(config) {
    this.tableId         = config.tableId;
    this.endpoint        = config.endpoint;
    this.columns         = config.columns || [];
    this.defaultPageSize = config.defaultPageSize || 10;
    this.onActionClick   = config.onActionClick   || null;
    this.onRenderCell    = config.onRenderCell     || null;

    /* Single source of truth */
    this.state = {
      page:          1,
      pageSize:      this.defaultPageSize,
      search:        '',
      sortColumn:    config.defaultSort    || '',
      sortDirection: config.defaultSortDir || 'asc',
      total:         0,
      data:          [],
      loading:       false,
    };

    /* Cached DOM refs */
    this._container    = null;
    this._tbodyEl      = null;
    this._paginationEl = null;
    this._searchEl     = null;
    this._infoEl       = null;
    this._loadingEl    = null;
    this._searchTimer  = null;
  }

  /* ═══════════════════════════════════════════════════════════════
     PUBLIC API
  ═══════════════════════════════════════════════════════════════ */

  init() {
    this._container = document.getElementById(this.tableId);
    if (!this._container) {
      console.error(`[ServerDataTable] #${this.tableId} not found.`);
      return;
    }
    this._buildSkeleton();
    this._attachEvents();
    this.fetchData();
  }

  /** Reset to page 1 and reload — use after external filter changes. */
  refresh() {
    this.state.page = 1;
    this.fetchData();
  }

  /* ═══════════════════════════════════════════════════════════════
     FETCH
  ═══════════════════════════════════════════════════════════════ */

  async fetchData() {
    this._setLoading(true);

    const params = new URLSearchParams({
      page:          this.state.page,
      pageSize:      this.state.pageSize,
      search:        this.state.search,
      sortColumn:    this.state.sortColumn,
      sortDirection: this.state.sortDirection,
    });

    try {
      const res = await fetch(`${this.endpoint}?${params.toString()}`, {
        method:  'GET',
        headers: { Accept: 'application/json' },
      });

      if (!res.ok) throw new Error(`Server returned ${res.status} ${res.statusText}`);

      const json = await res.json();
      this.state.data     = json.data     || [];
      this.state.total    = json.total    || 0;
      this.state.page     = json.page     || 1;
      this.state.pageSize = json.pageSize || this.state.pageSize;

      this._renderRows();
      this._renderPagination();
      this._updateInfo();

    } catch (err) {
      console.error('[ServerDataTable]', err);
      this._renderError(err.message);
    } finally {
      this._setLoading(false);
    }
  }

  /* ═══════════════════════════════════════════════════════════════
     BUILD SKELETON (runs once on init)
  ═══════════════════════════════════════════════════════════════ */

  _buildSkeleton() {
    this._container.classList.add('sdt-root');
    this._container.innerHTML = `
      <div class="sdt-toolbar">
        <div class="sdt-search-wrap">
          <svg class="sdt-search-icon" width="15" height="15" viewBox="0 0 24 24"
               fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
          </svg>
          <input class="sdt-search" type="search"
                 placeholder="Search requests…" autocomplete="off"/>
        </div>
        <div class="sdt-toolbar-right">
          <span class="sdt-info"></span>
          <select class="sdt-page-size">
            <option value="10">10 / page</option>
            <option value="25">25 / page</option>
            <option value="50">50 / page</option>
            <option value="100">100 / page</option>
          </select>
        </div>
      </div>

      <div class="sdt-table-wrap">
        <div class="sdt-loading-overlay" style="display:none" aria-live="polite">
          <div class="sdt-spinner"></div>
          <span>Fetching data…</span>
        </div>
        <div class="sdt-table-scroll">
          <table class="sdt-table" role="grid">
            <thead><tr>${this._buildHeaders()}</tr></thead>
            <tbody class="sdt-tbody"></tbody>
          </table>
        </div>
      </div>

      <div class="sdt-footer">
        <div class="sdt-pagination"></div>
      </div>`;

    this._tbodyEl      = this._container.querySelector('.sdt-tbody');
    this._paginationEl = this._container.querySelector('.sdt-pagination');
    this._searchEl     = this._container.querySelector('.sdt-search');
    this._infoEl       = this._container.querySelector('.sdt-info');
    this._loadingEl    = this._container.querySelector('.sdt-loading-overlay');

    this._container.querySelector('.sdt-page-size').value = this.state.pageSize;
  }

  _buildHeaders() {
    return this.columns.map(col => {
      const active = this.state.sortColumn === col.key;
      const dir    = active ? `sdt-sort-${this.state.sortDirection}` : '';
      const icon   = col.sortable
        ? `<span class="sdt-sort-icon" aria-hidden="true"></span>` : '';
      return `<th class="${col.sortable ? 'sdt-sortable' : ''} ${dir}"
                  data-key="${col.key}"
                  ${col.sortable ? 'tabindex="0"' : ''}>
                <span class="sdt-th-inner">${col.label}${icon}</span>
              </th>`;
    }).join('');
  }

  /* ═══════════════════════════════════════════════════════════════
     RENDER — ROWS
  ═══════════════════════════════════════════════════════════════ */

  _renderRows() {
    if (!this.state.data.length) {
      this._tbodyEl.innerHTML = `
        <tr class="sdt-empty-row">
          <td colspan="${this.columns.length}">
            <div class="sdt-empty-state">
              <svg width="48" height="48" viewBox="0 0 24 24" fill="none"
                   stroke="currentColor" stroke-width="1">
                <rect x="3" y="3" width="18" height="18" rx="2"/>
                <path d="M3 9h18M9 21V9"/>
              </svg>
              <p>No results found</p>
              <small>Try adjusting your search or filters.</small>
            </div>
          </td>
        </tr>`;
      return;
    }

    this._tbodyEl.innerHTML = this.state.data.map((row, i) => `
      <tr class="sdt-row" data-row-index="${i}">
        ${this.columns.map(col =>
          `<td data-label="${col.label}">${this._renderCell(col, row)}</td>`
        ).join('')}
      </tr>`).join('');
  }

  /* ─── Cell Dispatcher ──────────────────────────────────────── */

  _renderCell(col, row) {
    if (this.onRenderCell) {
      const custom = this.onRenderCell(col, row);
      if (custom !== undefined) return custom;
    }
    switch (col.type) {
      case 'badge':      return this._renderBadge(row[col.key]);
      case 'actions':    return this._renderActions(row);
      case 'expandable': return this._renderExpandable(row[col.key], col.maxVisible ?? 3);
      default:           return `<span>${this._escape(row[col.key] ?? '—')}</span>`;
    }
  }

  /* ─── Badge ────────────────────────────────────────────────── */

  _renderBadge(status) {
    if (!status) return '<span class="sdt-badge badge-default">—</span>';
    const map = {
      'pending':        'badge-pending',
      'approved':       'badge-approved',
      'rejected':       'badge-rejected',
      'under review':   'badge-review',
      'force approved': 'badge-force',
      'cancelled':      'badge-cancelled',
    };
    const cls = map[status.toLowerCase()] || 'badge-default';
    return `<span class="sdt-badge ${cls}">${this._escape(status)}</span>`;
  }

  /* ─── Actions ──────────────────────────────────────────────── */

  _renderActions(row) {
    return `
      <div class="sdt-actions">
        <button class="sdt-btn sdt-btn-review"
                data-action="review" data-id="${row.id}" title="Review">
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none"
               stroke="currentColor" stroke-width="2.2">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
            <circle cx="12" cy="12" r="3"/>
          </svg>
          Review
        </button>
        <button class="sdt-btn sdt-btn-approve"
                data-action="force_approve" data-id="${row.id}" title="Force Approve">
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none"
               stroke="currentColor" stroke-width="2.5">
            <polyline points="20 6 9 17 4 12"/>
          </svg>
          Force
        </button>
        <button class="sdt-btn sdt-btn-reject"
                data-action="reject" data-id="${row.id}" title="Reject">
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none"
               stroke="currentColor" stroke-width="2.5">
            <line x1="18" y1="6" x2="6" y2="18"/>
            <line x1="6"  y1="6" x2="18" y2="18"/>
          </svg>
          Reject
        </button>
      </div>`;
  }

  /* ─── Expandable list ──────────────────────────────────────── */

  _renderExpandable(items, max) {
    if (!Array.isArray(items) || !items.length)
      return '<span class="sdt-muted">—</span>';

    const visible = items.slice(0, max);
    const hidden  = items.slice(max);
    const uid     = `sdt-x-${Math.random().toString(36).slice(2, 8)}`;

    let html = `<ul class="sdt-expand-list">`;
    visible.forEach(item => {
      html += `<li><span class="sdt-dot"></span>${this._escape(item)}</li>`;
    });

    if (hidden.length) {
      html += `
        <li class="sdt-toggle-wrap">
          <button class="sdt-expand-btn" type="button"
                  onclick="
                    const el = document.getElementById('${uid}');
                    const open = el.classList.toggle('sdt-open');
                    this.innerHTML = open
                      ? '&#9650; Show less'
                      : '&#9660; +${hidden.length} more';
                  ">&#9660; +${hidden.length} more</button>
        </li>
        <li id="${uid}" class="sdt-expand-extra">
          <ul>${hidden.map(i =>
            `<li><span class="sdt-dot"></span>${this._escape(i)}</li>`
          ).join('')}</ul>
        </li>`;
    }

    return html + `</ul>`;
  }

  /* ═══════════════════════════════════════════════════════════════
     RENDER — PAGINATION
  ═══════════════════════════════════════════════════════════════ */

  _renderPagination() {
    const totalPages = Math.ceil(this.state.total / this.state.pageSize);
    if (totalPages <= 1) { this._paginationEl.innerHTML = ''; return; }

    const cur   = this.state.page;
    const pages = this._getPageRange(cur, totalPages);

    let html = `<button class="sdt-pg-btn sdt-pg-nav" data-page="${cur - 1}"
                         ${cur <= 1 ? 'disabled' : ''} aria-label="Previous">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                       stroke="currentColor" stroke-width="2.5">
                    <polyline points="15 18 9 12 15 6"/>
                  </svg>
                </button>`;

    pages.forEach(p => {
      if (p === '…') {
        html += `<span class="sdt-pg-ellipsis">…</span>`;
      } else {
        html += `<button class="sdt-pg-btn ${p === cur ? 'active' : ''}"
                         data-page="${p}" aria-label="Page ${p}">${p}</button>`;
      }
    });

    html += `<button class="sdt-pg-btn sdt-pg-nav" data-page="${cur + 1}"
                      ${cur >= totalPages ? 'disabled' : ''} aria-label="Next">
               <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5">
                 <polyline points="9 18 15 12 9 6"/>
               </svg>
             </button>`;

    this._paginationEl.innerHTML = html;
  }

  _getPageRange(cur, total) {
    if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
    if (cur <= 4)         return [1, 2, 3, 4, 5, '…', total];
    if (cur >= total - 3) return [1, '…', total - 4, total - 3, total - 2, total - 1, total];
    return [1, '…', cur - 1, cur, cur + 1, '…', total];
  }

  /* ═══════════════════════════════════════════════════════════════
     INFO LINE & ERROR STATE
  ═══════════════════════════════════════════════════════════════ */

  _updateInfo() {
    if (!this.state.total) { this._infoEl.textContent = ''; return; }
    const start = (this.state.page - 1) * this.state.pageSize + 1;
    const end   = Math.min(start + this.state.pageSize - 1, this.state.total);
    this._infoEl.textContent =
      `Showing ${start}–${end} of ${this.state.total.toLocaleString()}`;
  }

  _renderError(msg) {
    this._tbodyEl.innerHTML = `
      <tr class="sdt-empty-row">
        <td colspan="${this.columns.length}">
          <div class="sdt-empty-state sdt-error-state">
            <svg width="44" height="44" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1">
              <circle cx="12" cy="12" r="10"/>
              <line x1="12" y1="8" x2="12" y2="12"/>
              <circle cx="12" cy="16" r=".5" fill="currentColor"/>
            </svg>
            <p>Failed to load data</p>
            <small>${this._escape(msg)}</small>
          </div>
        </td>
      </tr>`;
  }

  /* ═══════════════════════════════════════════════════════════════
     EVENTS
  ═══════════════════════════════════════════════════════════════ */

  _attachEvents() {
    /* Search — debounced 300 ms */
    this._searchEl.addEventListener('input', e => {
      clearTimeout(this._searchTimer);
      this._searchTimer = setTimeout(() => {
        this.state.search = e.target.value.trim();
        this.state.page   = 1;
        this.fetchData();
      }, 300);
    });

    this._searchEl.addEventListener('keydown', e => {
      if (e.key === 'Enter') {
        clearTimeout(this._searchTimer);
        this.state.search = e.target.value.trim();
        this.state.page   = 1;
        this.fetchData();
      }
    });

    /* Delegated click */
    this._container.addEventListener('click', e => {
      /* Sort */
      const th = e.target.closest('th.sdt-sortable');
      if (th) {
        const key = th.dataset.key;
        this.state.sortDirection = this.state.sortColumn === key
          ? (this.state.sortDirection === 'asc' ? 'desc' : 'asc')
          : 'asc';
        this.state.sortColumn = key;
        this.state.page       = 1;
        this._refreshHeaders();
        this.fetchData();
        return;
      }

      /* Pagination */
      const pgBtn = e.target.closest('.sdt-pg-btn:not([disabled])');
      if (pgBtn) {
        this.state.page = parseInt(pgBtn.dataset.page, 10);
        this.fetchData();
        return;
      }

      /* Action buttons */
      const actionBtn = e.target.closest('[data-action]');
      if (actionBtn && this.onActionClick) {
        this.onActionClick(
          actionBtn.dataset.action,
          actionBtn.dataset.id,
          this._getRowData(actionBtn),
        );
      }
    });

    /* Keyboard sort */
    this._container.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ' ') {
        const th = e.target.closest('th.sdt-sortable');
        if (th) { e.preventDefault(); th.click(); }
      }
    });

    /* Page-size */
    this._container.querySelector('.sdt-page-size')
      .addEventListener('change', e => {
        this.state.pageSize = parseInt(e.target.value, 10);
        this.state.page     = 1;
        this.fetchData();
      });
  }

  _refreshHeaders() {
    this._container.querySelectorAll('th[data-key]').forEach(th => {
      th.classList.remove('sdt-sort-asc', 'sdt-sort-desc');
      if (th.dataset.key === this.state.sortColumn) {
        th.classList.add(`sdt-sort-${this.state.sortDirection}`);
      }
    });
  }

  _getRowData(el) {
    const tr  = el.closest('tr[data-row-index]');
    const idx = tr ? parseInt(tr.dataset.rowIndex, 10) : -1;
    return idx >= 0 ? (this.state.data[idx] || null) : null;
  }

  /* ═══════════════════════════════════════════════════════════════
     LOADING
  ═══════════════════════════════════════════════════════════════ */

  _setLoading(on) {
    this.state.loading = on;
    this._loadingEl.style.display = on ? 'flex' : 'none';
    this._container
      .querySelectorAll('.sdt-pg-btn, .sdt-page-size, .sdt-search')
      .forEach(el => { el.disabled = on; });
  }

  /* ═══════════════════════════════════════════════════════════════
     UTILITY
  ═══════════════════════════════════════════════════════════════ */

  _escape(str) {
    if (str == null) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }
}