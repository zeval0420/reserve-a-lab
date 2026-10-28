/*
 * Upload form: checks the entries, sends them to upload.php as multipart/form-data,
 * and shows the server's answer. The server checks everything again.
 */
(function () {
    'use strict';

    var form = document.getElementById('rr-upload');
    var file = document.getElementById('rr-pdf');
    var drop = document.getElementById('rr-drop');
    var dropText = document.getElementById('rr-drop-text');
    var title = document.getElementById('rr-title');
    var error = document.getElementById('rr-error');
    var result = document.getElementById('rr-result');
    var submit = document.getElementById('rr-submit');
    var maxBytes = +form.dataset.maxBytes;
    var idleText = dropText.textContent;

    function showError(msg) {
        error.textContent = msg;
        error.hidden = !msg;
    }

    function size(bytes) {
        return bytes >= 1048576 ? (bytes / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(bytes / 1024)) + ' KB';
    }

    function showChosen() {
        var f = file.files[0];
        dropText.textContent = f ? f.name + '  ·  ' + size(f.size) : idleText;
        drop.classList.toggle('rr-drop-has-file', !!f);
        // Start the title from the file name so there is something to edit.
        if (f && !title.value) {
            title.value = f.name.replace(/\.pdf$/i, '').replace(/[_]+/g, ' ').trim();
        }
    }

    file.addEventListener('change', function () { showError(''); showChosen(); });

    ['dragenter', 'dragover'].forEach(function (ev) {
        drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('rr-drop-over'); });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
        drop.addEventListener(ev, function () { drop.classList.remove('rr-drop-over'); });
    });
    drop.addEventListener('drop', function (e) {
        e.preventDefault();
        if (e.dataTransfer && e.dataTransfer.files.length) {
            file.files = e.dataTransfer.files;
            showError('');
            showChosen();
        }
    });

    // The first problem found, or '' when the entries look fine.
    function problem() {
        var f = file.files[0];
        if (!f) return 'Choose a PDF file.';
        if (!/\.pdf$/i.test(f.name) && f.type !== 'application/pdf') return 'Only PDF files are allowed.';
        if (f.size > maxBytes) return 'The PDF is too large (limit ' + size(maxBytes) + ').';
        if (!form.subject.value) return 'Choose a subject.';
        if (!title.value.trim()) return 'Enter the title.';
        var d = form.published.value.trim();
        if (!/^\d{4}(-\d{2}(-\d{2})?)?$/.test(d)) return 'Enter the date published as YYYY, YYYY-MM or YYYY-MM-DD.';
        return '';
    }

    function showResult(message, url) {
        result.textContent = '';
        result.appendChild(document.createTextNode(message + ' '));
        if (url) {
            var a = document.createElement('a');
            a.href = url;
            a.target = '_blank';
            a.rel = 'noopener';
            a.textContent = 'Open the PDF';
            result.appendChild(a);
            result.appendChild(document.createTextNode(' or '));
            var b = document.createElement('a');
            b.href = 'index.php';
            b.textContent = 'see it in the repository';
            result.appendChild(b);
            result.appendChild(document.createTextNode('.'));
        }
        result.hidden = false;
        result.scrollIntoView({ block: 'nearest' });
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        showError('');
        result.hidden = true;

        var msg = problem();
        if (msg) { showError(msg); return; }

        submit.disabled = true;
        submit.textContent = 'Uploading…';

        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        }).then(function (res) {
            return res.json().then(function (body) { return { ok: res.ok, status: res.status, body: body }; },
                function () { return { ok: false, status: res.status, body: {} }; });
        }).then(function (r) {
            if (r.ok && r.body.success) {
                showResult(r.body.message, r.body.url);
                // Keep the subject, since several papers in a row usually share one.
                var subject = form.subject.value;
                form.reset();
                form.subject.value = subject;
                showChosen();
            } else if (r.status === 401) {
                showError('Your session has expired. Log in again, then add the paper.');
            } else {
                showError(r.body.message || 'The upload failed (error ' + r.status + ').');
            }
        }).catch(function () {
            showError('Could not reach the server. Check the connection and try again.');
        }).then(function () {
            submit.disabled = false;
            submit.textContent = 'Add paper';
        });
    });
})();
