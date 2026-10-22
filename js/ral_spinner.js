/* Shared processing spinner. RalSpinner.show(text, sub) / RalSpinner.hide() / RalSpinner.isActive() */
(function (w) {
    var el = null, active = false;
    function build() {
        if (el) return el;
        el = document.createElement('div');
        el.className = 'ral-spinner-overlay';
        el.setAttribute('role', 'alertdialog');
        el.setAttribute('aria-live', 'assertive');
        el.setAttribute('aria-busy', 'true');
        el.innerHTML = '<div class="ral-spinner-card"><div class="ral-spinner-ring"></div>' +
            '<p class="ral-spinner-text"></p><p class="ral-spinner-sub"></p></div>';
        document.body.appendChild(el);
        return el;
    }
    w.RalSpinner = {
        show: function (text, sub) {
            var o = build();
            o.querySelector('.ral-spinner-text').textContent = text || 'Processing...';
            o.querySelector('.ral-spinner-sub').textContent = sub || 'Please wait. This may take a moment while emails are sent.';
            o.classList.add('show');
            active = true;
        },
        hide: function () {
            if (el) el.classList.remove('show');
            active = false;
        },
        isActive: function () { return active; }
    };
})(window);
