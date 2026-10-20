/**
 * laboratory-card.js
 * Reusable Laboratory Card Component System
 * Science Laboratory Reservation System
 *
 * Dependencies (loaded by layout):
 *   - Bootstrap 5
 *   - Bootstrap Icons
 *   - SweetAlert2
 *
 * Variants:
 *   createLabCard(data, "complete")  → client catalog card (color bar, Request button)
 *   createLabCard(data, "compact")   → client dashboard widget (color bar, Request button)
 *   createLabCard(data, "list")      → admin list row (color badge + hex, Edit/Remove/Toggle)
 */

'use strict';

/* ─── INTERNAL STATE STORE ─────────────────────────────────────
   Tracks runtime availability per lab ID so all rendered
   instances of the same lab stay in sync on toggle.
──────────────────────────────────────────────────────────────── */
const _labCardState = {};
const _isAdminMode = (typeof isAdmin !== 'undefined' && isAdmin) || window.location.pathname.includes('admin');

/* ─── UTILITY: HTML ESCAPE ─────────────────────────────────── */
function _escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
}

/* ─── SHARED FRAGMENTS ─────────────────────────────────────── */

/**
 * Availability badge (used on all variants).
 */
function _availabilityBadge(isAvailable) {
    const cls  = isAvailable ? 'available'   : 'unavailable';
    const text = isAvailable ? 'Available'   : 'Unavailable';
    return `<span class="lab-badge-availability ${cls}">${text}</span>`;
}

/**
 * Color badge with hex code — admin list card only.
 */
function _colorBadge(color) {
    return `
        <span class="lab-badge-color">
            <span class="lab-badge-color__dot" style="background:${_escapeHtml(color)};"></span>
            ${_escapeHtml(color)}
        </span>`;
}

/**
 * Pending requests badge.
 */
function _pendingBadge(count) {
    const hasPending = count > 0;
    return `
        <span class="lab-badge-pending ${hasPending ? 'has-pending' : ''}">
            <i class="bi bi-clock"></i>
            ${_escapeHtml(String(count))} pending
        </span>`;
}

/**
 * Color accent bar — replaces explicit color display on client cards.
 * Rendered as a thin stripe at the very top of the card.
 */
function _colorBar(color) {
    return `<div class="lab-card__color-bar" style="background:${_escapeHtml(color)};"></div>`;
}

/**
 * Displayed width of the card image per variant (matches laboratory-card.css), for `sizes`.
 * Generous on purpose: a too-large guess only costs bytes, a too-small one costs sharpness.
 */
const _LAB_IMAGE_SIZES = {
    complete: '(max-width: 768px) 95vw, (max-width: 1100px) 40vw, 30vw',
    compact:  '(max-width: 480px) 95vw, (max-width: 768px) 50vw, 300px',
    list:     '110px',
};

/**
 * How many cards (in render order) are likely above the fold for a variant:
 * their images load eagerly, the rest stay lazy.
 */
function _eagerImageCount(variant) {
    const w = window.innerWidth || document.documentElement.clientWidth || 1024;
    if (variant === 'list') return 6;                  // small rows, several fit on screen
    if (variant === 'compact') return w <= 768 ? 2 : 4;
    if (w <= 768) return 1;                            // complete: one column on small screens
    return Math.max(1, Math.floor((w * 0.8 + 20) / 300)); // complete: first grid row
}

/**
 * Card image. When the page supplies WebP mirror URLs (imageWebp640 / imageWebp1280 / imageWebp + imageWidth),
 * renders <picture> with a WebP srcset and the original <img> as fallback; otherwise a plain <img>.
 * The <img> always keeps the class lab-card__image (pages bind the gallery click to it).
 * `index` is the card's position in the gallery: the first visible cards load eagerly and the
 * very first gets fetchpriority="high" (it is the LCP element); without an index the image is lazy.
 */
function _labImage(image, name, data, variant, index) {
    const eager = typeof index === 'number' && index < _eagerImageCount(variant);
    const loadAttrs = eager ? (index === 0 ? ' fetchpriority="high"' : '') : ' loading="lazy"';
    const w = parseInt(data.imageWidth, 10);
    const h = parseInt(data.imageHeight, 10);
    const sizeAttrs = (w > 0 && h > 0) ? ` width="${w}" height="${h}"` : '';
    const img = `<img class="lab-card__image" src="${_escapeHtml(image)}" alt="${_escapeHtml(name)}"${sizeAttrs}${loadAttrs}>`;

    const candidates = [];
    if (data.imageWebp640) candidates.push(`${data.imageWebp640} 640w`);
    if (data.imageWebp1280) candidates.push(`${data.imageWebp1280} 1280w`);
    if (w > 0) {
        const full = data.imageWebp || (data.imageWebp640 ? data.imageOriginal : null);
        if (full) candidates.push(`${full} ${w}w`);
    }
    if (!candidates.length || !(w > 0)) return img;

    const sizes = _LAB_IMAGE_SIZES[variant] || _LAB_IMAGE_SIZES.complete;
    return `<picture class="lab-card__picture"><source type="image/webp" srcset="${_escapeHtml(candidates.join(', '))}" sizes="${sizes}">${img}</picture>`;
}

/**
 * Image or icon placeholder.
 * The placeholder background matches the lab color on client cards.
 */
function _imageOrPlaceholder(image, name, bgColor, data, variant, index) {
    if (image) {
        return _labImage(image, name, data || {}, variant, index);
    }
    const bg = bgColor ? `style="background:linear-gradient(135deg,${_escapeHtml(bgColor)}22,${_escapeHtml(bgColor)}55);"` : '';
    return `<div class="lab-card__image-placeholder" ${bg}><i class="bi bi-buildings"></i></div>`;
}

/**
 * Custom availability toggle slider (admin only).
 * Uses the provided .switch / .slider CSS — no Bootstrap switch.
 */
function _toggleSwitch(id, isAvailable) {
    const checked = isAvailable ? 'checked' : '';
    const escapedId = _escapeHtml(String(id));
    return `
        <label class="switch" title="Toggle availability">
            <input
                type="checkbox"
                class="lab-toggle-chk"
                data-scilab="${escapedId}"
                ${checked}
                onchange="toggleLab(this)"
            >
            <span class="slider"></span>
        </label>`;
}

/**
 * Admin action buttons: Edit + Remove.
 */
function _actionButtons(id) {
    const escapedId = _escapeHtml(String(id)).replace(/'/g, "\\'");
    return `
        <div class="lab-card__actions">
            <button class="btn-liquid btn-liquid-info" onclick="openEditModal('${escapedId}')" title="Edit laboratory">
                <i class="bi bi-pencil me-1"></i>Edit
            </button>
            <button class="btn-liquid btn-liquid-danger" onclick="openRemoveModal('${escapedId}')" title="Remove laboratory">
                <i class="bi bi-trash me-1"></i>Remove
            </button>
        </div>`;
}

/**
 * Client Request button.
 * Disabled (non-interactive) when lab is unavailable.
 */
function _requestButton(id, isAvailable) {
    const disabled = isAvailable ? '' : 'disabled';
    const label    = isAvailable ? '<i class="bi bi-calendar-plus me-1"></i>Request' : '<i class="bi bi-x-circle me-1"></i>Unavailable';
    const escapedId = _escapeHtml(String(id)).replace(/'/g, "\\'");
    return `
        <button
            class="btn-lab-request"
            onclick="requestLaboratory('${escapedId}')"
            ${disabled}
            title="${isAvailable ? 'Reserve this laboratory' : 'This laboratory is currently unavailable'}"
        >${label}</button>`;
}

/* ─── ═══════════════════════════════════════════════════════ ── */
/* ─── VARIANT 1: COMPLETE CARD (CLIENT) ─────────────────────── */
/* ─── ═══════════════════════════════════════════════════════ ── */

function _buildCompleteCard(data, isAvailable, index) {
    const id = _escapeHtml(String(data.id));
    return `
        <div class="lab-card lab-card--complete ${isAvailable ? '' : 'unavailable'}"
             id="lab-card-complete-${id}"
             data-lab-id="${id}"
             data-variant="complete"
             data-aos="fade-up"
             data-aos-duration="400">

            <!-- Color accent bar (represents lab color) -->
            ${_colorBar(data.color)}

            <!-- 16:9 Image -->
            <div class="lab-card__image-wrap">
                ${_imageOrPlaceholder(data.image, data.laboratoryName, data.color, data, 'complete', index)}
            </div>

            <!-- Body -->
            <div class="lab-card__body">
                <h5 class="lab-card__name">${_escapeHtml(data.laboratoryName)}</h5>

                <div class="lab-card__meta">
                    <div class="lab-card__location">
                        <i class="bi bi-geo-alt-fill"></i>
                        <span>${_escapeHtml(data.location)}</span>
                    </div>

                    <div class="lab-card__badges">
                        ${_pendingBadge(data.pendingRequests)}
                        <span id="lab-avail-badge-complete-${id}">${_availabilityBadge(isAvailable)}</span>
                    </div>
                </div>
            </div>

            <!-- Footer: Request button only -->
            <div class="lab-card__footer">
                ${_requestButton(data.id, isAvailable)}
            </div>
        </div>`;
}

/* ─── ═══════════════════════════════════════════════════════ ── */
/* ─── VARIANT 2: COMPACT CARD (CLIENT) ──────────────────────── */
/* ─── ═══════════════════════════════════════════════════════ ── */

function _buildCompactCard(data, isAvailable, index) {
    const id = _escapeHtml(String(data.id));
    return `
        <div class="lab-card lab-card--compact ${isAvailable ? '' : 'unavailable'}"
             id="lab-card-compact-${id}"
             data-lab-id="${id}"
             data-variant="compact"
             data-aos="zoom-in"
             data-aos-duration="300">

            <!-- Color accent bar -->
            ${_colorBar(data.color)}

            <!-- Thumbnail with overlaid badges -->
            <div class="lab-card__image-wrap">
                ${_imageOrPlaceholder(data.image, data.laboratoryName, data.color, data, 'compact', index)}
                <div class="lab-card__availability-overlay">
                    <span id="lab-avail-badge-compact-${id}">${_availabilityBadge(isAvailable)}</span>
                </div>
                <div class="lab-card__pending-overlay">
                    ${_pendingBadge(data.pendingRequests)}
                </div>
            </div>

            <!-- Body -->
            <div class="lab-card__body">
                <h6 class="lab-card__name">${_escapeHtml(data.laboratoryName)}</h6>
                <div class="lab-card__location">
                    <i class="bi bi-geo-alt-fill"></i>
                    <span>${_escapeHtml(data.location)}</span>
                </div>
            </div>

            <!-- Footer: Request button only -->
            <div class="lab-card__footer">
                ${_requestButton(data.id, isAvailable)}
            </div>
        </div>`;
}

/* ─── ═══════════════════════════════════════════════════════ ── */
/* ─── VARIANT 3: LIST CARD (ADMIN) ──────────────────────────── */
/* ─── ═══════════════════════════════════════════════════════ ── */

function _buildListCard(data, isAvailable, index) {
    const id = _escapeHtml(String(data.id));
    return `
        <div class="lab-card lab-card--list ${isAvailable ? '' : 'unavailable'}"
             id="lab-card-list-${id}"
             data-lab-id="${id}"
             data-variant="list"
             data-aos="fade-right"
             data-aos-duration="300">

            <div class="lab-card__inner">
                <!-- Thumbnail (no color bar — admin sees full color badge in footer) -->
                <div class="lab-card__image-wrap">
                    ${_imageOrPlaceholder(data.image, data.laboratoryName, null, data, 'list', index)}
                </div>

                <!-- Right content -->
                <div class="lab-card__content">
                    <div class="lab-card__body">
                        <!-- Name + location -->
                        <div class="lab-card__info">
                            <h6 class="lab-card__name">${_escapeHtml(data.laboratoryName)}</h6>
                            <div class="lab-card__location">
                                <i class="bi bi-geo-alt-fill"></i>
                                <span>${_escapeHtml(data.location)}</span>
                            </div>
                        </div>

                        <!-- Badges: pending + availability -->
                        <div class="lab-card__badges">
                            ${_pendingBadge(data.pendingRequests)}
                            <span id="lab-avail-badge-list-${id}">${_availabilityBadge(isAvailable)}</span>
                        </div>
                    </div>

                    <!-- Card footer: admin controls if admin, otherwise request button -->
                    <div class="lab-card__footer">
                        ${_isAdminMode ? `
                            ${_toggleSwitch(data.id, isAvailable)}
                            <div class="lab-card__footer-right">
                                ${_colorBadge(data.color)}
                                ${_actionButtons(data.id)}
                            </div>
                        ` : `
                            ${_requestButton(data.id, isAvailable)}
                        `}
                    </div>
                </div>
            </div>
        </div>`;
}

/* ─── PUBLIC API ────────────────────────────────────────────── */

/**
 * createLabCard(data, variant)
 * Primary factory. Returns an HTML string.
 *
 * @param {Object} data     — lab data object
 * @param {string} variant  — "complete" | "compact" | "list"
 * @param {number} [index]  — position in the gallery (0 = first); the first visible cards load
 *                            their image eagerly, the rest lazily. Omitted: lazy.
 * @returns {string}
 */
function createLabCard(data, variant, index) {
    const isAvailable = data.availability === 'Available';
    _labCardState[data.id] = isAvailable;

    switch (variant) {
        case 'complete': return _buildCompleteCard(data, isAvailable, index);
        case 'compact':  return _buildCompactCard(data, isAvailable, index);
        case 'list':     return _buildListCard(data, isAvailable, index);
        default:
            console.warn(`[LabCard] Unknown variant "${variant}". Defaulting to "complete".`);
            return _buildCompleteCard(data, isAvailable, index);
    }
}

/**
 * renderLabGallery(container, labsArray, variant)
 * Convenience batch renderer.
 *
 * @param {HTMLElement} container
 * @param {Array}       labsArray
 * @param {string}      variant
 */
function renderLabGallery(container, labsArray, variant) {
    if (!container) return;
    container.innerHTML = labsArray.map((lab, i) => createLabCard(lab, variant, i)).join('');
    if (typeof AOS !== 'undefined') AOS.refresh();
}

/**
 * toggleAvailability(labId)
 * Called by the admin slider onchange.
 * Syncs badge, card state, and toggle across all rendered instances.
 *
 * @param {number|string} labId
 */
function toggleAvailability(labId) {
    const newState = !_labCardState[labId];
    _labCardState[labId] = newState;

    // Sync every card variant that renders this lab
    document.querySelectorAll(`[data-lab-id="${labId}"]`).forEach(card => {
        const variant = card.dataset.variant;

        // 1. Card-level unavailability class
        card.classList.toggle('unavailable', !newState);

        // 2. Availability badge (each variant uses its own namespaced ID)
        const badge = card.querySelector(`#lab-avail-badge-${variant}-${labId}`);
        if (badge) badge.innerHTML = _availabilityBadge(newState);

        // 3. Toggle slider state (admin list only)
        const toggle = card.querySelector(`#lab-toggle-${labId}`);
        if (toggle) toggle.checked = newState;

        // 4. Request button state (client cards)
        const reqBtn = card.querySelector('.btn-lab-request');
        if (reqBtn) {
            if (newState) {
                reqBtn.disabled = false;
                reqBtn.innerHTML = '<i class="bi bi-calendar-plus me-1"></i>Request';
                reqBtn.title = 'Reserve this laboratory';
            } else {
                reqBtn.disabled = true;
                reqBtn.innerHTML = '<i class="bi bi-x-circle me-1"></i>Unavailable';
                reqBtn.title = 'This laboratory is currently unavailable';
            }
        }
    });
}

/**
 * requestLaboratory(labId)
 * Triggered by the client Request button.
 * Replace body with reservation form open / redirect logic.
 *
 * @param {number|string} labId
 */
function requestLaboratory(labId) {
    console.log('[LabCard] Request reservation for lab:', labId);
    // TODO: e.g. window.location.href = `reserve.php?lab_id=${labId}`;
    // or open a reservation modal:
    // const modal = new bootstrap.Modal(document.getElementById('reservationModal'));
    // populateReservationForm(labId);
    // modal.show();
}

/**
 * editLaboratory(labId)
 * Triggered by the admin Edit button.
 *
 * @param {number|string} labId
 */
function editLaboratory(labId) {
    if (typeof openEditModal === 'function') {
        openEditModal(labId);
    } else {
        console.log('[LabCard] Edit laboratory:', labId);
    }
}

/**
 * removeLaboratory(labId)
 * Triggered by the admin Remove button.
 * SweetAlert2 confirmation required.
 *
 * @param {number|string} labId
 */
function removeLaboratory(labId) {
    if (typeof openRemoveModal === 'function') {
        openRemoveModal(labId);
    } else {
        console.log('[LabCard] Remove laboratory:', labId);
    }
}
