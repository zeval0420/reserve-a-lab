/**
 * assets/js/formControls.js
 * ------------------------------------------------------------------
 * Small form building blocks shared by the admin Settings page and the
 * kiosk's settings modal, so both render switches and fields the same way.
 * ------------------------------------------------------------------
 */
import { el } from './utils.js';

/** Label + hint on the left, an on/off switch on the right. `dataset.key` identifies it. */
export function switchRow(label, hint, checked, key) {
  return el('div', { class: 'switch-row' }, [
    el('div', {}, [
      el('div', { style: 'font-weight:600' }, label),
      hint ? el('div', { class: 'faint', style: 'font-size:12px;margin-top:2px' }, hint) : null,
    ]),
    el('div', {
      class: `switch${checked ? ' is-on' : ''}`, role: 'switch', tabindex: '0', dataset: { key },
      'aria-checked': checked ? 'true' : 'false',
      onClick: toggleSwitch,
      onKeydown: (e) => { if (e.key === ' ' || e.key === 'Enter') { e.preventDefault(); toggleSwitch(e); } },
    }, el('div', { class: 'switch__knob' })),
  ]);
}

function toggleSwitch(e) {
  const node = e.currentTarget;
  node.classList.toggle('is-on');
  node.setAttribute('aria-checked', node.classList.contains('is-on') ? 'true' : 'false');
}

/** A labelled form field with an optional hint line underneath. */
export function field(labelText, inputNode, hint, full = false) {
  return el('div', { class: `field${full ? ' field--full' : ''}` }, [
    el('label', {}, labelText), inputNode,
    hint ? el('div', { class: 'faint', style: 'font-size:12px' }, hint) : null,
  ]);
}

/** Read a switchRow's current state from inside `root`. */
export const isOn = (root, key) => !!root.querySelector(`.switch[data-key="${key}"]`)?.classList.contains('is-on');
