/**
 * assets/js/kioskSettings.js
 * ------------------------------------------------------------------
 * The gear button on the kiosk's frame screen. Opens a modal that asks for
 * the admin passcode (same one as admin/settings.php), then shows the
 * email-recipients panel. The admin session is dropped again when the
 * modal closes so a guest can't wander in after the operator walks away.
 * ------------------------------------------------------------------
 */
import { $, el, apiFetch, apiPost } from './utils.js';
import { mountEmailSettings } from './emailSettings.js?v=1';

export class KioskSettings {
  constructor() {
    this.modal = $('#settings-modal');
    this.body = $('#settings-modal-body');
    $('#btn-settings-modal-close').addEventListener('click', () => this.close());
    $('#settings-modal-backdrop').addEventListener('click', () => this.close());
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && !this.modal.classList.contains('is-hidden')) this.close();
    });
  }

  async open() {
    this.modal.classList.remove('is-hidden');
    this.body.innerHTML = '';
    this.body.appendChild(el('div', { class: 'center-col', style: 'padding:48px' }, el('div', { class: 'spinner' })));
    let authed = false;
    try {
      authed = (await apiFetch('api/admin_auth.php')).authenticated;
    } catch (_) { /* fall through to the passcode form */ }
    authed ? this.showPanel() : this.showGate();
  }

  async close() {
    this.modal.classList.add('is-hidden');
    this.body.innerHTML = '';
    try { await apiPost('api/admin_auth.php', { action: 'logout' }); } catch (_) { /* best effort */ }
  }

  showGate() {
    const input = el('input', {
      type: 'password', inputmode: 'numeric', maxlength: '12', class: 'passcode-input', placeholder: '••••',
      autocomplete: 'off', 'aria-label': 'Admin passcode',
    });
    const hint = el('p', { class: 'faint', style: 'font-size:13px;min-height:18px' }, 'Staff only. Enter the admin passcode to continue.');
    const unlock = async () => {
      try {
        await apiPost('api/admin_auth.php', { action: 'login', passcode: input.value });
        this.showPanel();
      } catch (_) {
        input.value = '';
        input.style.borderColor = 'var(--color-danger)';
        hint.textContent = 'Incorrect passcode — try again.';
        hint.style.color = 'var(--color-danger)';
        input.focus();
      }
    };
    input.addEventListener('keydown', (e) => { if (e.key === 'Enter') unlock(); });

    this.body.innerHTML = '';
    this.body.appendChild(el('div', { class: 'gate-view' }, [
      el('div', { class: 'gate-view__icon' }, lockIcon()),
      el('h2', { class: 'camera-modal__title', style: 'padding:0' }, 'Settings'),
      input,
      hint,
      el('button', { type: 'button', class: 'btn btn--primary btn--block', style: 'margin-top:16px', onClick: unlock }, 'Unlock'),
    ]));
    input.focus();
  }

  async showPanel() {
    this.body.innerHTML = '';
    this.body.appendChild(el('h2', { class: 'camera-modal__title' }, 'Email settings'));
    this.body.appendChild(el('p', { class: 'camera-modal__sub' }, 'Choose who receives each finished session.'));
    const panel = el('div');
    this.body.appendChild(panel);
    this.body.appendChild(el('div', { class: 'settings-links' }, [
      el('a', { class: 'btn btn--ghost', href: 'admin/settings.php' }, '⚙ All settings'),
      el('a', { class: 'btn btn--ghost', href: 'admin/gallery.php' }, '🖼 Session gallery'),
    ]));
    await mountEmailSettings(panel, { apiBase: 'api/', onLocked: () => this.showGate() });
  }
}

function lockIcon() {
  const ns = 'http://www.w3.org/2000/svg';
  const svg = document.createElementNS(ns, 'svg');
  svg.setAttribute('width', '30'); svg.setAttribute('height', '30'); svg.setAttribute('viewBox', '0 0 24 24');
  svg.setAttribute('fill', 'none'); svg.setAttribute('stroke', 'currentColor'); svg.setAttribute('stroke-width', '2');
  svg.setAttribute('stroke-linecap', 'round'); svg.setAttribute('stroke-linejoin', 'round');
  svg.innerHTML = '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>';
  return svg;
}
