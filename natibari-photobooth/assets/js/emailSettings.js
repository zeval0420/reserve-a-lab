/**
 * assets/js/emailSettings.js
 * ------------------------------------------------------------------
 * The "who gets the prints by email" panel. Mounted in two places:
 *   - the kiosk's settings modal on index.php (assets/js/kioskSettings.js)
 *   - the Email tab of admin/settings.php
 * Both talk to the admin-only api/email_settings.php, so the caller is
 * responsible for the passcode gate; a 401 is surfaced through onLocked.
 * ------------------------------------------------------------------
 */
import { el, toast, apiFetch, apiPost } from './utils.js';
import { switchRow, field, isOn } from './formControls.js';

const EMAIL_RE = /^[^\s@,;<>()]+@[^\s@,;<>()]+\.[^\s@,;<>()]+$/;

/**
 * @param {HTMLElement} container  element to render into (its content is replaced)
 * @param {object} opts
 *   apiBase  path prefix of the api folder ("api/" from index.php, "../api/" from admin/)
 *   onLocked called when the server says the admin session has expired
 */
export async function mountEmailSettings(container, { apiBase = 'api/', onLocked = () => {} } = {}) {
  container.innerHTML = '';
  container.appendChild(el('div', { class: 'center-col', style: 'padding:48px' }, el('div', { class: 'spinner' })));

  let data;
  try {
    data = await apiFetch(`${apiBase}email_settings.php`);
  } catch (e) {
    if (/authentication/i.test(e.message)) { onLocked(); return; }
    container.innerHTML = '';
    container.appendChild(el('div', { class: 'email-status is-problem' }, `Could not load email settings: ${e.message}`));
    return;
  }

  const recipients = [...data.email.to];
  const chipList = el('div', { class: 'chip-list' });
  const addInput = el('input', {
    type: 'email', id: 'f-email-add', placeholder: 'name@school.edu.ph', autocomplete: 'off', inputmode: 'email',
    'aria-label': 'Add a recipient email address',
  });
  const status = el('div', { class: 'email-status' });
  const root = el('div', { class: 'email-panel' });

  /* ---------------- recipients ---------------- */
  function renderChips() {
    chipList.innerHTML = '';
    if (!recipients.length) {
      chipList.appendChild(el('div', { class: 'chip-empty' }, 'No recipients yet — add at least one address below.'));
      return;
    }
    recipients.forEach((addr, i) => chipList.appendChild(el('span', { class: 'chip' }, [
      addr,
      el('button', {
        type: 'button', class: 'chip__remove', 'aria-label': `Remove ${addr}`,
        onClick: () => { recipients.splice(i, 1); renderChips(); },
      }, '×'),
    ])));
  }

  /** Accepts one address or several separated by commas / semicolons / spaces. */
  function addFromInput() {
    const parts = addInput.value.split(/[\s,;]+/).map((p) => p.trim()).filter(Boolean);
    if (!parts.length) return;
    const bad = parts.filter((p) => !EMAIL_RE.test(p));
    if (bad.length) {
      addInput.classList.add('is-invalid');
      toast(`Not a valid email address: ${bad[0]}`, 'error');
      return;
    }
    addInput.classList.remove('is-invalid');
    parts.forEach((p) => {
      if (!recipients.some((r) => r.toLowerCase() === p.toLowerCase())) recipients.push(p);
    });
    addInput.value = '';
    renderChips();
  }
  addInput.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); addFromInput(); } });
  addInput.addEventListener('input', () => addInput.classList.remove('is-invalid'));

  /* ---------------- status banner ---------------- */
  function renderStatus(problem, smtpUser) {
    status.className = `email-status${problem ? ' is-problem' : ''}`;
    status.innerHTML = '';
    status.appendChild(el('span', { class: 'email-status__icon' }, problem ? '⚠' : '✓'));
    status.appendChild(el('span', {}, problem
      ? problem
      : `Ready — emails are sent from ${smtpUser}.`));
  }
  renderStatus(data.problem, data.smtp_user);

  /* ---------------- actions ---------------- */
  const subject = el('input', { type: 'text', id: 'f-email-subject', maxlength: '100', value: data.email.subject });
  const fromName = el('input', { type: 'text', id: 'f-email-from', maxlength: '60', value: data.email.from_name });

  async function save(btn) {
    addFromInput(); // a half-typed address in the box counts
    if (addInput.value) return; // it was invalid; addFromInput already told the user
    const payload = {
      enabled: isOn(root, 'email_enabled'),
      to: recipients,
      subject: subject.value.trim(),
      from_name: fromName.value.trim(),
      attach: {
        strip: isOn(root, 'attach_strip'),
        print: isOn(root, 'attach_print'),
        raw: isOn(root, 'attach_raw'),
      },
    };
    btn.disabled = true;
    const label = btn.textContent;
    btn.textContent = 'Saving…';
    try {
      const res = await apiPost(`${apiBase}email_settings.php`, payload);
      renderStatus(res.problem, res.smtp_user);
      toast('Email settings saved.', 'success');
    } catch (e) {
      if (/authentication/i.test(e.message)) { onLocked(); return; }
      toast(e.message, 'error', 5000);
    } finally {
      btn.disabled = false;
      btn.textContent = label;
    }
  }

  async function runCheck(btn, sendLatest) {
    if (sendLatest && !confirm(`Email the most recent accepted session to ${recipients.length} recipient(s) now?\n\nSave first if you changed the list.`)) return;
    btn.disabled = true;
    const label = btn.textContent;
    btn.textContent = sendLatest ? 'Sending…' : 'Checking…';
    try {
      const res = await apiFetch(`${apiBase}email_test.php${sendLatest ? '?send=latest' : ''}`);
      const login = res.mail.smtp_login;
      if (login !== 'ok') {
        toast(`Mail server login failed: ${login}`, 'error', 7000);
      } else if (sendLatest) {
        const s = res.send;
        if (typeof s === 'string') toast(s, 'error', 5000);
        else if (s.email_status === 'sent') toast('Test email sent.', 'success');
        else toast(`Send failed: ${s.email_error || s.email_status}`, 'error', 7000);
      } else {
        toast('Connected to the mail server.', 'success');
      }
    } catch (e) {
      if (/authentication/i.test(e.message)) { onLocked(); return; }
      toast(e.message, 'error', 5000);
    } finally {
      btn.disabled = false;
      btn.textContent = label;
    }
  }

  /* ---------------- layout ---------------- */
  const section = (label, children) => el('div', { class: 'email-panel__section' }, [
    el('div', { class: 'email-panel__label' }, label), ...[].concat(children),
  ]);

  const checkBtn = el('button', { type: 'button', class: 'btn btn--secondary', onClick: (e) => runCheck(e.currentTarget, false) }, 'Check connection');
  const testBtn = el('button', { type: 'button', class: 'btn btn--secondary', onClick: (e) => runCheck(e.currentTarget, true) }, 'Send latest session');
  const saveBtn = el('button', { type: 'button', class: 'btn btn--primary', onClick: (e) => save(e.currentTarget) }, 'Save');

  root.append(
    status,
    switchRow('Email every accepted session', 'When a guest taps Accept & Finish, their photos are sent to the addresses below.', data.email.enabled, 'email_enabled'),
    section('Send prints to', [
      chipList,
      el('div', { class: 'add-row' }, [
        addInput,
        el('button', { type: 'button', class: 'btn btn--secondary', onClick: addFromInput }, '+ Add'),
      ]),
      el('div', { class: 'faint', style: 'font-size:12px' }, 'Paste several addresses at once, separated by commas.'),
    ]),
    section('What to attach', [
      switchRow('Final strip', 'The finished photo strip (PNG).', data.email.attach.strip, 'attach_strip'),
      switchRow('Print layout', 'Two strips side by side, ready to print and cut.', data.email.attach.print, 'attach_print'),
      switchRow('Raw photos', 'The original photos, one file per shot.', data.email.attach.raw, 'attach_raw'),
    ]),
    section('Message', [
      el('div', { class: 'field-grid' }, [
        field('Subject', subject, 'The date is added automatically, so each day forms one email thread.'),
        field('Sender name', fromName),
      ]),
    ]),
    el('div', { class: 'email-panel__actions' }, [checkBtn, testBtn, saveBtn]),
  );

  renderChips();
  container.innerHTML = '';
  container.appendChild(root);
}
