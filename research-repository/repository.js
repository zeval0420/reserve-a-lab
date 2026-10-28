/*
 * Research repository: search and filter the list that index.php embeds in #rr-data.
 * Filters: keyword search, subject, published year range, keyword tags. The state is
 * kept in the URL, so a filtered view can be bookmarked or shared.
 */
(function () {
    'use strict';

    var PAGE_SIZE = 20;
    var TAGS_SHOWN = 12;

    var data = JSON.parse(document.getElementById('rr-data').textContent);
    var papers = data.papers;
    var subjectLabel = {};
    data.subjects.forEach(function (s) { subjectLabel[s.key] = s.label; });

    var el = {
        q: document.getElementById('rr-q'),
        subjects: document.getElementById('rr-subjects'),
        from: document.getElementById('rr-from'),
        to: document.getElementById('rr-to'),
        sort: document.getElementById('rr-sort'),
        tagsRow: document.getElementById('rr-tags-row'),
        tags: document.getElementById('rr-tags'),
        count: document.getElementById('rr-count'),
        clear: document.getElementById('rr-clear'),
        results: document.getElementById('rr-results'),
        more: document.getElementById('rr-more'),
        empty: document.getElementById('rr-empty')
    };

    var state = { q: '', subject: '', from: '', to: '', sort: 'new', tags: [], shown: PAGE_SIZE, allTags: false };

    // Lower-case without accents, so "senor" finds "señor".
    function norm(s) {
        return String(s).normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
    }

    papers.forEach(function (p) {
        p.haystack = norm([p.title, p.authors.join(' '), p.keywords.join(' '), p.abstract, p.file].join(' '));
        p.tagKeys = p.keywords.map(norm);
    });

    // Keywords by how many papers use them (shown under the filters).
    var tagCounts = {};
    papers.forEach(function (p) {
        p.keywords.forEach(function (k) {
            var key = norm(k);
            if (!tagCounts[key]) tagCounts[key] = { key: key, label: k, count: 0 };
            tagCounts[key].count++;
        });
    });
    var tagList = Object.keys(tagCounts).map(function (k) { return tagCounts[k]; })
        .sort(function (a, b) { return b.count - a.count || a.label.localeCompare(b.label); });

    var years = papers.map(function (p) { return p.year; });
    var minYear = Math.min.apply(null, years);
    var maxYear = Math.max.apply(null, years);

    // ─── URL state ───
    function readUrl() {
        var u = new URLSearchParams(location.search);
        state.q = u.get('q') || '';
        state.subject = subjectLabel[u.get('subject')] ? u.get('subject') : '';
        state.from = /^\d{4}$/.test(u.get('from') || '') ? u.get('from') : '';
        state.to = /^\d{4}$/.test(u.get('to') || '') ? u.get('to') : '';
        state.sort = ['new', 'old', 'title'].indexOf(u.get('sort')) >= 0 ? u.get('sort') : 'new';
        state.tags = u.getAll('tag').map(norm);
    }

    function writeUrl() {
        var u = new URLSearchParams();
        if (state.q) u.set('q', state.q);
        if (state.subject) u.set('subject', state.subject);
        if (state.from) u.set('from', state.from);
        if (state.to) u.set('to', state.to);
        if (state.sort !== 'new') u.set('sort', state.sort);
        state.tags.forEach(function (t) { u.append('tag', t); });
        var qs = u.toString();
        try { history.replaceState(null, '', location.pathname + (qs ? '?' + qs : '')); } catch (e) { /* file:// or sandboxed */ }
    }

    // ─── Filtering ───
    function matches(p) {
        if (state.subject && p.subject !== state.subject) return false;
        if (state.from && p.year < +state.from) return false;
        if (state.to && p.year > +state.to) return false;
        for (var i = 0; i < state.tags.length; i++) {
            if (p.tagKeys.indexOf(state.tags[i]) < 0) return false;
        }
        var terms = norm(state.q).split(/\s+/).filter(Boolean);
        for (var j = 0; j < terms.length; j++) {
            if (p.haystack.indexOf(terms[j]) < 0) return false;
        }
        return true;
    }

    function compare(a, b) {
        if (state.sort === 'title') return a.title.localeCompare(b.title);
        var d = a.date < b.date ? -1 : a.date > b.date ? 1 : 0;
        return (state.sort === 'old' ? d : -d) || a.title.localeCompare(b.title);
    }

    function filtersActive() {
        return !!(state.q || state.subject || state.from || state.to || state.tags.length);
    }

    // ─── Rendering ───
    function node(tag, cls, text) {
        var n = document.createElement(tag);
        if (cls) n.className = cls;
        if (text != null) n.textContent = text;
        return n;
    }

    function chip(label, count, pressed, onClick, cls) {
        var b = node('button', cls || 'rr-chip', label);
        b.type = 'button';
        b.setAttribute('aria-pressed', pressed ? 'true' : 'false');
        if (count != null) b.appendChild(node('small', null, '(' + count + ')'));
        b.addEventListener('click', onClick);
        return b;
    }

    function renderSubjects(visibleBySubject) {
        el.subjects.textContent = '';
        el.subjects.appendChild(chip('All', papers.length, !state.subject, function () { setState({ subject: '' }); }));
        data.subjects.forEach(function (s) {
            // The count shows what the other filters leave in that subject.
            el.subjects.appendChild(chip(s.label, visibleBySubject[s.key] || 0, state.subject === s.key,
                function () { setState({ subject: state.subject === s.key ? '' : s.key }); }));
        });
    }

    function renderTags() {
        el.tags.textContent = '';
        var picked = state.tags.map(function (k) { return tagCounts[k] || { key: k, label: k, count: 0 }; });
        var rest = tagList.filter(function (t) { return state.tags.indexOf(t.key) < 0; });
        var list = picked.concat(state.allTags ? rest : rest.slice(0, Math.max(0, TAGS_SHOWN - picked.length)));
        list.forEach(function (t) {
            el.tags.appendChild(chip(t.label, t.count, state.tags.indexOf(t.key) >= 0, function () { toggleTag(t.key); }));
        });
        if (tagList.length > TAGS_SHOWN) {
            var more = chip(state.allTags ? 'Fewer' : 'All ' + tagList.length + ' keywords', null, false, function () {
                state.allTags = !state.allTags;
                renderTags();
            });
            more.removeAttribute('aria-pressed');
            el.tags.appendChild(more);
        }
    }

    function renderYears() {
        [el.from, el.to].forEach(function (sel, i) {
            sel.textContent = '';
            var any = node('option', null, i === 0 ? 'Earliest' : 'Latest');
            any.value = '';
            sel.appendChild(any);
            for (var y = maxYear; y >= minYear; y--) {
                var o = node('option', null, y);
                o.value = y;
                sel.appendChild(o);
            }
        });
    }

    function card(p) {
        var li = node('li', 'rr-card');

        var top = node('div', 'rr-card-top');
        top.appendChild(node('span', 'rr-badge', subjectLabel[p.subject]));
        top.appendChild(node('span', 'rr-meta', (p.dateGuess ? 'Added ' : 'Published ') + p.dateLabel + '  ·  PDF, ' + p.size));
        li.appendChild(top);

        li.appendChild(node('h2', null, p.title));
        if (p.authors.length) li.appendChild(node('p', 'rr-authors', p.authors.join(', ')));
        if (p.abstract) li.appendChild(node('p', 'rr-abstract', p.abstract));

        var foot = node('div', 'rr-card-foot');
        var tags = node('div', 'rr-card-tags');
        p.keywords.forEach(function (k) {
            var t = node('button', 'rr-tag', k);
            t.type = 'button';
            t.title = 'Filter by ' + k;
            t.addEventListener('click', function () { toggleTag(norm(k)); });
            tags.appendChild(t);
        });
        foot.appendChild(tags);

        var actions = node('div', 'rr-actions');
        var view = node('a', 'rr-btn', 'View');
        view.href = p.url;
        view.target = '_blank';
        view.rel = 'noopener';
        view.setAttribute('aria-label', 'View ' + p.title + ' (opens in a new tab)');
        var dl = node('a', 'rr-btn rr-btn-ghost', 'Download');
        dl.href = p.url;
        dl.setAttribute('download', p.file);
        dl.setAttribute('aria-label', 'Download ' + p.title);
        actions.appendChild(view);
        actions.appendChild(dl);
        foot.appendChild(actions);
        li.appendChild(foot);
        return li;
    }

    function render() {
        // Counts per subject ignore the subject filter itself, so the chips show what each would give.
        var saved = state.subject;
        state.subject = '';
        var visibleBySubject = {};
        papers.forEach(function (p) {
            if (matches(p)) visibleBySubject[p.subject] = (visibleBySubject[p.subject] || 0) + 1;
        });
        state.subject = saved;

        var found = papers.filter(matches).sort(compare);

        el.q.value = state.q;
        el.from.value = state.from;
        el.to.value = state.to;
        el.sort.value = state.sort;
        renderSubjects(visibleBySubject);
        renderTags();
        el.tagsRow.hidden = tagList.length === 0;

        el.results.textContent = '';
        found.slice(0, state.shown).forEach(function (p) { el.results.appendChild(card(p)); });

        el.count.textContent = papers.length === 0 ? '' :
            found.length + (found.length === 1 ? ' paper found' : ' papers found');
        el.clear.hidden = !filtersActive();
        el.more.hidden = found.length <= state.shown;
        el.more.textContent = 'Show more (' + (found.length - state.shown) + ' left)';

        el.empty.hidden = found.length > 0;
        if (papers.length === 0) {
            el.empty.textContent = 'No research has been added yet.';
        } else if (found.length === 0) {
            el.empty.textContent = 'No papers match these filters. Try fewer keywords or a wider year range.';
        }
    }

    function setState(change) {
        Object.keys(change).forEach(function (k) { state[k] = change[k]; });
        state.shown = PAGE_SIZE;
        writeUrl();
        render();
    }

    function toggleTag(key) {
        var i = state.tags.indexOf(key);
        setState({ tags: i >= 0 ? state.tags.filter(function (t) { return t !== key; }) : state.tags.concat(key) });
    }

    // ─── Events ───
    var typing;
    el.q.addEventListener('input', function () {
        clearTimeout(typing);
        typing = setTimeout(function () { setState({ q: el.q.value.trim() }); }, 150);
    });
    el.from.addEventListener('change', function () { setState({ from: el.from.value }); });
    el.to.addEventListener('change', function () { setState({ to: el.to.value }); });
    el.sort.addEventListener('change', function () { setState({ sort: el.sort.value }); });
    el.clear.addEventListener('click', function () {
        setState({ q: '', subject: '', from: '', to: '', tags: [] });
        el.q.focus();
    });
    el.more.addEventListener('click', function () {
        state.shown += PAGE_SIZE;
        render();
    });

    renderYears();
    readUrl();
    render();
})();
