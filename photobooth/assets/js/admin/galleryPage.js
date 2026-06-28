/**
 * assets/js/admin/galleryPage.js
 * ------------------------------------------------------------------
 * Renders the session gallery inside admin/gallery.php and wires the
 * reprint button for each past session.
 * ------------------------------------------------------------------
 */
import { $, el, toast, apiFetch, apiPost } from '../utils.js';

const content = $('#gallery-content');

async function load() {
  content.innerHTML = '<div class="center-col" style="padding:64px"><div class="spinner"></div></div>';
  try {
    const { sessions } = await apiFetch('../api/sessions_list.php');
    render(sessions);
  } catch (e) {
    content.innerHTML = '';
    content.appendChild(el('div', { class: 'empty-state' }, 'Could not load sessions: ' + e.message));
  }
}

function render(sessions) {
  content.innerHTML = '';
  if (sessions.length === 0) {
    content.appendChild(el('div', { class: 'empty-state' }, 'No sessions yet. Strips will appear here after guests use the booth.'));
    return;
  }

  content.appendChild(el('p', { class: 'faint', style: 'margin-bottom:16px' }, `${sessions.length} session(s), newest first.`));

  const grid = el('div', { class: 'session-grid' });
  sessions.forEach((s) => grid.appendChild(buildCard(s)));
  content.appendChild(grid);
}

function buildCard(session) {
  const statusLabel = (session.print_status || 'not_printed').replace('_', ' ');
  const card = el('div', { class: 'session-card' }, [
    el('div', { class: 'session-card__thumb' },
      session.strip_url
        ? el('img', { src: '../' + session.strip_url, alt: session.session_id })
        : el('span', { class: 'faint' }, 'No strip')),
    el('div', { class: 'session-card__body' }, [
      el('div', { class: 'session-card__id' }, session.session_id),
      el('div', { class: 'faint', style: 'font-size:12px' }, `Template: ${session.template || '—'}`),
      el('span', { class: `session-card__status status-${session.print_status || 'not_printed'}` }, statusLabel),
      session.strip_url
        ? el('button', { class: 'btn btn--secondary btn--block', style: 'margin-top:6px', onClick: (e) => reprint(session.session_id, e.target) }, '🖨️ Reprint')
        : null,
    ]),
  ]);
  return card;
}

async function reprint(sessionId, btn) {
  btn.disabled = true;
  btn.textContent = 'Printing…';
  try {
    const { print_result } = await apiPost('../api/print_strip.php', { session_id: sessionId });
    toast(`Reprint ${print_result.status}.`, print_result.status === 'failed' ? 'error' : 'success');
  } catch (e) {
    toast('Reprint failed: ' + e.message, 'error');
  } finally {
    btn.disabled = false;
    btn.textContent = '🖨️ Reprint';
  }
}

$('#btn-logout')?.addEventListener('click', async () => {
  await apiPost('../api/admin_auth.php', { action: 'logout' });
  window.location.href = '../index.php';
});

load();
