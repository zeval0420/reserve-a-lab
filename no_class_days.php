<?php
    include('../scilab/helperFiles/db_connection.php');
    include('helperFiles/session_handler.php');
    require_once __DIR__ . '/helperFiles/holidays.php';

    if (!isset($_SESSION['role'])) {
        header("Location: index.php");
        exit();
    }
    if ($_SESSION['role'] != 'admin') {
        header("Location: requester_home.php");
        exit();
    }

    $noClassDays = ral_no_class_days($conn);
    $y = (int) date('Y');
    $publicHolidays = ral_holidays([$y, $y + 1]);
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <title>No Class Days</title>
        <?php $pageAssets = []; ?>
        <?php include('helperFiles/headData.php'); ?>
        <link rel="stylesheet" href="css/multi-date-picker.css?v=<?= filemtime(__DIR__ . '/css/multi-date-picker.css') ?>">
        <script src="js/multi-date-picker.js?v=<?= filemtime(__DIR__ . '/js/multi-date-picker.js') ?>"></script>
        <style>
            .main-wrapper { width: 80%; margin: 0 auto 2rem; }
            @media (max-width: 768px) { .main-wrapper { width: 95%; } }
            .nc-grid { display: grid; grid-template-columns: minmax(280px, 380px) minmax(0, 1fr); gap: 20px; align-items: start; }
            @media (max-width: 860px) { .nc-grid { grid-template-columns: minmax(0, 1fr); } }
            .nc-card { background: rgba(255,255,255,0.6); border: 1px solid var(--color-border); border-radius: var(--radius-card); box-shadow: var(--shadow-card); padding: 20px; margin-bottom: 20px; }
            .nc-card h2 { font-size: 18px; font-weight: 700; color: var(--color-primary-mid); margin: 0 0 6px; }
            .nc-sub { font-size: 13px; color: #6b7280; margin: 0 0 14px; }
            .nc-list { list-style: none; margin: 0; padding: 0; }
            .nc-list li { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 9px 0; border-top: 1px solid var(--color-border); }
            .nc-list li:first-child { border-top: 0; }
            .nc-date { font-weight: 700; color: var(--color-primary); }
            .nc-reason { font-size: 13px; color: #6b7280; }
            .nc-remove { border: 0; background: transparent; color: #dc3545; font-weight: 600; font-size: 13px; cursor: pointer; }
            .nc-remove:hover { text-decoration: underline; }
            .nc-empty { font-size: 13px; color: #6b7280; }
        </style>
    </head>
    <body>
        <?php include('helperFiles/header.php'); ?>
        <div class="main-wrapper">
            <div class="nc-grid">
                <div class="nc-card">
                    <h2>Set No-Class Days</h2>
                    <p class="nc-sub">Pick the days, add a reason, and save. Requesters can't book these days.</p>
                    <div id="date-picker"></div>
                    <div class="form-group" style="margin-top:14px;">
                        <label for="nc-reason">Reason</label>
                        <input type="text" id="nc-reason" class="form-control liquid-input" maxlength="100" placeholder="e.g. Teachers' Day, Typhoon suspension">
                    </div>
                    <button type="button" id="nc-save" class="btn-liquid">Mark as no class</button>
                </div>

                <div>
                    <div class="nc-card">
                        <h2>No-class days</h2>
                        <p class="nc-sub">Set by admins. Sundays are always unavailable.</p>
                        <ul class="nc-list" id="nc-list"></ul>
                    </div>
                    <div class="nc-card">
                        <h2>Public holidays</h2>
                        <p class="nc-sub">Always unavailable (listed in helperFiles/holidays.php).</p>
                        <ul class="nc-list">
                            <?php foreach ($publicHolidays as $d => $name): if ($d < date('Y-m-d')) continue; ?>
                                <li><span class="nc-date"><?= htmlspecialchars(date('M j, Y', strtotime($d))) ?></span><span class="nc-reason"><?= htmlspecialchars($name) ?></span></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php include('helperFiles/footer.php'); ?>
    </body>
    <script>
        let days = <?= json_encode($noClassDays ?: new stdClass, JSON_HEX_TAG) ?>;
        let picked = [];
        const fixed = <?= json_encode($publicHolidays, JSON_HEX_TAG) ?>;

        const picker = RalDatePicker.create('#date-picker', {
            maxDates: 366,
            offWeekdays: [0],
            offDates: Object.assign({}, fixed, days),
            notify: showToast,
            onChange: d => { picked = d; }
        });

        function fmt(d) {
            return new Date(d + 'T00:00:00').toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
        }

        function renderList() {
            const keys = Object.keys(days).sort();
            $('#nc-list').html(keys.length ? keys.map(d =>
                `<li><div><span class="nc-date">${ralEsc(fmt(d))}</span><br><span class="nc-reason">${ralEsc(days[d])}</span></div>
                 <button type="button" class="nc-remove" data-date="${ralEsc(d)}">Remove</button></li>`).join('')
                : '<li class="nc-empty">None set.</li>');
        }

        // Picker greys out whatever is already closed, so rebuild it after a change
        function refresh(newDays) {
            days = newDays || {};
            renderList();
            picker.setOffDates(Object.assign({}, fixed, days));
        }

        $('#nc-save').on('click', function () {
            if (!picked.length) { showToast('Pick at least one date.', 'warning'); return; }
            $.post('ajax/ajax_no_class.php', { action: 'add', dates: JSON.stringify(picked), reason: $('#nc-reason').val() }, function (res) {
                if (res.status !== 'success') { showToast(res.message || 'Could not save.', 'error'); return; }
                showToast(picked.length + ' day(s) marked as no class.', 'success');
                picker.clear();
                picked = [];
                $('#nc-reason').val('');
                refresh(res.days);
            }, 'json').fail(() => showToast('Could not save.', 'error'));
        });

        $(document).on('click', '.nc-remove', function () {
            $.post('ajax/ajax_no_class.php', { action: 'remove', date: $(this).data('date') }, function (res) {
                if (res.status === 'success') { showToast('Day reopened.', 'success'); refresh(res.days); }
            }, 'json').fail(() => showToast('Could not remove.', 'error'));
        });

        renderList();
    </script>
</html>
