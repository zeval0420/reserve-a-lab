(function () {
    /* ── Config ── */
    const API_URL      = '../../beta/games/index.php';
    const SCROLL_STEP  = 80;                  // px per arrow click
 
    /* Emoji map: assign icons by keyword in the filename */
    const ICON_MAP = [
        [/snake/i,   '🐍'],
        [/memory/i,  '🧠'],
        [/card/i,    '🃏'],
        [/puzzle/i,  '🧩'],
        [/shoot/i,   '🎯'],
        [/race/i,    '🏎️'],
        [/quiz/i,    '❓'],
        [/chess/i,   '♟️'],
        [/tetris/i,  '🟦'],
        [/block/i,   '🧱'],
        [/ball/i,    '⚽'],
        [/space/i,   '🚀'],
    ];
 
    function iconFor(filename) {
        for (const [re, emoji] of ICON_MAP) {
            if (re.test(filename)) return emoji;
        }
        return '🎮';
    }
 
    /* ── DOM refs ── */
    const track      = document.getElementById('games-track');
    const iframe     = document.getElementById('games-iframe');
    const placeholder = document.getElementById('games-placeholder');
    const nowPlaying = document.getElementById('games-now-playing');
    const arrowUp    = document.getElementById('games-arrow-up');
    const arrowDown  = document.getElementById('games-arrow-down');
    const trackWrap  = track.parentElement;
 
    /* ── Load game list ── */
    fetch(API_URL)
        .then(r => { if (!r.ok) throw new Error('fetch failed'); return r.json(); })
        .then(games => {
            track.innerHTML = '';
            if (!games.length) {
                track.innerHTML = '<div class="games-carousel__empty">No games found.<br>Add .html files to the games/ folder.</div>';
                return;
            }
            games.forEach(game => {
                const card = document.createElement('div');
                card.className = 'game-card';
                card.dataset.path  = game.path;
                card.dataset.title = game.title;
                card.innerHTML = `
                    <span class="game-card__icon">${iconFor(game.file)}</span>
                    <span class="game-card__name">${game.title}</span>
                    <span class="game-card__playing">▶ playing</span>`;
                card.addEventListener('click', () => loadGame(card, game));
                track.appendChild(card);
            });
        })
        .catch(() => {
            /* Fallback: hardcode a minimal list if PHP isn't running */
            track.innerHTML = '<div class="games-carousel__empty">Could not load game list.<br>Ensure games/index.php is reachable.</div>';
        });
 
    /* ── Load a game into the iframe ── */
    function loadGame(card, game) {
        /* Update active card */
        document.querySelectorAll('.game-card').forEach(c => c.classList.remove('active'));
        card.classList.add('active');
 
        /* Swap iframe */
        nowPlaying.textContent = game.title;
        placeholder.style.display = 'none';
        iframe.style.display = 'block';
        iframe.src = game.path;
 
        /* Scroll card into view in the track */
        card.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }
 
    /* ── Arrow scroll ── */
    arrowUp.addEventListener('click', () => {
        trackWrap.querySelector('.games-carousel__track').scrollBy({ top: -SCROLL_STEP, behavior: 'smooth' });
    });
    arrowDown.addEventListener('click', () => {
        trackWrap.querySelector('.games-carousel__track').scrollBy({ top: SCROLL_STEP, behavior: 'smooth' });
    });
 
    /* Hide arrows when not needed */
    function updateArrows() {
        const t = track;
        arrowUp.style.opacity   = t.scrollTop > 4 ? '1' : '0.3';
        arrowDown.style.opacity = (t.scrollTop + t.clientHeight < t.scrollHeight - 4) ? '1' : '0.3';
    }
    track.addEventListener('scroll', updateArrows);
    setTimeout(updateArrows, 600); // after load
}());