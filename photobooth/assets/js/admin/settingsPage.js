/**
 * assets/js/admin/settingsPage.js
 * ------------------------------------------------------------------
 * Renders and wires the Settings form inside admin/settings.php.
 * Talks only to the admin-protected api/settings_*.php endpoints.
 * ------------------------------------------------------------------
 */
import { $, el, toast, apiFetch, apiPost } from '../utils.js';
import { applyTheme } from '../theme.js';
import { CameraController } from '../camera.js';

const form = $('#settings-form');
let state = null; // { settings, available_printers, available_templates }

async function load() {
  state = await apiFetch('../api/settings_get.php');
  applyTheme(state.settings.ui.dark_mode);
  let cameraDevices = [];
  try {
    cameraDevices = await CameraController.listDevices();
  } catch { /* permissions not granted yet — still render the form */ }
  render(cameraDevices);
}

function switchRow(label, hint, checked, key) {
  const row = el('div', { class: 'switch-row' }, [
    el('div', {}, [el('div', {}, label), hint ? el('div', { class: 'faint', style: 'font-size:12px;margin-top:2px' }, hint) : null]),
    el('div', { class: `switch${checked ? ' is-on' : ''}`, dataset: { key }, onClick: (e) => e.currentTarget.classList.toggle('is-on') },
      el('div', { class: 'switch__knob' })),
  ]);
  return row;
}

function field(labelText, inputNode, full = false) {
  return el('div', { class: `field${full ? ' field--full' : ''}` }, [el('label', {}, labelText), inputNode]);
}

function render(cameraDevices) {
  const s = state.settings;
  form.innerHTML = '';

  // ---- Camera ----
  const cameraOptions = [el('option', { value: '' }, 'Default / system camera')]
    .concat(cameraDevices.map((d, i) => el('option', { value: d.deviceId, ...(s.camera.preferred_device_label === d.label ? { selected: 'selected' } : {}) }, d.label || `Camera ${i + 1}`)));

  form.appendChild(section('Camera', 'Choose which webcam the kiosk should use, and whether the live preview is mirrored.', [
    field('Camera device', el('select', { id: 'f-camera-device' }, cameraOptions)),
    switchRow('Mirror preview', 'Flips the live preview horizontally (selfie-style) — recommended for most webcams.', s.camera.mirror_preview, 'mirror_preview'),
  ]));

  // ---- Templates ----
  const tplOptions = state.available_templates.map((t) =>
    el('option', { value: t.id, ...(t.id === s.templates.default_template ? { selected: 'selected' } : {}) }, t.name));
  form.appendChild(section('Templates', 'Templates are auto-detected from the templates/ folder — drop in a new folder with config.json, frame.png and thumbnail.png and it appears here automatically.', [
    field('Default template', el('select', { id: 'f-default-template' }, tplOptions)),
    el('div', { class: 'field field--full' }, [
      el('label', {}, `Installed templates (${state.available_templates.length})`),
      el('div', { class: 'faint', style: 'font-size:13px' }, state.available_templates.map((t) => t.name).join(', ') || 'None found'),
      el('button', { class: 'btn btn--secondary', style: 'margin-top:8px;width:fit-content', id: 'btn-refresh-templates' }, '🔄 Refresh Templates'),
    ]),
  ]));

  // ---- Printing ----
  const printerOptions = [el('option', { value: '' }, '— None / simulate only —')]
    .concat(state.available_printers.map((p) => el('option', { value: p, ...(p === s.printing.printer_name ? { selected: 'selected' } : {}) }, p)));
  form.appendChild(section('Printing', 'Configure the printer used after a guest accepts their strip.', [
    switchRow('Automatic printing', 'Print immediately after the strip is accepted.', s.printing.auto_print, 'auto_print'),
    field('Printer', el('select', { id: 'f-printer-name' }, printerOptions)),
    field('Or type a printer name manually', el('input', { type: 'text', id: 'f-printer-name-manual', value: s.printing.printer_name, placeholder: 'e.g. DNP_DS620' })),
    field('Paper size', el('select', { id: 'f-paper-size' }, ['4x6', '5x7', '2x6', 'A4', 'Letter'].map((sz) =>
      el('option', { value: sz, ...(sz === s.printing.paper_size ? { selected: 'selected' } : {}) }, sz)))),
    field('Copies', el('input', { type: 'number', id: 'f-copies', min: '1', max: '20', value: s.printing.copies })),
    field('Margins (mm)', el('input', { type: 'number', id: 'f-margins', min: '0', max: '50', value: s.printing.margins_mm })),
    field('Scale (%)', el('input', { type: 'number', id: 'f-scale', min: '10', max: '200', value: s.printing.scale_percent })),
  ]));

  // ---- Countdown ----
  form.appendChild(section('Countdown', 'How long guests have to pose before each photo is taken.', [
    field('Countdown duration (seconds)', el('input', { type: 'number', id: 'f-countdown-seconds', min: '1', max: '10', value: s.countdown.seconds })),
    switchRow('Play countdown sound', 'Beep on each second of the countdown.', s.countdown.play_sound, 'countdown_play_sound'),
  ]));

  // ---- Storage ----
  form.appendChild(section('Storage', 'Sessions are saved to the filesystem — no database is used.', [
    field('Sessions folder', el('input', { type: 'text', id: 'f-sessions-dir', value: s.storage.sessions_dir })),
    field('JPEG quality (raw photos, 1-100)', el('input', { type: 'number', id: 'f-jpeg-quality', min: '1', max: '100', value: s.storage.jpeg_quality })),
  ]));

  // ---- Audio ----
  form.appendChild(section('Audio', 'Sound effect file paths (relative to the project root) and playback volume.', [
    field('Countdown sound file', el('input', { type: 'text', id: 'f-countdown-sound', value: s.audio.countdown_sound })),
    field('Shutter sound file', el('input', { type: 'text', id: 'f-shutter-sound', value: s.audio.shutter_sound })),
    field('Volume (0–1)', el('input', { type: 'number', id: 'f-volume', min: '0', max: '1', step: '0.05', value: s.audio.volume })),
  ]));

  // ---- Appearance ----
  form.appendChild(section('Appearance', 'Controls the built-in dark mode.', [
    field('Dark mode', el('select', { id: 'f-dark-mode' }, ['auto', 'light', 'dark'].map((v) =>
      el('option', { value: v, ...(v === s.ui.dark_mode ? { selected: 'selected' } : {}) }, v.charAt(0).toUpperCase() + v.slice(1))))),
    field('Idle return to welcome (seconds)', el('input', { type: 'number', id: 'f-idle-seconds', min: '5', max: '120', value: s.ui.idle_return_seconds })),
  ]));

  // ---- Admin ----
  form.appendChild(section('Admin Access', 'Change the passcode used to reach this hidden settings page.', [
    field('New passcode (leave blank to keep current)', el('input', { type: 'password', id: 'f-new-passcode', maxlength: '12', placeholder: '••••' })),
  ]));

  form.appendChild(el('div', { class: 'save-bar' }, [
    el('button', { class: 'btn btn--secondary', onClick: load }, 'Discard Changes'),
    el('button', { class: 'btn btn--primary btn--xl', id: 'btn-save' }, 'Save Settings'),
  ]));

  $('#btn-refresh-templates').addEventListener('click', refreshTemplates);
  $('#btn-save').addEventListener('click', save);
  $('#f-dark-mode').addEventListener('change', (e) => applyTheme(e.target.value));
}

function section(title, hint, children) {
  return el('section', { class: 'card admin-section' }, [
    el('h2', {}, title),
    el('p', { class: 'section-hint' }, hint),
    el('div', { class: 'field-grid' }, children),
  ]);
}

async function refreshTemplates() {
  const btn = $('#btn-refresh-templates');
  btn.disabled = true;
  btn.textContent = 'Scanning…';
  try {
    const res = await apiPost('../api/templates_refresh.php', {});
    toast(`Found ${res.count} template(s).`, 'success');
    await load();
  } catch (e) {
    toast('Refresh failed: ' + e.message, 'error');
  } finally {
    btn.disabled = false;
    btn.textContent = '🔄 Refresh Templates';
  }
}

function isOn(key) {
  const row = form.querySelector(`.switch[data-key="${key}"]`);
  return row ? row.classList.contains('is-on') : false;
}

async function save() {
  const manualPrinter = $('#f-printer-name-manual').value.trim();
  const settings = {
    camera: {
      preferred_device_label: $('#f-camera-device').selectedOptions[0]?.textContent || '',
      mirror_preview: isOn('mirror_preview'),
    },
    templates: { default_template: $('#f-default-template').value },
    printing: {
      auto_print: isOn('auto_print'),
      printer_name: manualPrinter || $('#f-printer-name').value,
      paper_size: $('#f-paper-size').value,
      copies: parseInt($('#f-copies').value, 10) || 1,
      margins_mm: parseFloat($('#f-margins').value) || 0,
      scale_percent: parseInt($('#f-scale').value, 10) || 100,
    },
    countdown: {
      seconds: parseInt($('#f-countdown-seconds').value, 10) || 3,
      play_sound: isOn('countdown_play_sound'),
    },
    storage: {
      sessions_dir: $('#f-sessions-dir').value.trim() || 'sessions',
      jpeg_quality: parseInt($('#f-jpeg-quality').value, 10) || 92,
    },
    audio: {
      countdown_sound: $('#f-countdown-sound').value.trim(),
      shutter_sound: $('#f-shutter-sound').value.trim(),
      volume: parseFloat($('#f-volume').value),
    },
    ui: {
      dark_mode: $('#f-dark-mode').value,
      idle_return_seconds: parseInt($('#f-idle-seconds').value, 10) || 20,
    },
  };

  const newPasscode = $('#f-new-passcode').value.trim();
  const payload = { settings };
  if (newPasscode) payload.new_passcode = newPasscode;

  const btn = $('#btn-save');
  btn.disabled = true;
  btn.textContent = 'Saving…';
  try {
    await apiPost('../api/settings_save.php', payload);
    toast('Settings saved.', 'success');
    await load();
  } catch (e) {
    toast('Could not save settings: ' + e.message, 'error');
  } finally {
    btn.disabled = false;
    btn.textContent = 'Save Settings';
  }
}

$('#btn-logout')?.addEventListener('click', async () => {
  await apiPost('../api/admin_auth.php', { action: 'logout' });
  window.location.href = '../index.php';
});

load();
